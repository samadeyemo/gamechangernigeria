<?php
/**
 * Resource library: free vs premium, gated by member email.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gc_register_resource_cpt() {
	register_post_type(
		'gc_resource',
		array(
			'labels'       => array(
				'name'          => 'Resources',
				'singular_name' => 'Resource',
				'add_new_item'  => 'Add Resource',
				'edit_item'     => 'Edit Resource',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-media-document',
			'supports'     => array( 'title', 'editor' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'gc_register_resource_cpt' );

function gc_resource_meta_box() {
	add_meta_box( 'gc_resource_meta', 'Resource settings', 'gc_resource_meta_box_html', 'gc_resource', 'side' );
}
add_action( 'add_meta_boxes', 'gc_resource_meta_box' );

function gc_resource_meta_box_html( $post ) {
	wp_nonce_field( 'gc_resource_meta', 'gc_resource_meta_nonce' );
	$access = get_post_meta( $post->ID, 'gc_resource_access', true ) ?: 'free';
	$file   = get_post_meta( $post->ID, 'gc_resource_file', true );
	?>
	<p><label><strong>Access level</strong></label><br>
		<select name="gc_resource_access" style="width:100%;">
			<option value="free" <?php selected( $access, 'free' ); ?>>Free members</option>
			<option value="premium" <?php selected( $access, 'premium' ); ?>>Premium members only</option>
		</select>
	</p>
	<p><label><strong>File URL</strong> (Media Library link or external)</label><br>
		<input type="url" name="gc_resource_file" value="<?php echo esc_attr( $file ); ?>" style="width:100%;" placeholder="https://…">
	</p>
	<p class="description">Upload the file under Media, copy the URL, paste here.</p>
	<?php
}

function gc_resource_meta_save( $post_id ) {
	if ( empty( $_POST['gc_resource_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_resource_meta_nonce'] ) ), 'gc_resource_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['gc_resource_access'] ) ) {
		$a = sanitize_key( $_POST['gc_resource_access'] );
		update_post_meta( $post_id, 'gc_resource_access', in_array( $a, array( 'free', 'premium' ), true ) ? $a : 'free' );
	}
	if ( isset( $_POST['gc_resource_file'] ) ) {
		update_post_meta( $post_id, 'gc_resource_file', esc_url_raw( wp_unslash( $_POST['gc_resource_file'] ) ) );
	}
}
add_action( 'save_post_gc_resource', 'gc_resource_meta_save' );

/** Resolve membership from email used at onboarding. */
function gc_membership_from_email( $email ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return array( 'level' => 'none', 'user_id' => 0 );
	}
	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		return array( 'level' => 'none', 'user_id' => 0 );
	}
	$tier = function_exists( 'gc_user_membership_tier' ) ? (int) gc_user_membership_tier( $user->ID ) : (int) get_user_meta( $user->ID, 'gc_premium_tier', true );
	if ( $tier >= 1 || user_can( $user, 'premium_member' ) || in_array( 'premium_member', (array) $user->roles, true ) ) {
		return array( 'level' => 'premium', 'user_id' => $user->ID, 'tier' => $tier );
	}
	if ( in_array( 'free_member', (array) $user->roles, true ) || get_user_meta( $user->ID, 'gc_is_free_member', true ) ) {
		return array( 'level' => 'free', 'user_id' => $user->ID );
	}
	// Any registered account counts as free baseline for library
	return array( 'level' => 'free', 'user_id' => $user->ID );
}

function gc_handle_resource_download() {
	if ( empty( $_POST['gc_res_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_res_nonce'] ) ), 'gc_resource_dl' ) ) {
		wp_die( 'Security check failed.', 403 );
	}
	$id    = absint( $_POST['resource_id'] ?? 0 );
	$email = sanitize_email( wp_unslash( $_POST['member_email'] ?? '' ) );
	$post  = get_post( $id );
	if ( ! $post || 'gc_resource' !== $post->post_type ) {
		wp_die( 'Resource not found.', 404 );
	}
	$access = get_post_meta( $id, 'gc_resource_access', true ) ?: 'free';
	$file   = get_post_meta( $id, 'gc_resource_file', true );
	if ( ! $file ) {
		wp_die( 'File not available.', 404 );
	}
	$mem = gc_membership_from_email( $email );
	if ( 'premium' === $access && 'premium' !== $mem['level'] ) {
		$res_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'resources' ) : home_url( '/resources/' );
		wp_safe_redirect( add_query_arg( array( 'gc_res' => 'premium_only' ), $res_url ) );
		exit;
	}
	if ( 'none' === $mem['level'] ) {
		$res_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'resources' ) : home_url( '/resources/' );
		wp_safe_redirect( add_query_arg( array( 'gc_res' => 'email' ), $res_url ) );
		exit;
	}
	wp_redirect( esc_url_raw( $file ) );
	exit;
}
/**
 * Handle resource downloads from the front-end.
 * This avoids /wp-admin/admin-post.php security restrictions.
 */
function gc_resource_download_frontend() {

    if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        return;
    }

    if ( empty( $_POST['action'] ) ) {
        return;
    }

    $action = sanitize_key( wp_unslash( $_POST['action'] ) );

    if ( 'gc_resource_dl' !== $action ) {
        return;
    }

    gc_handle_resource_download();
}

add_action( 'template_redirect', 'gc_resource_download_frontend', 1 );

add_action( 'admin_post_gc_resource_dl', 'gc_handle_resource_download' );
add_action( 'admin_post_nopriv_gc_resource_dl', 'gc_handle_resource_download' );

/** Admin: approve pending bank receipts → issue paid unlock link. */
function gc_admin_approve_receipt() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'gc_approve_receipt' );
	$id    = absint( $_GET['claim_id'] ?? 0 );
	$email = sanitize_email( wp_unslash( $_GET['email'] ?? '' ) );
	$tier  = absint( $_GET['tier'] ?? 1 );
	if ( $id ) {
		update_post_meta( $id, 'gc_workflow_status', 'paid' );
		$email = $email ?: sanitize_email( get_post_meta( $id, 'gc_email', true ) );
		$tier  = $tier ?: absint( get_post_meta( $id, 'gc_payment_tier', true ) );
		update_post_meta( $id, 'gc_approved_at', current_time( 'mysql' ) );
	}
	// Mark option pending rows approved
	$pending = get_option( 'gc_pending_receipts', array() );
	if ( is_array( $pending ) ) {
		foreach ( $pending as $i => $row ) {
			if ( (int) ( $row['id'] ?? 0 ) === $id || ( $email && strtolower( $row['email'] ?? '' ) === strtolower( $email ) ) ) {
				$pending[ $i ]['status'] = 'approved';
			}
		}
		update_option( 'gc_pending_receipts', $pending, false );
	}
	$ref  = 'ADMIN-RCPT-' . time() . '-' . wp_rand( 100, 999 );
	$tier = max( 1, $tier );
	$data = array(
		'ref'    => $ref,
		'tier'   => $tier,
		'amount' => 0,
		'email'  => $email,
		'time'   => time(),
		'method' => 'admin_receipt_approve',
	);
	if ( function_exists( 'gc_store_paid_unlock' ) ) {
		gc_store_paid_unlock( $ref, $data );
	} else {
		set_transient( 'gc_paid_' . md5( $ref ), $data, YEAR_IN_SECONDS * 2 );
	}
	// Also index by email so member can continue without the link
	if ( is_email( $email ) ) {
		update_option( 'gc_paid_email_' . md5( strtolower( $email ) ), $data, false );
	}
	$join = function_exists( 'gc_join_page_url' )
		? gc_join_page_url( array( 'paid' => '1', 'tier' => $tier, 'ref' => $ref ) )
		: home_url( '/' );
	$login = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : home_url( '/premium-login/' );
	$mail_ok = false;
	if ( is_email( $email ) ) {
		$host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org';
		$host = preg_replace( '/^www\./', '', $host );
		$from_addr = apply_filters( 'wp_mail_from', 'hello@' . $host );
		$headers = array(
			'From: Game Changers Nigeria <' . $from_addr . '>',
			'Reply-To: Game Changers Nigeria <' . $from_addr . '>',
			'Content-Type: text/plain; charset=UTF-8',
		);
		$subject = 'Payment confirmed — continue your Game Changers registration';
		$body  = "Hello,\n\n";
		$body .= "Your bank payment has been confirmed by our team.\n\n";
		$body .= "Continue registration and create your account (same email you used for payment):\n";
		$body .= $join . "\n\n";
		$body .= "Already registered? Log in here:\n" . $login . "\n\n";
		$body .= "You do not need to pay again. This link stays valid.\n\n";
		$body .= "— Game Changers Nigeria\n";
		$mail_ok = wp_mail( $email, $subject, $body, $headers );
		update_option( 'gc_last_approval_mail', array(
			'email'   => $email,
			'ok'      => $mail_ok ? 1 : 0,
			'time'    => current_time( 'mysql' ),
			'ref'     => $ref,
		), false );
	}
	$redir = admin_url( 'admin.php?page=gc-control-claims&gc_approved=1' );
	if ( ! $mail_ok ) {
		$redir = add_query_arg( 'gc_mail_fail', '1', $redir );
	}
	wp_safe_redirect( $redir );
	exit;
}

function gc_admin_delete_receipt_claim() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'gc_delete_receipt' );
	$id = absint( $_GET['claim_id'] ?? 0 );
	$email = sanitize_email( wp_unslash( $_GET['email'] ?? '' ) );
	if ( $id ) {
		wp_delete_post( $id, true );
	}
	$pending = get_option( 'gc_pending_receipts', array() );
	if ( is_array( $pending ) ) {
		$pending = array_values( array_filter( $pending, function ( $row ) use ( $id, $email ) {
			if ( $id && (int) ( $row['id'] ?? 0 ) === $id ) {
				return false;
			}
			if ( $email && strtolower( $row['email'] ?? '' ) === strtolower( $email ) ) {
				return false;
			}
			return true;
		} ) );
		update_option( 'gc_pending_receipts', $pending, false );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gc-control-claims&gc_deleted=1' ) );
	exit;
}
add_action( 'admin_post_gc_delete_receipt', 'gc_admin_delete_receipt_claim' );

add_action( 'admin_post_gc_approve_receipt', 'gc_admin_approve_receipt' );


function gc_handle_payment_help() {
	if ( empty( $_POST['gc_help_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_help_nonce'] ) ), 'gc_payment_help' ) ) {
		wp_die( 'Security check failed.', 403 );
	}
	$name = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) );
	$issue = sanitize_key( $_POST['gc_issue'] ?? '' );
	$details = sanitize_textarea_field( wp_unslash( $_POST['gc_details'] ?? '' ) );
	$page = function_exists( 'gc_page_url' ) ? gc_page_url( 'payment-help' ) : home_url( '/payment-help/' );
	if ( ! $name || ! is_email( $email ) || ! $issue || strlen( $details ) < 5 ) {
		wp_safe_redirect( add_query_arg( 'gc_help', 'error', $page ) );
		exit;
	}
	$id = wp_insert_post( array(
		'post_type'   => 'gc_submission',
		'post_status' => 'private',
		'post_title'  => $name . ' — Account/Payment help',
		'post_content'=> $details,
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'gc_form_type_label', 'Payment & Account Help' );
		update_post_meta( $id, 'gc_email', $email );
		update_post_meta( $id, 'gc_help_issue', $issue );
		update_post_meta( $id, 'gc_workflow_status', 'new' );
	}
	$admin = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
	$host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org';
	$from = apply_filters( 'wp_mail_from', 'hello@' . preg_replace( '/^www\./', '', $host ) );
	wp_mail(
		$admin,
		'[Help] ' . $issue . ' — ' . $email,
		"Name: {$name}\nEmail: {$email}\nIssue: {$issue}\n\n{$details}\n",
		array( 'From: Game Changers Nigeria <' . $from . '>', 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	wp_safe_redirect( add_query_arg( 'gc_help', 'sent', $page ) );
	exit;
}
add_action( 'admin_post_gc_payment_help', 'gc_handle_payment_help' );
add_action( 'admin_post_nopriv_gc_payment_help', 'gc_handle_payment_help' );

/** Admin: change member login email + send password reset to new address */
function gc_admin_change_member_email() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'gc_change_member_email' );
	$old = sanitize_email( wp_unslash( $_POST['old_email'] ?? '' ) );
	$new = sanitize_email( wp_unslash( $_POST['new_email'] ?? '' ) );
	$send_reset = ! empty( $_POST['send_reset'] );
	$redir = admin_url( 'admin.php?page=gc-control-access' );
	if ( ! is_email( $old ) || ! is_email( $new ) ) {
		wp_safe_redirect( add_query_arg( 'gc_email_err', 'invalid', $redir ) );
		exit;
	}
	$user = get_user_by( 'email', $old );
	if ( ! $user ) {
		wp_safe_redirect( add_query_arg( 'gc_email_err', 'notfound', $redir ) );
		exit;
	}
	if ( email_exists( $new ) && strtolower( $new ) !== strtolower( $old ) ) {
		wp_safe_redirect( add_query_arg( 'gc_email_err', 'taken', $redir ) );
		exit;
	}
	$updated = wp_update_user( array(
		'ID'         => $user->ID,
		'user_email' => $new,
		'user_login' => $user->user_login, // login name unchanged
	) );
	// WordPress does not change user_login via wp_update_user easily when login was email;
	// update email is enough for login-by-email plugins; also sync meta
	if ( is_wp_error( $updated ) ) {
		wp_safe_redirect( add_query_arg( 'gc_email_err', 'fail', $redir ) );
		exit;
	}
	update_user_meta( $user->ID, 'gc_email_changed_from', $old );
	update_user_meta( $user->ID, 'gc_email_changed_at', current_time( 'mysql' ) );
	// Migrate paid-email index
	$paid = get_option( 'gc_paid_email_' . md5( strtolower( $old ) ), false );
	if ( is_array( $paid ) ) {
		$paid['email'] = $new;
		update_option( 'gc_paid_email_' . md5( strtolower( $new ) ), $paid, false );
	}
	if ( $send_reset ) {
		retrieve_password( $user->user_login );
		// Force reset mail toward new email (user object already updated)
		$host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org';
		$from = apply_filters( 'wp_mail_from', 'hello@' . preg_replace( '/^www\./', '', $host ) );
		$key = get_password_reset_key( $user );
		if ( ! is_wp_error( $key ) ) {
			$link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
			wp_mail(
				$new,
				'Your Game Changers account email was updated',
				"Hello,\n\nAn administrator updated the email on your Game Changers Nigeria account.\n\nNew email: {$new}\n\nSet a new password here:\n{$link}\n\n— Game Changers Nigeria",
				array( 'From: Game Changers Nigeria <' . $from . '>' )
			);
		}
	}
	wp_safe_redirect( add_query_arg( 'gc_email_ok', '1', $redir ) );
	exit;
}
add_action( 'admin_post_gc_change_member_email', 'gc_admin_change_member_email' );


function gc_admin_delete_claim_log() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'gc_delete_claim_log' );
	$idx = absint( $_GET['idx'] ?? -1 );
	$logs = get_option( 'gc_payment_claims', array() );
	if ( is_array( $logs ) && array_key_exists( $idx, $logs ) ) {
		unset( $logs[ $idx ] );
		$logs = array_values( $logs );
		update_option( 'gc_payment_claims', $logs, false );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gc-control-claims&gc_deleted=1' ) );
	exit;
}
add_action( 'admin_post_gc_delete_claim_log', 'gc_admin_delete_claim_log' );
