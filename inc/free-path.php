<?php
/**
 * Free Path — 7-day ebook admin + 30-day short nuggets (auto daily rotation)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gc_default_free_nuggets() {
	$seeds = array(
		array( 'Start where you stand', 'Write one problem in your community in a single sentence. Share it with one trusted person today.' ),
		array( 'Small discipline', 'Protect 20 focused minutes today for learning or service. Note what you did.' ),
		array( 'Listen first', 'Ask two people what challenge they face. Listen fully before offering advice.' ),
		array( 'Honest choice', 'Name one decision where the easy path and the right path differ. Choose the right path once today.' ),
		array( 'Serve quietly', 'Do one helpful act that will not be posted online.' ),
		array( 'Learn in public', 'Read or watch 10 minutes on leadership or civic skill. Write three takeaways.' ),
		array( 'Invite with integrity', 'Invite one person who is ready to grow — explain why, do not spam.' ),
		array( 'Document impact', 'Record one action you took this week that helped someone.' ),
		array( 'Courage in a small room', 'Speak once for what is right in a small setting. Then listen more than you talk.' ),
		array( 'Steward time', 'Track one free hour today. Move part of it to growth or community work.' ),
		array( 'Clarify values', 'Write your top three values. Check one decision against them.' ),
		array( 'Build trust', 'Keep one small promise you made to someone this week.' ),
		array( 'See systems', 'Name a broken system near you and who it affects.' ),
		array( 'Practice gratitude', 'Thank one person who models the leadership you respect.' ),
		array( 'Skill stack', 'Spend 15 minutes practicing a skill outside pure leadership talk.' ),
		array( 'Local first', 'Identify one local need you can influence within 30 days.' ),
		array( 'Team of two', 'Partner with one peer on a tiny shared goal this week.' ),
		array( 'Feedback loop', 'Ask someone for one honest piece of feedback. Receive it without defending.' ),
		array( 'Resource map', 'List three people or groups who can help a problem you care about.' ),
		array( 'Public humility', 'Admit one thing you do not know yet — and start learning it.' ),
		array( 'Consistency over intensity', 'Repeat yesterday’s good habit for 10 minutes. Do not expand yet.' ),
		array( 'Citizen duty', 'Complete one civic or community responsibility you have delayed.' ),
		array( 'Mentor moment', 'Share one useful lesson with someone a step behind you.' ),
		array( 'Boundary practice', 'Say no to one distraction that steals your growth time.' ),
		array( 'Story of change', 'Write five lines about a change you want to see in five years.' ),
		array( 'Practical help', 'Offer one concrete help to someone within 48 hours.' ),
		array( 'Measure progress', 'Rate your week 1–10 on discipline. Choose one lever to improve.' ),
		array( 'Network with purpose', 'Reconnect with one person in your network with a clear, respectful ask or offer.' ),
		array( 'Rest with intention', 'Plan rest that restores you — not only escape. Protect it.' ),
		array( 'Close the loop', 'Finish one incomplete task that has been draining your focus.' ),
	);
	$out = array();
	foreach ( $seeds as $i => $pair ) {
		$out[ $i + 1 ] = array( 'title' => $pair[0], 'body' => $pair[1] );
	}
	return $out;
}

function gc_get_free_nuggets() {
	$saved = get_option( 'gc_free_nuggets', array() );
	if ( ! is_array( $saved ) || count( $saved ) < 1 ) {
		return gc_default_free_nuggets();
	}
	$out = array();
	$i   = 1;
	foreach ( $saved as $row ) {
		if ( empty( $row['title'] ) && empty( $row['body'] ) ) {
			continue;
		}
		$out[ $i ] = array(
			'title' => sanitize_text_field( $row['title'] ?? '' ),
			'body'  => sanitize_textarea_field( $row['body'] ?? '' ),
		);
		$i++;
	}
	return $out ? $out : gc_default_free_nuggets();
}

/** Today's free-path short nugget — rotates daily like Premium challenges. */
function gc_get_todays_free_nugget() {
	$nuggets = gc_get_free_nuggets();
	if ( empty( $nuggets ) ) {
		return null;
	}
	$keys = array_keys( $nuggets );
	$day  = (int) gmdate( 'z' );
	$idx  = $keys[ $day % count( $keys ) ];
	$row  = $nuggets[ $idx ];
	return (object) array(
		'day_index' => $idx,
		'title'     => $row['title'],
		'body'      => $row['body'],
		'total'     => count( $nuggets ),
	);
}

function gc_free_path_admin_menu() {
	add_menu_page(
		__( 'Free Path', 'game-changers' ),
		__( 'Free Path', 'game-changers' ),
		'edit_posts',
		'gc-free-path',
		'gc_free_path_admin_page',
		'dashicons-book-alt',
		26
	);
}
add_action( 'admin_menu', 'gc_free_path_admin_menu' );

function gc_free_path_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( isset( $_POST['gc_free_path_save'] ) && check_admin_referer( 'gc_free_path_save' ) ) {
		$file_id = absint( $_POST['gc_free_ebook_file'] ?? 0 );
		set_theme_mod( 'gc_free_ebook_file', $file_id );
		update_option( 'gc_free_ebook_file_id', $file_id );

		$titles = isset( $_POST['nugget_title'] ) ? (array) $_POST['nugget_title'] : array();
		$bodies = isset( $_POST['nugget_body'] ) ? (array) $_POST['nugget_body'] : array();
		$rows   = array();
		$max    = max( count( $titles ), count( $bodies ) );
		for ( $i = 0; $i < $max; $i++ ) {
			$title = sanitize_text_field( wp_unslash( $titles[ $i ] ?? '' ) );
			$body  = sanitize_textarea_field( wp_unslash( $bodies[ $i ] ?? '' ) );
			if ( $title || $body ) {
				$rows[] = array( 'title' => $title, 'body' => $body );
			}
		}
		$rows = array_slice( $rows, 0, 30 );
		update_option( 'gc_free_nuggets', $rows );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Free Path saved. Nuggets rotate automatically each day.', 'game-changers' ) . '</p></div>';
	}

	$file_id  = (int) get_theme_mod( 'gc_free_ebook_file', get_option( 'gc_free_ebook_file_id', 0 ) );
	$file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
	$nuggets  = gc_get_free_nuggets();
	$today    = gc_get_todays_free_nugget();

	wp_enqueue_media();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Free Path — Ebook & 30-Day Nuggets', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Upload the 7-Day Activation Challenge ebook and edit short nuggets. One nugget shows each day automatically (same idea as Premium daily challenges).', 'game-changers' ); ?></p>

		<?php if ( $today ) : ?>
			<div class="notice notice-info" style="padding:12px 14px;">
				<strong><?php esc_html_e( "Today's free nugget:", 'game-changers' ); ?></strong>
				<?php echo esc_html( $today->title ); ?> — <?php echo esc_html( wp_trim_words( $today->body, 24 ) ); ?>
				<em>(#<?php echo (int) $today->day_index; ?> / <?php echo (int) $today->total; ?>)</em>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'gc_free_path_save' ); ?>
			<input type="hidden" name="gc_free_path_save" value="1">

			<h2><?php esc_html_e( '7-Day Activation Challenge Ebook', 'game-changers' ); ?></h2>
			<p>
				<input type="hidden" id="gc_free_ebook_file" name="gc_free_ebook_file" value="<?php echo esc_attr( $file_id ); ?>">
				<button type="button" class="button" id="gc_ebook_upload_btn"><?php esc_html_e( 'Upload / Select PDF', 'game-changers' ); ?></button>
				<button type="button" class="button" id="gc_ebook_clear_btn"><?php esc_html_e( 'Clear', 'game-changers' ); ?></button>
				<span id="gc_ebook_label" style="margin-left:10px;">
					<?php
					echo $file_url
						? '<a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Current file', 'game-changers' ) . '</a>'
						: esc_html__( 'No file yet', 'game-changers' );
					?>
				</span>
			</p>

			<h2><?php esc_html_e( '30-Day Short Nuggets (auto-rotate daily)', 'game-changers' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Up to 30 rows. Day of year picks today’s row for free members.', 'game-changers' ); ?></p>
			<table class="widefat striped" style="max-width:960px;">
				<thead>
					<tr>
						<th style="width:40px;">#</th>
						<th style="width:28%;"><?php esc_html_e( 'Title', 'game-changers' ); ?></th>
						<th><?php esc_html_e( 'Short body', 'game-changers' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $i = 1; $i <= 30; $i++ ) :
						$row = $nuggets[ $i ] ?? array( 'title' => '', 'body' => '' );
						?>
						<tr>
							<td><?php echo (int) $i; ?></td>
							<td><input type="text" name="nugget_title[]" value="<?php echo esc_attr( $row['title'] ); ?>" class="widefat"></td>
							<td><textarea name="nugget_body[]" rows="2" class="widefat"><?php echo esc_textarea( $row['body'] ); ?></textarea></td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Free Path', 'game-changers' ) ); ?>
		</form>
	</div>
	<script>
	jQuery(function($){
		var frame;
		$('#gc_ebook_upload_btn').on('click', function(e){
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({ title: 'Select 7-Day Activation Ebook', button: { text: 'Use this file' }, multiple: false });
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				$('#gc_free_ebook_file').val(att.id);
				$('#gc_ebook_label').html('<a href="'+att.url+'" target="_blank">'+ (att.filename || 'Current file') +'</a>');
			});
			frame.open();
		});
		$('#gc_ebook_clear_btn').on('click', function(){
			$('#gc_free_ebook_file').val('0');
			$('#gc_ebook_label').text('No file yet');
		});
	});
	</script>
	<?php
}

/** Use free-path daily nugget when no Premium lesson is scheduled. */
function gc_free_nugget_as_lesson_fallback( $lesson ) {
	if ( $lesson ) {
		return $lesson;
	}
	$n = function_exists( 'gc_get_todays_free_nugget' ) ? gc_get_todays_free_nugget() : null;
	if ( ! $n ) {
		return null;
	}
	return (object) array(
		'ID'           => 0,
		'post_title'   => $n->title,
		'post_content' => $n->body,
	);
}

/** Dedicated free join — separate from Academy enlistments. */
function gc_handle_join_free() {
	if ( empty( $_POST['gc_join_free_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_join_free_nonce'] ) ), 'gc_join_free' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ) );
	}
	$redirect = ! empty( $_POST['gc_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['gc_redirect'] ) ) : home_url( '/join-free/' );
	$name  = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['gc_phone'] ?? '' ) );
	$state = sanitize_text_field( wp_unslash( $_POST['gc_state'] ?? '' ) );
	$town  = sanitize_text_field( wp_unslash( $_POST['gc_town'] ?? '' ) );
	$pass  = isset( $_POST['gc_password'] ) ? (string) wp_unslash( $_POST['gc_password'] ) : '';
	$pass2 = isset( $_POST['gc_password_confirm'] ) ? (string) wp_unslash( $_POST['gc_password_confirm'] ) : '';

	if ( empty( $name ) || ! is_email( $email ) || strlen( $pass ) < 8 || $pass !== $pass2 ) {
		wp_safe_redirect( add_query_arg( 'gc_free', 'error', $redirect ) );
		exit;
	}
	if ( email_exists( $email ) ) {
		wp_safe_redirect( add_query_arg( 'gc_free', 'exists', $redirect ) );
		exit;
	}

	$user_id = wp_create_user( $email, $pass, $email );
	if ( is_wp_error( $user_id ) ) {
		wp_safe_redirect( add_query_arg( 'gc_free', 'error', $redirect ) );
		exit;
	}
	wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => strtok( trim( $name ), ' ' ) ) );
	$user = new WP_User( $user_id );
	$user->set_role( 'free_member' );
	update_user_meta( $user_id, 'gc_membership_status', 'free' );
	if ( function_exists( 'gc_send_member_welcome_email' ) ) {
		gc_send_member_welcome_email( $email, $name );
	}
	if ( $phone ) {
		update_user_meta( $user_id, 'gc_phone', $phone );
	}
	if ( $state ) {
		update_user_meta( $user_id, 'gc_state', $state );
	}
	if ( $town ) {
		update_user_meta( $user_id, 'gc_town', $town );
	}
	$refcode = sanitize_text_field( wp_unslash( $_POST['gc_refcode'] ?? $_POST['refcode'] ?? '' ) );
	if ( $refcode ) {
		update_user_meta( $user_id, 'gc_referred_by_tag', strtoupper( $refcode ) );
		// Link to referrer user if tag matches
		if ( function_exists( 'gc_get_premium_user_by_tag' ) ) {
			$ref_user = gc_get_premium_user_by_tag( $refcode );
			if ( $ref_user ) {
				update_user_meta( $user_id, 'gc_referred_by_user', (int) $ref_user->ID );
				$cnt = (int) get_user_meta( $ref_user->ID, 'gc_referral_count', true ) + 1;
				update_user_meta( $ref_user->ID, 'gc_referral_count', $cnt );
			}
		}
	}

	if ( function_exists( 'gc_generate_membership_tag' ) ) {
		$member_id = wp_insert_post( array(
			'post_type'   => 'gc_member',
			'post_title'  => $name,
			'post_status' => 'publish',
		) );
		if ( $member_id && ! is_wp_error( $member_id ) ) {
			$tag = gc_generate_membership_tag();
			update_post_meta( $member_id, 'gc_member_tag', $tag );
			update_post_meta( $member_id, 'gc_member_email', $email );
			update_post_meta( $member_id, 'gc_member_phone', $phone );
			update_post_meta( $member_id, 'gc_member_state', $state );
			update_post_meta( $member_id, 'gc_member_town', $town );
			update_post_meta( $member_id, 'gc_member_user_id', $user_id );
			update_post_meta( $member_id, 'gc_member_status', 'free' );
			update_user_meta( $user_id, 'gc_member_cpt_id', $member_id );
		}
	}

	update_user_meta( $user_id, 'gc_free_path_started', current_time( 'Y-m-d' ) );
	if ( function_exists( 'gc_ensure_referral_tag' ) ) {
		gc_ensure_referral_tag( $user_id );
	}
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	// Land on Member Hub (free dashboard) — challenges start here.
	$hub = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
	wp_safe_redirect( add_query_arg( 'gc_free', 'welcome', $hub ) );
	exit;
}
add_action( 'admin_post_nopriv_gc_submit_join_free', 'gc_handle_join_free' );
add_action( 'admin_post_gc_submit_join_free', 'gc_handle_join_free' );


/**
 * Track free-member day index (1–30). Starts on first Hub visit after free join.
 */
function gc_free_path_day( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	$start = get_user_meta( $user_id, 'gc_free_path_started', true );
	if ( ! $start ) {
		$start = current_time( 'Y-m-d' );
		update_user_meta( $user_id, 'gc_free_path_started', $start );
	}
	$start_ts = strtotime( $start . ' 00:00:00' );
	$today_ts = strtotime( current_time( 'Y-m-d' ) . ' 00:00:00' );
	$day = (int) floor( ( $today_ts - $start_ts ) / DAY_IN_SECONDS ) + 1;
	return max( 1, min( 31, $day ) );
}

function gc_free_path_should_prompt_upgrade( $user_id = 0 ) {
	return false;
}

/** Which reminder day is active (for messaging). */
function gc_free_path_prompt_day( $user_id = 0 ) {
	$day = gc_free_path_day( $user_id );
	if ( in_array( $day, array( 15, 20, 25, 28 ), true ) ) {
		return $day;
	}
	return $day >= 30 ? $day : 0;
}

/**
 * Popup HTML for free members at/after day 30.
 */
function gc_render_free_upgrade_modal() {
	if ( ! is_user_logged_in() || ! gc_free_path_should_prompt_upgrade() ) {
		return '';
	}
	$upgrade = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
	$inner   = function_exists( 'gc_page_url' ) ? gc_page_url( 'inner-circle' ) : home_url( '/inner-circle/' );
	$day     = function_exists( 'gc_free_path_prompt_day' ) ? gc_free_path_prompt_day() : gc_free_path_day();
	$left    = max( 0, 30 - (int) gc_free_path_day() );
	ob_start();
	?>
	<div id="gc-free-upgrade-modal" class="gc-free-modal" role="dialog" aria-modal="true" aria-labelledby="gc-free-upgrade-title">
		<div class="gc-free-modal-card">
			<span class="gc-kicker"><?php echo (int) $day >= 30 ? esc_html__( 'Free path ending', 'game-changers' ) : esc_html__( 'Upgrade reminder', 'game-changers' ); ?></span>
			<h2 id="gc-free-upgrade-title"><?php esc_html_e( 'Upgrade to a higher tier to keep these benefits', 'game-changers' ); ?></h2>
			<p><?php
			if ( (int) $day >= 30 ) {
				esc_html_e( 'You have reached the end of the free 30-day challenge path. Upgrade to unlock full tier lessons, ongoing challenges, badges, and referral rewards.', 'game-changers' );
			} else {
				printf(
					/* translators: 1: day number 2: days left */
					esc_html__( 'You are on day %1$d of your free path (%2$d days left). Upgrade now to enjoy full tier lessons, continued challenges, and member benefits without interruption.', 'game-changers' ),
					(int) $day,
					(int) $left
				);
			}
			?></p>
			<div class="gc-free-modal-actions">
				<a class="gc-button gc-button-gold" href="<?php echo esc_url( $upgrade ); ?>"><?php esc_html_e( 'View tiers & upgrade', 'game-changers' ); ?></a>
				<a class="gc-button gc-button-light" href="<?php echo esc_url( $inner ); ?>"><?php esc_html_e( 'Join Inner Circle', 'game-changers' ); ?></a>
				<button type="button" class="gc-button gc-button-light" id="gc-free-modal-close"><?php esc_html_e( 'Not now', 'game-changers' ); ?></button>
			</div>
		</div>
	</div>
	<style>
	.gc-free-modal{position:fixed;inset:0;z-index:99999;background:rgba(13,26,15,.72);display:flex;align-items:center;justify-content:center;padding:20px}
	.gc-free-modal-card{background:#fff;border-radius:18px;padding:28px 24px;max-width:440px;width:100%;box-shadow:0 24px 60px rgba(0,0,0,.25)}
	.gc-free-modal-card h2{font:700 22px/1.25 'Playfair Display',serif;margin:10px 0 12px}
	.gc-free-modal-card p{color:#66756a;font-size:14px;line-height:1.55;margin:0 0 18px}
	.gc-free-modal-actions{display:flex;flex-wrap:wrap;gap:10px}
	.gc-free-modal.is-hidden{display:none}
	</style>
	<script>
	(function(){
		var m=document.getElementById('gc-free-upgrade-modal');
		if(!m) return;
		var day=<?php echo (int) $day; ?>;
		var k='gc_free_upgrade_dismissed_'+day;
		try{ if(sessionStorage.getItem(k)==='1'){ m.classList.add('is-hidden'); } }catch(e){}
		var b=document.getElementById('gc-free-modal-close');
		if(b){ b.addEventListener('click',function(){ m.classList.add('is-hidden'); try{sessionStorage.setItem(k,'1');}catch(e){} }); }
	})();
	</script>
	<?php
	return ob_get_clean();
}


/**
 * Free members: challenges yes; tier/premium lessons only after paid upgrade.
 * During first 30 days they may browse locked tier tabs but content stays locked without payment.
 * From day 30+, still no tier content until premium.
 */
function gc_user_is_free_member( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'administrator' ) ) {
		return false;
	}
	if ( function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $user_id ) ) {
		return false;
	}
	return true;
}

function gc_free_member_can_access_tier_lessons( $user_id = 0 ) {
	// Only premium (paid) members access tier lesson content.
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'administrator' ) ) {
		return true;
	}
	return function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $user_id );
}
