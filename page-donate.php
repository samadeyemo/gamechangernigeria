<?php
/**
 * Template Name: Donate
 * Live Paystack payment + narration for admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$key       = trim( (string) get_option( 'gc_paystack_public_key', '' ) );
$bank_name = get_option( 'gc_bank_name', '' );
$bank_acct = get_option( 'gc_bank_account_name', '' );
$bank_num  = get_option( 'gc_bank_account_number', '' );
$note      = get_option( 'gc_donation_note', 'Support the work of Game Changers Nigeria.' );
$email_pre = is_user_logged_in() ? wp_get_current_user()->user_email : '';
$ok_key    = $key && ( 0 === strpos( $key, 'pk_' ) );
?>
<style>
.gc-donate-page{padding:0 0 80px}
.gc-donate-hero{background:linear-gradient(165deg,#0a1a10,#163022);color:#f5f2ec;padding:120px 5vw 56px;text-align:center}
.gc-donate-hero .gc-kicker{color:#e8b840;font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:700}
.gc-donate-hero h1{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,48px);margin:12px 0;color:#fff;line-height:1.15}
.gc-donate-hero p{max-width:42ch;margin:0 auto;opacity:.88;line-height:1.65}
.gc-donate-wrap{max-width:520px;margin:-36px auto 0;padding:0 5vw}
.gc-donate-card{background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:16px;padding:28px 24px;box-shadow:0 18px 50px rgba(13,26,15,.08)}
.gc-donate-card h2{font-family:'Playfair Display',serif;font-size:24px;margin:0 0 18px}
.gc-donate-card label{display:block;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin:0 0 6px;color:#0d1a0f}
.gc-donate-card input,.gc-donate-card textarea{width:100%;padding:12px 14px;border:1px solid rgba(13,26,15,.16);border-radius:10px;font-size:16px;margin-bottom:16px;box-sizing:border-box;font-family:inherit}
.gc-donate-card textarea{min-height:88px;resize:vertical;line-height:1.5}
.gc-donate-card input{min-height:48px}
.gc-donate-amounts{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px}
.gc-donate-amounts button{flex:1 1 30%;min-height:42px;border:1px solid rgba(13,26,15,.14);background:#f5f2ec;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px}
.gc-donate-amounts button.is-active{background:#006b3f;color:#fff;border-color:#006b3f}
.gc-donate-actions{display:flex;flex-direction:column;gap:10px;margin-top:4px}
.gc-donate-actions .gc-button{width:100%;min-height:50px;text-align:center;border:0;cursor:pointer}
.gc-donate-bank{margin-top:20px;padding:16px;background:#f5f2ec;border-radius:12px;font-size:14px;line-height:1.6;display:none}
.gc-donate-bank.is-open{display:block}
.gc-donate-bank strong{display:block;margin-bottom:6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#006b3f}
.gc-donate-hint{font-size:12px;color:#66756a;margin:-8px 0 14px;line-height:1.5}
.gc-donate-secure{text-align:center;font-size:12px;color:#66756a;margin-top:14px}
.gc-donate-err{background:#fff3f0;border:1px solid #f0c0b0;color:#8b2e1a;padding:12px;border-radius:10px;margin-bottom:14px;font-size:14px}
@media(max-width:600px){.gc-donate-hero{padding:100px 4vw 48px}.gc-donate-card{padding:22px 18px}}
</style>
<main class="gc-donate-page">
  <section class="gc-donate-hero">
    <span class="gc-kicker"><?php esc_html_e( 'Support the movement', 'game-changers' ); ?></span>
    <h1><?php esc_html_e( 'Give to the work that creates impact.', 'game-changers' ); ?></h1>
    <p><?php echo esc_html( $note ); ?></p>
  </section>
  <div class="gc-donate-wrap">
    <div class="gc-donate-card">
      <h2><?php esc_html_e( 'Make a donation', 'game-changers' ); ?></h2>
      <?php if ( ! $ok_key ) : ?>
        <div class="gc-donate-err"><?php esc_html_e( 'Live Paystack is not configured yet. Admin: Settings → GC Payments → public key (pk_live_…).', 'game-changers' ); ?></div>
      <?php endif; ?>

      <label for="gc-donation-amount"><?php esc_html_e( 'Donation amount (₦)', 'game-changers' ); ?></label>
      <div class="gc-donate-amounts" id="gc-donate-presets">
        <button type="button" data-amt="2000">₦2,000</button>
        <button type="button" data-amt="5000" class="is-active">₦5,000</button>
        <button type="button" data-amt="10000">₦10,000</button>
        <button type="button" data-amt="25000">₦25,000</button>
      </div>
      <input id="gc-donation-amount" type="number" min="100" step="100" value="5000" inputmode="numeric">

      <label for="gc-donation-email"><?php esc_html_e( 'Your email', 'game-changers' ); ?></label>
      <input id="gc-donation-email" type="email" value="<?php echo esc_attr( $email_pre ); ?>" placeholder="you@example.com" autocomplete="email">

      <label for="gc-donation-narration"><?php esc_html_e( 'Narration / purpose', 'game-changers' ); ?></label>
      <p class="gc-donate-hint"><?php esc_html_e( 'What is this gift for? (chapter support, scholarship, general fund, etc.)', 'game-changers' ); ?></p>
      <textarea id="gc-donation-narration" maxlength="400" placeholder="<?php esc_attr_e( 'e.g. Support for Ibadan chapter', 'game-changers' ); ?>"></textarea>

      <div class="gc-donate-actions">
        <?php if ( $ok_key ) : ?>
          <button class="gc-button gc-button-gold" type="button" id="gc-donate-pay"><?php esc_html_e( 'Pay with Paystack', 'game-changers' ); ?></button>
        <?php endif; ?>
        <button class="gc-button gc-button-outline" type="button" id="gc-donate-bank-toggle"><?php esc_html_e( 'Bank transfer details', 'game-changers' ); ?></button>
      </div>

      <div class="gc-donate-bank" id="gc-donate-bank">
        <strong><?php esc_html_e( 'Local bank transfer', 'game-changers' ); ?></strong>
        <?php if ( $bank_name || $bank_acct || $bank_num ) : ?>
          <div><?php echo esc_html( $bank_name ); ?></div>
          <div><?php echo esc_html( $bank_acct ); ?></div>
          <div><code style="font-size:15px;font-weight:700;"><?php echo esc_html( $bank_num ); ?></code></div>
        <?php else : ?>
          <p><?php esc_html_e( 'Bank details will appear once an admin adds them in GC Payments.', 'game-changers' ); ?></p>
        <?php endif; ?>
      </div>
      <p class="gc-donate-secure">🔒 <?php esc_html_e( 'Secured by Paystack', 'game-changers' ); ?></p>
    </div>
  </div>
</main>
<?php if ( $ok_key ) : ?>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(function(){
  var key = <?php echo wp_json_encode( $key ); ?>;
  var presets = document.getElementById('gc-donate-presets');
  var amountEl = document.getElementById('gc-donation-amount');
  var emailEl = document.getElementById('gc-donation-email');
  var narrEl = document.getElementById('gc-donation-narration');
  if (presets && amountEl) {
    presets.addEventListener('click', function(e){
      var b = e.target.closest('button[data-amt]');
      if (!b) return;
      amountEl.value = b.getAttribute('data-amt');
      presets.querySelectorAll('button').forEach(function(x){ x.classList.remove('is-active'); });
      b.classList.add('is-active');
    });
  }
  var bankBtn = document.getElementById('gc-donate-bank-toggle');
  var bankBox = document.getElementById('gc-donate-bank');
  if (bankBtn && bankBox) bankBtn.addEventListener('click', function(){ bankBox.classList.toggle('is-open'); });

  var payBtn = document.getElementById('gc-donate-pay');
  if (payBtn) {
    payBtn.addEventListener('click', function(){
      if (!window.PaystackPop) { alert('Paystack is loading — try again.'); return; }
      var amount = parseInt(amountEl.value, 10) || 0;
      var email = (emailEl.value || '').trim();
      var narration = (narrEl.value || '').trim();
      if (amount < 100) { alert('Enter a valid amount.'); return; }
      if (!email || email.indexOf('@') < 0) { alert('Enter a valid email.'); return; }
      if (narration.length < 2) { alert('Please add a short narration so we know what this gift is for.'); return; }
      var ref = 'GCN-DON-' + Date.now() + '-' + Math.floor(Math.random()*9000+1000);
      // Log intent to server (optional beacon)
      try {
        var fd = new FormData();
        fd.append('action', 'gc_donate_intent_log');
        fd.append('email', email);
        fd.append('amount', amount);
        fd.append('narration', narration);
        fd.append('ref', ref);
        navigator.sendBeacon && navigator.sendBeacon('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', fd);
      } catch (e) {}
      var popup = new PaystackPop();
      popup.newTransaction({
        key: key,
        email: email,
        amount: amount * 100,
        reference: ref,
        currency: 'NGN',
        metadata: { purpose: narration, type: 'donation' },
        onSuccess: function(transaction) {
          alert('Thank you. Payment received' + (transaction && transaction.reference ? ' — ref: ' + transaction.reference : '') + '.');
        },
        onCancel: function() {}
      });
    });
  }
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
