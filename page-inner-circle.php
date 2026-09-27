<?php
/**
 * Template Name: Inner Circle
 * Tier 3+ only — same login, unique professional dashboard.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
}
nocache_headers();

$login = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : wp_login_url();
$hub   = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
$join  = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), $login ) );
	exit;
}

$user  = wp_get_current_user();
$uid   = $user->ID;
$tier  = function_exists( 'gc_user_membership_tier' ) ? (int) gc_user_membership_tier( $uid ) : (int) get_user_meta( $uid, 'gc_premium_tier', true );
$is_ic = function_exists( 'gc_user_is_inner_circle' ) ? gc_user_is_inner_circle( $uid ) : (bool) get_user_meta( $uid, 'gc_inner_circle', true );
$eligible = $tier >= 3 || current_user_can( 'administrator' );

// Optional: require tier-3 certificate if theme tracks it
$has_t3_cert = true;
if ( function_exists( 'gc_user_has_tier_certificate' ) ) {
	$has_t3_cert = gc_user_has_tier_certificate( $uid, 3 );
} else {
	$certs = (array) get_user_meta( $uid, 'gc_completed_certificates', true );
	if ( ! empty( $certs ) && ! in_array( 3, array_map( 'intval', $certs ), true ) ) {
		// soft: still allow if tier >= 3; certificate is encouraged
		$has_t3_cert = $tier >= 3;
	}
}

get_header();
$wa = get_option( 'gc_inner_circle_whatsapp', '' );
$points = (int) get_user_meta( $uid, 'gc_total_points', true );
$tag = get_user_meta( $uid, 'gc_inner_circle_tag', true );
if ( ! $tag && $is_ic && function_exists( 'gc_generate_premium_referral_tag' ) ) {
	$tag = 'IC-' . substr( gc_generate_premium_referral_tag(), 3 );
	update_user_meta( $uid, 'gc_inner_circle_tag', $tag );
}
?>
<style>
.gc-ic-page{padding:110px 5vw 80px;max-width:1100px;margin:0 auto}
.gc-ic-hero{background:linear-gradient(145deg,#0a1a10 0%,#1a2e22 50%,#0d1a0f 100%);color:#f5f2ec;border-radius:18px;padding:36px 28px;margin-bottom:28px;position:relative;overflow:hidden}
.gc-ic-hero::after{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(232,184,64,.08)}
.gc-ic-kicker{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#e8b840;font-weight:700}
.gc-ic-hero h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);margin:10px 0 12px;color:#fff}
.gc-ic-hero p{opacity:.88;max-width:52ch;line-height:1.65;margin:0}
.gc-ic-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:28px}
.gc-ic-card{background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:14px;padding:22px}
.gc-ic-card h3{margin:0 0 8px;font-size:17px}
.gc-ic-card p{margin:0;color:#66756a;font-size:14px;line-height:1.6}
.gc-ic-stat{font-size:28px;font-weight:800;color:#0d1a0f;margin-top:6px}
.gc-ic-gate{background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:28px;text-align:center;max-width:640px;margin:0 auto}
.gc-ic-gate h2{font-family:'Playfair Display',serif;margin:0 0 12px}
.gc-ic-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px;justify-content:center}
.gc-ic-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:rgba(232,184,64,.15);color:#e8b840;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
</style>

<main class="gc-ic-page">
<?php if ( ! $eligible ) : ?>
  <div class="gc-ic-gate">
    <span class="gc-ic-kicker" style="color:#006b3f;"><?php esc_html_e( 'Inner Circle', 'game-changers' ); ?></span>
    <h2><?php esc_html_e( 'Reserved for Tier 3 and above', 'game-changers' ); ?></h2>
    <p style="color:#66756a;line-height:1.65;"><?php esc_html_e( 'Inner Circle is for members who have reached Impact Architect (Tier 3) or higher — ideally after completing the Tier 3 pathway / certificate. You use the same email and password; no new account.', 'game-changers' ); ?></p>
    <p style="margin:14px 0 0;font-size:14px;color:#66756a;"><?php printf( esc_html__( 'Your current tier: %d', 'game-changers' ), (int) $tier ); ?></p>
    <div class="gc-ic-actions">
      <a class="gc-button gc-button-gold" href="<?php echo esc_url( add_query_arg( 'tier', 3, $join ) ); ?>"><?php esc_html_e( 'Upgrade to Tier 3', 'game-changers' ); ?></a>
      <a class="gc-button gc-button-outline" href="<?php echo esc_url( $hub ); ?>"><?php esc_html_e( 'Back to Member Hub', 'game-changers' ); ?></a>
    </div>
  </div>
<?php else : ?>

  <div class="gc-ic-hero">
    <span class="gc-ic-kicker"><?php esc_html_e( 'Inner Circle', 'game-changers' ); ?></span>
    <h1><?php printf( esc_html__( 'Welcome, %s', 'game-changers' ), esc_html( $user->display_name ) ); ?></h1>
    <p><?php esc_html_e( 'A professional space for Tier 3+ leaders — deeper collaboration, earning pathways, and influence beyond the standard Member Hub.', 'game-changers' ); ?></p>
    <?php if ( $is_ic ) : ?>
      <div style="margin-top:18px;"><span class="gc-ic-badge"><?php esc_html_e( 'Active member', 'game-changers' ); ?><?php echo $tag ? ' · ' . esc_html( $tag ) : ''; ?></span></div>
    <?php endif; ?>
  </div>

  <div class="gc-ic-grid">
    <div class="gc-ic-card">
      <div class="gc-ic-kicker" style="color:#006b3f;"><?php esc_html_e( 'Tier', 'game-changers' ); ?></div>
      <div class="gc-ic-stat"><?php echo (int) $tier; ?></div>
      <p><?php echo function_exists( 'gc_tier_label' ) ? esc_html( gc_tier_label( $tier ) ) : esc_html__( 'Impact pathway', 'game-changers' ); ?></p>
    </div>
    <div class="gc-ic-card">
      <div class="gc-ic-kicker" style="color:#006b3f;"><?php esc_html_e( 'Points', 'game-changers' ); ?></div>
      <div class="gc-ic-stat"><?php echo (int) $points; ?></div>
      <p><?php esc_html_e( 'Growth and referral activity on this same account.', 'game-changers' ); ?></p>
    </div>
    <div class="gc-ic-card">
      <div class="gc-ic-kicker" style="color:#006b3f;"><?php esc_html_e( 'Login', 'game-changers' ); ?></div>
      <div class="gc-ic-stat" style="font-size:16px;word-break:break-all;"><?php echo esc_html( $user->user_email ); ?></div>
      <p><?php esc_html_e( 'Same password as free / Premium — one identity.', 'game-changers' ); ?></p>
    </div>
  </div>

  <h2 style="font-family:'Playfair Display',serif;margin:0 0 14px;"><?php esc_html_e( 'Inner Circle benefits', 'game-changers' ); ?></h2>
  <div class="gc-ic-grid">
    <article class="gc-ic-card"><h3><?php esc_html_e( 'Earn with integrity', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Structured referral and contribution pathways tied to your existing membership tag — not a separate hustle login.', 'game-changers' ); ?></p></article>
    <article class="gc-ic-card"><h3><?php esc_html_e( 'Peer leadership room', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Connect with other Tier 3+ members building real interventions across states.', 'game-changers' ); ?></p></article>
    <article class="gc-ic-card"><h3><?php esc_html_e( 'Priority briefings', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Access strategic updates, opportunities, and tools ahead of the general hub.', 'game-changers' ); ?></p></article>
    <article class="gc-ic-card"><h3><?php esc_html_e( 'Mentorship depth', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Pathways to learn beyond leadership — skills, influence, and long-horizon growth.', 'game-changers' ); ?></p></article>
    <article class="gc-ic-card"><h3><?php esc_html_e( 'Recognition', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Inner Circle tag and standing after Tier 3 pathway / certificate readiness.', 'game-changers' ); ?></p></article>
    <article class="gc-ic-card"><h3><?php esc_html_e( 'One account', 'game-changers' ); ?></h3><p><?php esc_html_e( 'Free → Premium → Inner Circle on the same email and password. No re-registration.', 'game-changers' ); ?></p></article>
  </div>

  <div class="gc-ic-actions" style="justify-content:flex-start;margin-top:8px;">
    <?php if ( $wa ) : ?>
      <a class="gc-button gc-button-gold" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Inner Circle WhatsApp', 'game-changers' ); ?></a>
    <?php endif; ?>
    <a class="gc-button gc-button-outline" href="<?php echo esc_url( $hub ); ?>"><?php esc_html_e( 'Member Hub', 'game-changers' ); ?></a>
    <?php if ( ! $is_ic && function_exists( 'gc_user_is_inner_circle' ) ) : ?>
      <p style="width:100%;font-size:13px;color:#66756a;margin:8px 0 0;"><?php esc_html_e( 'Your tier qualifies. An admin may activate full Inner Circle status, or it activates with your Tier 3+ membership pathway.', 'game-changers' ); ?></p>
    <?php endif; ?>
  </div>
<?php endif; ?>
</main>
<?php get_footer(); ?>
