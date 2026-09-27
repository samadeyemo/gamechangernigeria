<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Chapter membership / "member process" — modelled on World Changers
 * Network's per-chapter join flow: a visitor requests to join a
 * SPECIFIC chapter (not just the national list), lands as
 * gc_member_status = 'pending', and that chapter's own leader
 * (gc_chapter_leader_allowed()) reviews and approves/declines them
 * from the Chapter Leader Dashboard. Site admins can always review
 * any chapter. Members created through the older national
 * "Enlistments" form have no gc_member_status meta at all and are
 * treated as already-approved (see gc_get_chapter_members()), so
 * nothing about the existing enlistment flow changes.
 */

/**
 * Renders the "Join This Chapter" form used on single-gc_chapter.php.
 */
function gc_render_chapter_join_form( $chapter_id ) {
	$chapter_id = (int) $chapter_id;
	if ( ! $chapter_id ) {
		return;
	}
	$status = isset( $_GET['gc_join'] ) ? sanitize_key( wp_unslash( $_GET['gc_join'] ) ) : '';
	?>
	<div class="gc-chapter-join">
		<?php if ( 'sent' === $status ) : ?>
			<div class="gc-form-notice gc-form-notice--ok"><strong><?php esc_html_e( 'Request sent.', 'game-changers' ); ?></strong> <?php esc_html_e( 'The chapter leader will review your request. You will get an email once approved.', 'game-changers' ); ?></div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="gc-form-notice gc-form-notice--err"><?php esc_html_e( 'Please enter your full name and a valid email address.', 'game-changers' ); ?></div>
		<?php elseif ( 'exists' === $status ) : ?>
			<div class="gc-form-notice gc-form-notice--ok"><?php esc_html_e( 'You already have a pending or approved request for this chapter.', 'game-changers' ); ?></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form gc-chapter-join-form">
			<input type="hidden" name="action" value="gc_chapter_join">
			<input type="hidden" name="gc_chapter_id" value="<?php echo esc_attr( $chapter_id ); ?>">
			<?php wp_nonce_field( 'gc_chapter_join', 'gc_chapter_join_nonce' ); ?>
			<div class="gc-form-row gc-form-row--split">
				<div><label>Full Name *</label><input required name="gc_name" autocomplete="name"></div>
				<div><label>Email Address *</label><input required type="email" name="gc_email" autocomplete="email"></div>
			</div>
			<div class="gc-form-row"><label>Phone / WhatsApp</label><input type="tel" name="gc_phone" autocomplete="tel"></div>
			<button class="gc-form-submit" type="submit"><?php esc_html_e( 'Request to Join this Chapter', 'game-changers' ); ?></button>
		</form>
	</div>
	<?php
}

function gc_handle_chapter_join_submit() {
	if ( empty( $_POST['gc_chapter_join_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_chapter_join_nonce'] ) ), 'gc_chapter_join' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}
	$chapter_id = absint( $_POST['gc_chapter_id'] ?? 0 );
	$chapter    = $chapter_id ? get_post( $chapter_id ) : null;
	if ( ! $chapter || 'gc_chapter' !== $chapter->post_type ) {
		wp_die( esc_html__( 'That chapter could not be found.', 'game-changers' ), 404 );
	}
	$redirect = get_permalink( $chapter );
	$name     = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) );
	$phone    = sanitize_text_field( wp_unslash( $_POST['gc_phone'] ?? '' ) );

	if ( empty( $name ) || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'gc_join', 'error', $redirect ) . '#gc-join' );
		exit;
	}

	// Reuse an existing member profile for this email if one exists (they may
	// be switching chapters or re-requesting); otherwise create a fresh one.
	$existing = get_posts( array(
		'post_type'      => 'gc_member',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array( array( 'key' => 'gc_member_email', 'value' => $email, 'compare' => '=' ) ),
	) );
	$member = ! empty( $existing ) ? $existing[0] : null;

	if ( $member ) {
		$same_chapter = (int) get_post_meta( $member->ID, 'gc_member_chapter_id', true ) === $chapter_id;
		$cur_status   = get_post_meta( $member->ID, 'gc_member_status', true );
		if ( $same_chapter && in_array( $cur_status, array( 'pending', 'approved' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'gc_join', 'exists', $redirect ) . '#gc-join' );
			exit;
		}
		$member_id = $member->ID;
		wp_update_post( array( 'ID' => $member_id, 'post_title' => $name ) );
	} else {
		$member_id = wp_insert_post( array(
			'post_type'   => 'gc_member',
			'post_title'  => $name,
			'post_status' => 'publish',
		) );
	}
	if ( ! $member_id || is_wp_error( $member_id ) ) {
		wp_safe_redirect( add_query_arg( 'gc_join', 'error', $redirect ) . '#gc-join' );
		exit;
	}

	if ( ! get_post_meta( $member_id, 'gc_member_tag', true ) ) {
		update_post_meta( $member_id, 'gc_member_tag', gc_generate_membership_tag() );
	}
	update_post_meta( $member_id, 'gc_member_email', $email );
	if ( $phone ) {
		update_post_meta( $member_id, 'gc_member_phone', $phone );
	}
	update_post_meta( $member_id, 'gc_member_chapter_id', $chapter_id );
	update_post_meta( $member_id, 'gc_member_state', gc_chapter_effective_state( $chapter_id ) );
	update_post_meta( $member_id, 'gc_member_status', 'pending' );

	// Notify the joiner.
	wp_mail(
		$email,
		sprintf( __( 'Your request to join %s — Game Changers Nigeria', 'game-changers' ), get_the_title( $chapter ) ),
		"Hello {$name},\n\nWe received your request to join " . get_the_title( $chapter ) . ". The chapter leader will review it, and you'll get an email as soon as you're approved.\n\nThank you,\nGame Changers Nigeria"
	);

	// Notify the chapter leader (fall back to site admin if the chapter has none yet).
	$leader_user_id = (int) get_post_meta( $chapter_id, 'gc_chapter_leader_user_id', true );
	$leader         = $leader_user_id ? get_user_by( 'id', $leader_user_id ) : null;
	$notify_to      = $leader ? $leader->user_email : gc_contact_email();
	wp_mail(
		$notify_to,
		sprintf( __( 'New member request for %s', 'game-changers' ), get_the_title( $chapter ) ),
		"{$name} ({$email}" . ( $phone ? ", {$phone}" : '' ) . ") has requested to join " . get_the_title( $chapter ) . ".\n\nReview and approve from your Chapter Leader Dashboard: " . gc_page_url( 'chapter-dashboard' )
	);

	wp_safe_redirect( add_query_arg( 'gc_join', 'sent', $redirect ) . '#gc-join' );
	exit;
}
add_action( 'admin_post_gc_chapter_join', 'gc_handle_chapter_join_submit' );
add_action( 'admin_post_nopriv_gc_chapter_join', 'gc_handle_chapter_join_submit' );

/**
 * Can the current user review (approve/decline) members of this chapter?
 * True for site admins, or for the approved leader of that exact chapter.
 */
function gc_can_review_chapter_members( $chapter_id ) {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( ! gc_chapter_leader_allowed() ) {
		return false;
	}
	$u   = wp_get_current_user();
	$own = (int) get_user_meta( $u->ID, 'gc_chapter_leader_chapter_id', true );
	return $own && $own === (int) $chapter_id;
}

function gc_handle_chapter_member_review( $approve ) {
	$field = $approve ? 'gc_member_approve_nonce' : 'gc_member_decline_nonce';
	$action_name = $approve ? 'gc_member_approve' : 'gc_member_decline';
	if ( empty( $_POST[ $field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action_name ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}
	$member_id = absint( $_POST['gc_member_id'] ?? 0 );
	$member    = $member_id ? get_post( $member_id ) : null;
	if ( ! $member || 'gc_member' !== $member->post_type ) {
		wp_die( esc_html__( 'That member could not be found.', 'game-changers' ), 404 );
	}
	$chapter_id = (int) get_post_meta( $member_id, 'gc_member_chapter_id', true );
	if ( ! gc_can_review_chapter_members( $chapter_id ) ) {
		wp_die( esc_html__( 'You are not authorised to review this member.', 'game-changers' ), 403 );
	}

	update_post_meta( $member_id, 'gc_member_status', $approve ? 'approved' : 'declined' );
	$email = get_post_meta( $member_id, 'gc_member_email', true );
	$chapter_title = $chapter_id ? get_the_title( $chapter_id ) : 'Game Changers Nigeria';
	if ( $email && is_email( $email ) ) {
		if ( $approve ) {
			wp_mail( $email, sprintf( __( "You're approved to join %s!", 'game-changers' ), $chapter_title ), "Hello {$member->post_title},\n\nWelcome — your request to join {$chapter_title} has been approved. Your membership tag is " . get_post_meta( $member_id, 'gc_member_tag', true ) . ".\n\nWelcome to the movement." );
		} else {
			wp_mail( $email, sprintf( __( 'Update on your %s request', 'game-changers' ), $chapter_title ), "Hello {$member->post_title},\n\nYour request to join {$chapter_title} was not approved at this time. If you think this is a mistake, please reply to this email." );
		}
	}
	wp_safe_redirect( add_query_arg( 'gc_chapter', $approve ? 'member_approved' : 'member_declined', wp_get_referer() ?: gc_page_url( 'chapter-dashboard' ) ) . '#gc-pending-members' );
	exit;
}
add_action( 'admin_post_gc_member_approve', function() { gc_handle_chapter_member_review( true ); } );
add_action( 'admin_post_gc_member_decline', function() { gc_handle_chapter_member_review( false ); } );

/**
 * Self-serve "Add a Local Group" — lets an approved chapter leader open a
 * child chapter under whatever they lead. Because this works off the
 * leader's OWN chapter id (whatever level it is), the exact same handler
 * naturally cascades the whole three-tier structure:
 *   State Chapter leader adds  -> City/LGA Group
 *   City/LGA Group leader adds -> Campus/Community Group
 * If a name + email are given for the new group, a dedicated chapter_leader
 * account is created for it (same mechanism as approving a state chapter
 * application) so that person gets their own dashboard login and can, in
 * turn, add the next level down under themselves.
 */
function gc_handle_chapter_add_local_group() {
	if ( ! gc_chapter_leader_allowed() || empty( $_POST['gc_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_group_nonce'] ) ), 'gc_group_save' ) ) {
		wp_die( esc_html__( 'Not authorised.', 'game-changers' ), 403 );
	}
	$u          = wp_get_current_user();
	$parent_id  = (int) get_user_meta( $u->ID, 'gc_chapter_leader_chapter_id', true );
	if ( ! $parent_id ) {
		wp_die( esc_html__( 'Set up your chapter before adding a group.', 'game-changers' ), 400 );
	}
	$title       = sanitize_text_field( wp_unslash( $_POST['gc_group_title'] ?? '' ) );
	$whatsapp    = esc_url_raw( wp_unslash( $_POST['gc_group_whatsapp'] ?? '' ) );
	$lead_name   = sanitize_text_field( wp_unslash( $_POST['gc_group_leader_name'] ?? '' ) );
	$lead_email  = sanitize_email( wp_unslash( $_POST['gc_group_leader_email'] ?? '' ) );
	if ( ! $title ) {
		wp_safe_redirect( add_query_arg( 'gc_chapter', 'group_error', gc_page_url( 'chapter-dashboard' ) ) . '#gc-local-groups' );
		exit;
	}
	$group_id = wp_insert_post( array(
		'post_type'   => 'gc_chapter',
		'post_title'  => $title,
		'post_status' => 'publish',
		'post_parent' => $parent_id,
		'post_author' => $u->ID,
	) );
	if ( ! $group_id || is_wp_error( $group_id ) ) {
		wp_safe_redirect( add_query_arg( 'gc_chapter', 'group_error', gc_page_url( 'chapter-dashboard' ) ) . '#gc-local-groups' );
		exit;
	}
	if ( $whatsapp ) {
		update_post_meta( $group_id, 'gc_whatsapp_link', $whatsapp );
	}
	update_post_meta( $group_id, 'gc_chapter_organiser', $lead_name ?: $u->display_name );

	if ( $lead_name && is_email( $lead_email ) ) {
		$leader = get_user_by( 'email', $lead_email );
		if ( ! $leader ) {
			$uid = wp_create_user( sanitize_user( strtolower( preg_replace( '/[^a-z0-9]/i', '.', $lead_name ) ), true ), wp_generate_password( 24, true, true ), $lead_email );
			$leader = is_wp_error( $uid ) ? null : get_user_by( 'id', $uid );
		}
		if ( $leader ) {
			$leader->add_role( 'chapter_leader' );
			update_user_meta( $leader->ID, 'gc_chapter_leader_approved', 1 );
			update_user_meta( $leader->ID, 'gc_chapter_leader_chapter_id', $group_id );
			update_user_meta( $leader->ID, 'gc_chapter_leader_state', gc_chapter_effective_state( $group_id ) );
			update_post_meta( $group_id, 'gc_chapter_leader_user_id', $leader->ID );
			$reset = get_password_reset_key( $leader );
			$link  = ! is_wp_error( $reset ) ? network_site_url( 'wp-login.php?action=rp&key=' . rawurlencode( $reset ) . '&login=' . rawurlencode( $leader->user_login ), 'login' ) : gc_page_url( 'chapter-login' );
			wp_mail(
				$lead_email,
				sprintf( __( "You've been appointed to lead %s — Game Changers Nigeria", 'game-changers' ), $title ),
				"Hello {$lead_name},\n\n" . $u->display_name . " has appointed you to lead {$title} (" . gc_chapter_level_label( $group_id ) . ") under Game Changers Nigeria.\n\nSet your password and log in here: {$link}\n\nOnce logged in, open your Chapter Leader Dashboard to manage {$title} — add its WhatsApp link, review members, and open groups of your own underneath it.\n\nWelcome to the movement."
			);
		}
	} else {
		update_post_meta( $group_id, 'gc_chapter_leader_user_id', $u->ID );
	}

	wp_safe_redirect( add_query_arg( 'gc_chapter', 'group_saved', gc_page_url( 'chapter-dashboard' ) ) . '#gc-local-groups' );
	exit;
}
add_action( 'admin_post_gc_chapter_add_group', 'gc_handle_chapter_add_local_group' );
