<?php
/**
 * Template Name: Become a Member (Join Flow)
 * Pay → Prove payment → Onboard → Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : array(
	1 => array( 'name' => 'Foundations Circle', 'price' => 5000 ),
	2 => array( 'name' => 'Influence Builder', 'price' => 25000 ),
	3 => array( 'name' => 'Impact Architect', 'price' => 75000 ),
	4 => array( 'name' => 'Strategic Partner', 'price' => 250000 ),
	5 => array( 'name' => 'Legacy Council', 'price' => 750000 ),
);

$selected = absint( $_GET['tier'] ?? 0 );
$ref      = sanitize_text_field( wp_unslash( $_GET['ref'] ?? '' ) );
$err      = sanitize_key( $_GET['gc_err'] ?? '' );
$prefill_email = '';
$paid_data = $ref ? get_transient( 'gc_paid_' . md5( $ref ) ) : false;
$paid = ( isset( $_GET['paid'] ) && '1' === $_GET['paid'] && is_array( $paid_data ) );

if ( is_array( $paid_data ) ) {
	if ( ! empty( $paid_data['tier'] ) ) {
		$selected = absint( $paid_data['tier'] );
	}
	if ( ! empty( $paid_data['email'] ) ) {
		$prefill_email = $paid_data['email'];
	}
}
if ( $selected < 1 || ! isset( $tiers[ $selected ] ) ) {
	$selected = 0;
}

$join_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : get_permalink();
$price = $selected ? (int) ( $tiers[ $selected ]['price'] ?? 0 ) : 0;
$tname = $selected ? ( $tiers[ $selected ]['name'] ?? ( 'Tier ' . $selected ) ) : '';

$err_msgs = array(
	'nonce' => 'Session expired. Please try again.',
	'tier' => 'Please choose a tier.',
	'email' => 'Enter the email you used for payment.',
	'receipt' => 'Please upload a payment receipt (JPG, PNG or PDF).',
	'upload' => 'Upload failed. Try a smaller image or PDF.',
	'invite' => 'That invite code is invalid or already used.',
	'invite_email' => 'This invite is locked to a different email.',
	'method' => 'Choose how you paid.',
	'payment' => 'Payment could not be verified.',
	'exists' => 'That email is already registered. Log in instead.',
	'pass' => 'Password must be at least 8 characters.',
);
?>
<main class="gc-join-page">
	<section class="gc-join-hero">
		<div class="gc-container">
			<span class="gc-lp-kicker">Join the movement</span>
			<h1>Become a <em>Game Changer</em></h1>
			<p>Choose your tier, complete payment, confirm payment, then create your member account.</p>
		</div>
	</section>

	<section class="gc-join-body">
		<div class="gc-container">
			<?php if ( $err && isset( $err_msgs[ $err ] ) ) : ?>
				<div class="gc-join-err" style="max-width:480px;margin:0 auto 20px;background:#fff3f0;border:1px solid #f0c0b0;padding:12px 16px;border-radius:10px;color:#8b2e1a;"><?php echo esc_html( $err_msgs[ $err ] ); ?></div>
			<?php endif; ?>

			<?php if ( $paid && $ref && is_array( $paid_data ) ) : ?>
				<!-- ONBOARD after verified proof -->
				<div class="gc-join-card gc-join-onboard" style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:28px;border:1px solid rgba(13,26,15,.1);">
					<div class="gc-join-step-badge" style="display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#006b3f;background:rgba(0,107,63,.08);padding:4px 10px;border-radius:999px;margin-bottom:12px;">Step 2 of 2 · Create your account</div>
					<h2 style="margin:0 0 8px;font-family:'Playfair Display',serif;">Payment received — finish registration</h2>
					<p style="color:#66756a;font-size:14px;">Your payment for <strong><?php echo esc_html( $tname ); ?></strong><?php if ( $price ) : ?> (₦<?php echo esc_html( number_format_i18n( $price ) ); ?>)<?php endif; ?> is confirmed. Create your login to open the Member Hub.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:18px;">
						<input type="hidden" name="action" value="gc_join_onboard">
						<input type="hidden" name="gc_payment_ref" value="<?php echo esc_attr( $ref ); ?>">
						<input type="hidden" name="gc_tier" value="<?php echo (int) $selected; ?>">
						<?php wp_nonce_field( 'gc_join_onboard', 'gc_join_nonce' ); ?>
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Full name on account</label>
						<input name="gc_name" required placeholder="e.g. Adewale Samuel" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Email (login)</label>
						<input type="email" name="gc_email" required value="<?php echo esc_attr( $prefill_email ); ?>" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Password (min. 8 characters)</label>
						<input type="password" name="gc_password" required minlength="8" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
							<div>
								<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Phone (optional)</label>
								<input name="gc_phone" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
							</div>
							<div>
								<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">State (optional)</label>
								<input name="gc_state" placeholder="e.g. Lagos" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
							</div>
						</div>
						<p style="font-size:12px;font-weight:700;margin:18px 0 6px;color:#006b3f;letter-spacing:.04em;text-transform:uppercase;">Payout account (for referral rewards)</p>
						<p style="font-size:12px;color:#66756a;margin:0 0 8px;">Saved once per email — will not be overwritten if you already submitted details.</p>
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Bank name</label>
						<input name="gc_bank_name" placeholder="e.g. GTBank" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Account name</label>
						<input name="gc_account_name" placeholder="Name on the account" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Account number (NUBAN)</label>
						<input name="gc_account_number" inputmode="numeric" pattern="[0-9]{10,}" placeholder="10-digit account number" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<label style="display:block;font-weight:700;font-size:13px;margin:12px 0 6px;">Referral code (optional)</label>
						<input name="gc_referral_code" placeholder="e.g. GC-7K2QN9" style="width:100%;padding:11px;border-radius:8px;border:1px solid #ccd0d4;">
						<p style="font-size:12px;color:#66756a;margin:6px 0 0;">If someone invited you, enter their code — they earn points when you join.</p>
						<button class="gc-button gc-button-gold" type="submit" style="width:100%;margin-top:18px;">Create account &amp; open dashboard</button>
					</form>
				</div>

			<?php elseif ( $selected ) : ?>
				<!-- PAY + CONFIRM -->
				<div class="gc-join-card" style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:28px;border:1px solid rgba(13,26,15,.1);">
					<div class="gc-join-step-badge" style="display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#006b3f;background:rgba(0,107,63,.08);padding:4px 10px;border-radius:999px;margin-bottom:12px;">Step 1 of 2 · Payment</div>
					<a href="<?php echo esc_url( $join_url ); ?>" style="font-size:13px;color:#66756a;">← Change tier</a>
					<h2 style="margin:10px 0 4px;font-family:'Playfair Display',serif;"><?php echo esc_html( $tname ); ?></h2>
					<div style="font-size:32px;font-weight:800;margin-bottom:8px;">₦<?php echo esc_html( number_format_i18n( $price ) ); ?></div>
					<p style="color:#66756a;font-size:14px;">Pay first, then confirm below with your payment email or bank receipt. Registration opens only after confirmation.</p>

					<div style="margin:18px 0;">
						<?php
						if ( function_exists( 'gc_join_paystack_button' ) ) {
							echo gc_join_paystack_button( $selected, $price ); // phpcs:ignore
						}
						?>
					</div>

					<hr style="border:0;border-top:1px solid rgba(13,26,15,.08);margin:22px 0;">

					<h3 style="font-size:15px;margin:0 0 12px;">Confirm your payment</h3>
					<p style="font-size:13px;color:#66756a;margin:0 0 14px;">Choose one option. You cannot skip payment.</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="gc-confirm-form">
						<input type="hidden" name="action" value="gc_pay_confirm">
						<input type="hidden" name="tier" value="<?php echo (int) $selected; ?>">
						<?php wp_nonce_field( 'gc_pay_confirm', 'gc_pay_confirm_nonce' ); ?>

						<label style="display:block;font-weight:700;font-size:13px;margin:0 0 6px;">Your email</label>
						<input type="email" name="pay_email" required placeholder="Email used for payment / contact" style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccd0d4;margin-bottom:14px;">

						<div style="margin-bottom:14px;padding:14px;background:#f8f6f1;border-radius:10px;">
							<label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
								<input type="radio" name="confirm_method" value="paystack_email" checked style="margin-top:3px;">
								<span><strong>I paid with Paystack</strong><br><span style="font-size:12px;color:#66756a;">Use the same email you paid with above. Optional reference below.</span></span>
							</label>
							<div style="margin-top:10px;padding-left:24px;">
								<input type="text" name="pay_ref" placeholder="Paystack reference (optional)" style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccd0d4;">
							</div>
						</div>

						<div style="margin-bottom:14px;padding:14px;background:#f8f6f1;border-radius:10px;">
							<label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
								<input type="radio" name="confirm_method" value="receipt" style="margin-top:3px;">
								<span><strong>I paid by bank transfer</strong><br><span style="font-size:12px;color:#66756a;">Upload your payment receipt (JPG, PNG or PDF)</span></span>
							</label>
							<div style="margin-top:10px;padding-left:24px;">
								<input type="file" name="receipt" accept="image/*,.pdf" style="width:100%;font-size:13px;">
							</div>
						</div>

						<div style="margin-bottom:14px;padding:14px;background:#f0f7f3;border-radius:10px;">
							<label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
								<input type="radio" name="confirm_method" value="invite" style="margin-top:3px;">
								<span><strong>I have an admin free-access code</strong><br><span style="font-size:12px;color:#66756a;">For members granted free entry by admin</span></span>
							</label>
							<div style="margin-top:10px;padding-left:24px;">
								<input type="text" name="invite_code" placeholder="Invite code" style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccd0d4;">
							</div>
						</div>

						<button class="gc-button gc-button-gold" type="submit" style="width:100%;">Confirm &amp; continue to registration</button>
					</form>
				</div>

			<?php else : ?>
				<!-- TIER PICKER -->
				<div style="text-align:center;margin-bottom:28px;">
					<h2 style="font-family:'Playfair Display',serif;margin:0 0 8px;">Choose your entry tier</h2>
					<p style="color:#66756a;max-width:520px;margin:0 auto;">Payment unlocks registration. After you pay and confirm, you create your login and open the Member Hub.</p>
				</div>
				<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;max-width:960px;margin:0 auto;">
					<?php foreach ( $tiers as $n => $tv ) :
						$pr = (int) ( $tv['price'] ?? 0 );
						$url = add_query_arg( 'tier', $n, $join_url );
						?>
						<div style="background:#fff;border:1px solid rgba(13,26,15,.12);border-radius:14px;padding:22px;text-align:center;">
							<div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#006b3f;">Tier <?php echo (int) $n; ?></div>
							<h3 style="margin:8px 0;font-family:'Playfair Display',serif;"><?php echo esc_html( $tv['name'] ?? ( 'Tier ' . $n ) ); ?></h3>
							<div style="font-size:26px;font-weight:800;margin:8px 0 14px;">₦<?php echo esc_html( number_format_i18n( $pr ) ); ?></div>
							<a class="gc-button <?php echo 1 === (int) $n ? 'gc-button-gold' : 'gc-button-green'; ?>" href="<?php echo esc_url( $url ); ?>">Select &amp; pay</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
