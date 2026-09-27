<?php
get_header(); $gc_chapters_list = gc_get_all_chapters(); $gc_q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; if ( $gc_q ) { $gc_needle = strtolower( $gc_q ); $gc_chapters_list = array_values( array_filter( $gc_chapters_list, function( $c ) use ( $gc_needle ) { return false !== strpos( strtolower( $c['title'] ), $gc_needle ) || false !== strpos( strtolower( $c['state'] ), $gc_needle ); } ) ); } ?>
<style>
  .gc-chapters-wrap { padding: 140px 5vw 100px; max-width: 1100px; margin: 0 auto; }
  .gc-chapters-head { text-align: center; margin-bottom: 56px; }
  .gc-chapters-head .section-label { color: var(--green); font-family:'Space Mono',monospace; font-size:12px; letter-spacing:.12em; text-transform:uppercase; }
  .gc-chapters-head h1 { font-family:'Playfair Display',serif; font-size: clamp(30px,4vw,46px); color: var(--ink); margin: 10px 0 14px; }
  .gc-chapters-head p { color: var(--muted); max-width: 620px; margin: 0 auto; line-height: 1.7; }
  .gc-chapters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 20px; }
  .gc-chapter-card { border: 1px solid var(--line); border-radius: 12px; padding: 22px 22px 24px; background: var(--card-bg); display:flex; flex-direction:column; }
  .gc-chapter-card h3 { font-family:'Playfair Display',serif; font-size: 19px; color: var(--ink); margin-bottom: 6px; }
  .gc-chapter-card .gc-organiser { font-size: 12px; color: var(--muted); margin-bottom: 14px; }
  .gc-chapter-card .gc-join-btn {
    margin-top: auto; display:inline-block; text-align:center; background: var(--green); color:#fff;
    padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight:600; text-decoration:none;
  }
  .gc-chapter-card .gc-join-btn:hover { background: var(--green-mid); }
  .gc-chapter-card .gc-pending { font-size: 12px; color: var(--muted); font-style: italic; margin-top:auto; }
  .gc-chapter-card .gc-member-count { font-size: 11px; font-weight:600; color: var(--green); background: rgba(26,86,50,.08); border-radius: 20px; padding: 3px 10px; display:inline-block; margin-bottom: 10px; align-self:flex-start; }
  .gc-featured-strip { margin-bottom: 48px; }
  .gc-featured-strip h2 { font-family:'Playfair Display',serif; font-size: 20px; color: var(--ink); margin-bottom: 16px; }
  .gc-featured-strip .gc-chapters-grid { grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); }
  .gc-featured-strip .gc-chapter-card { border-color: var(--gold); }
</style>
<div class="gc-chapters-wrap">
  <div class="gc-chapters-head">
    <div class="section-label">Grassroots Mobilization</div>
    <h1>Find Your State Chapter</h1>
    <p>Every state and the FCT has a Game Changers chapter. Tap "Join on WhatsApp" for your state to connect with organisers and members near you.</p>
  </div>
  <form method="get" style="max-width:420px;margin:0 auto 40px;display:flex;gap:8px;">
    <input type="search" name="q" value="<?php echo esc_attr( $gc_q ); ?>" placeholder="Search by chapter or state…" style="flex:1;padding:11px 14px;border:1px solid var(--line);border-radius:7px;">
    <button type="submit" class="gc-join-btn" style="padding:11px 20px;">Search</button>
    <?php if ( $gc_q ) : ?><a href="<?php echo esc_url( gc_page_url( 'chapters-archive' ) ); ?>" style="align-self:center;font-size:12px;color:var(--muted);white-space:nowrap;">Clear</a><?php endif; ?>
  </form>
  <?php $gc_featured = function_exists( 'gc_get_featured_chapters' ) ? gc_get_featured_chapters() : array(); if ( ! empty( $gc_featured ) ) : ?>
  <div class="gc-featured-strip">
    <h2>★ Spotlight Chapters</h2>
    <div class="gc-chapters-grid">
      <?php foreach ( $gc_featured as $fchapter ) : ?>
        <div class="gc-chapter-card">
          <span class="gc-member-count"><?php echo (int) gc_get_chapter_member_count( $fchapter->ID ); ?> members</span>
          <h3><?php echo esc_html( $fchapter->post_title ); ?></h3>
          <a href="<?php echo esc_url( get_permalink( $fchapter ) ); ?>" class="gc-join-btn">View Chapter</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="gc-chapters-grid">
    <?php foreach ( $gc_chapters_list as $chapter ) : ?>
      <div class="gc-chapter-card">
        <span class="gc-member-count"><?php echo (int) $chapter['member_count']; ?> member<?php echo 1 === (int) $chapter['member_count'] ? '' : 's'; ?></span>
        <h3><?php echo esc_html( $chapter['title'] ); ?><?php if ( $chapter['featured'] ) : ?> ★<?php endif; ?></h3>
        <?php if ( $chapter['organiser'] ) : ?>
          <div class="gc-organiser">Organiser: <?php echo esc_html( $chapter['organiser'] ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $chapter['local_groups'] ) ) : ?>
          <div class="gc-organiser"><?php echo (int) $chapter['local_groups']; ?> local group<?php echo 1 === (int) $chapter['local_groups'] ? '' : 's'; ?></div>
        <?php endif; ?>
        <?php if ( $chapter['whatsapp'] ) : ?>
          <a href="<?php echo esc_url( $chapter['whatsapp'] ); ?>" target="_blank" rel="noopener" class="gc-join-btn">Join on WhatsApp</a>
        <?php else : ?>
          <a href="<?php echo esc_url( $chapter['permalink'] ); ?>" class="gc-join-btn" style="background:var(--muted);">View Chapter</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ( ! $gc_q ) : $gc_ranked = $gc_chapters_list; usort( $gc_ranked, function( $a, $b ) { return $b['member_count'] <=> $a['member_count']; } ); $gc_ranked = array_slice( array_filter( $gc_ranked, function( $c ) { return $c['member_count'] > 0; } ), 0, 5 ); if ( ! empty( $gc_ranked ) ) : ?>
    <div style="margin-top:64px;">
      <h2 style="font-family:'Playfair Display',serif;font-size:22px;color:var(--ink);margin-bottom:6px;">Most Active Chapters</h2>
      <p style="color:var(--muted);font-size:13px;margin-bottom:18px;">Ranked by approved members, straight from real join requests.</p>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ( $gc_ranked as $gi => $gc_r ) : ?>
          <a href="<?php echo esc_url( $gc_r['permalink'] ); ?>" style="display:flex;justify-content:space-between;align-items:center;background:var(--card-bg);border:1px solid var(--line);border-radius:10px;padding:12px 18px;text-decoration:none;">
            <span style="color:var(--ink);font-weight:700;font-size:14px;"><?php echo esc_html( $gi + 1 ); ?>. <?php echo esc_html( $gc_r['title'] ); ?></span>
            <span style="color:var(--green);font-weight:700;font-size:13px;">👥 <?php echo (int) $gc_r['member_count']; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; endif; ?>
</div>
<?php get_footer(); ?>
