<?php
/**
 * National Dashboard — administrators only. Every number is a live
 * count from gc_chapter / gc_member, the same data already powering
 * the chapter directory and each Chapter Leader Dashboard, rolled up
 * State -> Chapter -> Local/Campus Group.
 */
if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
nocache_headers();
get_header();

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	?>
	<section class="gc-submit-hero gc-dark" style="text-align:center;">
		<div class="gc-container"><span class="gc-kicker">Game Changers Nigeria</span><h1>National Dashboard</h1><p style="margin:0 auto;">Log in with an Administrator account to see the network-wide dashboard.</p></div>
	</section>
	<?php
	if ( ! is_user_logged_in() ) {
		echo '<div class="gc-section"><div class="gc-container" style="text-align:center;"><a class="gc-form-submit" style="display:inline-block;width:auto;padding:14px 30px;text-decoration:none;" href="' . esc_url( add_query_arg( 'redirect_to', urlencode( get_permalink() ), gc_page_url( 'premium-login' ) ) ) . '">Log In</a></div></div>';
	}
	get_footer();
	return;
}

$chapters = gc_get_all_chapters(); // top-level State Chapters only
$total_members = 0; $total_groups = 0;
foreach ( $chapters as $c ) { $total_members += $c['member_count']; $total_groups += $c['local_groups']; }
?>
<section class="gc-submit-hero gc-dark" style="text-align:center;">
	<div class="gc-container">
		<span class="gc-kicker">Game Changers Nigeria</span>
		<h1>National Dashboard</h1>
		<p style="margin:0 auto;">A real-time look at the movement — every number below reflects the actual database, updated automatically as states, chapters and members grow.</p>
	</div>
</section>

<div class="gc-section"><div class="gc-container">

	<div class="gc-chapter-dash-stats" style="margin-bottom:56px;">
		<div class="gc-chapter-dash-stat"><span><?php echo count( $chapters ); ?></span><em>State Chapters</em></div>
		<div class="gc-chapter-dash-stat"><span><?php echo (int) $total_groups; ?></span><em>Local / Campus Groups</em></div>
		<div class="gc-chapter-dash-stat"><span><?php echo (int) $total_members; ?></span><em>Approved Members</em></div>
		<div class="gc-chapter-dash-stat"><span><?php $p = 0; foreach ( $chapters as $c ) { $p += gc_get_chapter_pending_count( $c['id'] ); } echo (int) $p; ?></span><em>Pending Requests</em></div>
	</div>

	<div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Drill down</span><h2>State → Chapter → Local/Campus Group</h2></div></div>

	<?php if ( empty( $chapters ) ) : ?>
		<div class="gc-empty">No chapters recorded yet.</div>
	<?php endif; ?>

	<div class="gc-national-list">
		<?php foreach ( $chapters as $c ) :
			$leader = gc_get_chapter_leader_user( $c['id'] );
			$children = gc_get_chapter_local_groups( $c['id'] );
		?>
			<div class="gc-national-chapter">
				<div class="gc-national-chapter-head">
					<div>
						<a href="<?php echo esc_url( $c['permalink'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a>
						<span class="gc-national-meta"><?php echo esc_html( $c['state'] ); ?><?php echo $leader ? ' · Led by ' . esc_html( $leader->display_name ) : ' · No leader assigned'; ?></span>
					</div>
					<div class="gc-national-counts">👥 <?php echo (int) $c['member_count']; ?> members · <?php echo (int) $c['local_groups']; ?> group<?php echo 1 === (int) $c['local_groups'] ? '' : 's'; ?></div>
				</div>
				<?php if ( ! empty( $children ) ) : ?>
					<ul class="gc-national-children">
						<?php foreach ( $children as $child ) :
							$cl = gc_get_chapter_leader_user( $child->ID );
							$grandchildren = gc_get_chapter_local_groups( $child->ID );
						?>
							<li>
								<div class="gc-national-chapter-head">
									<div><a href="<?php echo esc_url( get_permalink( $child ) ); ?>"><?php echo esc_html( $child->post_title ); ?></a><span class="gc-national-meta"><?php echo esc_html( gc_chapter_level_label( $child->ID ) ); ?><?php echo $cl ? ' · Led by ' . esc_html( $cl->display_name ) : ' · No leader assigned'; ?></span></div>
									<div class="gc-national-counts">👥 <?php echo (int) gc_get_chapter_member_count( $child->ID ); ?> members<?php echo ! empty( $grandchildren ) ? ' · ' . count( $grandchildren ) . ' group' . ( 1 === count( $grandchildren ) ? '' : 's' ) : ''; ?></div>
								</div>
								<?php if ( ! empty( $grandchildren ) ) : ?>
									<ul class="gc-national-children gc-national-children--nested">
										<?php foreach ( $grandchildren as $gc ) : ?>
											<li><a href="<?php echo esc_url( get_permalink( $gc ) ); ?>"><?php echo esc_html( $gc->post_title ); ?></a> <span class="gc-national-meta">👥 <?php echo (int) gc_get_chapter_member_count( $gc->ID ); ?></span></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

</div></div>
<style>
.gc-national-list{display:flex;flex-direction:column;gap:16px;margin-top:22px}
.gc-national-chapter{background:#fff;border:1px solid var(--gc-line);border-radius:12px;padding:20px 22px}
.gc-national-chapter-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px}
.gc-national-chapter-head a{font-weight:800;color:var(--gc-ink);text-decoration:none;font-size:15px}
.gc-national-meta{display:block;font-size:12px;color:var(--gc-muted);margin-top:2px}
.gc-national-counts{font-size:12px;color:var(--gc-green);font-weight:700;white-space:nowrap}
.gc-national-children{list-style:none;margin:16px 0 0;padding:16px 0 0 18px;border-top:1px dashed var(--gc-line);display:flex;flex-direction:column;gap:14px}
.gc-national-children--nested{border-top:0;padding:10px 0 0 18px;margin-top:10px;gap:8px}
.gc-national-children--nested li{display:flex;justify-content:space-between;align-items:center;font-size:13px}
.gc-national-children--nested a{color:var(--gc-ink);text-decoration:none;font-weight:600}
</style>
<?php get_footer(); ?>
