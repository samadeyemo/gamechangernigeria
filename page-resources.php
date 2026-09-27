<?php
/**
 * Template Name: Resources
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$ebook  = function_exists( 'gc_page_url' ) ? gc_page_url( 'free-ebook' ) : home_url( '/free-ebook/' );
$free   = function_exists( 'gc_page_url' ) ? gc_page_url( 'enlistments' ) : home_url( '/enlistments/' );
$premium = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
$hub    = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
?>
<main class="gc-page">
  <section class="gc-hero">
    <div class="gc-container">
      <p class="gc-eyebrow"><?php esc_html_e( 'RESOURCE LIBRARY', 'game-changers' ); ?></p>
      <h1><?php esc_html_e( 'Tools for leaders who want to do the work.', 'game-changers' ); ?></h1>
      <p><?php esc_html_e( 'Start with the free 7-day activation ebook, continue with 30-day short nuggets, and unlock deeper Premium tools when you are ready.', 'game-changers' ); ?></p>
      <div class="gc-actions" style="display:flex;flex-wrap:wrap;gap:12px;">
        <a class="gc-button gc-button-green" href="<?php echo esc_url( $ebook ); ?>"><?php esc_html_e( '7-Day Activation Ebook', 'game-changers' ); ?></a>
        <a class="gc-button gc-button-outline" href="<?php echo esc_url( is_user_logged_in() ? $hub : $free ); ?>"><?php esc_html_e( '30-Day Short Nuggets', 'game-changers' ); ?></a>
        <a class="gc-button gc-button-gold" href="<?php echo esc_url( $premium ); ?>"><?php esc_html_e( 'Premium membership', 'game-changers' ); ?></a>
      </div>
    </div>
  </section>

  <section class="gc-section">
    <div class="gc-container" style="display:grid;gap:18px;max-width:900px;">
      <article style="background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:14px;padding:22px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'Free membership path', 'game-changers' ); ?></h2>
        <p><?php esc_html_e( 'The 7-Day Activation Challenge ebook is linked to free membership. After activation week, free members receive 30-day short nuggets — brief daily prompts that build consistency.', 'game-changers' ); ?></p>
        <a href="<?php echo esc_url( $free ); ?>"><?php esc_html_e( 'Join free membership →', 'game-changers' ); ?></a>
      </article>
      <article style="background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border-radius:14px;padding:22px;">
        <h2 style="margin-top:0;color:#e8b840;"><?php esc_html_e( 'Premium membership benefits', 'game-changers' ); ?></h2>
        <p style="opacity:.9;"><?php esc_html_e( 'Appreciate mentorship as growth beyond a single skill. Premium members access deeper leadership tools and space to learn new things.', 'game-changers' ); ?></p>
        <ul style="line-height:1.85;">
          <li><?php esc_html_e( 'Game Changers books and manuals', 'game-changers' ); ?></li>
          <li><?php esc_html_e( 'Leadership badges', 'game-changers' ); ?></li>
          <li><?php esc_html_e( 'Game Changers badges', 'game-changers' ); ?></li>
          <li><?php esc_html_e( 'Referral bonuses', 'game-changers' ); ?></li>
          <li><?php esc_html_e( '2-year leadership growth tool', 'game-changers' ); ?></li>
          <li><?php esc_html_e( 'Mentorship pathways to learn beyond leadership', 'game-changers' ); ?></li>
        </ul>
        <a style="color:#e8b840;font-weight:700;" href="<?php echo esc_url( $premium ); ?>"><?php esc_html_e( 'Become a Premium member →', 'game-changers' ); ?></a>
      </article>
    </div>
  </section>
</main>
<?php get_footer(); ?>
