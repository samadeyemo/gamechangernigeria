<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — FRONT END
 *
 * Drop [gc_today] on the homepage/front-page.php for the "TODAY'S GAME
 * CHANGER" block, and [gc_member_growth] in the Member Hub (page-members.php
 * or wherever the hub template lives) for the Points/Streak/Badges/Level
 * panel. Both are also available as plain PHP calls — gc_render_today_block()
 * / gc_render_member_growth_block() — for direct use inside existing
 * templates without relying on the_content() running shortcodes.
 */

function gc_ajax_complete_activity() {
	check_ajax_referer( 'gc_member_nonce', 'gc_nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in to record this activity.', 'game-changers' ) ) );
	}

	$activity_type = isset( $_POST['activity_type'] ) ? sanitize_key( wp_unslash( $_POST['activity_type'] ) ) : '';
	$content_id    = isset( $_POST['content_id'] ) ? absint( $_POST['content_id'] ) : 0;
	$source        = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'ajax';

	$result = gc_record_activity( get_current_user_id(), $activity_type, $content_id, $source );

	if ( $result['success'] ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}
add_action( 'wp_ajax_gc_complete_activity', 'gc_ajax_complete_activity' );

function gc_render_today_block() {
	$challenge = function_exists( 'gc_get_todays_challenge' ) ? gc_get_todays_challenge() : null;
	$lesson    = function_exists( 'gc_get_todays_lesson' ) ? gc_get_todays_lesson() : null;
	if ( function_exists( 'gc_free_nugget_as_lesson_fallback' ) ) {
		$lesson = gc_free_nugget_as_lesson_fallback( $lesson );
	}
	$logged_in      = is_user_logged_in();
	$uid            = get_current_user_id();
	$lesson_done    = $logged_in && function_exists( 'gc_has_completed_today' ) && gc_has_completed_today( $uid, 'daily_lesson' );
	$challenge_done = $logged_in && function_exists( 'gc_has_completed_today' ) && gc_has_completed_today( $uid, 'daily_challenge' );
	$ebook          = function_exists( 'gc_page_url' ) ? gc_page_url( 'free-ebook' ) : home_url( '/free-ebook/' );
	$is_premium     = $logged_in && function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $uid );

	ob_start();
	?>
	<div class="gc-today-wrap" id="gc-daily-practice">
		<div class="gc-hub-section-head">
			<div>
				<span class="gc-kicker"><?php esc_html_e( 'Daily practice', 'game-changers' ); ?></span>
				<h2><?php esc_html_e( "Today's Game Changer", 'game-changers' ); ?></h2>
				<p class="gc-today-lead"><?php esc_html_e( 'Complete the challenge and the short nugget below. Only Mark Complete awards points — reading alone does not.', 'game-changers' ); ?></p>
			</div>
		</div>

		<!-- DAILY CHALLENGE — primary, always visible -->
		<article class="gc-today-challenge <?php echo $challenge_done ? 'is-done' : ''; ?>">
			<div class="gc-today-challenge-label">
				<span><?php esc_html_e( 'Daily challenge', 'game-changers' ); ?></span>
				<?php if ( $challenge_done ) : ?><em>✓ <?php esc_html_e( 'Done today', 'game-changers' ); ?></em><?php endif; ?>
			</div>
			<?php if ( $challenge ) : ?>
				<h3><?php echo esc_html( get_the_title( $challenge ) ); ?></h3>
				<div class="gc-today-body"><?php echo wp_kses_post( wpautop( $challenge->post_content ) ); ?></div>
				<?php if ( ! $logged_in ) : ?>
					<a class="gc-button gc-button-gold" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in to complete', 'game-changers' ); ?></a>
				<?php elseif ( $challenge_done ) : ?>
					<span class="gc-card-status">✓ <?php esc_html_e( 'Challenge completed — points counted', 'game-changers' ); ?></span>
				<?php else : ?>
					<button type="button" class="gc-button gc-button-gold gc-complete-btn"
						data-activity-type="daily_challenge"
						data-content-id="<?php echo esc_attr( $challenge->ID ); ?>"
						data-source="member_hub_today"><?php esc_html_e( 'Mark challenge complete', 'game-changers' ); ?></button>
				<?php endif; ?>
			<?php else : ?>
				<h3><?php esc_html_e( 'No challenge published for today', 'game-changers' ); ?></h3>
				<p><?php esc_html_e( 'Admin: add items under Challenges in WP Admin. Until then, use lessons below.', 'game-changers' ); ?></p>
			<?php endif; ?>
		</article>

		<div class="gc-today-secondary">
			<article class="gc-hub-card">
				<span class="gc-card-num"><?php esc_html_e( 'SHORT NUGGET · 5 PTS', 'game-changers' ); ?></span>
				<?php if ( $lesson ) : ?>
					<h3><?php echo esc_html( get_the_title( $lesson ) ); ?></h3>
					<div class="gc-today-body"><?php echo wp_kses_post( wpautop( $lesson->post_content ) ); ?></div>
					<?php if ( ! $logged_in ) : ?>
						<a class="gc-text-link" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in to mark complete →', 'game-changers' ); ?></a>
					<?php elseif ( $lesson_done ) : ?>
						<span class="gc-card-status">✓ <?php esc_html_e( 'Completed today', 'game-changers' ); ?></span>
					<?php else : ?>
						<button type="button" class="gc-button gc-button-light gc-complete-btn"
							data-activity-type="daily_lesson"
							data-content-id="<?php echo esc_attr( $lesson->ID ); ?>"
							data-source="member_hub_today"><?php esc_html_e( 'Mark nugget complete', 'game-changers' ); ?></button>
					<?php endif; ?>
				<?php else : ?>
					<h3><?php esc_html_e( 'Preparing today’s nugget…', 'game-changers' ); ?></h3>
					<p><?php if ( ! $is_premium ) : ?><a href="<?php echo esc_url( $ebook ); ?>"><?php esc_html_e( 'Get the free ebook', 'game-changers' ); ?></a><?php endif; ?></p>
				<?php endif; ?>
			</article>
		</div>
	</div>
	<style>
	.gc-today-wrap{margin:0 0 32px}
	.gc-today-lead{margin:8px 0 0;color:var(--gc-muted,#66756a);max-width:54ch;font-size:14px;line-height:1.55}
	.gc-today-challenge{background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border-radius:18px;padding:22px 20px 20px;margin:16px 0 14px}
	.gc-today-challenge.is-done{opacity:.92}
	.gc-today-challenge-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
	.gc-today-challenge-label span{font:700 11px/1 'Space Mono',monospace;letter-spacing:.12em;text-transform:uppercase;color:#e8b840}
	.gc-today-challenge-label em{font-style:normal;font-size:12px;opacity:.85}
	.gc-today-challenge h3{font:700 clamp(20px,2.8vw,26px)/1.2 'Playfair Display',serif;margin:0 0 10px;color:#fff}
	.gc-today-body{font-size:14.5px;line-height:1.6;opacity:.92;margin:0 0 16px}
	.gc-today-body p:last-child{margin-bottom:0}
	.gc-today-secondary{display:grid;grid-template-columns:1fr;gap:12px}
	@media(min-width:720px){.gc-today-secondary{grid-template-columns:1fr}}
	</style>
	<?php
	return ob_get_clean();
}

add_shortcode( 'gc_today', 'gc_render_today_block' );

function gc_render_member_growth_block() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$uid = get_current_user_id();
	if ( function_exists( 'gc_backfill_points_breakdown' ) ) {
		gc_backfill_points_breakdown( $uid );
	}
	if ( function_exists( 'gc_ensure_fifteen_badges' ) ) {
		gc_ensure_fifteen_badges();
	}
	$progress = function_exists( 'gc_member_progress' ) ? gc_member_progress( $uid ) : array();
	$badges_config = get_option( 'gc_badges_config', array() );
	if ( ! is_array( $badges_config ) ) {
		$badges_config = array();
	}

	$tier_pts      = (int) get_user_meta( $uid, 'gc_tier_points', true );
	if ( function_exists( 'gc_rebuild_challenge_points_from_ledger' ) ) {
		gc_rebuild_challenge_points_from_ledger( $uid );
	}
	$challenge_pts = (int) get_user_meta( $uid, 'gc_challenge_points', true );
	$ref_pts       = (int) get_user_meta( $uid, 'gc_referral_points', true );
	$l2_pts        = (int) get_user_meta( $uid, 'gc_referral_l2_points', true );
	$total_pts     = (int) get_user_meta( $uid, 'gc_total_points', true );
	if ( $total_pts < 1 ) {
		$total_pts = (int) ( $progress['points'] ?? 0 );
	}
	$streak  = (int) ( $progress['streak_current'] ?? get_user_meta( $uid, 'gc_streak_current', true ) );
	$level   = $progress['level'] ?? 'New Game Changer';
	$earned  = isset( $progress['badges'] ) && is_array( $progress['badges'] ) ? $progress['badges'] : array();
	$my_tier = function_exists( 'gc_user_membership_tier' ) ? (int) gc_user_membership_tier( $uid ) : (int) get_user_meta( $uid, 'gc_premium_tier', true );
	$badges_config = get_option( 'gc_badges_config', array() );
	if ( ! is_array( $badges_config ) ) { $badges_config = array(); }
	$earned_count = 0;
	foreach ( $badges_config as $b ) {
		$s = $b['slug'] ?? '';
		if ( $s && isset( $earned[ $s ] ) ) { $earned_count++; }
	}
	$badge_total = count( $badges_config );
	$other_pts = max( 0, $total_pts - $tier_pts - $challenge_pts - $ref_pts - $l2_pts );


	ob_start();
	?>
	<div class="gc-growth-panel" data-gc-growth>
		<div class="gc-growth-head">
			<span class="gc-kicker">Your growth</span>
			<h2><?php echo esc_html( $level ); ?></h2>
			<p><?php echo esc_html( number_format_i18n( $total_pts ) ); ?> total · <?php echo esc_html( $streak ); ?>-day streak · Tier <?php echo (int) $my_tier; ?></p>
		</div>
		<div class="gc-growth-score">
			<span>Total</span>
			<strong><?php echo esc_html( number_format_i18n( $total_pts ) ); ?></strong>
		</div>
		<div class="gc-growth-breakdown">
			<div>
				<span>Tier points</span>
				<strong><?php echo esc_html( number_format_i18n( $tier_pts ) ); ?></strong>
				<small>From your membership level</small>
			</div>
			<div class="gc-pts-chal">
				<span>Challenge points</span>
				<strong><?php echo esc_html( number_format_i18n( $challenge_pts ) ); ?></strong>
				<small>Daily challenges only</small>
			</div>
			<div class="gc-pts-ref">
				<span>Referral points</span>
				<strong><?php echo esc_html( number_format_i18n( $ref_pts ) ); ?></strong>
				<small>People you invited (1/3)</small>
			</div>
			<div class="gc-pts-net">
				<span>Network points</span>
				<strong><?php echo esc_html( number_format_i18n( $l2_pts ) ); ?></strong>
				<small>Their referrals (1/5)</small>
			</div>
		</div>
		<?php if ( $other_pts > 0 ) : ?>
		<div class="gc-growth-other" style="margin:12px 0 8px;font-size:13px;opacity:.85">
			Other activity points: <strong><?php echo esc_html( number_format_i18n( $other_pts ) ); ?></strong>
			<span style="opacity:.7"> (lessons, events, etc. — not challenge/referral)</span>
		</div>
		<?php endif; ?>
		<div class="gc-badge-head" style="display:flex;justify-content:space-between;align-items:center;margin:18px 0 10px;gap:12px;flex-wrap:wrap">
			<span class="gc-kicker" style="margin:0">Badges</span>
			<strong style="color:#e8b840;font-size:14px"><?php echo (int) $earned_count; ?> / <?php echo (int) $badge_total; ?> earned</strong>
		</div>
		<div class="gc-badge-row" aria-label="Badges in order 1 to 15">
			<?php
			$i = 0;
			foreach ( $badges_config as $badge ) :
				$i++;
				$slug  = isset( $badge['slug'] ) ? $badge['slug'] : '';
				$label = isset( $badge['label'] ) ? $badge['label'] : $slug;
				$is_on = $slug && ! empty( $earned[ $slug ] );
				?>
				<span class="gc-badge-chip <?php echo $is_on ? 'is-earned' : 'is-locked'; ?>">
					<span class="gc-badge-num"><?php echo (int) $i; ?></span>
					<?php echo $is_on ? '✓ ' : ''; ?><?php echo esc_html( $label ); ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
	<style>
	.gc-growth-panel{background:#0d1a0f;color:#f5f2ec;border-radius:18px;padding:24px 22px 20px;margin:0 0 28px}
	.gc-growth-head .gc-kicker{color:#e8b840}
	.gc-growth-head h2{font:900 clamp(26px,3.5vw,34px)/1.15 'Playfair Display',serif;margin:8px 0 6px;color:#fff}
	.gc-growth-head p{margin:0 0 18px;opacity:.75;font-size:14px}
	.gc-growth-score{display:flex;align-items:baseline;justify-content:space-between;padding:12px 0 16px;border-bottom:1px solid rgba(245,242,236,.12);margin-bottom:14px}
	.gc-growth-score span{font:700 10px/1 'Space Mono',monospace;letter-spacing:.12em;text-transform:uppercase;opacity:.65}
	.gc-growth-score strong{font:900 36px/1 'Playfair Display',serif;color:#e8c547}
	.gc-growth-breakdown{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
	.gc-growth-breakdown div{background:rgba(255,255,255,.05);border:1px solid rgba(245,242,236,.1);border-radius:12px;padding:12px 12px 14px}
	.gc-growth-breakdown span{display:block;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;opacity:.65;margin-bottom:6px}
	.gc-growth-breakdown strong{display:block;font-size:20px;font-weight:800;margin-bottom:4px}
	.gc-growth-breakdown small{font-size:11px;opacity:.55;line-height:1.3}
	.gc-badge-row{display:flex;flex-wrap:wrap;gap:8px}
	.gc-badge-chip{font:700 10px 'Space Mono',monospace;letter-spacing:.03em;padding:7px 12px;border-radius:999px;border:1px solid rgba(245,242,236,.15);color:rgba(245,242,236,.55);display:inline-flex;align-items:center;gap:5px}
	.gc-badge-chip .gc-badge-num{opacity:.5;font-size:9px}
	.gc-badge-chip.is-earned{background:#006b3f;border-color:#006b3f;color:#fff}
	@media(max-width:720px){.gc-growth-breakdown{grid-template-columns:1fr 1fr}}
	</style>
	<?php
	return ob_get_clean();
}
add_shortcode( 'gc_member_growth', 'gc_render_member_growth_block' );

/**
 * Minimal client-side wiring for the "Mark Complete" buttons above. Enqueued
 * only when a shortcode is actually on the page, and reuses the existing
 * gcAjax localisation (ajaxUrl + nonce) already set up in functions.php.
 */
function gc_enqueue_engagement_frontend_script() {
	// FIXED: this used to check has_shortcode($post->post_content, 'gc_today'),
	// but the Today/Growth blocks are inserted via direct PHP calls
	// (gc_render_today_block() / gc_render_member_growth_block()) inside
	// page templates, not as [gc_today] text in the post content — so
	// has_shortcode() always returned false, the script never loaded, and
	// clicking "Mark Complete" did nothing at all. Load it on any request
	// where those blocks are actually rendered: is_user_logged_in() covers
	// the Member Hub (where the blocks always print for a logged-in member).
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! wp_script_is( 'gc-main', 'enqueued' ) && ! wp_script_is( 'gc-main', 'registered' ) ) {
		wp_register_script( 'gc-engagement', false, array(), GC_THEME_VERSION, true );
		wp_enqueue_script( 'gc-engagement' );
		wp_localize_script( 'gc-engagement', 'gcAjax', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gc_member_nonce' ),
		) );
		$script_handle = 'gc-engagement';
	} else {
		$script_handle = 'gc-main';
	}

	$inline = <<<JS
/**
 * Offline-safe activity submission. On a poor/absent connection the
 * request is queued in localStorage instead of lost, and retried the
 * moment the device comes back online. The server is what prevents
 * duplicate points (one qualifying completion per day, per
 * gc_record_activity()'s dedup rule) — so even if a queued item is
 * resent more than once, it can only ever be awarded once. This queue
 * only ever holds "mark complete" actions, never login state.
 */
var GC_QUEUE_KEY = 'gc_offline_activity_queue';

function gcReadQueue(){
	try { return JSON.parse(localStorage.getItem(GC_QUEUE_KEY) || '[]'); } catch(e){ return []; }
}
function gcWriteQueue(q){
	try { localStorage.setItem(GC_QUEUE_KEY, JSON.stringify(q)); } catch(e){}
}
function gcSubmitActivity(payload){
	return fetch(gcAjax.ajaxUrl, {
		method: 'POST',
		headers: {'Content-Type':'application/x-www-form-urlencoded'},
		body: new URLSearchParams(payload)
	}).then(function(r){ return r.json(); });
}
function gcFlushQueue(){
	var queue = gcReadQueue();
	if (!queue.length) { return; }
	var remaining = [];
	var chain = Promise.resolve();
	queue.forEach(function(item){
		chain = chain.then(function(){
			return gcSubmitActivity(item).catch(function(){ remaining.push(item); });
		});
	});
	chain.then(function(){ gcWriteQueue(remaining); });
}
window.addEventListener('online', gcFlushQueue);
document.addEventListener('DOMContentLoaded', gcFlushQueue);

document.addEventListener('click', function(e){
	var btn = e.target.closest('.gc-complete-btn');
	if(!btn) return;
	btn.disabled = true;
	var original = btn.textContent;
	btn.textContent = 'Saving...';
	var payload = {
		action: 'gc_complete_activity',
		gc_nonce: gcAjax.nonce,
		activity_type: btn.dataset.activityType,
		content_id: btn.dataset.contentId,
		source: btn.dataset.source || ''
	};

	if (!navigator.onLine) {
		var queue = gcReadQueue();
		queue.push(payload);
		gcWriteQueue(queue);
		btn.textContent = 'Saved offline \u2013 will sync';
		return;
	}

	gcSubmitActivity(payload).then(function(res){
		if(res.success){
			btn.textContent = 'Completed \u2713';
			document.querySelectorAll('[data-gc-growth]').forEach(function(){ location.reload(); });
		} else {
			btn.disabled = false;
			btn.textContent = original;
			alert((res.data && res.data.message) || 'Could not record this activity.');
		}
	}).catch(function(){
		// Network dropped mid-request: queue it rather than losing the action.
		var queue = gcReadQueue();
		queue.push(payload);
		gcWriteQueue(queue);
		btn.textContent = 'Saved offline \u2013 will sync';
	});
});
JS;
	wp_add_inline_script( $script_handle, $inline );
}
add_action( 'wp_enqueue_scripts', 'gc_enqueue_engagement_frontend_script', 20 );
