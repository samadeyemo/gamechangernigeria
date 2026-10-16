<?php
/**
 * Template Name: Inner Circle
 * Exclusive dashboard for Tier 3+ leaders.
 * Same email/password as premium — no new account.
 * Guests can verify with email+password or Tier 3+ certificate code.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Guest verify / login (no new account) ---------- */
$gc_ic_errors = array();
if ( ! is_user_logged_in() && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['gc_ic_access_nonce'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_ic_access_nonce'] ) ), 'gc_ic_access' ) ) {
		$gc_ic_errors[] = 'Session expired. Please try again.';
	} else {
		$method = sanitize_key( wp_unslash( $_POST['gc_ic_method'] ?? 'login' ) );
		if ( 'cert' === $method ) {
			$code  = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['gc_ic_cert'] ?? '' ) ) ) );
			$email = sanitize_email( wp_unslash( $_POST['gc_ic_cert_email'] ?? '' ) );
			$tier  = 0;
			$uid   = 0;
			if ( $code && function_exists( 'gc_lookup_certificate' ) ) {
				$reg = gc_lookup_certificate( $code );
				if ( is_array( $reg ) ) {
					$tier = (int) ( $reg['tier'] ?? 0 );
					if ( ! empty( $reg['user_id'] ) ) {
						$uid = absint( $reg['user_id'] );
					} elseif ( ! empty( $reg['email'] ) ) {
						$u = get_user_by( 'email', $reg['email'] );
						if ( $u ) {
							$uid = (int) $u->ID;
						}
					}
				}
			}
			if ( ! $tier && $code && preg_match( '/^GCN-(\d{4})-(\d{5,})-T(\d+)$/', $code, $m ) ) {
				$uid  = absint( $m[2] );
				$tier = absint( $m[3] );
				$user = get_user_by( 'id', $uid );
				$expected = 'GCN-' . $m[1] . '-' . str_pad( (string) $uid, 5, '0', STR_PAD_LEFT ) . '-T' . $tier;
				if ( ! $user || strtoupper( $expected ) !== $code ) {
					$uid  = 0;
					$tier = 0;
				}
			}
			if ( $tier < 3 || ! $uid ) {
				$gc_ic_errors[] = 'That certificate is not valid for Inner Circle (Tier 3+ required), or the code was not found.';
			} else {
				$user = get_user_by( 'id', $uid );
				if ( ! $user ) {
					$gc_ic_errors[] = 'No member account is linked to that certificate. Use your membership email and password instead.';
				} elseif ( $email && strtolower( $user->user_email ) !== strtolower( $email ) ) {
					$gc_ic_errors[] = 'Email does not match the certificate holder. Use the email on your membership account.';
				} else {
					// Ensure tier meta and premium role, then log in
					$current = (int) get_user_meta( $uid, 'gc_premium_tier', true );
					if ( $tier > $current ) {
						update_user_meta( $uid, 'gc_premium_tier', $tier );
					}
					$user_obj = new WP_User( $uid );
					$user_obj->add_role( 'premium_member' );
					if ( function_exists( 'gc_mark_inner_circle' ) ) {
						gc_mark_inner_circle( $uid, true );
					} else {
						update_user_meta( $uid, 'gc_inner_circle', 1 );
					}
					wp_set_current_user( $uid );
					wp_set_auth_cookie( $uid, true );
					wp_safe_redirect( get_permalink() );
					exit;
				}
			}
		} else {
			// Email + password — same account as premium / free, no new registration
			$login = sanitize_text_field( wp_unslash( $_POST['gc_ic_email'] ?? '' ) );
			$pass  = wp_unslash( $_POST['gc_ic_password'] ?? '' );
			if ( ! $login || ! $pass ) {
				$gc_ic_errors[] = 'Enter your membership email and password.';
			} else {
				$creds = array(
					'user_login'    => $login,
					'user_password' => $pass,
					'remember'      => true,
				);
				// Allow email as login
				if ( is_email( $login ) ) {
					$by = get_user_by( 'email', $login );
					if ( $by ) {
						$creds['user_login'] = $by->user_login;
					}
				}
				$user = wp_signon( $creds, is_ssl() );
				if ( is_wp_error( $user ) ) {
					$gc_ic_errors[] = $user->get_error_message();
				} else {
					wp_safe_redirect( get_permalink() );
					exit;
				}
			}
		}
	}
}

get_header();

if ( ! is_user_logged_in() ) {
	$login_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : home_url( '/premium-login/' );
	?>
	<style>
	.gc-ic-shell{min-height:100vh;background:linear-gradient(165deg,#070f0b 0%,#0d1f16 40%,#122a1c 100%);color:#f5f2ec;padding:0 0 80px}
	.gc-ic-top{padding:100px 5vw 40px;border-bottom:1px solid rgba(232,184,64,.18)}
	.gc-ic-top-inner{max-width:560px;margin:0 auto}
	.gc-ic-kicker{font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#e8b840;font-weight:700}
	.gc-ic-top h1{font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,42px);margin:10px 0 8px;color:#fff;line-height:1.15}
	.gc-ic-top h1 em{font-style:italic;color:#e8b840}
	.gc-ic-body{max-width:480px;margin:0 auto;padding:36px 5vw 0}
	.gc-ic-panel{background:rgba(255,255,255,.05);border:1px solid rgba(232,184,64,.22);border-radius:16px;padding:28px 24px}
	.gc-ic-panel label{display:block;font-size:13px;font-weight:600;margin:0 0 6px;color:rgba(245,242,236,.85)}
	.gc-ic-panel input[type=text],.gc-ic-panel input[type=email],.gc-ic-panel input[type=password]{width:100%;padding:11px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.18);background:rgba(0,0,0,.25);color:#fff;margin-bottom:14px;box-sizing:border-box}
	.gc-ic-panel .gc-ic-err{background:rgba(180,40,40,.25);border:1px solid rgba(255,120,100,.4);padding:10px 12px;border-radius:8px;margin-bottom:14px;font-size:13px;color:#ffc9c0}
	.gc-ic-tabs{display:flex;gap:8px;margin-bottom:18px}
	.gc-ic-tabs button{flex:1;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:transparent;color:rgba(245,242,236,.7);cursor:pointer;font-weight:600;font-size:13px}
	.gc-ic-tabs button.active{background:rgba(232,184,64,.15);border-color:rgba(232,184,64,.4);color:#e8b840}
	</style>
	<main class="gc-ic-shell">
		<header class="gc-ic-top">
			<div class="gc-ic-top-inner">
				<div class="gc-ic-kicker">Game Changers Nigeria</div>
				<h1>Leadership <em>Inner Circle</em></h1>
				<p style="font-size:14px;color:rgba(245,242,236,.72);line-height:1.6;margin:0;">Use the same email and password as your membership — or verify with a Tier 3+ certificate code. No new account.</p>
			</div>
		</header>
		<div class="gc-ic-body">
			<div class="gc-ic-panel">
				<?php if ( ! empty( $gc_ic_errors ) ) : ?>
					<div class="gc-ic-err"><?php echo esc_html( implode( ' ', $gc_ic_errors ) ); ?></div>
				<?php endif; ?>
				<div class="gc-ic-tabs" role="tablist">
					<button type="button" class="active" data-ic-tab="login">Email &amp; password</button>
					<button type="button" data-ic-tab="cert">Certificate code</button>
				</div>
				<form method="post" id="gc-ic-login-form">
					<?php wp_nonce_field( 'gc_ic_access', 'gc_ic_access_nonce' ); ?>
					<input type="hidden" name="gc_ic_method" id="gc-ic-method" value="login">
					<div data-ic-panel="login">
						<label for="gc_ic_email">Membership email</label>
						<input type="email" name="gc_ic_email" id="gc_ic_email" required placeholder="you@example.com" autocomplete="username">
						<label for="gc_ic_password">Password</label>
						<input type="password" name="gc_ic_password" id="gc_ic_password" required placeholder="Your membership password" autocomplete="current-password">
						<button type="submit" class="gc-button gc-button-gold" style="width:100%;margin-top:6px;">Open Inner Circle</button>
					</div>
					<div data-ic-panel="cert" style="display:none;">
						<label for="gc_ic_cert">Tier 3+ certificate code</label>
						<input type="text" name="gc_ic_cert" id="gc_ic_cert" placeholder="e.g. GCN-2026-00001-T3" style="text-transform:uppercase;">
						<label for="gc_ic_cert_email">Email on your membership (optional check)</label>
						<input type="email" name="gc_ic_cert_email" id="gc_ic_cert_email" placeholder="you@example.com">
						<button type="submit" class="gc-button gc-button-gold" style="width:100%;margin-top:6px;">Verify &amp; enter</button>
					</div>
				</form>
				<p style="margin:18px 0 0;font-size:12px;color:rgba(245,242,236,.55);text-align:center;">
					<a href="<?php echo esc_url( $login_url ); ?>" style="color:#e8b840;">Premium login</a>
					· Tier 3 (Impact Architect) or higher required
				</p>
			</div>
		</div>
	</main>
	<script>
	(function(){
		var tabs = document.querySelectorAll('[data-ic-tab]');
		var method = document.getElementById('gc-ic-method');
		var loginPanel = document.querySelector('[data-ic-panel="login"]');
		var certPanel = document.querySelector('[data-ic-panel="cert"]');
		var email = document.getElementById('gc_ic_email');
		var pass = document.getElementById('gc_ic_password');
		var cert = document.getElementById('gc_ic_cert');
		tabs.forEach(function(btn){
			btn.addEventListener('click', function(){
				tabs.forEach(function(b){ b.classList.remove('active'); });
				btn.classList.add('active');
				var t = btn.getAttribute('data-ic-tab');
				method.value = t;
				if (t === 'cert') {
					loginPanel.style.display = 'none';
					certPanel.style.display = 'block';
					if (email) email.removeAttribute('required');
					if (pass) pass.removeAttribute('required');
					if (cert) cert.setAttribute('required', 'required');
				} else {
					loginPanel.style.display = 'block';
					certPanel.style.display = 'none';
					if (email) email.setAttribute('required', 'required');
					if (pass) pass.setAttribute('required', 'required');
					if (cert) cert.removeAttribute('required');
				}
			});
		});
	})();
	</script>
	<?php
	get_footer();
	return;
}

$uid   = get_current_user_id();
$user  = wp_get_current_user();
$name  = $user->display_name ?: $user->user_email;

/* Robust tier: never show locked for Tier 3+ just because role meta is missing */
$tier = 0;
if ( function_exists( 'gc_get_user_premium_tier' ) ) {
	$tier = max( $tier, (int) gc_get_user_premium_tier( $uid ) );
}
if ( function_exists( 'gc_user_membership_tier' ) ) {
	$tier = max( $tier, (int) gc_user_membership_tier( $uid ) );
}
$tier = max( $tier, (int) get_user_meta( $uid, 'gc_premium_tier', true ) );
if ( user_can( $uid, 'administrator' ) && function_exists( 'gc_tiers' ) ) {
	$keys = array_keys( gc_tiers() );
	if ( $keys ) {
		$tier = max( $tier, (int) max( $keys ) );
	}
}

$eligible = $tier >= 3;
$is_ic    = (bool) get_user_meta( $uid, 'gc_inner_circle', true );
if ( $eligible && ! $is_ic ) {
	// Auto-admit Tier 3+ to Inner Circle for dashboard access
	if ( function_exists( 'gc_mark_inner_circle' ) ) {
		gc_mark_inner_circle( $uid, true );
	} else {
		update_user_meta( $uid, 'gc_inner_circle', 1 );
	}
	$is_ic = true;
}
if ( function_exists( 'gc_user_is_inner_circle' ) && $eligible ) {
	// Soft-call so lesson progress can still promote; do not block view on failure
	gc_user_is_inner_circle( $uid );
	$is_ic = $is_ic || (bool) get_user_meta( $uid, 'gc_inner_circle', true );
}

$wa  = get_option( 'gc_inner_circle_whatsapp', '' );
$tag = get_user_meta( $uid, 'gc_inner_circle_tag', true );
if ( $is_ic && ! $tag ) {
	$tag = 'IC-' . strtoupper( substr( md5( (string) $uid ), 0, 6 ) );
	update_user_meta( $uid, 'gc_inner_circle_tag', $tag );
}
$dash = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
$tier_label = '';
if ( function_exists( 'gc_tiers' ) ) {
	$tiers = gc_tiers();
	if ( isset( $tiers[ $tier ]['name'] ) ) {
		$tier_label = $tiers[ $tier ]['name'];
	}
}
?>
<style>
.gc-ic-shell{min-height:100vh;background:linear-gradient(165deg,#070f0b 0%,#0d1f16 40%,#122a1c 100%);color:#f5f2ec;padding:0 0 80px}
.gc-ic-top{padding:100px 5vw 40px;border-bottom:1px solid rgba(232,184,64,.18)}
.gc-ic-top-inner{max-width:1080px;margin:0 auto;display:flex;flex-wrap:wrap;gap:24px;align-items:flex-end;justify-content:space-between}
.gc-ic-kicker{font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#e8b840;font-weight:700}
.gc-ic-top h1{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,48px);margin:10px 0 8px;color:#fff;line-height:1.15}
.gc-ic-top h1 em{font-style:italic;color:#e8b840}
.gc-ic-meta{font-size:14px;color:rgba(245,242,236,.72);line-height:1.6;max-width:42ch}
.gc-ic-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;background:rgba(232,184,64,.12);border:1px solid rgba(232,184,64,.35);font-size:13px;font-weight:600;color:#e8b840}
.gc-ic-body{max-width:1080px;margin:0 auto;padding:36px 5vw 0}
.gc-ic-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin:28px 0}
.gc-ic-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:22px 20px;backdrop-filter:blur(8px)}
.gc-ic-card h3{margin:0 0 8px;font-size:16px;font-weight:700;color:#e8b840}
.gc-ic-card p{margin:0;font-size:14px;line-height:1.55;color:rgba(245,242,236,.75)}
.gc-ic-panel{background:rgba(255,255,255,.05);border:1px solid rgba(232,184,64,.22);border-radius:16px;padding:28px 24px;margin-top:8px}
.gc-ic-panel h2{font-family:'Playfair Display',serif;font-size:26px;margin:0 0 12px;color:#fff}
.gc-ic-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px}
.gc-ic-actions .gc-button{min-height:46px}
.gc-ic-locked{opacity:.85;text-align:center;padding:48px 20px}
.gc-ic-locked h2{font-family:'Playfair Display',serif;color:#fff}
@media(max-width:640px){.gc-ic-top{padding:90px 4vw 32px}.gc-ic-body{padding:28px 4vw 0}}
</style>
<main class="gc-ic-shell">
  <header class="gc-ic-top">
    <div class="gc-ic-top-inner">
      <div>
        <div class="gc-ic-kicker">Game Changers Nigeria</div>
        <h1>Leadership <em>Inner Circle</em></h1>
        <p class="gc-ic-meta">A private tier for Impact Architects and above — same login, elevated access, peer leadership, and national influence.</p>
      </div>
      <?php if ( $eligible ) : ?>
        <div class="gc-ic-badge"><?php echo esc_html( $tag ? 'ID ' . $tag : 'Member' ); ?> · Tier <?php echo (int) $tier; ?><?php echo $tier_label ? ' · ' . esc_html( $tier_label ) : ''; ?></div>
      <?php endif; ?>
    </div>
  </header>

  <div class="gc-ic-body">
    <?php if ( ! $eligible ) : ?>
      <div class="gc-ic-panel gc-ic-locked">
        <h2>Reserved for Tier 3+</h2>
        <p style="color:rgba(245,242,236,.75);max-width:40ch;margin:0 auto 20px;line-height:1.6;">Inner Circle opens after you reach Impact Architect (Tier 3) or higher. Your current tier is <strong style="color:#fff;"><?php echo (int) $tier; ?></strong>. Continue your pathway on the premium dashboard.</p>
        <a class="gc-button gc-button-gold" href="<?php echo esc_url( $dash ); ?>">Open premium dashboard</a>
      </div>
    <?php else : ?>
      <p style="margin:0 0 4px;font-size:15px;color:rgba(245,242,236,.85);">Welcome back, <strong style="color:#fff;"><?php echo esc_html( $name ); ?></strong>.</p>
      <div class="gc-ic-grid">
        <article class="gc-ic-card"><h3>Strategic peer room</h3><p>Connect with leaders who share responsibility for chapter growth and national outcomes.</p></article>
        <article class="gc-ic-card"><h3>Priority pathways</h3><p>Early access to advanced materials, briefings, and leadership exercises.</p></article>
        <article class="gc-ic-card"><h3>Recognition</h3><p>Inner Circle standing after Tier 3 pathway readiness — visible in your membership identity.</p></article>
        <article class="gc-ic-card"><h3>One account</h3><p>Free → Premium → Inner Circle on the same email and password. No new registration.</p></article>
        <article class="gc-ic-card"><h3>Referral impact</h3><p>Stronger referral value as you invite serious leaders into structured growth.</p></article>
        <article class="gc-ic-card"><h3>National influence</h3><p>Space to shape conversations that move beyond outrage into organised action.</p></article>
      </div>
      <div class="gc-ic-panel">
        <h2>Your next step</h2>
        <p style="margin:0;color:rgba(245,242,236,.78);line-height:1.6;max-width:48ch;">Stay active on your premium dashboard, complete Tier 3 materials, and join the private channel when available.</p>
        <div class="gc-ic-actions">
          <a class="gc-button gc-button-gold" href="<?php echo esc_url( $dash ); ?>">Premium dashboard</a>
          <?php if ( $wa ) : ?>
            <a class="gc-button gc-button-outline" style="border-color:rgba(232,184,64,.4);color:#e8b840;" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Inner Circle WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
