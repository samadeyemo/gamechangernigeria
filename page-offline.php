<?php
/**
 * The service worker's offline fallback. Shown when someone opens the
 * installed app (or any page) with no connection and nothing cached
 * for that exact URL yet. Auto-seeded at /offline/ — see inc/pwa.php.
 */
if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
get_header();
?>
<main class="gc-offline-page">
	<div class="gc-container" style="text-align:center;padding:150px 5vw 100px;">
		<span class="gc-kicker">You're offline</span>
		<h1 style="font:900 clamp(34px,5vw,52px)/1.1 'Playfair Display',serif;margin:16px 0;color:var(--gc-ink);">No connection right now.</h1>
		<p style="color:var(--gc-muted);max-width:480px;margin:0 auto 26px;">This page hasn't been saved for offline use yet. Reconnect and it will load normally — pages you've already visited will keep working offline.</p>
		<button onclick="location.reload()" class="gc-form-submit" style="width:auto;padding:14px 30px;">Try Again</button>
	</div>
</main>
<?php get_footer(); ?>
