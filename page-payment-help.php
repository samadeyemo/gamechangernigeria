<?php
/**
 * Template Name: Payment & Account Help
 * Professional complaints / email correction requests.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$sent = isset( $_GET['gc_help'] ) && 'sent' === $_GET['gc_help'];
$err  = isset( $_GET['gc_help'] ) && 'error' === $_GET['gc_help'];
?>
<style>
.gc-help-page{padding:110px 0 80px}
.gc-help-wrap{max-width:560px;margin:0 auto;padding:0 5vw}
.gc-help-card{background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:16px;padding:28px 24px;box-shadow:0 18px 50px rgba(13,26,15,.06)}
.gc-help-card h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,36px);margin:0 0 10px}
.gc-help-card > p{color:#66756a;line-height:1.6;margin:0 0 20px}
.gc-help-card label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px}
.gc-help-card input,.gc-help-card select,.gc-help-card textarea{width:100%;padding:12px 14px;border:1px solid rgba(13,26,15,.16);border-radius:10px;font-size:15px;margin-bottom:14px;box-sizing:border-box;font-family:inherit}
.gc-help-card textarea{min-height:120px;resize:vertical}
.gc-help-ok{background:#f0f7f3;border:1px solid rgba(0,107,63,.25);color:#0b2417;padding:12px 14px;border-radius:10px;margin-bottom:16px;line-height:1.5}
.gc-help-err{background:#fff3f0;border:1px solid #f0c0b0;color:#8b2e1a;padding:12px 14px;border-radius:10px;margin-bottom:16px}
</style>
<main class="gc-help-page">
  <div class="gc-help-wrap">
    <div class="gc-help-card">
      <h1>Payment &amp; account help</h1>
      <p>Paid by bank and waiting for approval? Wrong email on registration? Lodge a clear complaint — our team will respond from the admin desk.</p>
      <?php if ( $sent ) : ?>
        <div class="gc-help-ok">Your message was sent. We will review it and contact you on the email you provided.</div>
      <?php elseif ( $err ) : ?>
        <div class="gc-help-err">Please complete all required fields and try again.</div>
      <?php endif; ?>
      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="gc_payment_help">
        <?php wp_nonce_field( 'gc_payment_help', 'gc_help_nonce' ); ?>
        <label>Your full name</label>
        <input name="gc_name" required placeholder="As on your payment or application">
        <label>Email you used (or the one that should receive mail)</label>
        <input type="email" name="gc_email" required placeholder="you@example.com">
        <label>Issue type</label>
        <select name="gc_issue" required>
          <option value="">Select…</option>
          <option value="bank_approval">Bank payment approved but no email / cannot continue</option>
          <option value="wrong_email">Wrong email on my account</option>
          <option value="no_mail">Not receiving emails</option>
          <option value="password">Need password reset on a new email</option>
          <option value="other">Other payment or account issue</option>
        </select>
        <label>Details</label>
        <textarea name="gc_details" required placeholder="Include tier, approximate payment date, and any reference if you paid with Paystack."></textarea>
        <button type="submit" class="gc-button gc-button-gold" style="width:100%;min-height:48px;">Submit complaint</button>
      </form>
    </div>
  </div>
</main>
<?php get_footer(); ?>
