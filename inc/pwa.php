<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'GC_DISABLE_INSTALL_APP', false );
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Makes the site installable as an app on mobile and desktop (Chrome,
 * Edge, Android, and "Add to Home Screen" on iOS Safari), with basic
 * offline support for pages the visitor has already opened.
 *
 * manifest.json and sw.js MUST be served from the domain root (not
 * /wp-content/themes/...) for the browser to register them with
 * root scope, so both are served through rewrite rules rather than
 * as static theme files.
 */

// ---- Root-scoped URLs for manifest.json and sw.js ----------------------
add_action( 'init', function () {
	add_rewrite_rule( '^manifest\.json$', 'index.php?gc_pwa=manifest', 'top' );
	add_rewrite_rule( '^sw\.js$', 'index.php?gc_pwa=sw', 'top' );
} );
add_filter( 'query_vars', function ( $vars ) { $vars[] = 'gc_pwa'; return $vars; } );

// Piggyback on the theme's existing one-time rewrite-flush marker pattern
// so these new root URLs work immediately without a manual Permalinks
// resave — same mechanism already used for the chapter hierarchy change.
add_action( 'init', function () {
	$marker = 'gc_rewrite_flushed_pwa_v1';
	if ( ! get_option( $marker ) ) {
		flush_rewrite_rules( false );
		update_option( $marker, 1 );
	}
}, 999 );

function gc_pwa_icon_url( $size ) {
	$attachment_setting = 'gc_pwa_icon_' . $size;
	$url = gc_opt( $attachment_setting, '' );
	if ( $url ) { return $url; }
	return get_template_directory_uri() . '/assets/pwa/icon-' . $size . '.png';
}

add_action( 'template_redirect', function () {
	$which = get_query_var( 'gc_pwa' );
	if ( ! $which ) { return; }

	if ( 'manifest' === $which ) {
		nocache_headers();
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode( array(
			'name'             => get_bloginfo( 'name' ) ?: 'Game Changers Nigeria',
			'short_name'       => 'Game Changers',
			'description'      => 'A national leadership community — learn, connect, lead and create measurable change.',
			'start_url'        => home_url( '/?pwa=1' ),
			'scope'            => home_url( '/' ),
			'display'          => 'standalone',
			'background_color' => '#f5f2ec',
			'theme_color'      => '#006b3f',
			'icons'            => array(
				array( 'src' => gc_pwa_icon_url( 192 ), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' ),
				array( 'src' => gc_pwa_icon_url( 512 ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ),
			),
		), JSON_UNESCAPED_SLASHES );
		exit;
	}

	if ( 'sw' === $which ) {
		nocache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		$offline_url = gc_page_url( 'offline' );
		?>
const GC_CACHE = 'gc-pwa-v2111';
const GC_OFFLINE_URL = <?php echo wp_json_encode( $offline_url ); ?>;

self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(GC_CACHE).then((cache) => cache.addAll([GC_OFFLINE_URL]))
	);
	self.skipWaiting();
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys().then((keys) =>
			Promise.all(keys.filter((k) => k !== GC_CACHE).map((k) => caches.delete(k)))
		)
	);
	self.clients.claim();
});

// Page navigations: try the network first (so content is always fresh
// when online), fall back to a cached copy, then to the offline page.
// Static assets (css/js/images/fonts): cache-first, since these rarely
// change per-request and this is what makes the app feel instant and
// keeps already-visited pages usable with no connection.
self.addEventListener('fetch', (event) => {
	const req = event.request;
	if (req.method !== 'GET') { return; }

	if (req.mode === 'navigate') {
		event.respondWith(
			fetch(req)
				.then((res) => {
					const copy = res.clone();
					caches.open(GC_CACHE).then((cache) => cache.put(req, copy));
					return res;
				})
				.catch(() =>
					caches.match(req).then((cached) => cached || caches.match(GC_OFFLINE_URL))
				)
		);
		return;
	}

	if (/\.(css|js|png|jpg|jpeg|webp|svg|woff2?)$/.test(new URL(req.url).pathname)) {
		event.respondWith(
			caches.match(req).then((cached) =>
				cached ||
				fetch(req).then((res) => {
					const copy = res.clone();
					caches.open(GC_CACHE).then((cache) => cache.put(req, copy));
					return res;
				}).catch(() => cached)
			)
		);
	}
});
		<?php
		exit;
	}
} );

// ---- Register the manifest + service worker, and offer an install button ----
add_action( 'wp_head', function () {
	echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.json' ) ) . '">' . "\n";
	echo '<meta name="theme-color" content="#006b3f">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="Game Changers">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( gc_pwa_icon_url( 192 ) ) . '">' . "\n";
} );

add_action( 'wp_footer', function () {
	?>
	<!-- Unobtrusive install prompt. Shown only once the browser actually
	     offers an install (beforeinstallprompt); dismissing it (or
	     installing) sets a permanent flag so it never reappears. -->
	<div id="gc-install-banner" class="gc-install-banner" hidden role="dialog" aria-label="Install Game Changers Nigeria">
		<div class="gc-install-banner-copy">
			<strong>Install Game Changers Nigeria</strong>
			<span>Get quicker access to your daily challenges, learning, opportunities and Member Hub.</span>
		</div>
		<div class="gc-install-banner-actions">
			<button type="button" id="gc-install-app-btn" class="gc-button gc-button-green" style="padding:8px 18px;">Install App</button>
			<button type="button" id="gc-install-dismiss-btn" class="gc-button gc-button-light" style="padding:8px 18px;">Not Now</button>
		</div>
	</div>
	<!-- Bottom app-style navigation, shown only when the site is actually
	     running as an installed app on a small screen (see gc-app-mode
	     class below); a normal browser tab never sees this. -->
	<nav class="gc-app-bottom-nav" aria-label="App navigation">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<a href="<?php echo esc_url( home_url( '/academy/' ) ); ?>">Learn</a>
		<a href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">Opportunities</a>
		<a href="<?php echo esc_url( gc_page_url( 'premium-dashboard' ) ); ?>">Hub</a>
	</nav>
	<style>
	.gc-install-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;background:#0b2417;color:#fff;border-radius:14px;padding:16px 18px;display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap;box-shadow:0 12px 30px rgba(0,0,0,.25);max-width:640px;margin:0 auto}
	.gc-install-banner[hidden]{display:none}
	.gc-install-banner-copy{display:flex;flex-direction:column;gap:2px;font-size:13px}
	.gc-install-banner-copy strong{font-size:15px}
	.gc-install-banner-actions{display:flex;gap:8px;flex-shrink:0}
	.gc-app-bottom-nav{display:none}
	html.gc-app-mode .gc-app-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:9998;display:flex;background:#fff;border-top:1px solid var(--gc-line,#e5e2da);padding:8px 0 calc(8px + env(safe-area-inset-bottom,0px))}
	html.gc-app-mode .gc-app-bottom-nav a{flex:1;text-align:center;font-size:11px;font-weight:700;text-decoration:none;color:var(--gc-ink,#0b2417)}
	html.gc-app-mode body{padding-bottom:56px}
	@media(min-width:900px){ html.gc-app-mode .gc-app-bottom-nav{display:none} html.gc-app-mode body{padding-bottom:0} }
	</style>
	<script>
	if ('serviceWorker' in navigator) {
		window.addEventListener('load', function () {
			navigator.serviceWorker.register('/sw.js').catch(function () {});
		});
	}
	(function () {
		// Detect "running as the installed app" (standalone display mode
		// on Android/desktop, navigator.standalone on iOS) so app-only UI
		// like the bottom nav only ever appears there, never in a normal tab.
		var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
		if (isStandalone) { document.documentElement.classList.add('gc-app-mode'); }

		var deferredPrompt = null;
		var banner = document.getElementById('gc-install-banner');
		var installBtn = document.getElementById('gc-install-app-btn');
		var dismissBtn = document.getElementById('gc-install-dismiss-btn');
		var DISMISS_KEY = 'gc_install_dismissed';
		var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
		var isAndroid = /android/i.test(navigator.userAgent);

		function showBanner(mode) {
			if (isStandalone || localStorage.getItem(DISMISS_KEY)) { return; }
			if (!banner) return;
			banner.hidden = false;
			var copy = banner.querySelector('.gc-install-banner-copy span');
			if (mode === 'ios' && copy) {
				copy.textContent = 'On iPhone/iPad: tap Share → Add to Home Screen.';
				if (installBtn) installBtn.textContent = 'How to install';
			} else if (mode === 'manual' && copy) {
				copy.textContent = 'Use your browser menu → Install app / Add to Home screen.';
			}
		}
		function hideBanner() {
			if (banner) { banner.hidden = true; }
		}

		window.addEventListener('beforeinstallprompt', function (e) {
			e.preventDefault();
			deferredPrompt = e;
			showBanner('chrome');
		});

		// iOS never fires beforeinstallprompt — show instructions after short delay
		if (isIOS && !isStandalone) {
			setTimeout(function () { showBanner('ios'); }, 2500);
		}
		// Android without prompt yet (criteria not met) — still offer manual tip
		if (isAndroid && !isStandalone) {
			setTimeout(function () {
				if (!deferredPrompt) showBanner('manual');
			}, 4000);
		}

		if (installBtn) {
			installBtn.addEventListener('click', function () {
				if (deferredPrompt) {
					hideBanner();
					deferredPrompt.prompt();
					deferredPrompt.userChoice.finally(function () { deferredPrompt = null; });
					return;
				}
				if (isIOS) {
					alert('Install on iPhone/iPad:\\n1. Tap the Share button\\n2. Scroll and tap “Add to Home Screen”\\n3. Tap Add');
					return;
				}
				alert('Open your browser menu and choose “Install app” or “Add to Home screen”.');
			});
		}
		if (dismissBtn) {
			dismissBtn.addEventListener('click', function () {
				localStorage.setItem(DISMISS_KEY, '1');
				hideBanner();
			});
		}
		window.addEventListener('appinstalled', function () {
			localStorage.setItem(DISMISS_KEY, '1');
			hideBanner();
		});
	})();
	</script>
	<?php
} );

/**
 * A ready-made "Install App" button any template can drop in — hidden
 * by default, revealed by the script above only when the browser has
 * actually offered an install prompt (so it never shows uselessly on
 * iOS Safari or an already-installed app).
 */
function gc_install_app_button( $label = 'Install App' ) {
	echo '<button type="button" class="gc-install-app-btn-manual gc-form-submit" style="width:auto;padding:10px 20px;" onclick="document.getElementById(\'gc-install-app-btn\') && document.getElementById(\'gc-install-app-btn\').click();">' . esc_html( $label ) . '</button>';
}

function gc_seed_offline_page() {
	$p = get_page_by_path( 'offline' );
	if ( ! $p ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Offline', 'post_name' => 'offline', 'post_status' => 'publish' ) );
		if ( $id && ! is_wp_error( $id ) ) { update_post_meta( $id, '_wp_page_template', 'page-offline.php' ); }
	} elseif ( $p ) {
		update_post_meta( $p->ID, '_wp_page_template', 'page-offline.php' );
	}
}
add_action( 'after_switch_theme', 'gc_seed_offline_page', 30 );
add_action( 'admin_init', function () {
	if ( get_option( 'gc_offline_page_v1' ) !== '1' ) {
		gc_seed_offline_page();
		update_option( 'gc_offline_page_v1', '1' );
	}
} );


