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
	if ( ! gc_user_is_premium( $user_id ) ) {
		return 0;
	}
	$t = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	return $t > 0 ? $t : 1;
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
	if ( ! $referrer_id || ! $new_user_id || $referrer_id === $new_user_id ) {
		return;
	}
	// Points scale lightly by tier joined
	$points_map = array( 1 => 50, 2 => 100, 3 => 200, 4 => 400, 5 => 600 );
	$points     = isset( $points_map[ (int) $tier ] ) ? $points_map[ (int) $tier ] : 50;

	$current = (int) get_user_meta( $referrer_id, 'gc_total_points', true );
	update_user_meta( $referrer_id, 'gc_total_points', $current + $points );

	$ref_count = (int) get_user_meta( $referrer_id, 'gc_referral_count', true );
	update_user_meta( $referrer_id, 'gc_referral_count', $ref_count + 1 );

	// Log on both users
	update_user_meta( $new_user_id, 'gc_referred_by', $referrer_id );
	$log = (array) get_user_meta( $referrer_id, 'gc_referral_log', true );
	$log[] = array(
		'user_id' => $new_user_id,
		'tier'    => (int) $tier,
		'points'  => $points,
		'time'    => current_time( 'mysql' ),
	);
	update_user_meta( $referrer_id, 'gc_referral_log', $log );

	// Optional: feed engagement engine if available
	if ( function_exists( 'gc_record_activity' ) ) {
		// Use community_contribution as closest type if referral type not configured
		gc_record_activity( $referrer_id, 'community_contribution', $new_user_id, 'referral_join' );
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
	set_transient( 'gc_paid_' . md5( $ref ), array(
		'ref'    => $ref,
		'tier'   => $tier,
		'amount' => $amount_naira,
		'email'  => $email,
		'time'   => time(),
	), DAY_IN_SECONDS );

	// Already logged in (free or premium) → migrate/upgrade same account, same password
	if ( is_user_logged_in() ) {
		$uid = get_current_user_id();
		$current_tier = function_exists( 'gc_user_membership_tier' ) ? gc_user_membership_tier( $uid ) : (int) get_user_meta( $uid, 'gc_premium_tier', true );
		$target = max( $tier, 1 );
		if ( $target > $current_tier || ! gc_user_is_premium( $uid ) ) {
			gc_migrate_user_to_premium( $uid, $target, $ref );
		} else {
			update_user_meta( $uid, 'gc_last_payment_ref', $ref );
	if ( function_exists( 'gc_save_payout_account' ) ) {
		gc_save_payout_account( $email, $bank_name, $account_name, $account_number, $uid );
	}
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

	$paid = $ref ? get_transient( 'gc_paid_' . md5( $ref ) ) : false;
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
	update_user_meta( $uid, 'gc_phone', $phone );
	update_user_meta( $uid, 'gc_state', $state );
	update_user_meta( $uid, 'gc_joined_at', current_time( 'mysql' ) );
	update_user_meta( $uid, 'gc_total_points', 25 ); // welcome points

	// Referral
	if ( $ref_tag_in ) {
		$referrer = gc_user_by_referral_tag( $ref_tag_in );
		if ( $referrer ) {
			update_user_meta( $uid, 'gc_referrer_tag_raw', $ref_tag_in );
			gc_award_referral_points( $referrer->ID, $uid, $tier );
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
	$shop   = gc_join_pay_link_for_tier( $tier );
	$key    = get_option( 'gc_paystack_public_key', '' );
	ob_start();

	// Always show something actionable
	if ( $shop ) {
		$sep = ( strpos( $shop, '?' ) !== false ) ? '&' : '?';
		$url = $shop . $sep . 'tier=' . $tier;
		?>
		<a class="gc-button gc-button-gold gc-join-pay-btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
			Pay ₦<?php echo esc_html( number_format_i18n( $amount ) ); ?> with Paystack
		</a>
		<p class="gc-join-hint" style="margin-top:10px;font-size:13px;color:#66756a;">After paying, return here and confirm with the email you used on Paystack, or upload your bank receipt.</p>
		<?php
	}

	if ( $key && $amount ) {
		$ref = 'GCN-T' . $tier . '-' . time() . '-' . wp_rand( 1000, 9999 );
		$verify = add_query_arg(
			array( 'action' => 'gc_paystack_verify', 'tier' => $tier ),
			admin_url( 'admin-post.php' )
		);
		?>
		<button type="button" class="gc-button <?php echo $shop ? 'gc-button-green' : 'gc-button-gold'; ?> gc-join-pay-btn"
			data-gc-paystack="1"
			data-amount="<?php echo esc_attr( $amount ); ?>"
			data-ref="<?php echo esc_attr( $ref ); ?>"
			data-tier="<?php echo esc_attr( $tier ); ?>"
			data-email="<?php echo esc_attr( $email ); ?>"
			data-verify="<?php echo esc_url( $verify ); ?>">
			<?php echo $shop ? 'Or pay in-app (Paystack popup)' : 'Pay ₦' . esc_html( number_format_i18n( $amount ) ) . ' with Paystack'; ?>
		</button>
		<script src="https://js.paystack.co/v2/inline.js"></script>
		<script>
		(function(){
			var key = <?php echo wp_json_encode( $key ); ?>;
			document.querySelectorAll('.gc-join-pay-btn[data-gc-paystack]').forEach(function(b){
				if (b.getAttribute('data-bound') === '1') return;
				b.setAttribute('data-bound', '1');
				b.addEventListener('click', function(){
					if (!window.PaystackPop) { alert('Paystack is loading — try again in a moment.'); return; }
					var amount = parseInt(b.getAttribute('data-amount'), 10) * 100;
					var email = b.getAttribute('data-email') || prompt('Enter your email for payment receipt') || '';
					if (!email || email.indexOf('@') < 0) { alert('A valid email is required to pay.'); return; }
					var tier = parseInt(b.getAttribute('data-tier'), 10) || 1;
					var ref = b.getAttribute('data-ref');
					var verifyBase = b.getAttribute('data-verify');
					var p = new PaystackPop();
					p.newTransaction({
						key: key,
						email: email,
						amount: amount,
						ref: ref,
						metadata: { tier: tier },
						onSuccess: function(t){
							location.href = verifyBase + '&reference=' + encodeURIComponent(t.reference || ref);
						},
						onCancel: function(){}
					});
				});
			});
		})();
		</script>
		<?php
	}

	if ( ! $shop && ! $key ) {
		?>
		<div class="gc-join-err">Payment is not configured yet. Add a Paystack public key or a Paystack Shop link in <strong>WP Admin → Settings → GC Payments</strong>.</div>
		<?php
	}

	return ob_get_clean();
}

add_action( 'admin_init', function () {
	register_setting( 'gc_payments', 'gc_paystack_shop_link', array( 'sanitize_callback' => 'esc_url_raw' ) );
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

	// Admin free-invite path (for non-registered members)
	if ( 'invite' === $method && $invite ) {
		$invites = get_option( 'gc_free_invites', array() );
		$key = strtoupper( $invite );
		if ( empty( $invites[ $key ] ) || ! empty( $invites[ $key ]['revoked'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
			exit;
		}
		$inv = $invites[ $key ];
		$max = isset( $inv['max_uses'] ) ? absint( $inv['max_uses'] ) : 10000;
		$uses = isset( $inv['uses'] ) ? absint( $inv['uses'] ) : 0;
		// Legacy single-use flag
		if ( ! empty( $inv['used'] ) && empty( $inv['max_uses'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
			exit;
		}
		if ( $max > 0 && $uses >= $max ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite' ) ) );
			exit;
		}
		// Email lock: if set, only that email may use the code
		if ( ! empty( $inv['email'] ) ) {
			if ( ! $email || strtolower( $inv['email'] ) !== strtolower( $email ) ) {
				wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'invite_email' ) ) );
				exit;
			}
		}
		$use_tier = ! empty( $inv['tier'] ) ? absint( $inv['tier'] ) : $tier;
		$ref = 'INVITE-' . $key . '-' . time() . '-' . wp_rand( 100, 999 );
		set_transient( 'gc_paid_' . md5( $ref ), array(
			'ref'     => $ref,
			'tier'    => $use_tier,
			'amount'  => 0,
			'email'   => $email ?: ( $inv['email'] ?? '' ),
			'time'    => time(),
			'method'  => 'invite',
			'invite'  => $key,
			'free'    => 1,
		), DAY_IN_SECONDS );
		wp_safe_redirect( gc_join_page_url( array( 'paid' => '1', 'tier' => $use_tier, 'ref' => $ref ) ) );
		exit;
	}

	if ( 'paystack_email' === $method ) {
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'email' ) ) );
			exit;
		}
		$ref = $ref_in ? $ref_in : ( 'EMAIL-' . time() . '-' . wp_rand( 100, 999 ) );
		set_transient( 'gc_paid_' . md5( $ref ), array(
			'ref'     => $ref,
			'tier'    => $tier,
			'amount'  => 0,
			'email'   => $email,
			'time'    => time(),
			'method'  => 'paystack_email',
			'pending_admin' => 1, // admin can audit
		), DAY_IN_SECONDS );
		// Log for admin review
		$logs = get_option( 'gc_payment_claims', array() );
		if ( ! is_array( $logs ) ) { $logs = array(); }
		$logs[] = array( 'ref' => $ref, 'tier' => $tier, 'email' => $email, 'method' => 'paystack_email', 'time' => current_time( 'mysql' ) );
		update_option( 'gc_payment_claims', array_slice( $logs, -200 ), false );
		wp_safe_redirect( gc_join_page_url( array( 'paid' => '1', 'tier' => $tier, 'ref' => $ref ) ) );
		exit;
	}

	if ( 'receipt' === $method ) {
		if ( empty( $_FILES['receipt']['name'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'receipt' ) ) );
			exit;
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$upload = wp_handle_upload( $_FILES['receipt'], array( 'test_form' => false, 'mimes' => array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png' => 'image/png',
			'pdf' => 'application/pdf',
			'webp' => 'image/webp',
		) ) );
		if ( isset( $upload['error'] ) ) {
			wp_safe_redirect( gc_join_page_url( array( 'tier' => $tier, 'gc_err' => 'upload' ) ) );
			exit;
		}
		$ref = 'RCPT-' . time() . '-' . wp_rand( 100, 999 );
		set_transient( 'gc_paid_' . md5( $ref ), array(
			'ref'     => $ref,
			'tier'    => $tier,
			'amount'  => 0,
			'email'   => $email,
			'time'    => time(),
			'method'  => 'receipt',
			'receipt' => $upload['url'],
			'pending_admin' => 1,
		), DAY_IN_SECONDS );
		$logs = get_option( 'gc_payment_claims', array() );
		if ( ! is_array( $logs ) ) { $logs = array(); }
		$logs[] = array( 'ref' => $ref, 'tier' => $tier, 'email' => $email, 'method' => 'receipt', 'receipt' => $upload['url'], 'time' => current_time( 'mysql' ) );
		update_option( 'gc_payment_claims', array_slice( $logs, -200 ), false );
		wp_safe_redirect( gc_join_page_url( array( 'paid' => '1', 'tier' => $tier, 'ref' => $ref ) ) );
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
	if ( $tier < 1 || empty( $tiers[ $tier ] ) || ( $current > 0 && $tier <= $current ) ) {
		$dash = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
		wp_safe_redirect( add_query_arg( 'gc_err', 'tier', $dash ) . '#gc-upgrade' );
		exit;
	}
	$method = sanitize_key( $_POST['confirm_method'] ?? 'paystack_email' );
	$email  = sanitize_email( wp_unslash( $_POST['pay_email'] ?? wp_get_current_user()->user_email ) );
	$ref_in = sanitize_text_field( wp_unslash( $_POST['pay_ref'] ?? '' ) );
	$invite = sanitize_text_field( wp_unslash( $_POST['invite_code'] ?? '' ) );
	$dash_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );

	$receipt = '';
	if ( 'invite' === $method ) {
		$invites = get_option( 'gc_free_invites', array() );
		$key = strtoupper( trim( $invite ) );
		$ok = false;
		if ( $key && is_array( $invites ) && ! empty( $invites[ $key ] ) && empty( $invites[ $key ]['revoked'] ) ) {
			$inv = $invites[ $key ];
			$max = isset( $inv['max_uses'] ) ? absint( $inv['max_uses'] ) : 10000;
			$uses = isset( $inv['uses'] ) ? absint( $inv['uses'] ) : 0;
			if ( ! empty( $inv['used'] ) && empty( $inv['max_uses'] ) ) {
				$ok = false;
			} elseif ( $max > 0 && $uses >= $max ) {
				$ok = false;
			} else {
				$ok = true;
				$invites[ $key ]['uses'] = $uses + 1;
				if ( empty( $inv['max_uses'] ) ) {
					$invites[ $key ]['used'] = 1;
				}
				update_option( 'gc_free_invites', $invites, false );
			}
		}
		// Fallback simple option
		if ( ! $ok ) {
			$single = strtoupper( trim( (string) get_option( 'gc_admin_invite_code', '' ) ) );
			$codes = array_filter( array_map( 'strtoupper', array_map( 'trim', explode( ',', (string) get_option( 'gc_admin_free_codes', '' ) ) ) ) );
			if ( $single ) {
				$codes[] = $single;
			}
			$ok = $key && in_array( $key, $codes, true );
		}
		if ( ! $ok ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'invite', $dash_url ) . '#gc-upgrade' );
			exit;
		}
		$ref_in = $ref_in ?: ( 'INVITE-' . $key . '-' . $uid );
	} elseif ( 'receipt' === $method ) {
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
		$receipt = $upload['url'];
	} else {
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'gc_err', 'email', $dash_url ) . '#gc-upgrade' );
			exit;
		}
	}

	// Apply upgrade / free→premium migration (same email & password)
	gc_migrate_user_to_premium( $uid, $tier, $ref_in ?: ( 'UPG-T' . $tier . '-' . $uid . '-' . time() ) );
	$ref = $ref_in ?: ( 'UPG-T' . $tier . '-' . $uid . '-' . time() );
	$logs = get_option( 'gc_payment_claims', array() );
	if ( ! is_array( $logs ) ) { $logs = array(); }
	$logs[] = array(
		'ref' => $ref,
		'tier' => $tier,
		'email' => $email,
		'method' => 'upgrade_' . $method,
		'receipt' => $receipt,
		'user_id' => $uid,
		'time' => current_time( 'mysql' ),
	);
	update_option( 'gc_payment_claims', array_slice( $logs, -200 ), false );
	update_user_meta( $uid, 'gc_last_upgrade_at', current_time( 'mysql' ) );
	update_user_meta( $uid, 'gc_last_upgrade_ref', $ref );
	wp_safe_redirect( home_url( '/premium-dashboard/?upgraded=1#gc-upgrade' ) );
	exit;
}
add_action( 'admin_post_gc_tier_upgrade', 'gc_handle_tier_upgrade' );
