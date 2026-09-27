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
	$challenge     = gc_get_todays_challenge();
	$lesson        = gc_get_todays_lesson();
	if ( function_exists( 'gc_free_nugget_as_lesson_fallback' ) ) {
		$lesson = gc_free_nugget_as_lesson_fallback( $lesson );
	}
	$logged_in     = is_user_logged_in();
	$uid           = get_current_user_id();
	$lesson_done   = $logged_in && gc_has_completed_today( $uid, 'daily_lesson' );
	$challenge_done = $logged_in && gc_has_completed_today( $uid, 'daily_challenge' );

	$free_join = function_exists( 'gc_page_url' ) ? gc_page_url( 'join-free' ) : home_url( '/join-free/' );
	$premium   = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
	$ebook     = function_exists( 'gc_page_url' ) ? gc_page_url( 'free-ebook' ) : home_url( '/free-ebook/' );
	$is_premium_member = $logged_in && function_exists( 'gc_user_is_premium' ) && gc_user_is_premium( $uid );
	$is_free_member    = $logged_in && ! $is_premium_member;

	ob_start();
	?>
	<div class="gc-hub-section-head"><div><span class="gc-kicker"><?php esc_html_e( 'Daily nuggets', 'game-changers' ); ?></span><h2><?php esc_html_e( "Today's Game Changer", 'game-changers' ); ?></h2></div>
		<?php if ( $is_free_member || ! $logged_in ) : ?>
		<p style="margin:8px 0 0;color:var(--gc-muted,#66756a);max-width:52ch;"><?php esc_html_e( 'Free path: 7-day activation ebook → 30-day short nuggets. Come back daily.', 'game-changers' ); ?>
			<a href="<?php echo esc_url( $ebook ); ?>"><?php esc_html_e( 'Get the ebook', 'game-changers' ); ?></a>
		</p>
		<?php else : ?>
		<p style="margin:8px 0 0;color:var(--gc-muted,#66756a);max-width:52ch;"><?php esc_html_e( 'Your daily practice — complete today’s nugget and challenge to keep your streak.', 'game-changers' ); ?></p>
		<?php endif; ?>
	</div>
	<div class="gc-hub-grid three">
		<article class="gc-hub-card">
			<span class="gc-card-num"><?php esc_html_e( '30-DAY SHORT NUGGET · 5 MIN · 5 PTS', 'game-changers' ); ?></span>
			<?php if ( $lesson ) : ?>
				<h3><?php echo esc_html( get_the_title( $lesson ) ); ?></h3>
				<p><?php echo wp_kses_post( wpautop( $lesson->post_content ) ); ?></p>
				<?php if ( ! $logged_in ) : ?>
					<strong><a class="gc-text-link" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in to mark complete →</a></strong>
				<?php elseif ( $lesson_done ) : ?>
					<span class="gc-card-status">✓ Completed today</span>
				<?php else : ?>
					<button type="button" class="gc-button gc-button-light gc-complete-btn" data-activity-type="daily_lesson" data-content-id="<?php echo esc_attr( $lesson->ID ); ?>" data-source="member_hub_today">Mark Complete</button>
				<?php endif; ?>
			<?php else : ?>
				<h3>Preparing today's lesson…</h3>
				<p>Check back shortly — a new 5-minute lesson is on its way.</p>
			<?php endif; ?>
		</article>
		<article class="gc-hub-card">
			<span class="gc-card-num">ACT · TODAY'S CHALLENGE</span>
			<?php if ( $challenge ) : ?>
				<h3><?php echo esc_html( get_the_title( $challenge ) ); ?></h3>
				<p><?php echo wp_kses_post( wpautop( $challenge->post_content ) ); ?></p>
				<?php if ( ! $logged_in ) : ?>
					<strong><a class="gc-text-link" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in to mark complete →</a></strong>
				<?php elseif ( $challenge_done ) : ?>
					<span class="gc-card-status">✓ Completed today</span>
				<?php else : ?>
					<button type="button" class="gc-button gc-button-light gc-complete-btn" data-activity-type="daily_challenge" data-content-id="<?php echo esc_attr( $challenge->ID ); ?>" data-source="member_hub_today">Mark Complete</button>
				<?php endif; ?>
			<?php else : ?>
				<h3>Preparing today's challenge…</h3>
				<p>Check back shortly — a new challenge is on its way.</p>
			<?php endif; ?>
		</article>
		<article class="gc-hub-card">
			<span class="gc-card-num">DISCOVER</span>
			<?php
			$opportunity = get_posts( array( 'post_type' => 'gc_opportunity', 'post_status' => 'publish', 'posts_per_page' => 1, 'orderby' => 'rand' ) );
			if ( ! empty( $opportunity ) ) :
				?>
				<h3><?php echo esc_html( get_the_title( $opportunity[0] ) ); ?></h3>
				<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $opportunity[0]->post_content ), 20 ) ); ?></p>
				<strong><a class="gc-text-link" href="<?php echo esc_url( get_permalink( $opportunity[0] ) ); ?>">View opportunity →</a></strong>
			<?php else : ?>
				<h3>Explore opportunities</h3>
				<p>Jobs, training and grants curated for the Game Changers community.</p>
				<strong><a class="gc-text-link" href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">Browse opportunities →</a></strong>
			<?php endif; ?>
		</article>
	</div>

	<?php if ( $is_free_member || ! $logged_in ) : ?>
	<aside class="gc-hub-card gc-free-premium-upsell" style="margin-top:22px;background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border:0;">
		<span class="gc-card-num" style="color:#e8b840;"><?php esc_html_e( 'PREMIUM MEMBERSHIP', 'game-changers' ); ?></span>
		<h3 style="color:#fff;"><?php esc_html_e( 'Benefits beyond daily practice', 'game-changers' ); ?></h3>
		<p style="opacity:.9;"><?php esc_html_e( 'Daily nuggets build the habit. Premium is for members who want deeper mentorship tools — and room to learn new things beyond leadership.', 'game-changers' ); ?></p>
		<ul style="margin:0 0 14px;padding-left:18px;line-height:1.85;opacity:.95;">
			<li><?php esc_html_e( 'Game Changers books and manuals', 'game-changers' ); ?></li>
			<li><?php esc_html_e( 'Leadership badges', 'game-changers' ); ?></li>
			<li><?php esc_html_e( 'Game Changers badges', 'game-changers' ); ?></li>
			<li><?php esc_html_e( 'Referral bonuses', 'game-changers' ); ?></li>
			<li><?php esc_html_e( '2-year leadership growth tool', 'game-changers' ); ?></li>
			<li><?php esc_html_e( 'Mentorship pathways to learn beyond leadership', 'game-changers' ); ?></li>
		</ul>
		<p style="margin:0;display:flex;flex-wrap:wrap;gap:12px;">
			<a class="gc-button gc-button-gold" href="<?php echo esc_url( $premium ); ?>"><?php esc_html_e( 'Explore Premium', 'game-changers' ); ?></a>
			<?php if ( ! $logged_in ) : ?>
				<a class="gc-text-link" style="color:#e8b840;" href="<?php echo esc_url( $free_join ); ?>"><?php esc_html_e( 'Start free membership →', 'game-changers' ); ?></a>
			<?php endif; ?>
		</p>
	</aside>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}
add_shortcode( 'gc_today', 'gc_render_today_block' );

function gc_render_member_growth_block() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$progress      = gc_member_progress( get_current_user_id() );
	$badges_config = get_option( 'gc_badges_config', array() );
	ob_start();
	?>
	<div class="gc-hub-progress-card" data-gc-growth>
		<div><span class="gc-kicker">Your growth</span><h2><?php echo esc_html( $progress['level'] ); ?></h2><p><?php echo esc_html( $progress['points'] ); ?> points · <?php echo esc_html( $progress['streak_current'] ); ?>-day streak</p></div>
	</div>
	<div class="gc-hub-stats">
		<div><strong><?php echo esc_html( $progress['points'] ); ?></strong><span>Points</span></div>
		<div><strong><?php echo esc_html( $progress['streak_current'] ); ?></strong><span>Day Streak</span></div>
		<div><strong><?php echo esc_html( $progress['streak_longest'] ); ?></strong><span>Longest Streak</span></div>
		<div><strong><?php echo esc_html( count( $progress['badges'] ) ); ?></strong><span>Badges Earned</span></div>
	</div>
	<div class="gc-badge-row">
		<?php foreach ( $badges_config as $badge ) : $earned = isset( $progress['badges'][ $badge['slug'] ] ); ?>
			<span class="gc-badge-chip <?php echo $earned ? 'is-earned' : 'is-locked'; ?>"><?php echo $earned ? '🏆 ' : ''; ?><?php echo esc_html( $badge['label'] ); ?></span>
		<?php endforeach; ?>
	</div>
	<style>
	.gc-badge-row{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0 50px}
	.gc-badge-chip{font:700 10px 'Space Mono',monospace;letter-spacing:.04em;padding:8px 14px;border-radius:999px;border:1px solid var(--gc-line);color:var(--gc-muted)}
	.gc-badge-chip.is-earned{background:var(--gc-green);border-color:var(--gc-green);color:#fff}
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
