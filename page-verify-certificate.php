<?php
/**
 * Public certificate verification.
 * Checks admin registry first, then legacy GCN-YEAR-USERID-T# pattern.
 */
get_header();
$code = strtoupper( trim( sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ) ) );
$match_name = '';
$match_tier = 0;
$match_ok   = false;
$course     = '';

if ( $code && function_exists( 'gc_lookup_certificate' ) ) {
	$reg = gc_lookup_certificate( $code );
	if ( $reg ) {
		$match_ok   = true;
		$match_name = $reg['name'];
		$match_tier = (int) $reg['tier'];
		$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
		$course = isset( $tiers[ $match_tier ]['name'] ) ? $tiers[ $match_tier ]['name'] : ( 'Tier ' . $match_tier );
	}
}

if ( $code && ! $match_ok && preg_match( '/^GCN-(\d{4})-(\d{5,})-T(\d+)$/', $code, $m ) ) {
	$cert_year = $m[1];
	$user_id   = absint( $m[2] );
	$tier      = absint( $m[3] );
	$user      = get_user_by( 'id', $user_id );
	$expected  = 'GCN-' . $cert_year . '-' . str_pad( (string) $user_id, 5, '0', STR_PAD_LEFT ) . '-T' . $tier;
	if ( $user && strtoupper( $expected ) === $code ) {
		$match_ok   = true;
		$match_name = get_user_meta( $user_id, 'gc_certificate_name', true ) ?: $user->display_name;
		$match_tier = $tier;
		$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array();
		$course = isset( $tiers[ $tier ]['name'] ) ? $tiers[ $tier ]['name'] : ( 'Tier ' . $tier );
	}
}
?>
<main class="gc-interior">
<section class="gc-page-hero gc-dark">
	<div class="gc-container">
		<span class="gc-kicker">Credential verification</span>
		<h1>Verify a<br><em>Game Changer certificate.</em></h1>
		<p>Enter the tracking code printed on the certificate — for example <strong>GCN-2026-00001-T1</strong>.</p>
	</div>
</section>
<section class="gc-section">
	<div class="gc-container" style="max-width:560px;">
		<form class="gc-workflow-form" method="get" style="background:#fff;padding:24px;border-radius:14px;border:1px solid var(--gc-line,rgba(13,26,15,.12));">
			<label style="display:block;font-weight:700;margin-bottom:8px;">Certificate code</label>
			<input name="code" value="<?php echo esc_attr( $code ); ?>" placeholder="GCN-2026-00001-T1" style="width:100%;padding:12px;margin-bottom:14px;border-radius:8px;border:1px solid #ccd0d4;">
			<button class="gc-button gc-button-gold" type="submit">Verify certificate</button>
		</form>
		<?php if ( $code ) : ?>
			<div style="margin-top:24px;background:#fff;padding:24px;border-radius:14px;border:1px solid var(--gc-line,rgba(13,26,15,.12));">
				<?php if ( $match_ok ) : ?>
					<span class="gc-kicker gc-kicker-green">Verified</span>
					<h2 style="margin:8px 0;"><?php echo esc_html( $match_name ); ?></h2>
					<p>This certificate is genuine. Issued for <strong><?php echo esc_html( $course ); ?></strong> (Tier <?php echo (int) $match_tier; ?>).</p>
					<p style="font-size:13px;color:var(--gc-muted);">Code: <code><?php echo esc_html( $code ); ?></code></p>
				<?php else : ?>
					<h2>Certificate not found</h2>
					<p>Check the code and try again. It should look like <strong>GCN-2026-00001-T1</strong>.</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
</main>
<?php get_footer(); ?>
