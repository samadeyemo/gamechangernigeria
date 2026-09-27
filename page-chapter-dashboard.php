<?php if(!defined('DONOTCACHEPAGE')){define('DONOTCACHEPAGE',true);} nocache_headers(); if(!is_user_logged_in()) {wp_safe_redirect(gc_page_url('chapter-login'));exit;} if(!gc_chapter_leader_allowed()){wp_die('Your chapter leader access is not approved yet.',403);} $u=wp_get_current_user(); $cid=(int)get_user_meta($u->ID,'gc_chapter_leader_chapter_id',true); $ch=$cid?get_post($cid):null; $state=$ch?get_post_meta($cid,'gc_chapter_state',true):get_user_meta($u->ID,'gc_chapter_leader_state',true); $map=gc_ng_state_capitals(); get_header(); ?>
<main class="gc-chapter-dash">
<section class="gc-submit-hero gc-dark gc-chapter-dash-hero">
	<div class="gc-container" style="text-align:center;">
		<span class="gc-kicker">Chapter leader dashboard</span>
		<h1><?php echo esc_html( $ch ? $ch->post_title : ( $state . ' Chapter' ) ); ?></h1>
		<?php if ( $cid ) : ?><p class="gc-chapter-dash-level"><?php echo esc_html( gc_chapter_level_label( $cid ) ); ?></p><?php endif; ?>
		<p><a href="<?php echo esc_url( gc_page_url( 'premium-dashboard' ) ); ?>">← Back to My Member Dashboard</a></p>
	</div>
</section>

<?php $gc_notice=isset($_GET['gc_chapter'])?sanitize_key(wp_unslash($_GET['gc_chapter'])):''; if ( $cid ) :
	$gc_pending         = gc_get_chapter_members( $cid, 'pending' );
	$gc_approved_count  = gc_get_chapter_member_count( $cid );
	$gc_approved_members= gc_get_chapter_members( $cid, 'approved' );
	$gc_local_groups    = gc_get_chapter_local_groups( $cid );
	$gc_own_level       = gc_chapter_level_label( $cid );
	$gc_child_level     = gc_chapter_child_level_label( $cid );
	$gc_events          = gc_get_chapter_upcoming_events( $cid, 3 );
	$gc_projects        = get_posts( array( 'post_type' => 'gc_project', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC' ) );
?>
<div class="gc-container">

	<div class="gc-chapter-dash-stats">
		<div class="gc-chapter-dash-stat"><span><?php echo (int) $gc_approved_count; ?></span><em>Approved Members</em></div>
		<div class="gc-chapter-dash-stat"><span><?php echo count( $gc_pending ); ?></span><em>Pending Requests</em></div>
		<div class="gc-chapter-dash-stat"><span><?php echo count( $gc_local_groups ); ?></span><em><?php echo esc_html( $gc_child_level ); ?>s</em></div>
		<div class="gc-chapter-dash-stat"><span><?php echo (int) gc_count_chapter_upcoming_events( $cid ); ?></span><em>Upcoming Events</em></div>
	</div>

	<div class="gc-chapter-dash-referral">
		<h2>📎 Your Chapter Link</h2>
		<p>Share this link — anyone who joins through it lands directly in <?php echo esc_html( $ch ? $ch->post_title : 'your chapter' ); ?>, pending your approval below.</p>
		<input type="text" value="<?php echo esc_url( gc_get_chapter_referral_link( $cid ) ); ?>" readonly onclick="this.select();">
		<p class="gc-chapter-dash-referral-count"><strong><?php echo (int) gc_get_chapter_referral_count( $cid ); ?></strong> member<?php echo 1 === (int) gc_get_chapter_referral_count( $cid ) ? '' : 's'; ?> have joined through this link so far.</p>
	</div>

	<?php gc_render_chapter_pathway_widget( $u->ID ); ?>

	<section class="gc-chapter-lessons" id="gc-pending-members">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Membership</span><h2>Pending join &amp; transfer requests.</h2></div><p><?php echo count( $gc_pending ); ?> request<?php echo 1 === count( $gc_pending ) ? '' : 's'; ?> waiting on your review.</p></div>
		<?php if ( 'member_approved' === $gc_notice ) : ?><div class="gc-form-notice gc-form-notice--ok">Member approved.</div>
		<?php elseif ( 'member_declined' === $gc_notice ) : ?><div class="gc-form-notice">Member request declined.</div>
		<?php endif; ?>
		<?php if ( empty( $gc_pending ) ) : ?>
			<div class="gc-empty">No pending requests right now.</div>
		<?php else : ?>
			<div class="gc-chapter-dash-rows">
				<?php foreach ( $gc_pending as $p ) : $gc_is_transfer = (int) get_post_meta( $p->ID, 'gc_member_chapter_id', true ) === $cid && get_post_meta( $p->ID, 'gc_member_status', true ) === 'pending'; ?>
					<div class="gc-chapter-dash-row">
						<div>
							<strong><?php echo esc_html( $p->post_title ); ?></strong>
							<span class="gc-chapter-dash-row-sub"><?php echo esc_html( get_post_meta( $p->ID, 'gc_member_email', true ) ); ?><?php $ph = get_post_meta( $p->ID, 'gc_member_phone', true ); if ( $ph ) { echo ' · ' . esc_html( $ph ); } ?></span>
						</div>
						<div class="gc-chapter-dash-row-actions">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="gc_member_approve"><input type="hidden" name="gc_member_id" value="<?php echo esc_attr( $p->ID ); ?>"><?php wp_nonce_field( 'gc_member_approve', 'gc_member_approve_nonce' ); ?><button class="gc-form-submit gc-btn-sm" type="submit">Approve</button></form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="gc_member_decline"><input type="hidden" name="gc_member_id" value="<?php echo esc_attr( $p->ID ); ?>"><?php wp_nonce_field( 'gc_member_decline', 'gc_member_decline_nonce' ); ?><button class="gc-btn-sm gc-btn-ghost" type="submit">Decline</button></form>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="gc-chapter-lessons" id="gc-local-groups">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Multiply</span><h2><?php echo esc_html( $gc_child_level ); ?>s under your <?php echo esc_html( strtolower( $gc_own_level ) ); ?>.</h2></div><p>You're managing a <strong><?php echo esc_html( $gc_own_level ); ?></strong>. Open a <?php echo esc_html( strtolower( $gc_child_level ) ); ?> underneath it — optionally appoint someone to lead it, and they'll get their own dashboard login to manage it (and open the next level down themselves).</p></div>
		<?php if ( 'group_saved' === $gc_notice ) : ?><div class="gc-form-notice gc-form-notice--ok"><?php echo esc_html( $gc_child_level ); ?> created.</div>
		<?php elseif ( 'group_error' === $gc_notice ) : ?><div class="gc-form-notice gc-form-notice--err">Please enter a name.</div>
		<?php endif; ?>
		<?php if ( ! empty( $gc_local_groups ) ) : ?>
			<div class="gc-chapter-dash-cards">
				<?php foreach ( $gc_local_groups as $g ) : $gl = (int) get_post_meta( $g->ID, 'gc_chapter_leader_user_id', true ); $gu = $gl ? get_userdata( $gl ) : false; ?>
					<a class="gc-chapter-dash-card" href="<?php echo esc_url( get_permalink( $g ) ); ?>">
						<strong><?php echo esc_html( $g->post_title ); ?></strong>
						<span>👥 <?php echo (int) gc_get_chapter_member_count( $g->ID ); ?> members<?php echo $gu ? ' · Led by ' . esc_html( $gu->display_name ) : ' · No leader assigned yet'; ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="gc-form-card"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form"><input type="hidden" name="action" value="gc_chapter_add_group"><?php wp_nonce_field( 'gc_group_save', 'gc_group_nonce' ); ?><div class="gc-form-row"><label><?php echo esc_html( $gc_child_level ); ?> Name</label><input name="gc_group_title" placeholder="e.g. Surulere" required></div><div class="gc-form-row"><label>WhatsApp Group Link (optional)</label><input type="url" name="gc_group_whatsapp" placeholder="https://chat.whatsapp.com/..."></div><div class="gc-form-row gc-form-row--split"><div><label>Appoint a Leader — Name (optional)</label><input name="gc_group_leader_name" placeholder="Leave blank to manage it yourself"></div><div><label>Appoint a Leader — Email (optional)</label><input type="email" name="gc_group_leader_email" placeholder="their@email.com"></div></div><button class="gc-form-submit" type="submit">Add <?php echo esc_html( $gc_child_level ); ?></button></form></div>
	</section>

	<?php if ( ! empty( $gc_approved_members ) ) : ?>
	<section class="gc-chapter-lessons">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Community</span><h2>Members</h2></div></div>
		<div class="gc-chapter-dash-members">
			<?php foreach ( $gc_approved_members as $m ) : $mi = strtoupper( substr( $m->post_title, 0, 1 ) ); ?>
				<div class="gc-chapter-dash-member">
					<span class="gc-avatar-fallback"><?php echo esc_html( $mi ); ?></span>
					<div><strong><?php echo esc_html( $m->post_title ); ?></strong><span><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_email', true ) ); ?></span></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( ! empty( $gc_events ) ) : ?>
	<section class="gc-chapter-lessons">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Calendar</span><h2>Upcoming Events</h2></div></div>
		<div class="gc-chapter-dash-rows">
			<?php foreach ( $gc_events as $ev ) : ?>
				<a class="gc-chapter-dash-row" href="<?php echo esc_url( get_permalink( $ev ) ); ?>"><strong><?php echo esc_html( $ev->post_title ); ?></strong><span class="gc-chapter-dash-row-sub"><?php $d = get_post_meta( $ev->ID, 'gc_event_date', true ); echo $d ? esc_html( date_i18n( 'M j, Y', strtotime( $d ) ) ) : ''; ?></span></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( ! empty( $gc_projects ) ) : ?>
	<section class="gc-chapter-lessons">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Impact</span><h2>Projects</h2></div></div>
		<div class="gc-chapter-dash-rows">
			<?php foreach ( $gc_projects as $pr ) : ?>
				<a class="gc-chapter-dash-row" href="<?php echo esc_url( get_permalink( $pr ) ); ?>"><strong><?php echo esc_html( $pr->post_title ); ?></strong></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="gc-chapter-lessons">
		<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Chapter learning</span><h2>Lessons for your chapter.</h2></div><p>Published Academy lessons are available here as well as in the Member Hub.</p></div>
		<div class="gc-chapter-lesson-grid">
			<?php $lessons = get_posts( array( 'post_type' => 'gc_lesson', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ); foreach ( $lessons as $lesson ) : ?>
				<article><span>ACADEMY LESSON</span><h3><?php echo esc_html( $lesson->post_title ); ?></h3><p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $lesson->post_content ), 18 ) ); ?></p><a class="gc-text-link" href="<?php echo esc_url( get_permalink( $lesson ) ); ?>">Open lesson →</a></article>
			<?php endforeach; if ( empty( $lessons ) ) : ?><div class="gc-empty">No lessons have been published yet.</div><?php endif; ?>
		</div>
	</section>

	<details class="gc-chapter-dash-settings">
		<summary>⚙️ Chapter Settings — name, state, about, WhatsApp link</summary>
		<div class="gc-form-card"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form"><input type="hidden" name="action" value="gc_chapter_leader_save"><?php wp_nonce_field( 'gc_leader_save', 'gc_leader_nonce' ); ?><div class="gc-form-row"><label>Chapter Name</label><input name="gc_title" value="<?php echo esc_attr( $ch ? $ch->post_title : ( $state . ' Chapter' ) ); ?>"></div><div class="gc-form-row"><label>State</label><select name="gc_state" required><?php foreach ( $map as $s => $cap ) : ?><option value="<?php echo esc_attr( $s ); ?>" <?php selected( $state, $s ); ?>><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select></div><div class="gc-form-row"><label>About the Chapter</label><textarea name="gc_about" rows="6"><?php echo esc_textarea( $ch ? $ch->post_content : '' ); ?></textarea></div><div class="gc-form-row"><label>WhatsApp Group Link</label><input type="url" name="gc_whatsapp" value="<?php echo esc_attr( $ch ? get_post_meta( $cid, 'gc_whatsapp_link', true ) : '' ); ?>" placeholder="https://chat.whatsapp.com/..."></div><button class="gc-form-submit" type="submit">Save Chapter</button></form></div>
	</details>

</div>
<?php endif; ?>
</main>
<style>
.gc-chapter-dash-hero .gc-kicker{color:var(--gc-gold)}
.gc-chapter-dash-hero p a{color:rgba(255,255,255,.7);text-decoration:underline;font-size:13px}
.gc-chapter-dash-level{text-transform:uppercase;letter-spacing:.08em;font-size:11px;font-weight:800;color:var(--gc-gold);margin:4px 0 14px !important}
.gc-chapter-dash .gc-container{max-width:1080px;margin:0 auto;padding:0 5vw}
.gc-chapter-dash-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:46px 0 40px}
.gc-chapter-dash-stat{background:#fff;border:1px solid var(--gc-line);border-radius:12px;padding:22px 10px;text-align:center}
.gc-chapter-dash-stat span{display:block;font:900 34px 'Playfair Display',serif;color:var(--gc-gold)}
.gc-chapter-dash-stat em{font:700 10px 'Space Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:var(--gc-muted);font-style:normal}
.gc-chapter-dash-referral{background:#fff;border:1px solid var(--gc-line);border-left:4px solid var(--gc-green);border-radius:12px;padding:26px 28px;margin-bottom:40px}
.gc-chapter-dash-referral h2{font:800 17px 'Playfair Display',serif;margin:0 0 8px;color:var(--gc-ink)}
.gc-chapter-dash-referral p{color:var(--gc-muted);font-size:13px;margin:0 0 14px}
.gc-chapter-dash-referral input{width:100%;padding:11px 13px;border:1px solid var(--gc-line);border-radius:7px;font-size:13px;background:var(--gc-paper)}
.gc-chapter-dash-referral-count{margin-top:10px !important}
.gc-pathway-widget{margin-bottom:48px}
.gc-pathway-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px;margin-top:18px}
.gc-pathway-card{background:#fff;border:1px solid var(--gc-line);border-top:4px solid var(--gc-gold);border-radius:12px;padding:22px}
.gc-pathway-level{font:700 10px 'Space Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:var(--gc-green)}
.gc-pathway-card h3{font:800 18px 'Playfair Display',serif;margin:8px 0 6px;color:var(--gc-ink)}
.gc-pathway-card p{color:var(--gc-muted);font-size:12px;margin:0 0 12px}
.gc-pathway-bar{height:6px;border-radius:4px;background:var(--gc-line);overflow:hidden;margin-bottom:14px}
.gc-pathway-bar span{display:block;height:100%;background:var(--gc-green)}
.gc-pathway-badge{display:inline-block;font-size:11px;font-weight:800;color:var(--gc-muted);background:var(--gc-paper);border-radius:20px;padding:4px 12px;margin-bottom:10px}
.gc-pathway-badge--done{color:#146c3a;background:rgba(20,108,58,.1)}
.gc-pathway-card .gc-text-link{display:block}
.gc-chapter-dash-rows{display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.gc-chapter-dash-row{background:#fff;border:1px solid var(--gc-line);border-radius:10px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;text-decoration:none}
.gc-chapter-dash-row strong{color:var(--gc-ink);font-size:14px}
.gc-chapter-dash-row-sub{display:block;color:var(--gc-muted);font-size:12px;margin-top:2px}
.gc-chapter-dash-row-actions{display:flex;gap:8px}
.gc-btn-sm{min-height:auto !important;padding:8px 16px !important;font-size:12px !important}
.gc-btn-ghost{background:transparent!important;color:var(--gc-muted)!important;border:1px solid var(--gc-line)!important;border-radius:7px;padding:8px 16px;font-size:12px;font-weight:700;cursor:pointer}
.gc-chapter-dash-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:20px}
.gc-chapter-dash-card{background:#fff;border:1px solid var(--gc-line);border-radius:10px;padding:16px 18px;text-decoration:none;display:flex;flex-direction:column;gap:6px}
.gc-chapter-dash-card strong{color:var(--gc-ink);font-size:14px}
.gc-chapter-dash-card span{color:var(--gc-muted);font-size:12px}
.gc-chapter-dash-members{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px}
.gc-chapter-dash-member{background:#fff;border:1px solid var(--gc-line);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.gc-chapter-dash-member .gc-avatar-fallback{width:36px;height:36px;border-radius:50%;font-size:13px}
.gc-chapter-dash-member strong{display:block;font-size:13px;color:var(--gc-ink)}
.gc-chapter-dash-member span{display:block;font-size:11px;color:var(--gc-muted)}
.gc-chapter-dash-settings{margin:44px 0 60px;background:#fff;border:1px solid var(--gc-line);border-radius:12px;padding:20px 24px}
.gc-chapter-dash-settings summary{cursor:pointer;font-weight:700;font-size:13px;color:var(--gc-ink)}
.gc-chapter-dash-settings .gc-form-card{margin-top:18px;border:0;box-shadow:none;padding:0}
@media(max-width:760px){.gc-chapter-dash-stats{grid-template-columns:repeat(2,1fr)}}
</style>
<?php get_footer(); ?>
