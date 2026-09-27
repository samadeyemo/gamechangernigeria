<?php
/**
 * Template Name: Free Ebook (7-Day Activation Challenge)
 * Lead magnet → free membership path + soft premium upsell
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$gc_status = isset( $_GET['gc_ebook'] ) ? sanitize_key( wp_unslash( $_GET['gc_ebook'] ) ) : '';
$free_join = function_exists( 'gc_page_url' ) ? gc_page_url( 'enlistments' ) : home_url( '/enlistments/' );
$premium   = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
$hub       = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
?>
<style>
  .gc-ebook-wrap{padding:140px 5vw 100px;max-width:640px;margin:0 auto;text-align:center}
  .gc-ebook-wrap .section-label{color:var(--green,#006b3f);font-family:'Space Mono',monospace;font-size:12px;letter-spacing:.12em;text-transform:uppercase}
  .gc-ebook-wrap h1{font-family:'Playfair Display',serif;font-size:clamp(26px,4vw,40px);color:var(--ink,#0d1a0f);margin:10px 0 14px}
  .gc-ebook-wrap > p{color:var(--muted,#66756a);line-height:1.7;margin-bottom:24px}
  .gc-ebook-wrap .gc-form-card{text-align:left;background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:28px 24px;margin:0 auto 28px}
  .gc-ebook-wrap .gc-form-row{margin-bottom:16px}
  .gc-ebook-wrap label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;letter-spacing:.04em;text-transform:uppercase}
  .gc-ebook-wrap input{width:100%;min-height:46px;padding:0 14px;border:1px solid rgba(13,26,15,.18);border-radius:8px}
  .gc-ebook-wrap .gc-form-submit{width:100%;min-height:50px;border:0;border-radius:8px;background:var(--gold,#c8992a);color:var(--ink,#0d1a0f);font-weight:800;cursor:pointer;text-transform:uppercase;letter-spacing:.06em;font-size:12px}
  .gc-ebook-wrap .gc-form-submit:hover{background:var(--gold-light,#e8b840)}
  .gc-ebook-wrap .gc-form-notice{padding:12px 16px;border-radius:7px;font-size:13px;margin-bottom:22px;background:rgba(200,40,40,.08);color:#a02222;border:1px solid rgba(200,40,40,.2);text-align:left}
  .gc-ebook-path{display:grid;gap:14px;text-align:left;margin:28px 0}
  .gc-ebook-path article{background:#fbfaf6;border:1px solid rgba(13,26,15,.1);border-radius:12px;padding:18px 16px}
  .gc-ebook-path h3{margin:0 0 8px;font-size:16px}
  .gc-ebook-path p{margin:0 0 12px;color:var(--muted,#66756a);font-size:14px;line-height:1.6}
  .gc-ebook-benefits{text-align:left;background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;border-radius:14px;padding:22px 18px;margin-top:8px}
  .gc-ebook-benefits h3{margin:0 0 10px;color:#e8b840;font-size:15px;letter-spacing:.04em;text-transform:uppercase}
  .gc-ebook-benefits ul{margin:0;padding-left:18px;line-height:1.8;font-size:14px}
  .gc-ebook-benefits a{color:#e8b840;font-weight:700}
</style>
<div class="gc-ebook-wrap">
  <div class="section-label"><?php esc_html_e( 'Free membership path', 'game-changers' ); ?></div>
  <h1><?php esc_html_e( '7-Day Activation Challenge Ebook', 'game-changers' ); ?></h1>
  <p><?php esc_html_e( 'Start free. This short ebook activates your first week as a Game Changer — practical steps you can take immediately. After activation, continue with 30-day short nuggets under free membership, and grow further with Premium when you are ready.', 'game-changers' ); ?></p>

  <?php if ( 'error' === $gc_status ) : ?>
    <div class="gc-form-notice"><?php esc_html_e( 'Please fill in your name and a valid email.', 'game-changers' ); ?></div>
  <?php elseif ( 'checkemail' === $gc_status ) : ?>
    <div class="gc-form-notice" style="background:rgba(0,140,81,.1);color:var(--green,#006b3f);border-color:rgba(0,140,81,.25);">
      <?php esc_html_e( 'Almost there — check your email and click the confirmation link to download the ebook. The link expires in 30 minutes.', 'game-changers' ); ?>
      <br><br>
      <?php
      printf(
        wp_kses_post( __( 'Next: %1$s so you can receive the %2$s.', 'game-changers' ) ),
        '<a href="' . esc_url( $free_join ) . '"><strong>' . esc_html__( 'join free membership', 'game-changers' ) . '</strong></a>',
        '<strong>' . esc_html__( '30-day short nuggets', 'game-changers' ) . '</strong>'
      );
      ?>
    </div>
  <?php elseif ( 'notregistered' === $gc_status ) : ?>
    <div class="gc-form-notice">
      <?php
      printf(
        wp_kses_post( __( 'That email is not on free membership yet. %s first (it is free), then come back with the same email.', 'game-changers' ) ),
        '<a href="' . esc_url( $free_join ) . '">' . esc_html__( 'Join free membership', 'game-changers' ) . '</a>'
      );
      ?>
    </div>
  <?php elseif ( 'nofile' === $gc_status ) : ?>
    <div class="gc-form-notice"><?php esc_html_e( 'The ebook file is not uploaded yet — please check back soon.', 'game-changers' ); ?></div>
  <?php endif; ?>

  <div class="gc-form-card">
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <input type="hidden" name="action" value="gc_submit_ebook">
      <input type="hidden" name="gc_redirect" value="<?php echo esc_url( get_permalink() ); ?>">
      <?php wp_nonce_field( 'gc_ebook_lead', 'gc_ebook_nonce' ); ?>
      <div class="gc-form-row">
        <label for="gc_name"><?php esc_html_e( 'Full Name', 'game-changers' ); ?></label>
        <input type="text" id="gc_name" name="gc_name" required>
      </div>
      <div class="gc-form-row">
        <label for="gc_email"><?php esc_html_e( 'Email', 'game-changers' ); ?></label>
        <input type="email" id="gc_email" name="gc_email" required>
      </div>
      <button type="submit" class="gc-form-submit"><?php esc_html_e( 'Get the 7-Day Activation Ebook', 'game-changers' ); ?></button>
    </form>
  </div>

  <div class="gc-ebook-path">
    <article>
      <h3><?php esc_html_e( '1. Free membership', 'game-changers' ); ?></h3>
      <p><?php esc_html_e( 'Join free membership to unlock community access and the 30-day short nuggets path that follows your activation week.', 'game-changers' ); ?></p>
      <a class="gc-button gc-button-green" href="<?php echo esc_url( $free_join ); ?>"><?php esc_html_e( 'Join free membership', 'game-changers' ); ?></a>
    </article>
    <article>
      <h3><?php esc_html_e( '2. 30-day short nuggets', 'game-changers' ); ?></h3>
      <p><?php esc_html_e( 'Daily short leadership nuggets that build discipline after your 7-day activation — designed for free members who want consistency without overwhelm.', 'game-changers' ); ?></p>
      <a class="gc-button gc-button-outline" href="<?php echo esc_url( is_user_logged_in() ? $hub : $free_join ); ?>">
        <?php echo is_user_logged_in() ? esc_html__( 'Open daily nuggets', 'game-changers' ) : esc_html__( 'Join free to get nuggets', 'game-changers' ); ?>
      </a>
    </article>
  </div>

  <div class="gc-ebook-benefits">
    <h3><?php esc_html_e( 'Why grow into Premium membership', 'game-changers' ); ?></h3>
    <p style="margin:0 0 10px;opacity:.9;font-size:14px;line-height:1.6;"><?php esc_html_e( 'Beyond daily leadership practice, Premium is for members who want deeper tools, recognition, and long-term growth — including learning beyond leadership.', 'game-changers' ); ?></p>
    <ul>
      <li><?php esc_html_e( 'Game Changers books and manuals', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Leadership badges', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Game Changers badges', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Referral bonuses', 'game-changers' ); ?></li>
      <li><?php esc_html_e( '2-year leadership growth tool', 'game-changers' ); ?></li>
      <li><?php esc_html_e( 'Mentorship and pathways to learn new skills beyond leadership', 'game-changers' ); ?></li>
    </ul>
    <p style="margin:14px 0 0;"><a href="<?php echo esc_url( $premium ); ?>"><?php esc_html_e( 'Explore Premium membership →', 'game-changers' ); ?></a></p>
  </div>
</div>
<?php get_footer(); ?>
