<?php
/**
 * Admin Control Room — free tier access, certificates registry,
 * course materials, Inner Circle, member progress.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Free / locked tier access (admin only) ---------- */

function gc_user_has_tier_access( $user_id, $tier ) {
	$user_id = absint( $user_id );
	$tier    = absint( $tier );
	if ( ! $user_id || ! $tier ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	// Explicit lock
	if ( get_user_meta( $user_id, 'gc_tier_locked_' . $tier, true ) ) {
		return false;
	}
	// Admin free grant for this tier or "all"
	if ( get_user_meta( $user_id, 'gc_free_access_all', true ) ) {
		return true;
	}
	if ( get_user_meta( $user_id, 'gc_free_access_tier_' . $tier, true ) ) {
		return true;
	}
	// Paid / assigned tier
	$paid = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	if ( $paid >= $tier ) {
		return true;
	}
	// Override used by certificates
	if ( get_user_meta( $user_id, 'gc_cert_override_tier_' . $tier, true ) ) {
		return true;
	}
	return false;
}

function gc_admin_grant_free_access( $user_id, $tier = 0 ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return;
	}
	$user = new WP_User( $user_id );
	if ( ! in_array( 'premium_member', (array) $user->roles, true ) ) {
		$user->add_role( 'premium_member' );
	}
	if ( $tier <= 0 ) {
		update_user_meta( $user_id, 'gc_free_access_all', 1 );
		$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
		$max   = $tiers ? max( array_keys( $tiers ) ) : 5;
		update_user_meta( $user_id, 'gc_premium_tier', $max );
		for ( $i = 1; $i <= $max; $i++ ) {
			delete_user_meta( $user_id, 'gc_tier_locked_' . $i );
			update_user_meta( $user_id, 'gc_free_access_tier_' . $i, 1 );
		}
	} else {
		update_user_meta( $user_id, 'gc_free_access_tier_' . $tier, 1 );
		delete_user_meta( $user_id, 'gc_tier_locked_' . $tier );
		$current = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
		if ( $tier > $current ) {
			update_user_meta( $user_id, 'gc_premium_tier', $tier );
		}
	}
}

function gc_admin_lock_access( $user_id, $tier = 0 ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return;
	}
	if ( $tier <= 0 ) {
		delete_user_meta( $user_id, 'gc_free_access_all' );
		$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array( 1, 2, 3, 4, 5 );
		foreach ( array_keys( (array) $tiers ) as $i ) {
			delete_user_meta( $user_id, 'gc_free_access_tier_' . $i );
			update_user_meta( $user_id, 'gc_tier_locked_' . $i, 1 );
		}
	} else {
		delete_user_meta( $user_id, 'gc_free_access_tier_' . $tier );
		update_user_meta( $user_id, 'gc_tier_locked_' . $tier, 1 );
	}
}

/* ---------- Certificate registry (admin-generated, verifiable) ---------- */

function gc_cert_registry_table() {
	return array(); // stored as option list for simplicity
}

function gc_register_certificate( $args ) {
	$name   = sanitize_text_field( $args['name'] ?? '' );
	$tier   = absint( $args['tier'] ?? 1 );
	$user_id = absint( $args['user_id'] ?? 0 );
	$year   = absint( $args['year'] ?? date( 'Y' ) );
	if ( ! $name ) {
		return new WP_Error( 'name', 'Name is required' );
	}
	$registry = get_option( 'gc_certificate_registry', array() );
	if ( ! is_array( $registry ) ) {
		$registry = array();
	}
	// Next sequence
	$seq = 1;
	foreach ( $registry as $row ) {
		if ( isset( $row['seq'] ) && (int) $row['seq'] >= $seq ) {
			$seq = (int) $row['seq'] + 1;
		}
	}
	$code = sprintf( 'GCN-%d-%05d-T%d', $year, $seq, $tier );
	$entry = array(
		'code'       => $code,
		'name'       => $name,
		'tier'       => $tier,
		'user_id'    => $user_id,
		'seq'        => $seq,
		'year'       => $year,
		'created_at' => current_time( 'mysql' ),
		'created_by' => get_current_user_id(),
	);
	$registry[ $code ] = $entry;
	update_option( 'gc_certificate_registry', $registry, false );
	if ( $user_id ) {
		update_user_meta( $user_id, 'gc_certificate_code_tier_' . $tier, $code );
		update_user_meta( $user_id, 'gc_certificate_name', $name );
	}
	return $entry;
}

function gc_lookup_certificate( $code ) {
	$code = strtoupper( trim( (string) $code ) );
	$registry = get_option( 'gc_certificate_registry', array() );
	if ( isset( $registry[ $code ] ) ) {
		return $registry[ $code ];
	}
	return null;
}

/* ---------- Course materials (per tier) ---------- */

function gc_get_tier_materials( $tier ) {
	$all = get_option( 'gc_tier_materials', array() );
	$tier = absint( $tier );
	return ( is_array( $all ) && isset( $all[ $tier ] ) && is_array( $all[ $tier ] ) ) ? $all[ $tier ] : array();
}

/* ---------- Inner Circle eligibility ---------- */

function gc_user_is_inner_circle( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}
	// Explicit grant / already marked
	if ( get_user_meta( $user_id, 'gc_inner_circle', true ) ) {
		return true;
	}
	// Must have reached Tier 3+ (payment completed for Impact Architect or higher)
	$tier = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	if ( function_exists( 'gc_get_user_premium_tier' ) ) {
		$tier = max( $tier, (int) gc_get_user_premium_tier( $user_id ) );
	}
	if ( $tier < 3 ) {
		return false;
	}
	// Must have completed the course/lessons for accessible tiers (payment alone is not enough)
	$lessons_ok = true;
	if ( function_exists( 'gc_user_lesson_progress' ) ) {
		$prog = gc_user_lesson_progress( $user_id );
		// Require full completion when lessons exist; if none published yet, tier alone qualifies
		if ( ! empty( $prog['total'] ) && (int) $prog['percent'] < 100 ) {
			$lessons_ok = false;
		}
	} else {
		// Fallback: require at least one completed lesson recorded
		$completed = (array) get_user_meta( $user_id, 'gc_completed_lessons', true );
		$completed = array_filter( array_map( 'absint', $completed ) );
		if ( empty( $completed ) ) {
			// Soft-pass if no lesson CPT content exists site-wide
			$any_lessons = get_posts( array(
				'post_type'      => 'gc_lesson',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			if ( ! empty( $any_lessons ) ) {
				$lessons_ok = false;
			}
		}
	}
	if ( ! $lessons_ok ) {
		return false;
	}
	// Eligible: auto-promote to Inner Circle
	if ( function_exists( 'gc_mark_inner_circle' ) ) {
		gc_mark_inner_circle( $user_id, true );
	} else {
		update_user_meta( $user_id, 'gc_inner_circle', 1 );
	}
	return true;
}

function gc_mark_inner_circle( $user_id, $on = true ) {
	if ( $on ) {
		update_user_meta( $user_id, 'gc_inner_circle', 1 );
		$tag = get_user_meta( $user_id, 'gc_inner_circle_tag', true );
		if ( ! $tag ) {
			$tag = 'IC-' . strtoupper( wp_generate_password( 6, false, false ) );
			update_user_meta( $user_id, 'gc_inner_circle_tag', $tag );
		}
	} else {
		delete_user_meta( $user_id, 'gc_inner_circle' );
	}
}

/* ---------- Admin menu: Control Room ---------- */

add_action( 'admin_menu', function () {
	add_menu_page(
		'GC Control Room',
		'GC Control Room',
		'manage_options',
		'gc-control-room',
		'gc_render_control_room',
		'dashicons-shield',
		3
	);
	add_submenu_page( 'gc-control-room', 'Certificates', 'Certificates', 'manage_options', 'gc-control-certs', 'gc_render_control_certs' );
	add_submenu_page( 'gc-control-room', 'Materials', 'Course Materials', 'manage_options', 'gc-control-materials', 'gc_render_control_materials' );
	add_submenu_page( 'gc-control-room', 'Members Access', 'Members Access', 'manage_options', 'gc-control-access', 'gc_render_control_access' );
	add_submenu_page( 'gc-control-room', 'Payment claims', 'Payment claims', 'manage_options', 'gc-control-claims', 'gc_render_control_claims' );
}, 20 );

function gc_render_control_room() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$members = get_users( array( 'role' => 'premium_member', 'number' => 50, 'orderby' => 'registered', 'order' => 'DESC' ) );
	$wa = get_option( 'gc_inner_circle_whatsapp', '' );
	if ( isset( $_POST['gc_cr_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_cr_nonce'] ) ), 'gc_cr_save' ) ) {
		if ( isset( $_POST['gc_inner_circle_whatsapp'] ) ) {
			update_option( 'gc_inner_circle_whatsapp', esc_url_raw( wp_unslash( $_POST['gc_inner_circle_whatsapp'] ) ) );
			$wa = get_option( 'gc_inner_circle_whatsapp', '' );
			echo '<div class="notice notice-success"><p>Saved.</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1>Game Changers — Control Room</h1>
		<p>Grant free leadership access, manage Inner Circle, certificates and materials.</p>
		<form method="post" style="max-width:640px;background:#fff;padding:16px 20px;border:1px solid #ccd0d4;margin-bottom:24px;">
			<?php wp_nonce_field( 'gc_cr_save', 'gc_cr_nonce' ); ?>
			<h2>Inner Circle WhatsApp</h2>
			<p>Members in the Inner Circle dashboard see this link to message admin.</p>
			<input type="url" class="regular-text" name="gc_inner_circle_whatsapp" value="<?php echo esc_attr( $wa ); ?>" placeholder="https://wa.me/234…">
			<?php submit_button( 'Save WhatsApp link' ); ?>
		</form>
		<h2>Recent premium members</h2>
		<table class="widefat striped">
			<thead><tr><th>Name</th><th>Email</th><th>Tier</th><th>Inner Circle</th><th>Points</th><th>Referrals</th></tr></thead>
			<tbody>
			<?php foreach ( $members as $u ) :
				$tier = (int) get_user_meta( $u->ID, 'gc_premium_tier', true );
				$pts  = (int) get_user_meta( $u->ID, 'gc_total_points', true );
				$refs = (int) get_user_meta( $u->ID, 'gc_referral_count', true );
				$ic   = gc_user_is_inner_circle( $u->ID );
				?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $u->ID ) ); ?>"><?php echo esc_html( $u->display_name ); ?></a></td>
					<td><?php echo esc_html( $u->user_email ); ?></td>
					<td><?php echo (int) $tier; ?></td>
					<td><?php echo $ic ? 'Yes' : '—'; ?></td>
					<td><?php echo (int) $pts; ?></td>
					<td><?php echo (int) $refs; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function gc_render_control_access() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>Members Access</h1>';
	if ( ! empty( $_GET['gc_email_ok'] ) ) {
		echo '<div class="notice notice-success"><p>Member email updated. Password reset sent to the new address if selected.</p></div>';
	}
	if ( ! empty( $_GET['gc_email_err'] ) ) {
		$errs = array( 'invalid' => 'Invalid email.', 'notfound' => 'No user with that old email.', 'taken' => 'New email already in use.', 'fail' => 'Update failed.' );
		$e = sanitize_key( $_GET['gc_email_err'] );
		echo '<div class="notice notice-error"><p>' . esc_html( $errs[ $e ] ?? 'Error' ) . '</p></div>';
	}
	echo '<h2>Fix member email &amp; send password reset</h2>';
	echo '<p>Use when someone registered with the wrong email or is not receiving mail. Login name stays the same; email used for mail and login-by-email updates.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:520px;background:#fff;padding:16px;border:1px solid #ccd0d4;border-radius:8px;margin-bottom:28px;">';
	echo '<input type="hidden" name="action" value="gc_change_member_email">';
	wp_nonce_field( 'gc_change_member_email' );
	echo '<p><label>Current email on account<br><input type="email" name="old_email" class="regular-text" required></label></p>';
	echo '<p><label>New correct email<br><input type="email" name="new_email" class="regular-text" required></label></p>';
	echo '<p><label><input type="checkbox" name="send_reset" value="1" checked> Send password-reset link to the <strong>new</strong> email</label></p>';
	echo '<p><button type="submit" class="button button-primary">Update email</button></p>';
	echo '</form>';
	$msg = '';
	// Free invite for non-registered people
	if ( isset( $_POST['gc_invite_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_invite_nonce'] ) ), 'gc_invite' ) ) {
		$act = sanitize_key( $_POST['gc_invite_action'] ?? '' );
		$invites = get_option( 'gc_free_invites', array() );
		if ( ! is_array( $invites ) ) { $invites = array(); }
		if ( 'create' === $act ) {
			$code = strtoupper( sanitize_text_field( wp_unslash( $_POST['invite_code'] ?? '' ) ) );
			if ( ! $code ) {
				$code = 'GC-FREE-' . strtoupper( wp_generate_password( 6, false, false ) );
			}
			$email = sanitize_email( wp_unslash( $_POST['invite_email'] ?? '' ) );
			$tier  = absint( $_POST['invite_tier'] ?? 0 );
			$max_uses = absint( $_POST['invite_max'] ?? 10000 );
			if ( $max_uses < 1 ) { $max_uses = 10000; }
			$invites[ $code ] = array(
				'code'  => $code,
				'email' => $email,
				'tier'  => $tier,
				'used'  => 0,
				'uses'  => 0,
				'max_uses' => $max_uses,
				'revoked' => 0,
				'created' => current_time( 'mysql' ),
			);
			update_option( 'gc_free_invites', $invites, false );
			$msg = 'Invite created: ' . $code . ' (up to ' . $max_uses . ' uses)' . ( $email ? ' · locked to ' . $email : ' · open' );
		} elseif ( 'revoke' === $act ) {
			$key = strtoupper( sanitize_text_field( wp_unslash( $_POST['invite_key'] ?? '' ) ) );
			if ( isset( $invites[ $key ] ) ) {
				$invites[ $key ]['revoked'] = 1;
				update_option( 'gc_free_invites', $invites, false );
				$msg = 'Invite locked/revoked: ' . $key;
			}
		} elseif ( 'delete' === $act ) {
			$key = strtoupper( sanitize_text_field( wp_unslash( $_POST['invite_key'] ?? '' ) ) );
			if ( isset( $invites[ $key ] ) ) {
				unset( $invites[ $key ] );
				update_option( 'gc_free_invites', $invites, false );
				$msg = 'Invite deleted: ' . $key;
			}
		}
	}
	if ( isset( $_POST['gc_access_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_access_nonce'] ) ), 'gc_access' ) ) {
		$uid  = absint( $_POST['user_id'] ?? 0 );
		$tier = absint( $_POST['tier'] ?? 0 );
		$act  = sanitize_key( $_POST['gc_access_action'] ?? '' );
		if ( $uid && 'grant_all' === $act ) {
			gc_admin_grant_free_access( $uid, 0 );
			gc_mark_inner_circle( $uid, true );
			$msg = 'Granted free access to all tiers.';
		} elseif ( $uid && 'grant_tier' === $act && $tier ) {
			gc_admin_grant_free_access( $uid, $tier );
			$msg = 'Granted free access to tier ' . $tier . '.';
		} elseif ( $uid && 'lock_all' === $act ) {
			gc_admin_lock_access( $uid, 0 );
			$msg = 'Locked all free access.';
		} elseif ( $uid && 'lock_tier' === $act && $tier ) {
			gc_admin_lock_access( $uid, $tier );
			$msg = 'Locked tier ' . $tier . '.';
		} elseif ( $uid && 'inner_on' === $act ) {
			gc_mark_inner_circle( $uid, true );
			$msg = 'Marked Inner Circle.';
		} elseif ( $uid && 'inner_off' === $act ) {
			gc_mark_inner_circle( $uid, false );
			$msg = 'Removed Inner Circle.';
		}
	}
	$users = get_users( array( 'orderby' => 'display_name', 'number' => 200 ) );
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array( 1 => array( 'name' => 'T1' ), 2 => array( 'name' => 'T2' ), 3 => array( 'name' => 'T3' ), 4 => array( 'name' => 'T4' ), 5 => array( 'name' => 'T5' ) );
	?>
	<div class="wrap">
		<h1>Members — Free Access & Locks</h1>
		<?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
		<p>Grant free leadership access without payment, or lock it again. Only admins can do this.</p>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;max-width:720px;">
			<?php wp_nonce_field( 'gc_access', 'gc_access_nonce' ); ?>
			<p>
				<label>Member<br>
				<select name="user_id" required style="min-width:280px;">
					<option value="">— select —</option>
					<?php foreach ( $users as $u ) : ?>
						<option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name . ' (' . $u->user_email . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
				</label>
			</p>
			<p>
				<label>Tier (for grant/lock single tier)<br>
				<select name="tier">
					<option value="0">All tiers</option>
					<?php foreach ( $tiers as $n => $tv ) : ?>
						<option value="<?php echo (int) $n; ?>"><?php echo esc_html( 'Tier ' . $n . ' — ' . ( $tv['name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				</label>
			</p>
			<p>
				<button class="button button-primary" name="gc_access_action" value="grant_all">Grant ALL tiers free</button>
				<button class="button" name="gc_access_action" value="grant_tier">Grant selected tier free</button>
				<button class="button" name="gc_access_action" value="lock_tier">Lock selected tier</button>
				<button class="button" name="gc_access_action" value="lock_all">Lock all free access</button>
			</p>
			<p>
				<button class="button button-primary" name="gc_access_action" value="inner_on">Mark Inner Circle</button>
				<button class="button" name="gc_access_action" value="inner_off">Remove Inner Circle</button>
			</p>
		</form>

		<hr style="margin:32px 0;">
		<h2>Free access for non-registered people</h2>
		<p>Create an invite code so someone can register <strong>without payment</strong>. Optionally lock it to one email. Revoke (lock) anytime.</p>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;max-width:720px;margin-bottom:16px;">
			<?php wp_nonce_field( 'gc_invite', 'gc_invite_nonce' ); ?>
			<p>
				<label>Invite code (leave blank to auto-generate)<br>
				<input class="regular-text" name="invite_code" placeholder="e.g. GC-FREE-LAGOS"></label>
			</p>
			<p>
				<label>Lock to email (optional — leave blank for open code)<br>
				<input type="email" class="regular-text" name="invite_email" placeholder="member@email.com"></label>
			</p>
			<p>
				<label>Tier to grant<br>
				<select name="invite_tier">
					<option value="0">All / highest available</option>
					<?php foreach ( $tiers as $n => $tv ) : ?>
						<option value="<?php echo (int) $n; ?>"><?php echo esc_html( 'Tier ' . $n . ' — ' . ( $tv['name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				</label>
			</p>
			<p>
				<label>Max number of people who can use this code<br>
				<input type="number" name="invite_max" value="10000" min="1" max="100000" class="small-text"> (default 10,000 — good for scholarships / gatherings)</label>
			</p>
			<button class="button button-primary" name="gc_invite_action" value="create">Generate free invite</button>
		</form>
		<?php
		$invites = get_option( 'gc_free_invites', array() );
		if ( is_array( $invites ) && $invites ) :
			?>
			<table class="widefat striped" style="max-width:980px;">
				<thead><tr><th>Code</th><th>Email lock</th><th>Tier</th><th>Uses</th><th>Status</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $invites as $k => $inv ) :
					$uses = isset( $inv['uses'] ) ? (int) $inv['uses'] : ( ! empty( $inv['used'] ) ? 1 : 0 );
					$max  = isset( $inv['max_uses'] ) ? (int) $inv['max_uses'] : 10000;
					$st = ! empty( $inv['revoked'] ) ? 'Locked' : ( ( $max && $uses >= $max ) || ( ! empty( $inv['used'] ) && empty( $inv['max_uses'] ) ) ? 'Full' : 'Active' );
					?>
					<tr>
						<td><code><?php echo esc_html( $k ); ?></code></td>
						<td><?php echo esc_html( ! empty( $inv['email'] ) ? $inv['email'] : '— open —' ); ?></td>
						<td><?php echo ! empty( $inv['tier'] ) ? (int) $inv['tier'] : 'All'; ?></td>
						<td><?php echo (int) $uses; ?> / <?php echo (int) $max; ?></td>
						<td><?php echo esc_html( $st ); ?></td>
						<td>
							<form method="post" style="display:inline;"><?php wp_nonce_field( 'gc_invite', 'gc_invite_nonce' ); ?>
								<input type="hidden" name="invite_key" value="<?php echo esc_attr( $k ); ?>">
								<?php if ( empty( $inv['revoked'] ) ) : ?>
								<button class="button" name="gc_invite_action" value="revoke">Lock / revoke</button>
								<?php endif; ?>
								<button class="button button-link-delete" name="gc_invite_action" value="delete" onclick="return confirm('Delete this code permanently?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</div>
	<?php
}

function gc_render_control_certs() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['gc_cert_gen_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_cert_gen_nonce'] ) ), 'gc_cert_gen' ) ) {
		$entry = gc_register_certificate( array(
			'name'    => sanitize_text_field( wp_unslash( $_POST['cert_name'] ?? '' ) ),
			'tier'    => absint( $_POST['cert_tier'] ?? 1 ),
			'user_id' => absint( $_POST['cert_user'] ?? 0 ),
			'year'    => absint( $_POST['cert_year'] ?? date( 'Y' ) ),
		) );
		if ( is_wp_error( $entry ) ) {
			$msg = $entry->get_error_message();
		} else {
			$msg = 'Registered: ' . $entry['code'] . ' for ' . $entry['name'];
		}
	}
	$registry = get_option( 'gc_certificate_registry', array() );
	$users = get_users( array( 'role' => 'premium_member', 'number' => 200 ) );
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
	?>
	<div class="wrap">
		<h1>Generate certificate tracking IDs</h1>
		<?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
		<p>Format: <code>GCN-YEAR-#####-T#</code>. Stored for public verification.</p>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;max-width:640px;margin-bottom:24px;">
			<?php wp_nonce_field( 'gc_cert_gen', 'gc_cert_gen_nonce' ); ?>
			<p><label>Name on certificate<br><input class="regular-text" name="cert_name" required placeholder="Full name as printed"></label></p>
			<p><label>Tier<br><select name="cert_tier"><?php foreach ( $tiers as $n => $tv ) : ?><option value="<?php echo (int) $n; ?>"><?php echo esc_html( 'Tier ' . $n . ' — ' . ( $tv['name'] ?? '' ) ); ?></option><?php endforeach; ?></select></label></p>
			<p><label>Link to member (optional)<br>
				<select name="cert_user"><option value="0">— none —</option>
				<?php foreach ( $users as $u ) : ?><option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option><?php endforeach; ?>
				</select>
			</label></p>
			<p><label>Year<br><input type="number" name="cert_year" value="<?php echo esc_attr( date( 'Y' ) ); ?>" min="2024" max="2099"></label></p>
			<?php submit_button( 'Generate & save tracking ID' ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th>Code</th><th>Name</th><th>Tier</th><th>Date</th></tr></thead>
			<tbody>
			<?php if ( empty( $registry ) ) : ?>
				<tr><td colspan="4">No certificates registered yet.</td></tr>
			<?php else : foreach ( array_reverse( $registry ) as $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( $row['code'] ); ?></code></td>
					<td><?php echo esc_html( $row['name'] ); ?></td>
					<td><?php echo (int) $row['tier']; ?></td>
					<td><?php echo esc_html( $row['created_at'] ?? '' ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function gc_render_control_materials() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	$all = get_option( 'gc_tier_materials', array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	if ( isset( $_POST['gc_mat_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_mat_nonce'] ) ), 'gc_mat' ) ) {
		$tier  = absint( $_POST['mat_tier'] ?? 1 );
		$title = sanitize_text_field( wp_unslash( $_POST['mat_title'] ?? '' ) );
		$url   = esc_url_raw( wp_unslash( $_POST['mat_url'] ?? '' ) );
		if ( $title && $url ) {
			if ( empty( $all[ $tier ] ) || ! is_array( $all[ $tier ] ) ) {
				$all[ $tier ] = array();
			}
			$all[ $tier ][] = array( 'title' => $title, 'url' => $url, 'added' => current_time( 'mysql' ) );
			update_option( 'gc_tier_materials', $all, false );
			$msg = 'Material added for tier ' . $tier;
		}
		if ( ! empty( $_POST['mat_delete'] ) ) {
			list( $dt, $di ) = array_map( 'absint', explode( ':', sanitize_text_field( wp_unslash( $_POST['mat_delete'] ) ) ) );
			if ( isset( $all[ $dt ][ $di ] ) ) {
				array_splice( $all[ $dt ], $di, 1 );
				update_option( 'gc_tier_materials', $all, false );
				$msg = 'Material removed.';
			}
		}
	}
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
	?>
	<div class="wrap">
		<h1>Course materials by tier</h1>
		<?php if ( $msg ) : ?><div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
		<p>Members see downloads for tiers they have access to on their dashboard.</p>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;max-width:640px;margin-bottom:24px;">
			<?php wp_nonce_field( 'gc_mat', 'gc_mat_nonce' ); ?>
			<p><label>Tier<br><select name="mat_tier"><?php foreach ( $tiers as $n => $tv ) : ?><option value="<?php echo (int) $n; ?>"><?php echo esc_html( 'Tier ' . $n . ' — ' . ( $tv['name'] ?? '' ) ); ?></option><?php endforeach; ?></select></label></p>
			<p><label>Title<br><input class="regular-text" name="mat_title" required placeholder="e.g. Leadership Manual PDF"></label></p>
			<p><label>File / link URL<br><input class="regular-text" name="mat_url" required placeholder="https://… or Media Library URL"></label></p>
			<?php submit_button( 'Add material' ); ?>
		</form>
		<?php foreach ( $tiers as $n => $tv ) :
			$items = $all[ $n ] ?? array();
			?>
			<h2><?php echo esc_html( 'Tier ' . $n . ' — ' . ( $tv['name'] ?? '' ) ); ?></h2>
			<?php if ( empty( $items ) ) : ?><p>No materials yet.</p><?php else : ?>
			<ul><?php foreach ( $items as $i => $it ) : ?>
				<li>
					<a href="<?php echo esc_url( $it['url'] ); ?>" target="_blank"><?php echo esc_html( $it['title'] ); ?></a>
					<form method="post" style="display:inline;"><?php wp_nonce_field( 'gc_mat', 'gc_mat_nonce' ); ?>
						<button class="button-link-delete" name="mat_delete" value="<?php echo esc_attr( $n . ':' . $i ); ?>">Remove</button>
					</form>
				</li>
			<?php endforeach; ?></ul>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/* Extend payments settings form fields via admin_footer if on that page */
add_action( 'admin_footer', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->id !== 'settings_page_gc-payments' ) {
		return;
	}
	$shop  = get_option( 'gc_paystack_shop_link', '' );
	$links = get_option( 'gc_paystack_tier_links', array() );
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
	?>
	<script>
	(function(){
		var form = document.querySelector('form[action="options.php"]');
		if (!form) return;
		var box = document.createElement('div');
		box.innerHTML = <?php echo wp_json_encode(
			'<h2>Paystack payment links (Shop)</h2>' .
			'<p>If inline keys are empty, members use these links. Shared default: <code>https://paystack.shop/pay/tzpci3b9qo</code></p>' .
			'<table class="form-table"><tr><th>Default Shop link</th><td><input class="regular-text" name="gc_paystack_shop_link" value="' . esc_attr( $shop ) . '" placeholder="https://paystack.shop/pay/…"></td></tr>' .
			implode( '', array_map( function ( $n ) use ( $links, $tiers ) {
				$v = isset( $links[ $n ] ) ? $links[ $n ] : '';
				$label = 'Tier ' . $n . ' link';
				return '<tr><th>' . esc_html( $label ) . '</th><td><input class="regular-text" name="gc_paystack_tier_links[' . (int) $n . ']" value="' . esc_attr( $v ) . '"></td></tr>';
			}, array_keys( $tiers ) ) ) .
			'</table>'
		); ?>;
		form.appendChild(box);
	})();
	</script>
	<?php
} );


function gc_render_control_claims() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	// Pending bank receipts from submissions
	$pending_posts = get_posts( array(
		'post_type'      => 'gc_submission',
		'post_status'    => 'private',
		'posts_per_page' => 100,
		'meta_key'       => 'gc_workflow_status',
		'meta_value'     => 'pending_payment_review',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	$pending_opt = get_option( 'gc_pending_receipts', array() );
	if ( ! is_array( $pending_opt ) ) { $pending_opt = array(); }
	$logs = get_option( 'gc_payment_claims', array() );
	if ( ! is_array( $logs ) ) { $logs = array(); }

	echo '<div class="wrap"><h1>Payment claims</h1>';
	if ( ! empty( $_GET['gc_approved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Receipt approved. Member was emailed a permanent registration link (same payment email).</p></div>';
	}
	if ( ! empty( $_GET['gc_mail_fail'] ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>Approved, but the confirmation email may not have sent. Check SMTP / hosting mail. Member can still continue on Become a Member using the same email (see “Admin already approved”).</p></div>';
	}
	if ( ! empty( $_GET['gc_deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Pending claim deleted.</p></div>';
	}
	echo '<p>Approve bank receipts only after you confirm the transfer. Paystack references are verified automatically — they do not need approval here.</p>';

	echo '<h2>Pending bank receipts (action required)</h2>';
	echo '<table class="widefat striped"><thead><tr><th>Time</th><th>Email</th><th>Tier</th><th>Receipt</th><th>Action</th></tr></thead><tbody>';
	$rows = 0;
	foreach ( $pending_posts as $p ) {
		$rows++;
		$email = get_post_meta( $p->ID, 'gc_email', true );
		$tier  = (int) get_post_meta( $p->ID, 'gc_payment_tier', true );
		$url   = get_post_meta( $p->ID, 'gc_receipt_url', true );
		$approve = wp_nonce_url(
			admin_url( 'admin-post.php?action=gc_approve_receipt&claim_id=' . (int) $p->ID . '&email=' . rawurlencode( $email ) . '&tier=' . $tier ),
			'gc_approve_receipt'
		);
		echo '<tr>';
		echo '<td>' . esc_html( get_the_date( 'Y-m-d H:i', $p ) ) . '</td>';
		echo '<td>' . esc_html( $email ) . '</td>';
		echo '<td>' . esc_html( $tier ?: '—' ) . '</td>';
		echo '<td>' . ( $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">View receipt</a>' : '—' ) . '</td>';
		$delete = wp_nonce_url(
			admin_url( 'admin-post.php?action=gc_delete_receipt&claim_id=' . (int) $p->ID . '&email=' . rawurlencode( $email ) ),
			'gc_delete_receipt'
		);
		echo '<td style="white-space:nowrap;">';
		echo '<a class="button button-primary" href="' . esc_url( $approve ) . '" onclick="return confirm(\'Confirm this bank payment and email the member?\');">Approve &amp; email member</a> ';
		echo '<a class="button" style="color:#b32d2e;" href="' . esc_url( $delete ) . '" onclick="return confirm(\'Delete this pending claim?\');">Delete</a>';
		echo '</td>';
		echo '</tr>';
	}
	// Option-based pending not already in posts
	foreach ( array_reverse( $pending_opt ) as $row ) {
		if ( ( $row['status'] ?? '' ) === 'approved' ) { continue; }
		$id = absint( $row['id'] ?? 0 );
		if ( $id ) {
			$already = false;
			foreach ( $pending_posts as $p ) {
				if ( (int) $p->ID === $id ) { $already = true; break; }
			}
			if ( $already ) { continue; }
		}
		$rows++;
		$email = $row['email'] ?? '';
		$tier  = (int) ( $row['tier'] ?? 0 );
		$url   = $row['receipt'] ?? '';
		$approve = wp_nonce_url(
			admin_url( 'admin-post.php?action=gc_approve_receipt&claim_id=' . $id . '&email=' . rawurlencode( $email ) . '&tier=' . $tier ),
			'gc_approve_receipt'
		);
		echo '<tr>';
		echo '<td>' . esc_html( $row['time'] ?? '' ) . '</td>';
		echo '<td>' . esc_html( $email ) . '</td>';
		echo '<td>' . esc_html( $tier ?: '—' ) . '</td>';
		echo '<td>' . ( $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">View receipt</a>' : '—' ) . '</td>';
		$delete = wp_nonce_url(
			admin_url( 'admin-post.php?action=gc_delete_receipt&claim_id=' . $id . '&email=' . rawurlencode( $email ) ),
			'gc_delete_receipt'
		);
		echo '<td style="white-space:nowrap;">';
		echo '<a class="button button-primary" href="' . esc_url( $approve ) . '" onclick="return confirm(\'Confirm this bank payment and email the member?\');">Approve &amp; email member</a> ';
		echo '<a class="button" style="color:#b32d2e;" href="' . esc_url( $delete ) . '" onclick="return confirm(\'Delete this pending claim?\');">Delete</a>';
		echo '</td>';
		echo '</tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="5">No pending receipts.</td></tr>';
	}
	echo '</tbody></table>';

	echo '<h2 style="margin-top:32px;">Recent confirmation log</h2>';
	echo '<table class="widefat striped"><thead><tr><th>Time</th><th>Method</th><th>Email</th><th>Tier</th><th>Ref</th><th>Receipt</th><th></th></tr></thead><tbody>';
	if ( ! $logs ) {
		echo '<tr><td colspan="7">No claims logged yet.</td></tr>';
	} else {
		foreach ( array_reverse( $logs, true ) as $idx => $row ) {
			$row['_idx'] = $idx;
			echo '<tr>';
			echo '<td>' . esc_html( $row['time'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $row['method'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $row['email'] ?? '' ) . '</td>';
			echo '<td>' . (int) ( $row['tier'] ?? 0 ) . '</td>';
			echo '<td><code>' . esc_html( $row['ref'] ?? '' ) . '</code></td>';
			echo '<td>' . ( ! empty( $row['receipt'] ) ? '<a href="' . esc_url( $row['receipt'] ) . '" target="_blank">View</a>' : '—' ) . '</td>';
			$idx = isset( $row['_idx'] ) ? (int) $row['_idx'] : -1;
			$del = wp_nonce_url( admin_url( 'admin-post.php?action=gc_delete_claim_log&idx=' . $idx ), 'gc_delete_claim_log' );
			echo '<td><a class="button" style="color:#b32d2e;" href="' . esc_url( $del ) . '" onclick="return confirm(\'Delete this log row?\');">Delete</a></td>';
			echo '</tr>';
		}
	}
	echo '</tbody></table></div>';
}



/** Approve mentor application → public mentor profile */
function gc_admin_approve_mentor() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'gc_approve_mentor' );
	$id = absint( $_GET['submission_id'] ?? 0 );
	$sub = get_post( $id );
	if ( ! $sub || 'gc_submission' !== $sub->post_type ) {
		wp_die( 'Not found', 404 );
	}
	$email = get_post_meta( $id, 'gc_email', true );
	$phone = get_post_meta( $id, 'gc_phone', true );
	$name  = $sub->post_title;
	// Clean title if it has form label
	$name = preg_replace( '/\s*—\s*.*$/', '', $name );
	$name = trim( $name ) ?: 'Mentor';
	$mentor_id = wp_insert_post( array(
		'post_type'    => 'gc_mentor',
		'post_status'  => 'publish',
		'post_title'   => $name,
		'post_content' => $sub->post_content,
	) );
	if ( $mentor_id && ! is_wp_error( $mentor_id ) ) {
		update_post_meta( $mentor_id, 'gc_email', $email );
		update_post_meta( $mentor_id, 'gc_phone', $phone );
		update_post_meta( $mentor_id, 'gc_from_submission', $id );
		update_post_meta( $id, 'gc_workflow_status', 'approved_mentor' );
		update_post_meta( $id, 'gc_mentor_post_id', $mentor_id );
		if ( is_email( $email ) ) {
			$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
			$mentors_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'mentors' ) : home_url( '/mentors/' );
			wp_mail(
				$email,
				'You are now listed as a Game Changers mentor',
				"Hello {$name},\n\nYour mentorship application was approved. Your profile is now public on the Mentors page:\n{$mentors_url}\n\n— Game Changers Nigeria",
				array( 'From: Game Changers Nigeria <' . $from . '>' )
			);
		}
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=gc_mentor&gc_mentor_approved=1' ) );
	exit;
}
add_action( 'admin_post_gc_approve_mentor', 'gc_admin_approve_mentor' );

/** Mentors management notice + approve links on submissions list */
function gc_mentor_submission_row_actions( $actions, $post ) {
	if ( 'gc_submission' !== $post->post_type ) {
		return $actions;
	}
	$label = strtolower( (string) get_post_meta( $post->ID, 'gc_form_type_label', true ) );
	$status = get_post_meta( $post->ID, 'gc_workflow_status', true );
	if ( false === strpos( $label, 'mentor' ) ) {
		return $actions;
	}
	if ( 'approved_mentor' === $status ) {
		$actions['gc_mentor_done'] = '<span style="color:green;">Mentor published</span>';
		return $actions;
	}
	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=gc_approve_mentor&submission_id=' . (int) $post->ID ),
		'gc_approve_mentor'
	);
	$actions['gc_approve_mentor'] = '<a href="' . esc_url( $url ) . '" style="color:#006b3f;font-weight:700;">Approve → show on Mentors page</a>';
	return $actions;
}
add_filter( 'post_row_actions', 'gc_mentor_submission_row_actions', 10, 2 );
