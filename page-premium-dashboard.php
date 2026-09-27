<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); } nocache_headers(); if ( ! is_user_logged_in() ) { wp_safe_redirect( gc_page_url( 'premium-login' ) ); exit; } $gc_user = wp_get_current_user(); $gc_is_premium = function_exists( 'gc_user_is_premium' ) ? gc_user_is_premium( $gc_user->ID ) : ( current_user_can( 'administrator' ) || in_array( 'premium_member', (array) $gc_user->roles, true ) ); $gc_member = function_exists( 'gc_member_for_user' ) ? gc_member_for_user( $gc_user->ID ) : null; $gc_progress = function_exists( 'gc_user_lesson_progress' ) ? gc_user_lesson_progress( $gc_user->ID ) : array( 'done' => 0, 'total' => 0, 'percent' => 0 ); $gc_tier = function_exists( 'gc_get_user_premium_tier' ) ? gc_get_user_premium_tier( $gc_user->ID ) : 1; $gc_ref_tag = get_user_meta( $gc_user->ID, 'gc_premium_referral_tag', true ); $gc_ref_count = $gc_ref_tag && function_exists( 'gc_count_premium_referrals' ) ? gc_count_premium_referrals( $gc_user->ID ) : 0;
 $gc_points_total = (int) get_user_meta( $gc_user->ID, 'gc_total_points', true ); $gc_ref_meta_count = (int) get_user_meta( $gc_user->ID, 'gc_referral_count', true ); $gc_mentor_id = (int) get_user_meta( $gc_user->ID, 'gc_assigned_mentor_id', true ); $gc_mentor = $gc_mentor_id ? get_post( $gc_mentor_id ) : null; $gc_chapter_id = $gc_member ? (int) get_post_meta( $gc_member->ID, 'gc_member_chapter_id', true ) : 0; $gc_cohort_id = $gc_member ? (int) get_post_meta( $gc_member->ID, 'gc_member_cohort_id', true ) : 0; $gc_chapter = $gc_chapter_id ? get_post( $gc_chapter_id ) : null; $gc_cohort = $gc_cohort_id ? get_post( $gc_cohort_id ) : null; $gc_projects = $gc_member ? get_posts( array( 'post_type'=>'gc_project','post_status'=>'publish','posts_per_page'=>4,'meta_query'=>array( array( 'key'=>'gc_project_leader_id','value'=>$gc_member->ID ) ) ) ) : array(); $gc_events = get_posts( array( 'post_type'=>'gc_event','post_status'=>'publish','posts_per_page'=>4,'meta_key'=>'gc_event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array( array('key'=>'gc_event_date','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE') ) ) ); $gc_opps = get_posts( array( 'post_type'=>'gc_opportunity','post_status'=>'publish','posts_per_page'=>4,'meta_key'=>'gc_opportunity_deadline','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array( array('key'=>'gc_opportunity_deadline','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE') ) ) ); get_header(); ?>

<?php if ( isset( $_GET['joined'] ) && '1' === $_GET['joined'] ) : ?>
<div class="gc-joined-banner" style="background:linear-gradient(90deg,#006b3f,#0d1a0f);color:#fff;padding:16px 5vw;text-align:center;font-size:14px;">
  Welcome to the Member Hub. Your tier is active<?php
  $tag = get_user_meta( get_current_user_id(), 'gc_premium_referral_tag', true );
  if ( $tag ) { echo ' · Your referral code: <strong style="letter-spacing:.06em;">' . esc_html( $tag ) . '</strong>'; }
  $pts = (int) get_user_meta( get_current_user_id(), 'gc_total_points', true );
  if ( $pts ) { echo ' · ' . (int) $pts . ' points'; }
  ?>. Share your code to earn points when others join.
</div>
<?php endif; ?>

<div class="gc-hub">
<?php if ( ! $gc_is_premium ) :
  $gc_tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
  $gc_ebook = function_exists( 'gc_page_url' ) ? gc_page_url( 'free-ebook' ) : home_url( '/free-ebook/' );
  $gc_login_email = $gc_user->user_email;
?>
<main class="gc-hub-shell" style="max-width:1100px;margin:0 auto;padding:100px 5vw 60px;">
  <?php if ( isset( $_GET['joined'] ) || isset( $_GET['upgraded'] ) ) : ?>
    <div style="background:#e8f5ef;color:#006b3f;border-radius:10px;padding:14px 16px;margin-bottom:22px;font-size:14px;">
      <?php esc_html_e( 'Welcome. Your Premium tier is now active on this same account — no new email or password needed.', 'game-changers' ); ?>
    </div>
  <?php endif; ?>

  <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:16px;align-items:flex-end;margin-bottom:28px;">
    <div>
      <span class="gc-kicker"><?php esc_html_e( 'Free membership', 'game-changers' ); ?></span>
      <h1 style="margin:6px 0 8px;font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,40px);">
        <?php printf( esc_html__( 'Welcome, %s.', 'game-changers' ), esc_html( $gc_user->display_name ) ); ?>
      </h1>
      <p style="margin:0;color:#66756a;max-width:48ch;line-height:1.65;">
        <?php esc_html_e( 'You are on the free path: activation ebook, 30-day short nuggets, and community access. When you are ready, upgrade to Premium below — same login, same password.', 'game-changers' ); ?>
      </p>
    </div>
    <span style="display:inline-flex;align-items:center;padding:8px 14px;border-radius:999px;background:#f0f4f1;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#006b3f;">
      <?php esc_html_e( 'Free member', 'game-changers' ); ?>
    </span>
  </div>

  <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-bottom:28px;">
    <article style="background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:14px;padding:20px;">
      <div style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:#c8992a;font-weight:700;margin-bottom:8px;"><?php esc_html_e( 'Start', 'game-changers' ); ?></div>
      <h3 style="margin:0 0 8px;"><?php esc_html_e( '7-Day Activation Ebook', 'game-changers' ); ?></h3>
      <p style="margin:0 0 14px;color:#66756a;font-size:14px;line-height:1.6;"><?php esc_html_e( 'Activate your first week with practical steps.', 'game-changers' ); ?></p>
      <a class="gc-button gc-button-green" href="<?php echo esc_url( $gc_ebook ); ?>"><?php esc_html_e( 'Get the ebook', 'game-changers' ); ?></a>
    </article>
    <article style="background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:14px;padding:20px;">
      <div style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:#c8992a;font-weight:700;margin-bottom:8px;"><?php esc_html_e( 'Daily', 'game-changers' ); ?></div>
      <h3 style="margin:0 0 8px;"><?php esc_html_e( '30-day short nuggets', 'game-changers' ); ?></h3>
      <p style="margin:0 0 14px;color:#66756a;font-size:14px;line-height:1.6;"><?php esc_html_e( 'Short daily prompts to build consistency after activation.', 'game-changers' ); ?></p>
      <?php if ( function_exists( 'gc_render_today_block' ) ) { echo gc_render_today_block(); } ?>
    </article>
  </div>

  <section id="gc-upgrade" style="background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border-radius:16px;padding:28px 22px;margin-bottom:40px;">
    <div style="font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#e8b840;font-weight:700;"><?php esc_html_e( 'Upgrade in place', 'game-changers' ); ?></div>
    <h2 style="margin:8px 0 10px;font-family:'Playfair Display',serif;color:#fff;font-size:clamp(22px,3vw,30px);">
      <?php esc_html_e( 'Become Premium — same email, same password', 'game-changers' ); ?>
    </h2>
    <p style="margin:0 0 18px;opacity:.9;max-width:56ch;line-height:1.65;font-size:15px;">
      <?php esc_html_e( 'Choose a tier, complete payment, and this account upgrades automatically. No second registration. Your free progress stays with you.', 'game-changers' ); ?>
    </p>
    <ul style="margin:0 0 22px;padding-left:18px;line-height:1.85;opacity:.95;font-size:14px;">
      <li><?php esc_html_e( 'Game Changers books and manuals', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Leadership badges & Game Changers badges', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Referral bonuses', 'game-changers' ); ?></li>
      <li><?php esc_html_e( '2-year leadership growth tool', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Mentorship pathways beyond leadership', 'game-changers' ); ?></li>
    </ul>

    <?php if ( empty( $gc_tiers ) ) : ?>
      <p><?php esc_html_e( 'Tiers are being configured. Please check back shortly.', 'game-changers' ); ?></p>
    <?php else : ?>
      <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <?php foreach ( $gc_tiers as $tn => $tv ) :
          $price = (int) ( $tv['price'] ?? 0 );
          $name  = $tv['name'] ?? ( 'Tier ' . $tn );
        ?>
          <div style="background:rgba(255,255,255,.06);border:1px solid rgba(232,184,64,.25);border-radius:12px;padding:16px;">
            <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#e8b840;margin-bottom:6px;"><?php echo esc_html( 'Tier ' . (int) $tn ); ?></div>
            <strong style="display:block;margin-bottom:4px;"><?php echo esc_html( $name ); ?></strong>
            <div style="font-size:18px;font-weight:800;margin-bottom:12px;">₦<?php echo esc_html( number_format_i18n( $price ) ); ?></div>
            <?php
            if ( function_exists( 'gc_join_paystack_button' ) ) {
              echo gc_join_paystack_button( (int) $tn, $price, $gc_login_email );
            } else {
              $join = add_query_arg( 'tier', (int) $tn, gc_page_url( 'become-a-member' ) );
              echo '<a class="gc-button gc-button-gold" href="' . esc_url( $join ) . '">' . esc_html__( 'Select tier', 'game-changers' ) . '</a>';
            }
            ?>
          </div>
        <?php endforeach; ?>
      </div>
      <p style="margin:16px 0 0;font-size:12px;opacity:.7;">
        <?php printf( esc_html__( 'Paying as %s — after payment you stay logged in on this account.', 'game-changers' ), esc_html( $gc_login_email ) ); ?>
      </p>
    <?php endif; ?>
  </section>
</main>
<?php get_footer(); return; ?>
<?php else : ?>
  <div class="gc-hub-shell">
    <aside class="gc-hub-side">
      <div class="gc-hub-side-title"><span class="gc-kicker">Member Hub</span><strong>My journey</strong></div>
      <div class="gc-hub-person"><strong><?php echo esc_html( $gc_user->display_name ); ?></strong><small><?php echo esc_html( $gc_member ? gc_member_field($gc_member->ID,'gc_member_state','Nigeria') : 'Game Changer' ); ?></small></div>
      <nav>
        <a class="is-active" href="#today">Today</a><a href="#overview">Overview</a><a href="#learning">Learning</a><a href="#community">Community</a><a href="#impact">My Impact</a><a href="#opportunities">Opportunities</a><a href="#resources">Resources</a>
      </nav>
      <a class="gc-hub-logout" href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>">Log out</a>
    </aside>
    <main class="gc-hub-main">
      <div class="gc-hub-top"><div><span class="gc-kicker">Game Changer Hub</span><h1>Welcome back, <?php echo esc_html( $gc_user->display_name ); ?>.</h1><p>Keep learning, keep connecting, keep creating impact.</p></div><span class="gc-tier-pill"><?php echo function_exists('gc_tier_label') ? esc_html(gc_tier_label($gc_tier)) : 'Tier '.(int)$gc_tier.' Member'; ?></span></div>
      <section id="today" class="gc-hub-section">
        <?php if ( function_exists( 'gc_render_member_growth_block' ) ) { echo gc_render_member_growth_block(); } ?>
        <?php if ( function_exists( 'gc_render_today_block' ) ) { echo gc_render_today_block(); } ?>
      </section>
      <section id="overview" class="gc-hub-section">
        <div class="gc-hub-progress-card"><div><span class="gc-kicker">Your journey</span><h2><?php echo $gc_cohort ? esc_html($gc_cohort->post_title) : 'Leadership development'; ?></h2><p><?php echo (int)$gc_progress['done']; ?> of <?php echo (int)$gc_progress['total']; ?> lessons completed</p></div><div class="gc-ring" style="--progress:<?php echo (int)$gc_progress['percent']; ?>%"><strong><?php echo (int)$gc_progress['percent']; ?>%</strong><small>complete</small></div></div>
        <div class="gc-hub-stats"><div><strong><?php echo (int)$gc_progress['done']; ?></strong><span>Lessons complete</span></div><div><strong><?php echo $gc_mentor ? '1' : '0'; ?></strong><span>Mentor assigned</span></div><div><strong><?php echo count($gc_projects); ?></strong><span>Impact projects</span></div><div><strong><?php echo (int)$gc_ref_count; ?></strong><span>Referrals</span></div></div>
      </section>
      <section id="learning" class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Develop</span><h2>Continue learning</h2></div><a href="#learning">View all</a></div><div class="gc-hub-grid two">
      <?php $lessons=get_posts(array('post_type'=>'gc_lesson','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'menu_order title','order'=>'ASC')); $shown=0; foreach($lessons as $lesson): $lt=(int)(get_post_meta($lesson->ID,'gc_lesson_tier',true)?:1); if($lt>$gc_tier)continue; if($shown++>=6)break; $done=in_array($lesson->ID,(array)get_user_meta($gc_user->ID,'gc_completed_lessons',true),true); ?><article class="gc-hub-card"><span class="gc-card-num"><?php echo esc_html(sprintf('%02d',$lesson->menu_order ?: $shown)); ?></span><h3><?php echo esc_html($lesson->post_title); ?></h3><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($lesson->post_content),18)); ?></p><span class="gc-card-status"><?php echo $done ? '✓ Completed' : 'Ready to learn'; ?></span><a class="gc-text-link" href="<?php echo esc_url(get_permalink($lesson)); ?>">Open lesson →</a></article><?php endforeach; if(!$shown): ?><div class="gc-empty">No lessons are available for your tier yet.</div><?php endif; ?></div></section>
      <section id="community" class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Connect</span><h2>Your community</h2></div></div><div class="gc-hub-grid three"><article class="gc-hub-card gc-dark-card"><span class="gc-kicker">Chapter</span><h3><?php echo $gc_chapter ? esc_html($gc_chapter->post_title) : 'Not assigned yet'; ?></h3><p><?php echo $gc_chapter ? 'Your local Game Changers community.' : 'Ask the team to connect you to your nearest chapter.'; ?></p><a href="<?php echo esc_url(gc_page_url('find-a-chapter')); ?>">Find a chapter →</a></article><article class="gc-hub-card"><span class="gc-kicker">Mentorship</span><h3><?php echo $gc_mentor ? esc_html($gc_mentor->post_title) : 'Find your mentor'; ?></h3><p><?php echo $gc_mentor ? 'Your assigned mentor is ready to support your growth.' : 'Your mentor assignment will appear here when matched.'; ?></p></article><article class="gc-hub-card"><span class="gc-kicker">Cohort</span><h3><?php echo $gc_cohort ? esc_html($gc_cohort->post_title) : 'Academy'; ?></h3><p>Build leadership capability alongside a community of peers.</p><a href="<?php echo esc_url(home_url('/academy/')); ?>">Explore Academy →</a></article></div></section>
      <section id="impact" class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Serve</span><h2>My impact</h2></div><a href="<?php echo esc_url(home_url('/impact-projects/')); ?>">Explore projects</a></div><div class="gc-hub-grid two"><?php foreach($gc_projects as $project): ?><article class="gc-hub-card"><span class="gc-card-status"><?php echo esc_html(get_post_meta($project->ID,'gc_project_status',true) ?: 'Impact project'); ?></span><h3><?php echo esc_html($project->post_title); ?></h3><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($project->post_content),20)); ?></p><strong><?php echo number_format_i18n((int)get_post_meta($project->ID,'gc_project_people_reached',true)); ?> people reached</strong></article><?php endforeach; if(empty($gc_projects)): ?><div class="gc-empty">Your impact projects will appear here when you lead or are assigned to a project.</div><?php endif; ?></div></section>
      <section id="opportunities" class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Grow</span><h2>Opportunities</h2></div><a href="<?php echo esc_url(home_url('/opportunities/')); ?>">See all</a></div><div class="gc-hub-list"><?php foreach($gc_opps as $opp): ?><a href="<?php echo esc_url(get_permalink($opp)); ?>"><span><?php echo esc_html(get_post_meta($opp->ID,'gc_opportunity_category',true) ?: 'Opportunity'); ?></span><strong><?php echo esc_html($opp->post_title); ?></strong><small><?php echo esc_html(get_post_meta($opp->ID,'gc_opportunity_organisation',true)); ?></small></a><?php endforeach; if(empty($gc_opps)): ?><div class="gc-empty">No current opportunities have been added.</div><?php endif; ?></div></section>
      <section class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Connect</span><h2>Upcoming events</h2></div><a href="<?php echo esc_url(home_url('/events/')); ?>">See calendar</a></div><div class="gc-hub-list"><?php foreach($gc_events as $event): ?><a href="<?php echo esc_url(get_permalink($event)); ?>"><span><?php echo esc_html(get_post_meta($event->ID,'gc_event_date',true)); ?></span><strong><?php echo esc_html($event->post_title); ?></strong><small><?php echo esc_html(get_post_meta($event->ID,'gc_event_location',true)); ?></small></a><?php endforeach; if(empty($gc_events)): ?><div class="gc-empty">No upcoming events have been added.</div><?php endif; ?></div></section>
      <section class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Take action</span><h2>Next steps</h2></div></div><div class="gc-hub-actions"><a class="gc-button" href="<?php echo esc_url(home_url('/academy/')); ?>">Explore Academy</a><a class="gc-button gc-button-light" href="<?php echo esc_url(home_url('/academy-apply/')); ?>">Apply to a cohort</a><a class="gc-button gc-button-light" href="<?php echo esc_url(gc_page_url('certificate')); ?>">My Certificates</a><a class="gc-button gc-button-light" href="<?php echo esc_url(home_url('/verify-certificate/')); ?>">Verify Certificate</a></div></section>
      <section id="resources" class="gc-hub-section"><div class="gc-hub-section-head"><div><span class="gc-kicker">Resources</span><h2>Your member resources</h2></div></div><div class="gc-hub-actions"><a class="gc-button" href="#resources">Documents</a><a class="gc-button gc-button-light" href="<?php echo esc_url(gc_page_url('members')); ?>">Member directory</a><?php if($gc_ref_tag): ?><div class="gc-ref-mini"><small>Your referral code</small><strong><?php echo esc_html($gc_ref_tag); ?></strong></div><?php endif; ?></div></section>
    </main>
  </div>
<?php endif; ?>
</div>

<?php
$gc_uid = get_current_user_id();
$gc_my_tier = function_exists( 'gc_get_user_premium_tier' ) ? (int) gc_get_user_premium_tier( $gc_uid ) : (int) get_user_meta( $gc_uid, 'gc_premium_tier', true );
$gc_is_ic = function_exists( 'gc_user_is_inner_circle' ) ? gc_user_is_inner_circle( $gc_uid ) : false;
$gc_ic_tag = get_user_meta( $gc_uid, 'gc_inner_circle_tag', true );
$gc_wa = get_option( 'gc_inner_circle_whatsapp', '' );
$gc_ref_tag_dash = get_user_meta( $gc_uid, 'gc_premium_referral_tag', true );
$gc_points_dash = (int) get_user_meta( $gc_uid, 'gc_total_points', true );
$gc_ref_count_dash = (int) get_user_meta( $gc_uid, 'gc_referral_count', true );
$gc_ref_log = (array) get_user_meta( $gc_uid, 'gc_referral_log', true );
$gc_ref_link = $gc_ref_tag_dash ? add_query_arg( 'refcode', $gc_ref_tag_dash, function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' ) ) : '';
?>
<section class="gc-hub-section" id="gc-hub-growth" style="max-width:1100px;margin:32px auto;padding:0 5vw 48px;">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:28px;">
    <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:20px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#006b3f;">Your tier</div>
      <div style="font-size:28px;font-weight:800;margin-top:6px;">Tier <?php echo (int) $gc_my_tier; ?></div>
    </div>
    <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:20px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#006b3f;">Points</div>
      <div style="font-size:28px;font-weight:800;margin-top:6px;"><?php echo (int) $gc_points_dash; ?></div>
    </div>
    <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:20px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#006b3f;">Referrals</div>
      <div style="font-size:28px;font-weight:800;margin-top:6px;"><?php echo (int) $gc_ref_count_dash; ?></div>
    </div>
  </div>
  <?php if ( $gc_ref_tag_dash ) : ?>
  <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:22px;margin-bottom:20px;">
    <h3 style="margin:0 0 8px;font-family:'Playfair Display',serif;">Your referral link</h3>
    <p style="margin:0 0 10px;color:#66756a;font-size:14px;">Share this so others join under you. You earn points when they complete payment.</p>
    <code style="display:block;padding:12px;background:#f5f2ec;border-radius:8px;word-break:break-all;"><?php echo esc_html( $gc_ref_link ); ?></code>
    <p style="margin:10px 0 0;font-size:13px;">Code: <strong><?php echo esc_html( $gc_ref_tag_dash ); ?></strong></p>
    <?php if ( ! empty( $gc_ref_log ) ) : ?>
      <ul style="margin:14px 0 0;padding-left:18px;font-size:13px;">
        <?php foreach ( array_reverse( $gc_ref_log ) as $row ) :
          $ru = ! empty( $row['user_id'] ) ? get_user_by( 'id', $row['user_id'] ) : null; ?>
          <li><?php echo esc_html( $ru ? $ru->display_name : 'Member' ); ?> · Tier <?php echo (int) ( $row['tier'] ?? 0 ); ?> · +<?php echo (int) ( $row['points'] ?? 0 ); ?> pts</li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:22px;margin-bottom:20px;">
    <h3 style="margin:0 0 12px;font-family:'Playfair Display',serif;">Course materials</h3>
    <?php
    $any = false;
    for ( $ti = 1; $ti <= max( 1, $gc_my_tier ); $ti++ ) {
      if ( function_exists( 'gc_user_has_tier_access' ) && ! gc_user_has_tier_access( $gc_uid, $ti ) ) { continue; }
      $mats = function_exists( 'gc_get_tier_materials' ) ? gc_get_tier_materials( $ti ) : array();
      if ( empty( $mats ) ) { continue; }
      $any = true;
      echo '<h4 style="margin:12px 0 6px;font-size:13px;color:#006b3f;">Tier ' . (int) $ti . '</h4><ul style="margin:0;padding-left:18px;">';
      foreach ( $mats as $mrow ) {
        echo '<li style="margin-bottom:6px;"><a href="' . esc_url( $mrow['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $mrow['title'] ) . '</a></li>';
      }
      echo '</ul>';
    }
    if ( ! $any ) {
      echo '<p style="color:#66756a;font-size:14px;margin:0;">No materials uploaded for your tier yet.</p>';
    }
    ?>
  </div>
  <?php if ( $gc_is_ic ) : ?>
  <div style="background:linear-gradient(165deg,#0a1a10,#12261a);color:#f5f2ec;border-radius:14px;padding:28px;margin-bottom:20px;">
    <div style="font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#e8b840;font-weight:700;">Inner Circle</div>
    <h3 style="margin:8px 0;font-family:'Playfair Display',serif;color:#fff;">Leadership Inner Circle</h3>
    <p style="opacity:.85;font-size:14px;line-height:1.6;">Completed pathway recognition, premium tag, and direct admin channel.</p>
    <?php if ( $gc_ic_tag ) : ?><p style="margin:10px 0;">Your tag: <strong style="letter-spacing:.08em;"><?php echo esc_html( $gc_ic_tag ); ?></strong></p><?php endif; ?>
    <p style="font-size:14px;">Referrals: <strong><?php echo (int) $gc_ref_count_dash; ?></strong> · Points: <strong><?php echo (int) $gc_points_dash; ?></strong></p>
    <?php if ( $gc_wa ) : ?>
      <a class="gc-button gc-button-gold" style="margin-top:12px;" href="<?php echo esc_url( $gc_wa ); ?>" target="_blank" rel="noopener">Chat admin on WhatsApp</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>


<?php
/* Upgrade + Daily ACT challenge */
$gc_all_tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
$gc_higher = array();
foreach ( $gc_all_tiers as $tn => $tv ) {
	if ( (int) $tn > (int) $gc_my_tier ) {
		$gc_higher[ $tn ] = $tv;
	}
}
?>
<?php if ( ! empty( $gc_higher ) ) : ?>
<section id="gc-upgrade" style="max-width:1100px;margin:0 auto 28px;padding:0 5vw;">
  <div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:22px;">
    <h3 style="margin:0 0 6px;font-family:'Playfair Display',serif;">Continue to the next tier</h3>
    <p style="margin:0 0 14px;color:#66756a;font-size:14px;">You are on Tier <?php echo (int) $gc_my_tier; ?>. Pay for a higher tier here — no new registration. Lessons and challenges unlock for that level.</p>
    <?php if ( isset( $_GET['upgraded'] ) ) : ?>
      <p style="background:#e8f5ef;color:#006b3f;padding:10px 12px;border-radius:8px;font-size:14px;">Tier upgraded. New lessons are available above.</p>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
      <input type="hidden" name="action" value="gc_tier_upgrade">
      <?php wp_nonce_field( 'gc_upgrade', 'gc_upgrade_nonce' ); ?>
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Upgrade to</label>
      <select name="upgrade_tier" required style="width:100%;max-width:320px;padding:10px;border-radius:8px;border:1px solid #ccd0d4;margin-bottom:12px;">
        <?php foreach ( $gc_higher as $tn => $tv ) : ?>
          <option value="<?php echo (int) $tn; ?>"><?php echo esc_html( 'Tier ' . $tn . ' — ' . ( $tv['name'] ?? '' ) . ' · ₦' . number_format_i18n( (int) ( $tv['price'] ?? 0 ) ) ); ?></option>
        <?php endforeach; ?>
      </select>
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Email used for payment</label>
      <input type="email" name="pay_email" required value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" style="width:100%;max-width:320px;padding:10px;border-radius:8px;border:1px solid #ccd0d4;margin-bottom:12px;">
      <div style="margin-bottom:10px;">
        <label style="display:block;margin-bottom:6px;"><input type="radio" name="confirm_method" value="paystack_email" checked> I paid with Paystack</label>
        <input type="text" name="pay_ref" placeholder="Paystack reference (optional)" style="width:100%;max-width:320px;padding:8px;border-radius:8px;border:1px solid #ccd0d4;margin:4px 0 10px 22px;">
        <label style="display:block;margin-bottom:6px;"><input type="radio" name="confirm_method" value="receipt"> Bank transfer — upload receipt</label>
        <input type="file" name="receipt" accept="image/*,.pdf" style="margin:4px 0 0 22px;font-size:13px;">
        <label style="display:block;margin:12px 0 6px;"><input type="radio" name="confirm_method" value="invite"> Admin free-access code</label>
        <input type="text" name="invite_code" placeholder="Invite / free-access code" style="width:100%;max-width:320px;padding:8px;border-radius:8px;border:1px solid #ccd0d4;margin:4px 0 6px 22px;" autocomplete="off">
        <p style="font-size:12px;color:#66756a;margin:0 0 0 22px;">For members granted free entry by admin — same account, no second login.</p>
      </div>
      <?php
      $shop = function_exists( 'gc_join_pay_link_for_tier' ) ? gc_join_pay_link_for_tier( array_key_first( $gc_higher ) ) : get_option( 'gc_paystack_shop_link', '' );
      if ( $shop ) :
        ?><p style="font-size:13px;margin:8px 0;"><a href="<?php echo esc_url( $shop ); ?>" target="_blank" rel="noopener">Open Paystack payment →</a> then confirm below.</p><?php
      endif;
      ?>
      <button class="gc-button gc-button-gold" type="submit">Confirm upgrade</button>
    </form>
  </div>
</section>
<?php endif; ?>

<section id="gc-daily-act" style="max-width:1100px;margin:0 auto 28px;padding:0 5vw;">
  <div style="background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border-radius:14px;padding:26px;">
    <div style="font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#e8b840;font-weight:700;">Come back every day</div>
    <h3 style="margin:8px 0;font-family:'Playfair Display',serif;color:#fff;">Today’s leadership ACT</h3>
    <?php
    $acts = array(
      array( 'title' => 'Name one broken system near you', 'body' => 'Write it in one sentence. Then list 3 people affected. Practical step: share the sentence with one trusted peer today.' ),
      array( 'title' => 'Practice ethical clarity', 'body' => 'Identify one decision you face this week. Write the honest option and the easy option. Practical step: choose the honest one and tell someone why.' ),
      array( 'title' => 'Lead in a small room', 'body' => 'Call or message 2 people in your circle. Ask what problem they want solved. Practical step: offer one concrete help within 48 hours.' ),
      array( 'title' => 'Citizen discipline', 'body' => 'Track how you use 1 hour of free time today. Practical step: move 20 minutes to learning or community work.' ),
      array( 'title' => 'Referral with integrity', 'body' => 'Invite only someone ready to grow. Practical step: send your referral link with a short reason they should join — not spam.' ),
      array( 'title' => 'Document impact', 'body' => 'Note one action you took this week that helped someone. Practical step: save it in your Member Hub notes or journal.' ),
      array( 'title' => 'Public courage, private humility', 'body' => 'Speak up once for what is right in a small setting. Practical step: follow up by listening more than talking.' ),
    );
    $day = (int) gmdate( 'z' ); // day of year
    $act = $acts[ $day % count( $acts ) ];
    ?>
    <p style="font-size:18px;font-weight:700;margin:0 0 8px;color:#e8b840;"><?php echo esc_html( $act['title'] ); ?></p>
    <p style="opacity:.9;line-height:1.65;margin:0 0 16px;font-size:15px;"><?php echo esc_html( $act['body'] ); ?></p>
    <p style="font-size:12px;opacity:.7;margin:0;">New ACT each day · Tier <?php echo (int) $gc_my_tier; ?> pathway · Mark progress in Today’s Challenge above when available</p>
  </div>
</section>

<?php get_footer(); ?>
