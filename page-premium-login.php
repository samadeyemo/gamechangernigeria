<?php
if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); } nocache_headers();
if ( is_user_logged_in() ) { wp_safe_redirect( !empty($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : gc_page_url( 'premium-dashboard' ) ); exit; } $gc_login_errors = array(); if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['gc_login_nonce_field'] ) ) { if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_login_nonce_field'] ) ), 'gc_login_action' ) ) { $creds = array( 'user_login' => sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ), 'user_password' => wp_unslash( $_POST['pwd'] ?? '' ), 'remember' => ! empty( $_POST['rememberme'] ), ); $user = wp_signon( $creds, false ); if ( is_wp_error( $user ) ) { $gc_login_errors[] = $user->get_error_message(); } else { wp_safe_redirect( !empty($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : gc_page_url( 'premium-dashboard' ) ); exit; } } else { $gc_login_errors[] = __( 'Security check failed. Please try again.', 'game-changers' ); } } get_header(); ?>
<main class="gc-premium-login-page">
<div class="gc-container gc-premium-login-grid">
	<section class="gc-premium-login-intro">
		<span class="gc-kicker">Member Hub</span>
		<h1>Welcome back,<br><em>Game Changer.</em></h1>
		<p>Log in to track your learning progress, connect with your chapter, and download your certificates.</p>
		<div class="gc-login-diamonds" aria-hidden="true">
			<span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
		</div>
	</section>
	<section class="gc-premium-login-card">
		<span class="gc-login-card-label">Member Login</span>
		<h2>Log in to your dashboard.</h2>
		<p>Use the email and password from free membership or Premium enrolment. Free members can upgrade to Premium from the dashboard without a new account.</p>
		<?php if ( ! empty( $gc_login_errors ) ) : ?>
			<div class="gc-form-notice gc-form-notice--err">
				<?php foreach ( $gc_login_errors as $error ) : ?>
					<p style="margin:0;"><?php echo wp_kses_post( $error ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<form name="loginform" id="loginform" method="post" class="gc-form gc-premium-login-form">
			<?php wp_nonce_field( 'gc_login_action', 'gc_login_nonce_field' ); ?><input type="hidden" name="redirect_to" value="<?php echo esc_url( !empty($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : '' ); ?>">
			<div class="gc-form-row">
				<label for="user_login"><?php esc_html_e( 'Email or Username', 'game-changers' ); ?></label>
				<input type="text" name="log" id="user_login" placeholder="you@example.com" required autocomplete="username">
			</div>
			<div class="gc-form-row">
				<div class="gc-premium-login-label-row">
					<label for="user_pass"><?php esc_html_e( 'Password', 'game-changers' ); ?></label>
					<a href="<?php echo esc_url( wp_lostpassword_url( gc_page_url( 'premium-login' ) ) ); ?>" class="gc-text-link"><?php esc_html_e( 'Forgot?', 'game-changers' ); ?></a>
				</div>
				<div class="gc-pw-wrap">
					<input type="password" name="pwd" id="user_pass" placeholder="••••••••" required autocomplete="current-password">
					<button type="button" class="gc-pw-toggle" id="gc-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'game-changers' ); ?>">Show</button>
				</div>
			</div>
			<label class="gc-premium-login-remember">
				<input name="rememberme" type="checkbox" id="rememberme" value="forever">
				<span><?php esc_html_e( 'Keep me signed in', 'game-changers' ); ?></span>
			</label>
			<button type="submit" name="wp-submit" id="wp-submit" class="gc-form-submit"><?php esc_html_e( 'Log In to Dashboard', 'game-changers' ); ?></button>
		</form>
		<div class="gc-premium-login-foot">
			<span><?php esc_html_e( "Don't have access?", 'game-changers' ); ?></span> <a href="<?php echo esc_url( gc_page_url( 'enlistments' ) ); ?>"><?php esc_html_e( 'Enlist here', 'game-changers' ); ?></a>
		</div>
	</section>
</div>
</main>
<style>
.gc-premium-login-page{padding:150px 0 90px;background:var(--gc-paper);min-height:78vh}
.gc-premium-login-grid{display:grid;grid-template-columns:1fr 460px;gap:70px;align-items:center}
.gc-premium-login-intro{position:relative}
.gc-premium-login-intro h1{font:900 clamp(46px,6vw,76px)/1.02 'Playfair Display',serif;margin:18px 0}
.gc-premium-login-intro h1 em{font-style:normal;color:var(--gc-green-3)}
.gc-premium-login-intro>p{max-width:560px;color:var(--gc-muted);font-size:17px;line-height:1.8}
.gc-premium-login-card{background:#fff;border:1px solid var(--gc-line);border-radius:18px;padding:34px;box-shadow:var(--gc-shadow)}
.gc-login-card-label{font:700 9px 'Space Mono',monospace;letter-spacing:.16em;color:var(--gc-green);text-transform:uppercase}
.gc-premium-login-card h2{font:900 30px 'Playfair Display',serif;margin:12px 0 5px}
.gc-premium-login-card>p{color:var(--gc-muted);font-size:13px;margin-bottom:24px}
.gc-premium-login-form .gc-form-row{margin-bottom:18px}
.gc-premium-login-label-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:7px}
.gc-premium-login-label-row label{margin:0!important}
.gc-pw-wrap{position:relative}
.gc-pw-wrap input{padding-right:64px!important}
.gc-pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;font:700 10px 'Space Mono',monospace;letter-spacing:.06em;text-transform:uppercase;color:var(--gc-green);padding:6px}
.gc-pw-toggle:hover{color:var(--gc-green-2)}
.gc-premium-login-remember{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gc-muted);cursor:pointer;margin-bottom:22px}
.gc-premium-login-remember input{accent-color:var(--gc-green)}
.gc-premium-login-form .gc-form-submit{width:100%}
.gc-premium-login-foot{border-top:1px solid var(--gc-line);margin-top:24px;padding-top:18px;color:var(--gc-muted);font-size:12px;text-align:center}
.gc-premium-login-foot a{color:var(--gc-green);font-weight:800;text-decoration:none}
@media(max-width:800px){
	.gc-premium-login-page{padding:120px 0 60px}
	.gc-premium-login-grid{grid-template-columns:1fr;gap:30px}
	.gc-premium-login-card{padding:24px}
	.gc-premium-login-intro h1{font-size:clamp(42px,12vw,58px)}
}
</style>
<script>
(function () {
	var btn = document.getElementById('gc-pw-toggle');
	var pw  = document.getElementById('user_pass');
	if (!btn || !pw) return;
	btn.addEventListener('click', function () {
		var showing = pw.type === 'text';
		pw.type = showing ? 'password' : 'text';
		btn.textContent = showing ? 'Show' : 'Hide';
	});
})();
</script>
<?php get_footer(); ?>
