<?php
/**
 * Template Name: Donate
 * Mature donation page with validated Paystack public key.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$pk_raw = trim( (string) get_option( 'gc_paystack_public_key', '' ) );
// Accept only live/test public keys
$pk = ( 0 === strpos( $pk_raw, 'pk_' ) ) ? $pk_raw : '';
$pk_invalid = $pk_raw && ! $pk;
$bank_name = get_option( 'gc_bank_name', '' );
$bank_acct = get_option( 'gc_bank_account_name', '' );
$bank_num  = get_option( 'gc_bank_account_number', '' );
$note      = get_option( 'gc_donation_note', 'Support the work of Game Changers Nigeria.' );
$email_pre = is_user_logged_in() ? wp_get_current_user()->user_email : '';
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
.gc-donate-card input{width:100%;min-height:48px;padding:0 14px;border:1px solid rgba(13,26,15,.16);border-radius:10px;font-size:16px;margin-bottom:16px;box-sizing:border-box}
.gc-donate-amounts{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px}
.gc-donate-amounts button{flex:1 1 30%;min-height:42px;border:1px solid rgba(13,26,15,.14);background:#f5f2ec;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px}
.gc-donate-amounts button.is-active{background:#006b3f;color:#fff;border-color:#006b3f}
.gc-donate-actions{display:flex;flex-direction:column;gap:10px;margin-top:8px}
.gc-donate-actions .gc-button{width:100%;min-height:50px}
.gc-donate-bank{margin-top:20px;padding:16px;background:#f5f2ec;border-radius:12px;font-size:14px;line-height:1.6;display:none}
.gc-donate-bank.is-open{display:block}
.gc-donate-bank strong{display:block;margin-bottom:6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#006b3f}
.gc-donate-note{font-size:13px;color:#66756a;margin:14px 0 0;line-height:1.55}
.gc-donate-err{background:rgba(180,40,40,.08);border:1px solid rgba(180,40,40,.2);color:#8b1a1a;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;line-height:1.5}
.gc-donate-secure{text-align:center;font-size:12px;color:#66756a;margin-top:14px}
@media(max-width:600px){
  .gc-donate-hero{padding:100px 4vw 48px}
  .gc-donate-card{padding:22px 18px}
}
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

      <?php if ( $pk_invalid ) : ?>
        <div class="gc-donate-err">
          <?php esc_html_e( 'Paystack is misconfigured: a public key must start with pk_ (test or live). Update it under WP Admin → Theme payments / Paystack settings. Secret keys (sk_) cannot be used in the browser.', 'game-changers' ); ?>
        </div>
      <?php elseif ( ! $pk ) : ?>
        <div class="gc-donate-err">
          <?php esc_html_e( 'Online card payment is not available yet. Use bank transfer below, or ask an admin to add the Paystack public key (pk_…).', 'game-changers' ); ?>
        </div>
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

      <div class="gc-donate-actions">
        <?php if ( $pk ) : ?>
          <button class="gc-button gc-button-gold" type="button" id="gc-donate-paystack"><?php esc_html_e( 'Donate with Paystack', 'game-changers' ); ?></button>
        <?php endif; ?>
        <button class="gc-button gc-button-outline" type="button" id="gc-donate-bank-toggle"><?php esc_html_e( 'Bank transfer details', 'game-changers' ); ?></button>
      </div>

      <div class="gc-donate-bank" id="gc-donate-bank">
        <strong><?php esc_html_e( 'Local bank transfer', 'game-changers' ); ?></strong>
        <?php if ( $bank_name || $bank_acct || $bank_num ) : ?>
          <div><?php echo esc_html( $bank_name ); ?></div>
          <div><?php echo esc_html( $bank_acct ); ?></div>
          <div><code style="font-size:15px;font-weight:700;"><?php echo esc_html( $bank_num ); ?></code></div>
          <p class="gc-donate-note"><?php esc_html_e( 'Use your full name as the narration. Email a receipt to the team if requested.', 'game-changers' ); ?></p>
        <?php else : ?>
          <p><?php esc_html_e( 'Bank details will appear here once an admin adds them in settings.', 'game-changers' ); ?></p>
        <?php endif; ?>
      </div>

      <?php if ( $pk ) : ?>
        <p class="gc-donate-secure">🔒 <?php esc_html_e( 'Secured by Paystack', 'game-changers' ); ?></p>
      <?php endif; ?>
    </div>
  </div>
</main>
<script>
(function(){
  var presets = document.getElementById('gc-donate-presets');
  var amount = document.getElementById('gc-donation-amount');
  if (presets && amount) {
    presets.addEventListener('click', function(e){
      var b = e.target.closest('button[data-amt]');
      if (!b) return;
      amount.value = b.getAttribute('data-amt');
      presets.querySelectorAll('button').forEach(function(x){ x.classList.remove('is-active'); });
      b.classList.add('is-active');
    });
  }
  var bankBtn = document.getElementById('gc-donate-bank-toggle');
  var bankBox = document.getElementById('gc-donate-bank');
  if (bankBtn && bankBox) {
    bankBtn.addEventListener('click', function(){
      bankBox.classList.toggle('is-open');
    });
  }
})();
</script>
<?php if ( $pk ) : ?>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(function(){
  var btn = document.getElementById('gc-donate-paystack');
  if (!btn || typeof PaystackPop === 'undefined') return;
  var key = <?php echo wp_json_encode( $pk ); ?>;
  btn.addEventListener('click', function(){
    var email = (document.getElementById('gc-donation-email') || {}).value || '';
    var amt = Number((document.getElementById('gc-donation-amount') || {}).value || 0);
    if (!email || email.indexOf('@') < 1) {
      alert('Please enter a valid email.');
      return;
    }
    if (!amt || amt < 100) {
      alert('Minimum donation is ₦100.');
      return;
    }
    if (!key || key.indexOf('pk_') !== 0) {
      alert('Paystack public key is missing or invalid. Ask an admin to set a key starting with pk_.');
      return;
    }
    try {
      var handler = PaystackPop.setup({
        key: key,
        email: email,
        amount: Math.round(amt * 100),
        currency: 'NGN',
        ref: 'GCN-DON-' + Date.now(),
        callback: function(response){
          var u = <?php echo wp_json_encode( admin_url( 'admin-post.php' ) ); ?> + '?action=gc_paystack_verify&reference=' + encodeURIComponent(response.reference);
          window.location.href = u;
        },
        onClose: function(){}
      });
      handler.openIframe();
    } catch (err) {
      // Fallback to v2 newTransaction API
      try {
        var p = new PaystackPop();
        p.newTransaction({
          key: key,
          email: email,
          amount: Math.round(amt * 100),
          onSuccess: function(t){
            window.location.href = <?php echo wp_json_encode( admin_url( 'admin-post.php' ) ); ?> + '?action=gc_paystack_verify&reference=' + encodeURIComponent(t.reference);
          }
        });
      } catch (e2) {
        alert('Could not start Paystack. Check that the public key is valid (pk_test_… or pk_live_…).');
      }
    }
  });
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
