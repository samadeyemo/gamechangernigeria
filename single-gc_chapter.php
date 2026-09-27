<?php
get_header(); while ( have_posts() ) : the_post(); $gc_chapter_id = get_the_ID(); $whatsapp = get_post_meta( $gc_chapter_id, 'gc_whatsapp_link', true ); $organiser = get_post_meta( $gc_chapter_id, 'gc_chapter_organiser', true ); $gc_member_count = gc_get_chapter_member_count( $gc_chapter_id ); $gc_local_groups = gc_get_chapter_local_groups( $gc_chapter_id ); ?>
	<style>
	  .gc-chapter-single { padding: 140px 5vw 100px; max-width: 720px; margin: 0 auto; }
	  .gc-chapter-single h1 { font-family:'Playfair Display',serif; font-size: clamp(30px,4vw,42px); color: var(--ink); margin-bottom: 10px; }
	  .gc-chapter-single .gc-organiser { color: var(--muted); font-size: 13px; margin-bottom: 8px; }
	  .gc-chapter-single .gc-member-count { display:inline-block; font-size: 12px; font-weight:600; color: var(--green); background: rgba(26,86,50,.08); border-radius: 20px; padding: 4px 12px; margin-bottom: 24px; }
	  .gc-chapter-single .gc-content { line-height: 1.8; color: var(--ink); margin-bottom: 32px; }
	  .gc-chapter-single .gc-join-btn {
	    display:inline-block; background: var(--green); color:#fff; padding: 14px 26px;
	    border-radius: 6px; font-weight:600; text-decoration:none;
	  }
	  .gc-chapter-single .gc-join-btn:hover { background: var(--green-mid); }
	  .gc-chapter-single .gc-back { display:block; margin-top:28px; font-size:13px; color: var(--muted); text-decoration:none; }
	  .gc-chapter-single .gc-local-groups { margin-top: 40px; padding-top: 28px; border-top: 1px solid var(--line); }
	  .gc-chapter-single .gc-local-groups h2 { font-family:'Playfair Display',serif; font-size: 20px; color: var(--ink); margin-bottom: 12px; }
	  .gc-chapter-single .gc-local-groups ul { list-style:none; padding:0; margin:0; display:grid; gap:8px; }
	  .gc-chapter-single .gc-local-groups a { color: var(--green); text-decoration:none; font-weight:600; }
	  #gc-join { margin-top: 40px; padding-top: 28px; border-top: 1px solid var(--line); }
	  #gc-join h2 { font-family:'Playfair Display',serif; font-size: 20px; color: var(--ink); margin-bottom: 6px; }
	  #gc-join p { color: var(--muted); font-size: 13px; margin-bottom: 18px; }
	</style>
	<div class="gc-chapter-single">
		<h1><?php the_title(); ?></h1>
		<div class="gc-organiser" style="text-transform:uppercase;letter-spacing:.04em;font-weight:700;color:var(--green);"><?php echo esc_html( gc_chapter_level_label( $gc_chapter_id ) ); ?></div>
		<?php if ( $organiser ) : ?>
			<div class="gc-organiser">Organiser: <?php echo esc_html( $organiser ); ?></div>
		<?php endif; ?>
		<div class="gc-member-count"><?php echo esc_html( $gc_member_count ); ?> <?php echo 1 === $gc_member_count ? esc_html__( 'member', 'game-changers' ) : esc_html__( 'members', 'game-changers' ); ?></div>
		<div class="gc-content"><?php the_content(); ?></div>
		<?php if ( $whatsapp ) : ?>
			<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" class="gc-join-btn">Join this Chapter on WhatsApp</a>
		<?php else : ?>
			<p style="color:var(--muted); font-style:italic;">A WhatsApp group for this chapter hasn't been added yet — check back soon.</p>
		<?php endif; ?>

		<div id="gc-join">
			<h2><?php esc_html_e( 'Request Chapter Membership', 'game-changers' ); ?></h2>
			<p><?php esc_html_e( "Want to be listed as a member of this chapter? Send a request — the chapter leader reviews and approves it.", 'game-changers' ); ?></p>
			<?php gc_render_chapter_join_form( $gc_chapter_id ); ?>
		</div>

		<?php if ( ! empty( $gc_local_groups ) ) : ?>
			<div class="gc-local-groups">
				<h2><?php echo esc_html( gc_chapter_child_level_label( $gc_chapter_id ) ); ?>s</h2>
				<ul>
					<?php foreach ( $gc_local_groups as $group ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $group ) ); ?>"><?php echo esc_html( $group->post_title ); ?></a> <span style="color:var(--muted);font-size:12px;">— <?php echo esc_html( gc_get_chapter_member_count( $group->ID ) ); ?> members</span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<a href="<?php echo esc_url( gc_page_url( 'chapters-archive' ) ); ?>" class="gc-back">← Back to all chapters</a>
	</div>
<?php endwhile; get_footer(); 