<?php
/**
 * JOIN FLOW — Pay first → Onboard → Dashboard + Referral points
 *
 * Flow:
 *  1. User picks a tier on Leadership Program / Become a Member
 *  2. Pays via Paystack (or marks bank transfer pending)
 *  3. After successful Paystack verify → redirected to onboard form
 *  4. Submits name, email, password, optional referral code
 *  5. Account created as premium_member, tier assigned, referral tag issued
 *  6. Referrer (if valid) receives points
 *  7. User logged in → premium dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register free + premium roles once. */
function gc_register_member_roles() {
	if ( ! get_role( 'free_member' ) ) {
		add_role(
			'free_member',
			__( 'Free Member', 'game-changers' ),
			array( 'read' => true )
		);
	}
	if ( ! get_role( 'premium_member' ) ) {
		add_role(
			'premium_member',
			__( 'Premium Member', 'game-changers' ),
			array( 'read' => true )
		);
	}
}
add_action( 'init', 'gc_register_member_roles', 5 );

/** Whether user has premium access. */
function gc_user_is_premium( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	if ( user_can( $user_id, 'administrator' ) ) {
		return true;
	}
	return in_array( 'premium_member', (array) $user->roles, true );
}

/** Effective tier: 0 = free member, 1–5 = premium tiers. */
function gc_user_membership_tier( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	// Administrators always operate at the highest configured tier.
	if ( user_can( $user_id, 'administrator' ) && function_exists( 'gc_tiers' ) ) {
		$keys = array_keys( gc_tiers() );
		if ( $keys ) {
			return (int) max( $keys );
		}
	}
	$t = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	if ( $t > 0 ) {
		return $t;
	}
	if ( function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $user_id ) ) {
		return 1;
	}
	return 0;
}

/**
 * Migrate a logged-in free account to premium after verified payment.
 * Same user, same email, same password — only role + tier change.
 */
function gc_migrate_user_to_premium( $user_id, $tier = 1, $payment_ref = '' ) {
	$user_id = absint( $user_id );
	$tier    = max( 1, absint( $tier ) );
	if ( ! $user_id ) {
		return false;
	}
	$user = new WP_User( $user_id );
	if ( ! $user->exists() ) {
		return false;
	}
	// Keep free_member history but grant premium
	$user->add_role( 'premium_member' );
	update_user_meta( $user_id, 'gc_premium_tier', $tier );
	update_user_meta( $user_id, 'gc_membership_status', 'premium' );
	update_user_meta( $user_id, 'gc_premium_since', current_time( 'mysql' ) );
	if ( $payment_ref ) {
		update_user_meta( $user_id, 'gc_last_payment_ref', $payment_ref );
	}
	if ( $payment_ref && function_exists( 'gc_wallet_credit_referral_on_payment' ) ) {
		gc_wallet_credit_referral_on_payment( $user_id, $tier, $payment_ref, 0 );
	}
	if ( ! get_user_meta( $user_id, 'gc_premium_referral_tag', true ) ) {
		$tag_fn = function_exists( 'gc_generate_premium_referral_tag' ) ? 'gc_generate_premium_referral_tag' : ( function_exists( 'gc_generate_membership_tag' ) ? 'gc_generate_membership_tag' : null );
		if ( $tag_fn ) {
			update_user_meta( $user_id, 'gc_premium_referral_tag', call_user_func( $tag_fn ) );
		}
	}
	if ( (int) $tier >= 3 && function_exists( 'gc_user_is_inner_circle' ) ) {
		gc_user_is_inner_circle( $user_id );
	}
	// Link gc_member CPT if email matches
	$email = $user->user_email;
	if ( $email && function_exists( 'gc_get_member_by_email' ) ) {
		$m = gc_get_member_by_email( $email );
		if ( $m ) {
			update_post_meta( $m->ID, 'gc_member_user_id', $user_id );
			update_post_meta( $m->ID, 'gc_member_status', 'premium' );
			update_post_meta( $m->ID, 'gc_member_tier', $tier );
		}
	} else {
		$found = get_posts( array(
			'post_type'      => 'gc_member',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => 'gc_member_email',
			'meta_value'     => $email,
		) );
		if ( ! empty( $found ) ) {
			update_post_meta( $found[0]->ID, 'gc_member_user_id', $user_id );
			update_post_meta( $found[0]->ID, 'gc_member_status', 'premium' );
			update_post_meta( $found[0]->ID, 'gc_member_tier', $tier );
		}
	}
	do_action( 'gc_user_migrated_to_premium', $user_id, $tier, $payment_ref );
	return true;
}



/** Ensure alias exists for tier lookup. */
if ( ! function_exists( 'gc_get_user_premium_tier' ) ) {
	function gc_get_user_premium_tier( $uid ) {
		if ( function_exists( 'gc_get_user_premium_tier_v3' ) ) {
			return gc_get_user_premium_tier_v3( $uid );
		}
		$t = (int) get_user_meta( $uid, 'gc_premium_tier', true );
		return $t > 0 ? $t : 1;
	}
}

/** Unique premium referral tag for a user. */


/**
 * Find existing payout bank details by email (user meta or member CPT).
 * Returns array|null with bank_name, account_name, account_number, source.
 */
function gc_get_payout_account_by_email( $email ) {
	$email = sanitize_email( $email );
	if ( ! $email ) {
		return null;
	}
	$user = get_user_by( 'email', $email );
	if ( $user ) {
		$num = get_user_meta( $user->ID, 'gc_account_number', true );
		if ( $num ) {
			return array(
				'bank_name'       => get_user_meta( $user->ID, 'gc_bank_name', true ),
				'account_name'    => get_user_meta( $user->ID, 'gc_account_name', true ),
				'account_number'  => $num,
				'user_id'         => $user->ID,
				'source'          => 'user',
			);
		}
	}
	$found = get_posts( array(
		'post_type'      => 'gc_member',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => 'gc_member_email', 'value' => $email, 'compare' => '=' ),
			array( 'key' => 'gc_member_account_number', 'value' => '', 'compare' => '!=' ),
		),
	) );
	if ( ! empty( $found ) ) {
		$m = $found[0];
		$num = get_post_meta( $m->ID, 'gc_member_account_number', true );
		if ( $num ) {
			return array(
				'bank_name'      => get_post_meta( $m->ID, 'gc_member_bank_name', true ),
				'account_name'   => get_post_meta( $m->ID, 'gc_member_account_name', true ),
				'account_number' => $num,
				'member_id'      => $m->ID,
				'source'         => 'member',
			);
		}
	}
	return null;
}

/**
 * Save payout account once per email. Never overwrites existing details.
 * Returns true if saved, false if skipped (already on file or invalid).
 */
function gc_save_payout_account( $email, $bank_name, $account_name, $account_number, $user_id = 0 ) {
	$email = sanitize_email( $email );
	$bank_name = sanitize_text_field( $bank_name );
	$account_name = sanitize_text_field( $account_name );
	$account_number = preg_replace( '/\D+/', '', (string) $account_number );

	if ( ! $email || strlen( $account_number ) < 10 ) {
		return false;
	}
	// Do not override if this email already has account details
	if ( gc_get_payout_account_by_email( $email ) ) {
		return false;
	}

	if ( ! $user_id ) {
		$u = get_user_by( 'email', $email );
		$user_id = $u ? (int) $u->ID : 0;
	}
	if ( $user_id ) {
		update_user_meta( $user_id, 'gc_bank_name', $bank_name );
		update_user_meta( $user_id, 'gc_account_name', $account_name );
		update_user_meta( $user_id, 'gc_account_number', $account_number );
		update_user_meta( $user_id, 'gc_payout_account_saved_at', current_time( 'mysql' ) );
	}

	$members = get_posts( array(
		'post_type'      => 'gc_member',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'meta_key'       => 'gc_member_email',
		'meta_value'     => $email,
	) );
	if ( ! empty( $members ) ) {
		$mid = $members[0]->ID;
		// only set if empty
		if ( ! get_post_meta( $mid, 'gc_member_account_number', true ) ) {
			update_post_meta( $mid, 'gc_member_bank_name', $bank_name );
			update_post_meta( $mid, 'gc_member_account_name', $account_name );
			update_post_meta( $mid, 'gc_member_account_number', $account_number );
		}
	}
	return true;
}

/** True if email belongs to a premium member with tier >= 1 (any paid tier). */
function gc_email_is_premium_tier_member( $email ) {
	$email = sanitize_email( $email );
	if ( ! $email ) {
		return false;
	}
	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		return false;
	}
	if ( user_can( $user->ID, 'administrator' ) ) {
		return true;
	}
	if ( function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $user->ID ) ) {
		$t = function_exists( 'gc_user_membership_tier' ) ? gc_user_membership_tier( $user->ID ) : (int) get_user_meta( $user->ID, 'gc_premium_tier', true );
		return $t >= 1;
	}
	if ( in_array( 'premium_member', (array) $user->roles, true ) ) {
		$t = (int) get_user_meta( $user->ID, 'gc_premium_tier', true );
		return $t >= 1;
	}
	return false;
}

function gc_generate_premium_referral_tag() {
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	do {
		$code = 'GC-';
		for ( $i = 0; $i < 6; $i++ ) {
			$code .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
		}
		$exists = get_users( array(
			'meta_key'   => 'gc_premium_referral_tag',
			'meta_value' => $code,
			'number'     => 1,
			'fields'     => 'ID',
		) );
	} while ( ! empty( $exists ) );
	return $code;
}

/** Find user by premium referral tag. */
function gc_user_by_referral_tag( $tag ) {
	$tag = strtoupper( trim( (string) $tag ) );
	if ( ! $tag ) {
		return null;
	}
	$users = get_users( array(
		'meta_key'   => 'gc_premium_referral_tag',
		'meta_value' => $tag,
		'number'     => 1,
	) );
	return ! empty( $users ) ? $users[0] : null;
}

/** Award referral points to a referrer (simple meta + optional engagement). */
function gc_award_referral_points( $referrer_id, $new_user_id, $tier = 1 ) {
	$referrer_id = absint( $referrer_id );
	$new_user_id = absint( $new_user_id );
	$tier        = max( 1, absint( $tier ) );
	if ( ! $referrer_id || ! $new_user_id || $referrer_id === $new_user_id ) {
		return;
	}

	// Base = tier growth points (admin-configurable).
	if ( function_exists( 'gc_get_tier_growth_points' ) ) {
		$base = (int) gc_get_tier_growth_points( $tier );
	} else {
		$map  = array( 1 => 500, 2 => 2500, 3 => 7500, 4 => 25000, 5 => 75000 );
		$base = isset( $map[ $tier ] ) ? (int) $map[ $tier ] : 500;
	}
	if ( $base < 1 ) {
		return;
	}

	// Generation 1 (direct): 1/3 of tier points (floor).
	$direct = (int) floor( $base / 3 );
	if ( $direct > 0 ) {
		$ref_pts = (int) get_user_meta( $referrer_id, 'gc_referral_points', true ) + $direct;
		update_user_meta( $referrer_id, 'gc_referral_points', $ref_pts );
		$total = (int) get_user_meta( $referrer_id, 'gc_total_points', true ) + $direct;
		update_user_meta( $referrer_id, 'gc_total_points', $total );
	}

	$ref_count = (int) get_user_meta( $referrer_id, 'gc_referral_count', true );
	update_user_meta( $referrer_id, 'gc_referral_count', $ref_count + 1 );

	update_user_meta( $new_user_id, 'gc_referred_by', $referrer_id );
	$log   = (array) get_user_meta( $referrer_id, 'gc_referral_log', true );
	$log[] = array(
		'user_id' => $new_user_id,
		'tier'    => $tier,
		'points'  => $direct,
		'gen'     => 1,
		'time'    => current_time( 'mysql' ),
	);
	update_user_meta( $referrer_id, 'gc_referral_log', $log );

	// Generation 2 only: the person who referred $referrer_id gets 1/5.
	// Generation 3+ gets nothing.
	$upline = (int) get_user_meta( $referrer_id, 'gc_referred_by', true );
	if ( $upline && $upline !== $referrer_id && $upline !== $new_user_id ) {
		$indirect = (int) floor( $base / 5 );
		if ( $indirect > 0 ) {
			$l2 = (int) get_user_meta( $upline, 'gc_referral_l2_points', true ) + $indirect;
			update_user_meta( $upline, 'gc_referral_l2_points', $l2 );
			$ut = (int) get_user_meta( $upline, 'gc_total_points', true ) + $indirect;
			update_user_meta( $upline, 'gc_total_points', $ut );
			$ulog   = (array) get_user_meta( $upline, 'gc_referral_log', true );
			$ulog[] = array(
				'user_id' => $new_user_id,
				'tier'    => $tier,
				'points'  => $indirect,
				'gen'     => 2,
				'via'     => $referrer_id,
				'time'    => current_time( 'mysql' ),
			);
			update_user_meta( $upline, 'gc_referral_log', $ulog );
		}
	}

	if ( function_exists( 'gc_record_activity' ) ) {
		gc_record_activity( $referrer_id, 'community_contribution', $new_user_id, 'referral_join' );
	}
	if ( function_exists( 'gc_maybe_auto_upgrade_by_invites' ) ) {
		gc_maybe_auto_upgrade_by_invites( $referrer_id );
	}
}

/**
 * Replace default Paystack verify with full join-aware handler.
 * Keeps payment log, then routes to onboard or dashboard.
 */
function gc_verify_paystack_join() {
	$ref = sanitize_text_field( wp_unslash( $_GET['reference'] ?? '' ) );
	$key = get_option( 'gc_paystack_secret_key', '' );
	if ( ! $ref || ! $key ) {
		wp_die( esc_html__( 'Payment verification is not configured.', 'game-changers' ) );
	}

	$r = wp_remote_get(
		'https://api.paystack.co/transaction/verify/' . rawurlencode( $ref ),
		array(
			'timeout' => 20,
			'headers' => array( 'Authorization' => 'Bearer ' . $key ),
		)
	);
	if ( is_wp_error( $r ) ) {
		wp_die( esc_html__( 'Could not verify payment.', 'game-changers' ) );
	}
	$body = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( empty( $body['data']['status'] ) || 'success' !== $body['data']['status'] ) {
		wp_die( esc_html__( 'Payment was not successful.', 'game-changers' ) );
	}

	$amount_kobo = isset( $body['data']['amount'] ) ? (int) $body['data']['amount'] : 0;
	$amount_naira = $amount_kobo / 100;
	$meta         = isset( $body['data']['metadata'] ) ? $body['data']['metadata'] : array();
	$tier         = isset( $meta['tier'] ) ? absint( $meta['tier'] ) : absint( $_GET['tier'] ?? 1 );
	if ( $tier < 1 ) {
		$tier = 1;
	}
	$email = isset( $body['data']['customer']['email'] ) ? sanitize_email( $body['data']['customer']['email'] ) : '';

	// Persist payment record
	$id = wp_insert_post( array(
		'post_type'   => 'gc_submission',
		'post_status' => 'private',
		'post_title'  => 'Paystack Payment — ' . $ref,
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'gc_form_type_label', 'Paystack Payment' );
		update_post_meta( $id, 'gc_payment_reference', $ref );
		update_post_meta( $id, 'gc_payment_amount', $amount_naira );
		update_post_meta( $id, 'gc_payment_tier', $tier );
		update_post_meta( $id, 'gc_workflow_status', 'paid' );
		if ( $email ) {
			update_post_meta( $id, 'gc_email', $email );
		}
		if ( is_user_logged_in() ) {
			update_post_meta( $id, 'gc_user_id', get_current_user_id() );
		}
	}

	// Store a short-lived transient so onboard form can prove payment
	gc_store_paid_unlock( $ref, array(
		'ref'    => $ref,
		'tier'   => $tier,
		'amount' => $amount_naira,
		'email'  => $email,
		'time'   => time(),
	) );

	// Already logged in (free or premium) → migrate/upgrade same account, same password
	if ( is_user_logged_in() ) {
		$uid = get_current_user_id();
		$current_tier = function_exists( 'gc_user_membership_tier' ) ? gc_user_membership_tier( $uid ) : (int) get_user_meta( $uid, 'gc_premium_tier', true );
		$target = max( $tier, 1 );
		if ( $target > $current_tier || ! gc_user_is_premium( $uid ) ) {
			gc_migrate_user_to_premium( $uid, $target, $ref );
		} else {
			update_user_meta( $uid, 'gc_last_payment_ref', $ref );
		}
		$dash = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
		wp_safe_redirect( add_query_arg( array( 'joined' => '1', 'upgraded' => '1' ), $dash ) );
		exit;
	}

	// Not logged in → onboard form
	$onboard = add_query_arg(
		array(
			'paid' => '1',
			'ref'  => rawurlencode( $ref ),
			'tier' => $tier,
		),
		gc_page_url( 'become-a-member' )
	);
	wp_safe_redirect( $onboard );
	exit;
}

// Override the old verify handler by removing it and using ours.
add_action( 'init', function () {
	remove_action( 'admin_post_gc_paystack_verify', 'gc_verify_paystack' );
	remove_action( 'admin_post_nopriv_gc_paystack_verify', 'gc_verify_paystack' );
}, 5 );
add_action( 'admin_post_gc_paystack_verify', 'gc_verify_paystack_join' );
add_action( 'admin_post_nopriv_gc_paystack_verify', 'gc_verify_paystack_join' );

/**
 * Handle post-payment registration (onboard form).
 */
function gc_handle_join_onboard() {
	if ( empty( $_POST['gc_join_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_join_nonce'] ) ), 'gc_join_onboard' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}

	$ref   = sanitize_text_field( wp_unslash( $_POST['gc_payment_ref'] ?? '' ) );
	$tier  = absint( $_POST['gc_tier'] ?? 1 );
	$name  = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) );
	$pass  = (string) ( $_POST['gc_password'] ?? '' );
	$phone = sanitize_text_field( wp_unslash( $_POST['gc_phone'] ?? '' ) );
	$state = sanitize_text_field( wp_unslash( $_POST['gc_state'] ?? '' ) );
	$bank_name = sanitize_text_field( wp_unslash( $_POST['gc_bank_name'] ?? '' ) );
	$account_name = sanitize_text_field( wp_unslash( $_POST['gc_account_name'] ?? '' ) );
	$account_number = sanitize_text_field( wp_unslash( $_POST['gc_account_number'] ?? '' ) );
	$ref_tag_in = strtoupper( sanitize_text_field( wp_unslash( $_POST['gc_referral_code'] ?? '' ) ) );

	$paid = $ref ? gc_get_paid_unlock( $ref ) : false;
	if ( ( ! $paid || empty( $paid['ref'] ) ) && is_email( $email ) ) {
		$by = get_option( 'gc_paid_email_' . md5( strtolower( $email ) ), false );
		if ( is_array( $by ) && ! empty( $by['ref'] ) ) {
			$paid = $by;
			$ref  = (string) $by['ref'];
		}
	}
	if ( ! $paid || empty( $paid['ref'] ) ) {
		wp_safe_redirect( add_query_arg( 'gc_err', 'payment', gc_page_url( 'become-a-member' ) ) );
		exit;
	}
	if ( $tier < 1 ) {
		$tier = absint( $paid['tier'] ?: 1 );
	}

	if ( ! $name || ! is_email( $email ) || strlen( $pass ) < 8 ) {
		wp_safe_redirect( add_query_arg( array(
			'paid'   => '1',
			'ref'    => rawurlencode( $ref ),
			'tier'   => $tier,
			'gc_err' => 'fields',
		), gc_page_url( 'become-a-member' ) ) );
		exit;
	}

	if ( email_exists( $email ) ) {
		wp_safe_redirect( add_query_arg( array(
			'paid'   => '1',
			'ref'    => rawurlencode( $ref ),
			'tier'   => $tier,
			'gc_err' => 'email',
		), gc_page_url( 'become-a-member' ) ) );
		exit;
	}

	$uid = wp_create_user( $email, $pass, $email );
	if ( is_wp_error( $uid ) ) {
		wp_safe_redirect( add_query_arg( array(
			'paid'   => '1',
			'ref'    => rawurlencode( $ref ),
			'tier'   => $tier,
			'gc_err' => 'create',
		), gc_page_url( 'become-a-member' ) ) );
		exit;
	}

	wp_update_user( array(
		'ID'           => $uid,
		'display_name' => $name,
		'first_name'   => $name,
		'role'         => 'premium_member',
	) );
	$user = new WP_User( $uid );
	$user->set_role( 'premium_member' );

	update_user_meta( $uid, 'gc_premium_tier', $tier );
	if ( (int) $tier >= 3 && function_exists( 'gc_user_is_inner_circle' ) ) { gc_user_is_inner_circle( $uid ); }
	update_user_meta( $uid, 'gc_premium_referral_tag', gc_generate_premium_referral_tag() );
	update_user_meta( $uid, 'gc_last_payment_ref', $ref );
	if ( $ref && function_exists( 'gc_wallet_credit_referral_on_payment' ) ) {
		gc_wallet_credit_referral_on_payment( $uid, $tier, $ref, 0 );
	}
	update_user_meta( $uid, 'gc_phone', $phone );
	update_user_meta( $uid, 'gc_state', $state );
	update_user_meta( $uid, 'gc_joined_at', current_time( 'mysql' ) );
	// Welcome bonus on top of tier points (do not reset total to 25)
	$welcome = 25;
	$current_pts = (int) get_user_meta( $uid, 'gc_total_points', true );
	update_user_meta( $uid, 'gc_total_points', $current_pts + $welcome );
	if ( function_exists( 'gc_send_premium_welcome_email' ) ) {
		gc_send_premium_welcome_email( $email, $name, $tier );
	} elseif ( function_exists( 'gc_send_member_welcome_email' ) ) {
		gc_send_member_welcome_email( $email, $name );
	}

	// Referral
	if ( $ref_tag_in ) {
		$referrer = gc_user_by_referral_tag( $ref_tag_in );
		if ( $referrer ) {
			update_user_meta( $uid, 'gc_referrer_tag_raw', $ref_tag_in );
			gc_award_referral_points( $referrer->ID, $uid, $tier );
			if ( function_exists( 'gc_maybe_auto_upgrade_by_invites' ) ) {
				gc_maybe_auto_upgrade_by_invites( $referrer->ID );
			}
		}
	}

	// Submission log for admin
	$sid = wp_insert_post( array(
		'post_type'   => 'gc_submission',
		'post_status' => 'publish',
		'post_title'  => $name,
	) );
	if ( $sid && ! is_wp_error( $sid ) ) {
		update_post_meta( $sid, 'gc_form_type_label', 'Premium Membership' );
		update_post_meta( $sid, 'gc_email', $email );
		update_post_meta( $sid, 'gc_phone', $phone );
		update_post_meta( $sid, 'gc_state', $state );
		update_post_meta( $sid, 'gc_payment_reference', $ref );
		update_post_meta( $sid, 'gc_payment_tier', $tier );
		update_post_meta( $sid, 'gc_workflow_status', 'approved' );
		update_post_meta( $sid, 'gc_premium_approved', $uid );
		update_post_meta( $sid, 'gc_user_id', $uid );
		if ( $ref_tag_in ) {
			update_post_meta( $sid, 'gc_referrer_tag_raw', $ref_tag_in );
		}
	}

	delete_transient( 'gc_paid_' . md5( $ref ) );

	// Log the new member in
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true );

	wp_safe_redirect( gc_page_url( 'premium-dashboard' ) . '?joined=1' );
	exit;
}
add_action( 'admin_post_nopriv_gc_join_onboard', 'gc_handle_join_onboard' );
add_action( 'admin_post_gc_join_onboard', 'gc_handle_join_onboard' );

/** Build Paystack metadata-aware payment button markup for a tier. */

/**
 * Payment CTA for a tier.
 * Priority:
 *  1. Per-tier Paystack Shop / payment link (Settings or option gc_paystack_link_tier_{n})
 *  2. Inline Paystack popup if public key is set
 *  3. Fallback message
 */
function gc_join_pay_link_for_tier( $tier ) {
	$tier = absint( $tier );
	$links = get_option( 'gc_paystack_tier_links', array() );
	if ( is_array( $links ) && ! empty( $links[ $tier ] ) ) {
		return esc_url_raw( $links[ $tier ] );
	}
	// Single shared shop link fallback (admin can set one default)
	$shared = get_option( 'gc_paystack_shop_link', '' );
	if ( $shared ) {
		return esc_url_raw( $shared );
	}
	return '';
}

function gc_join_paystack_button( $tier, $amount, $email = '' ) {
	$tier   = absint( $tier );
	$amount = (int) $amount;
	$key    = trim( (string) get_option( 'gc_paystack_public_key', '' ) );
	ob_start();

	if ( ! $key || 0 !== strpos( $key, 'pk_' ) ) {
		?>
		<div class="gc-join-err" style="margin-bottom:12px;">Paystack is not configured. Admin: add a live public key (pk_live_…) under <strong>Settings → GC Payments</strong>.</div>
		<?php
		return ob_get_clean();
	}
	if ( $amount < 100 ) {
		?>
		<div class="gc-join-err">Invalid amount.</div>
		<?php
		return ob_get_clean();
	}

	$ref = 'GCN-T' . $tier . '-' . time() . '-' . wp_rand( 1000, 9999 );
	$verify = add_query_arg(
		array( 'action' => 'gc_paystack_verify', 'tier' => $tier ),
		admin_url( 'admin-post.php' )
	);
	?>
	<button type="button" class="gc-button gc-button-gold gc-join-pay-btn"
		data-gc-paystack="1"
		data-amount="<?php echo esc_attr( $amount ); ?>"
		data-ref="<?php echo esc_attr( $ref ); ?>"
		data-tier="<?php echo esc_attr( $tier ); ?>"
		data-email="<?php echo esc_attr( $email ); ?>"
		data-verify="<?php echo esc_url( $verify ); ?>">
		Pay ₦<?php echo esc_html( number_format_i18n( $amount ) ); ?> with Paystack
	</button>
	<p class="gc-join-hint" style="margin-top:10px;font-size:13px;color:#66756a;">Pay securely with Paystack. After success you will continue registration automatically. If you already paid, enter your Paystack reference below to confirm.</p>
	<script src="https://js.paystack.co/v2/inline.js"></script>
	<script>
	(function(){
		var key = <?php echo wp_json_encode( $key ); ?>;
		document.querySelectorAll('.gc-join-pay-btn[data-gc-paystack]').forEach(function(b){
			if (b.getAttribute('data-bound') === '1') return;
			b.setAttribute('data-bound', '1');
			b.addEventListener('click', function(){
				if (!window.PaystackPop) { alert('Paystack is loading — try again in a moment.'); return; }
				var amount = parseInt(b.getAttribute('data-amount'), 10) || 0;
				var ref = b.getAttribute('data-ref') || '';
				var email = b.getAttribute('data-email') || '';
				if (!email) {
					email = window.prompt('Enter the email you will use for this payment:') || '';
				}
				if (!email || email.indexOf('@') < 0) { alert('A valid email is required for Paystack.'); return; }
				var verifyBase = b.getAttribute('data-verify') || '';
				var popup = new PaystackPop();
				popup.newTransaction({
					key: key,
					email: email,
					amount: amount * 100,
					reference: ref,
					currency: 'NGN',
					metadata: { tier: b.getAttribute('data-tier') || '1' },
					onSuccess: function(transaction) {
						var r = (transaction && transaction.reference) ? transaction.reference : ref;
						window.location.href = verifyBase + '&reference=' + encodeURIComponent(r);
					},
					onCancel: function() {}
				});
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}



add_action( 'admin_init', function () {
	register_setting( 'gc_payments', 'gc_paystack_shop_link', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'gc_payments', 'gc_paystack_public_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'gc_payments', 'gc_paystack_secret_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'gc_payments', 'gc_cohort_paystack_link', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'gc_payments', 'gc_paystack_tier_links', array(
		'sanitize_callback' => function ( $v ) {
			$out = array();
			if ( is_array( $v ) ) {
				foreach ( $v as $k => $url ) {
					$out[ absint( $k ) ] = esc_url_raw( $url );
				}
			}
			return $out;
		},
	) );
	register_setting( 'gc_payments', 'gc_inner_circle_whatsapp', array( 'sanitize_callback' => 'esc_url_raw' ) );
} );

/** Default shop link the admin provided (can change in settings). */
add_action( 'after_setup_theme', function () {
	if ( get_option( 'gc_paystack_shop_link' ) === false || get_option( 'gc_paystack_shop_link' ) === '' ) {
		// Only seed if never set
		if ( false === get_option( 'gc_paystack_shop_seeded_v1', false ) ) {
			update_option( 'gc_paystack_shop_link', 'https://paystack.shop/pay/tzpci3b9qo' );
			update_option( 'gc_paystack_shop_seeded_v1', 1 );
		}
	}
} );



/** Store paid unlock permanently (and long transient). No short expiry. */
function gc_store_paid_unlock( $ref, $data ) {
	$ref = (string) $ref;
	$data = is_array( $data ) ? $data : array();
	$data['ref'] = $ref;
	$data['time'] = isset( $data['time'] ) ? $data['time'] : time();
	set_transient( 'gc_paid_' . md5( $ref ), $data, YEAR_IN_SECONDS * 2 );
	$all = get_option( 'gc_paid_unlocks', array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	$all[ md5( $ref ) ] = $data;
	// Keep last 2000 unlocks
	if ( count( $all ) > 2000 ) {
		$all = array_slice( $all, -2000, null, true );
	}
	update_option( 'gc_paid_unlocks', $all, false );
}

function gc_get_paid_unlock( $ref ) {
	$ref = (string) $ref;
	if ( ! $ref ) {
		return false;
	}
	$data = get_transient( 'gc_paid_' . md5( $ref ) );
	if ( is_array( $data ) ) {
		return $data;
	}
	$all = get_option( 'gc_paid_unlocks', array() );
	if ( is_array( $all ) && ! empty( $all[ md5( $ref ) ] ) ) {
		return $all[ md5( $ref ) ];
	}
	return false;
}

/* ========== Secure payment confirmation (no free bypass) ========== */

function gc_join_page_url( $args = array() ) {
	$base = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
	return $args ? add_query_arg( $args, $base ) : $base;
}

/**
 * Confirm payment by email used on Paystack OR by bank receipt upload.
 * Creates a paid transient only after proof is submitted.
 */
function gc_handle_payment_confirm() {
	if ( empty( $_POST['gc_pay_confirm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_pay_confirm_nonce'] ) ), 'gc_pay_confirm' ) ) {
		wp_safe_redirect( gc_join_page_url( array( 'gc_err' => 'nonce' ) ) );
		exit;
	}
	$tier   = absint( $_POST['tier'] ?? 0 );
	$method = sanitize_key( $_POST['confirm_method'] ?? '' );
	$email  = sanitize_email( wp_unslash( $_POST['pay_email'] ?? '' ) );
	$ref_in = sanitize_text_field( wp_unslash( $_POST['pay_ref'] ?? '' ) );
	$invite = sanitize_text_field( wp_unslash( $_POST['invite_code'] ?? '' ) );

	if ( $tier < 1 ) {
		wp_safe_redirect( gc_join_page_url( array( 'gc_err' => 'tier' ) ) );
		exit;
	}

	/* ---- A) Admin free-access code only (true bypass) ---- */
	if ( 'invite' === $method ) {
		if ( ! $invite ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
			exit;
		}
		$invites = get_option( 'gc_free_invites', array() );
		$key = strtoupper( trim( $invite ) );
		// Also accept comma-list admin codes from settings
		$legacy = array_filter( array_map( 'strtoupper', array_map( 'trim', explode( ',', (string) get_option( 'gc_admin_free_codes', '' ) ) ) ) );
		$ok = false;
		$use_tier = $tier;
		$inv_email = '';
		if ( ! empty( $invites[ $key ] ) && empty( $invites[ $key ]['revoked'] ) ) {
			$inv = $invites[ $key ];
			$max = isset( $inv['max_uses'] ) ? absint( $inv['max_uses'] ) : 10000;
			$uses = isset( $inv['uses'] ) ? absint( $inv['uses'] ) : 0;
			if ( ! empty( $inv['used'] ) && empty( $inv['max_uses'] ) ) {
				wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
				exit;
			}
			if ( $max > 0 && $uses >= $max ) {
				wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
				exit;
			}
			if ( ! empty( $inv['email'] ) && $email && strtolower( $inv['email'] ) !== strtolower( $email ) ) {
				wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite_email' ) ) );
				exit;
			}
			$ok = true;
			$use_tier = ! empty( $inv['tier'] ) ? absint( $inv['tier'] ) : $tier;
			$inv_email = $inv['email'] ?? '';
			$invites[ $key ]['uses'] = $uses + 1;
			update_option( 'gc_free_invites', $invites, false );
		} elseif ( in_array( $key, $legacy, true ) ) {
			$ok = true;
		}
		if ( ! $ok ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
			exit;
		}
		$ref = 'INVITE-' . $key . '-' . time() . '-' . wp_rand( 100, 999 );
		gc_store_paid_unlock( $ref, array(
			'ref'    => $ref,
			'tier'   => $use_tier,
			'amount' => 0,
			'email'  => $email ?: $inv_email,
			'time'   => time(),
			'method' => 'invite',
			'invite' => $key,
			'free'   => 1,
		) );
		wp_safe_redirect( gc_join_page_url( array( 'paid' => '1', 'tier' => $use_tier, 'ref' => $ref ) ) );
		exit;
	}

	/* ---- B) Paystack: MUST have reference + live API verify ---- */
	if ( 'paystack_email' === $method || 'paystack' === $method ) {
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'email' ) ) );
			exit;
		}
		$ref_in = trim( $ref_in );
		if ( strlen( $ref_in ) < 6 ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'ref_required' ) ) );
			exit;
		}
		$secret = get_option( 'gc_paystack_secret_key', '' );
		if ( ! $secret || 0 !== strpos( $secret, 'sk_' ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'paystack_config' ) ) );
			exit;
		}
		$r = wp_remote_get(
			'https://api.paystack.co/transaction/verify/' . rawurlencode( $ref_in ),
			array(
				'timeout' => 25,
				'headers' => array( 'Authorization' => 'Bearer ' . $secret ),
			)
		);
		if ( is_wp_error( $r ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'verify_fail' ) ) );
			exit;
		}
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( empty( $body['data']['status'] ) || 'success' !== $body['data']['status'] ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'not_paid' ) ) );
			exit;
		}
		$pay_email = isset( $body['data']['customer']['email'] ) ? sanitize_email( $body['data']['customer']['email'] ) : '';
		// Email on form should match Paystack customer (case-insensitive)
		if ( $pay_email && strtolower( $pay_email ) !== strtolower( $email ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'email_mismatch' ) ) );
			exit;
		}
		// Prevent reusing same successful reference twice
		$used = get_option( 'gc_used_paystack_refs', array() );
		if ( ! is_array( $used ) ) {
			$used = array();
		}
		$ref_key = strtoupper( $ref_in );
		if ( ! empty( $used[ $ref_key ] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'ref_used' ) ) );
			exit;
		}
		$amount_naira = isset( $body['data']['amount'] ) ? ( (int) $body['data']['amount'] ) / 100 : 0;
		$used[ $ref_key ] = array( 'email' => $email, 'tier' => $tier, 'time' => current_time( 'mysql' ) );
		update_option( 'gc_used_paystack_refs', $used, false );

		gc_store_paid_unlock( $ref_in, array(
			'ref'    => $ref_in,
			'tier'   => $tier,
			'amount' => $amount_naira,
			'email'  => $email,
			'time'   => time(),
			'method' => 'paystack_verified',
		) );

		$id = wp_insert_post( array(
			'post_type'   => 'gc_submission',
			'post_status' => 'private',
			'post_title'  => 'Paystack verified — ' . $ref_in,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'gc_form_type_label', 'Paystack Payment' );
			update_post_meta( $id, 'gc_payment_reference', $ref_in );
			update_post_meta( $id, 'gc_payment_amount', $amount_naira );
			update_post_meta( $id, 'gc_payment_tier', $tier );
			update_post_meta( $id, 'gc_email', $email );
			update_post_meta( $id, 'gc_workflow_status', 'paid' );
		}

		wp_safe_redirect( gc_join_page_url( array( 'paid' => '1', 'tier' => $tier, 'ref' => $ref_in ) ) );
		exit;
	}

	/* ---- C) Bank receipt: PENDING only — admin must approve ---- */
	if ( 'receipt' === $method ) {
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'email' ) ) );
			exit;
		}
		if ( empty( $_FILES['receipt']['name'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'receipt' ) ) );
			exit;
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$upload = wp_handle_upload( $_FILES['receipt'], array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png'          => 'image/png',
				'pdf'          => 'application/pdf',
				'webp'         => 'image/webp',
			),
		) );
		if ( isset( $upload['error'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'upload' ) ) );
			exit;
		}
		$claim_id = wp_insert_post( array(
			'post_type'   => 'gc_submission',
			'post_status' => 'private',
			'post_title'  => 'Bank receipt pending — ' . $email,
		) );
		if ( $claim_id && ! is_wp_error( $claim_id ) ) {
			update_post_meta( $claim_id, 'gc_form_type_label', 'Bank Receipt Claim' );
			update_post_meta( $claim_id, 'gc_email', $email );
			update_post_meta( $claim_id, 'gc_payment_tier', $tier );
			update_post_meta( $claim_id, 'gc_receipt_url', $upload['url'] );
			update_post_meta( $claim_id, 'gc_workflow_status', 'pending_payment_review' );
		}
		$pending = get_option( 'gc_pending_receipts', array() );
		if ( ! is_array( $pending ) ) {
			$pending = array();
		}
		$pending[] = array(
			'id'      => $claim_id,
			'email'   => $email,
			'tier'    => $tier,
			'receipt' => $upload['url'],
			'time'    => current_time( 'mysql' ),
			'status'  => 'pending',
		);
		update_option( 'gc_pending_receipts', array_slice( $pending, -200 ), false );

		$admin = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
		if ( is_email( $admin ) ) {
			$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
			$headers = array( 'From: Game Changers Nigeria <' . $from . '>', 'Reply-To: ' . $email );
			wp_mail(
				$admin,
				'[Receipt] Pending review — Tier ' . $tier . ' — ' . $email,
				"A bank-transfer receipt was uploaded and is waiting for admin approval.\n\nEmail: {$email}\nTier: {$tier}\nReceipt: {$upload['url']}\n\nApprove in WP Admin → GC Control Room → Payment claims. Do not grant access until you confirm the transfer.",
				$headers
			);
		}
		// DO NOT set gc_paid_ transient — registration stays locked
		wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_pending' => '1' ) ) );
		exit;
	}

	wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'method' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_gc_pay_confirm', 'gc_handle_payment_confirm' );
add_action( 'admin_post_gc_pay_confirm', 'gc_handle_payment_confirm' );

/** Mark invite used after successful registration */
function gc_consume_invite_if_any( $paid ) {
	if ( empty( $paid['invite'] ) ) {
		return;
	}
	$key = strtoupper( $paid['invite'] );
	$invites = get_option( 'gc_free_invites', array() );
	if ( ! isset( $invites[ $key ] ) ) {
		return;
	}
	$uses = isset( $invites[ $key ]['uses'] ) ? absint( $invites[ $key ]['uses'] ) : 0;
	$invites[ $key ]['uses'] = $uses + 1;
	$invites[ $key ]['last_used_at'] = current_time( 'mysql' );
	$max = isset( $invites[ $key ]['max_uses'] ) ? absint( $invites[ $key ]['max_uses'] ) : 10000;
	// Only mark fully "used up" when max reached (not on first use)
	if ( $max > 0 && $invites[ $key ]['uses'] >= $max ) {
		$invites[ $key ]['used'] = 1;
	}
	update_option( 'gc_free_invites', $invites, false );
}


/** Logged-in member upgrades to a higher tier without re-registering */
function gc_handle_tier_upgrade() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	}
	if ( empty( $_POST['gc_upgrade_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_upgrade_nonce'] ) ), 'gc_upgrade' ) ) {
		wp_safe_redirect( home_url( '/premium-dashboard/?gc_err=nonce' ) );
		exit;
	}
	$uid  = get_current_user_id();
	$tier = absint( $_POST['upgrade_tier'] ?? 0 );
	$current = function_exists( 'gc_user_membership_tier' ) ? (int) gc_user_membership_tier( $uid ) : (int) get_user_meta( $uid, 'gc_premium_tier', true );
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
	$dash_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
	if ( $tier < 1 || empty( $tiers[ $tier ] ) || ( $current > 0 && $tier <= $current ) ) {
		wp_safe_redirect( add_query_arg( 'gc_err', 'tier', $dash_url ) . '#gc-upgrade' );
		exit;
	}
	$method = sanitize_key( $_POST['confirm_method'] ?? 'paystack_email' );
	$email  = sanitize_email( wp_unslash( $_POST['pay_email'] ?? wp_get_current_user()->user_email ) );
	$ref_in = trim( sanitize_text_field( wp_unslash( $_POST['pay_ref'] ?? '' ) ) );
	$invite = sanitize_text_field( wp_unslash( $_POST['invite_code'] ?? '' ) );

	if ( 'invite' === $method ) {
		$invites = get_option( 'gc_free_invites', array() );
		$key = strtoupper( trim( $invite ) );
		$ok = false;
		if ( $key && is_array( $invites ) && ! empty( $invites[ $key ] ) && empty( $invites[ $key ]['revoked'] ) ) {
			$inv = $invites[ $key ];
			$max = isset( $inv['max_uses'] ) ? absint( $inv['max_uses'] ) : 10000;
			$uses = isset( $inv['uses'] ) ? absint( $inv['uses'] ) : 0;
			if ( ( empty( $inv['used'] ) || ! empty( $inv['max_uses'] ) ) && ( $max <= 0 || $uses < $max ) ) {
				$ok = true;
				$invites[ $key ]['uses'] = $uses + 1;
				update_option( 'gc_free_invites', $invites, false );
			}
		}
		if ( ! $ok ) {
			$codes = array_filter( array_map( 'strtoupper', array_map( 'trim', explode( ',', (string) get_option( 'gc_admin_free_codes', '' ) ) ) ) );
			$single = strtoupper( trim( (string) get_option( 'gc_admin_invite_code', '' ) ) );
			if ( $single ) { $codes[] = $single; }
			$ok = $key && in_array( $key, $codes, true );
		}
		if ( ! $ok ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'invite', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		gc_migrate_user_to_premium( $uid, $tier, 'INVITE-UPG-' . $key . '-' . $uid );
		wp_safe_redirect( add_query_arg( 'gc_upgraded', '1', $dash_url ) );
		exit;
	}

	if ( 'paystack_email' === $method || 'paystack' === $method ) {
		if ( strlen( $ref_in ) < 6 ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'ref_required', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$secret = get_option( 'gc_paystack_secret_key', '' );
		if ( ! $secret || 0 !== strpos( $secret, 'sk_' ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'paystack_config', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$r = wp_remote_get(
			'https://api.paystack.co/transaction/verify/' . rawurlencode( $ref_in ),
			array( 'timeout' => 25, 'headers' => array( 'Authorization' => 'Bearer ' . $secret ) )
		);
		if ( is_wp_error( $r ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'verify_fail', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( empty( $body['data']['status'] ) || 'success' !== $body['data']['status'] ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'not_paid', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$used = get_option( 'gc_used_paystack_refs', array() );
		if ( ! is_array( $used ) ) { $used = array(); }
		$ref_key = strtoupper( $ref_in );
		if ( ! empty( $used[ $ref_key ] ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'ref_used', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$used[ $ref_key ] = array( 'email' => $email, 'tier' => $tier, 'uid' => $uid, 'time' => current_time( 'mysql' ) );
		update_option( 'gc_used_paystack_refs', $used, false );
		gc_migrate_user_to_premium( $uid, $tier, $ref_in );
		wp_safe_redirect( add_query_arg( 'gc_upgraded', '1', $dash_url ) );
		exit;
	}

	if ( 'receipt' === $method ) {
		if ( empty( $_FILES['receipt']['name'] ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'receipt', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$upload = wp_handle_upload( $_FILES['receipt'], array( 'test_form' => false ) );
		if ( isset( $upload['error'] ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'upload', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$id = wp_insert_post( array(
			'post_type'   => 'gc_submission',
			'post_status' => 'private',
			'post_title'  => 'Upgrade receipt pending — ' . $email,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'gc_form_type_label', 'Bank Receipt Claim' );
			update_post_meta( $id, 'gc_email', $email );
			update_post_meta( $id, 'gc_payment_tier', $tier );
			update_post_meta( $id, 'gc_user_id', $uid );
			update_post_meta( $id, 'gc_receipt_url', $upload['url'] );
			update_post_meta( $id, 'gc_workflow_status', 'pending_payment_review' );
		}
		$admin = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
		if ( is_email( $admin ) ) {
			$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
			wp_mail( $admin, '[Upgrade receipt] Tier ' . $tier . ' — ' . $email, "Pending admin approval.\nReceipt: {$upload['url']}\nUser ID: {$uid}", array( 'From: Game Changers Nigeria <' . $from . '>' ) );
		}
		// Do NOT upgrade until admin approves
		wp_safe_redirect( add_query_arg( 'gc_pending', '1', $dash_url ) . '#gc-upgrade' );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'gc_err', 'method', $dash_url ) . '#gc-upgrade' );
	exit;
}
add_action( 'admin_post_gc_tier_upgrade', 'gc_handle_tier_upgrade' );


/**
 * Donation: log amount, email, narration — then send donor to Paystack Shop.
 */
function gc_handle_donate_start() {
	if ( empty( $_POST['gc_donate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_donate_nonce'] ) ), 'gc_donate_start' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}
	$email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) );
	$amount = absint( $_POST['gc_amount'] ?? 0 );
	$narration = sanitize_textarea_field( wp_unslash( $_POST['gc_narration'] ?? '' ) );
	$shop = trim( (string) get_option( 'gc_paystack_shop_link', '' ) );
	if ( ! $shop ) {
		$shop = 'https://paystack.shop/pay/tzpci3b9qo';
	}
	$donate_page = function_exists( 'gc_page_url' ) ? gc_page_url( 'donate' ) : home_url( '/donate/' );

	if ( ! is_email( $email ) || $amount < 100 || strlen( $narration ) < 2 ) {
		wp_safe_redirect( add_query_arg( 'gc_err', 'fields', $donate_page ) );
		exit;
	}

	$log = get_option( 'gc_donation_intents', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$log[] = array(
		'email'     => $email,
		'amount'    => $amount,
		'narration' => $narration,
		'user_id'   => get_current_user_id(),
		'time'      => current_time( 'mysql' ),
		'ip'        => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
	);
	update_option( 'gc_donation_intents', array_slice( $log, -300 ), false );

	// Notify admin so they know purpose / source
	$admin = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
	if ( is_email( $admin ) ) {
		$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
		$headers = array(
			'From: Game Changers Nigeria <' . $from . '>',
			'Reply-To: ' . $email,
		);
		$subject = sprintf( '[Donation] ₦%s — %s', number_format_i18n( $amount ), $email );
		$body    = "New donation intent (Paystack Shop)\n\n";
		$body   .= 'Amount: ₦' . number_format_i18n( $amount ) . "\n";
		$body   .= 'Email: ' . $email . "\n";
		$body   .= "Narration / purpose:\n" . $narration . "\n\n";
		$body   .= 'Time: ' . current_time( 'mysql' ) . "\n";
		$body   .= "Donor will complete payment at: " . $shop . "\n";
		wp_mail( $admin, $subject, $body, $headers );
	}

	// Open Paystack Shop — amount is chosen on Paystack page if shop allows custom
	wp_redirect( $shop );
	exit;
}
add_action( 'admin_post_gc_donate_start', 'gc_handle_donate_start' );
add_action( 'admin_post_nopriv_gc_donate_start', 'gc_handle_donate_start' );


/** Log donation intent (email + narration) before Paystack popup. */
function gc_ajax_donate_intent_log() {
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? $_REQUEST['email'] ?? '' ) );
	$amount = absint( $_POST['amount'] ?? $_REQUEST['amount'] ?? 0 );
	$narration = sanitize_textarea_field( wp_unslash( $_POST['narration'] ?? $_REQUEST['narration'] ?? '' ) );
	$ref = sanitize_text_field( wp_unslash( $_POST['ref'] ?? $_REQUEST['ref'] ?? '' ) );
	$log = get_option( 'gc_donation_intents', array() );
	if ( ! is_array( $log ) ) { $log = array(); }
	$log[] = array(
		'email' => $email,
		'amount' => $amount,
		'narration' => $narration,
		'ref' => $ref,
		'time' => current_time( 'mysql' ),
		'method' => 'paystack_live',
	);
	update_option( 'gc_donation_intents', array_slice( $log, -300 ), false );
	$admin = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
	if ( is_email( $admin ) && is_email( $email ) ) {
		$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
		wp_mail(
			$admin,
			sprintf( '[Donation] ₦%s — %s', number_format_i18n( $amount ), $email ),
			"Live Paystack donation started.\n\nAmount: ₦" . number_format_i18n( $amount ) . "\nEmail: {$email}\nNarration: {$narration}\nRef: {$ref}\n",
			array( 'From: Game Changers Nigeria <' . $from . '>', 'Reply-To: ' . $email )
		);
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_gc_donate_intent_log', 'gc_ajax_donate_intent_log' );
add_action( 'wp_ajax_nopriv_gc_donate_intent_log', 'gc_ajax_donate_intent_log' );
