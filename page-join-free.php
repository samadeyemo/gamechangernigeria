<?php
/**
 * Template Name: Join the Movement
 * Dedicated free membership — separate from Academy enlistments.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$gc_refcode = '';
if ( ! empty( $_GET['refcode'] ) ) {
	$gc_refcode = sanitize_text_field( wp_unslash( $_GET['refcode'] ) );
} elseif ( ! empty( $_GET['ref'] ) ) {
	$gc_refcode = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
}

$gc_status = isset( $_GET['gc_free'] ) ? sanitize_key( wp_unslash( $_GET['gc_free'] ) ) : '';
$login_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : home_url( '/premium-login/' );
$ebook_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'free-ebook' ) : home_url( '/free-ebook/' );
$hub_url   = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
?>
<style>
.gc-join-free-page{padding:120px 5vw 80px;max-width:560px;margin:0 auto}
.gc-join-free-page .gc-kicker{color:var(--gc-green,#006b3f);font-size:11px;letter-spacing:.14em;text-transform:uppercase;font-weight:700}
.gc-join-free-page h1{font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,40px);margin:10px 0 12px;color:var(--gc-ink,#0d1a0f)}
.gc-join-free-page .lead{color:var(--gc-muted,#66756a);line-height:1.7;margin:0 0 28px}
.gc-join-free-card{background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:28px 24px}
.gc-join-free-card .gc-form-row{margin-bottom:16px}
.gc-join-free-card label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;letter-spacing:.04em;text-transform:uppercase}
.gc-join-free-card input,.gc-join-free-card select{width:100%;min-height:46px;padding:0 14px;border:1px solid rgba(13,26,15,.18);border-radius:8px;font-size:15px}
.gc-join-free-card .gc-form-row--split{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:560px){.gc-join-free-card .gc-form-row--split{grid-template-columns:1fr}}
.gc-join-free-card .gc-form-submit{width:100%;min-height:50px;border:0;border-radius:8px;background:var(--gc-gold,#c8992a);color:var(--gc-ink,#0d1a0f);font-weight:800;cursor:pointer;text-transform:uppercase;letter-spacing:.06em;font-size:12px;margin-top:8px}
.gc-join-free-notice{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:18px;line-height:1.5}
.gc-join-free-notice.ok{background:rgba(0,140,81,.1);color:#006b3f;border:1px solid rgba(0,140,81,.25)}
.gc-join-free-notice.err{background:rgba(200,40,40,.08);color:#a02222;border:1px solid rgba(200,40,40,.2)}
.gc-join-free-foot{margin-top:20px;font-size:14px;color:#66756a;text-align:center}
.gc-join-free-foot a{color:var(--gc-green,#006b3f);font-weight:700}
</style>
<main class="gc-join-free-page">
  <span class="gc-kicker"><?php esc_html_e( 'Free membership', 'game-changers' ); ?></span>
  <h1><?php esc_html_e( 'Join the Movement', 'game-changers' ); ?></h1>
  <p class="lead"><?php esc_html_e( 'Create your free account for the 7-day activation path, 30-day short nuggets, and Member Hub. The same email and password later unlock Premium and Inner Circle — no new login.', 'game-changers' ); ?></p>

  <?php if ( 'success' === $gc_status ) : ?>
    <div class="gc-join-free-notice ok">
      <?php esc_html_e( 'Welcome. Your free membership is active.', 'game-changers' ); ?>
      <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:10px;">
        <a class="gc-button gc-button-green" href="<?php echo esc_url( $hub_url ); ?>"><?php esc_html_e( 'Open Member Hub', 'game-changers' ); ?></a>
        <a class="gc-button gc-button-outline" href="<?php echo esc_url( $ebook_url ); ?>"><?php esc_html_e( '7-Day Ebook', 'game-changers' ); ?></a>
      </p>
    </div>
  <?php elseif ( 'error' === $gc_status ) : ?>
    <div class="gc-join-free-notice err"><?php esc_html_e( 'Please complete required fields with a valid email and matching passwords (min. 8 characters).', 'game-changers' ); ?></div>
  <?php elseif ( 'exists' === $gc_status ) : ?>
    <div class="gc-join-free-notice err"><?php printf( wp_kses_post( __( 'That email is already registered. %s instead.', 'game-changers' ) ), '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'game-changers' ) . '</a>' ); ?></div>
  <?php endif; ?>

  <?php if ( 'success' !== $gc_status ) : ?>
  <div class="gc-join-free-card">
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <input type="hidden" name="action" value="gc_submit_join_free">
      <input type="hidden" name="gc_redirect" value="<?php echo esc_url( get_permalink() ); ?>">
      <?php wp_nonce_field( 'gc_join_free', 'gc_join_free_nonce' );
    if ( $gc_refcode ) {
      echo '<input type="hidden" name="gc_refcode" value="' . esc_attr( $gc_refcode ) . '">';
    } ?>
      <div class="gc-form-row">
        <label for="gc_name"><?php esc_html_e( 'Full Name', 'game-changers' ); ?> *</label>
        <input type="text" id="gc_name" name="gc_name" required autocomplete="name">
      </div>
      <div class="gc-form-row">
        <label for="gc_email"><?php esc_html_e( 'Email', 'game-changers' ); ?> *</label>
        <input type="email" id="gc_email" name="gc_email" required autocomplete="email">
      </div>
      <div class="gc-form-row">
        <label for="gc_phone"><?php esc_html_e( 'Phone / WhatsApp', 'game-changers' ); ?></label>
        <input type="tel" id="gc_phone" name="gc_phone" autocomplete="tel">
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_state"><?php esc_html_e( 'State', 'game-changers' ); ?></label>
          <select id="gc_state" name="gc_state">
            <option value=""><?php esc_html_e( 'Select state', 'game-changers' ); ?></option>
            <?php if ( function_exists( 'gc_ng_states' ) ) : foreach ( gc_ng_states() as $state ) : ?>
              <option value="<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $state ); ?></option>
            <?php endforeach; endif; ?>
          </select>
        </div>
        <div>
          <label for="gc_town"><?php esc_html_e( 'Town / City', 'game-changers' ); ?></label>
          <input type="text" id="gc_town" name="gc_town">
        </div>
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_password"><?php esc_html_e( 'Create password', 'game-changers' ); ?> *</label>
          <input type="password" id="gc_password" name="gc_password" required minlength="8" autocomplete="new-password">
        </div>
        <div>
          <label for="gc_password_confirm"><?php esc_html_e( 'Confirm password', 'game-changers' ); ?> *</label>
          <input type="password" id="gc_password_confirm" name="gc_password_confirm" required minlength="8" autocomplete="new-password">
        </div>
      </div>
      <p style="font-size:13px;color:#66756a;margin:0 0 12px;"><?php esc_html_e( 'This login works for free, Premium, and Inner Circle — you will not create a second account.', 'game-changers' ); ?></p>
      <button type="submit" class="gc-form-submit"><?php esc_html_e( 'Join the Movement — free', 'game-changers' ); ?></button>
    </form>
  </div>
  <?php endif; ?>
  <p class="gc-join-free-foot"><?php esc_html_e( 'Already registered?', 'game-changers' ); ?> <a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'game-changers' ); ?></a></p>
</main>
<?php get_footer(); ?>
