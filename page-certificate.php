<?php
/**
 * This page is personal to whoever's logged in (it carries a per-session
 * security token in its "Save Name" form) and must never be served from
 * a page cache — a cached copy would hand every visitor someone else's
 * stale token, which is exactly what causes "Security check failed."
 * nocache_headers() covers browsers/proxies; DONOTCACHEPAGE is honoured
 * by essentially every WordPress caching plugin (WP Rocket, WP Super
 * Cache, W3 Total Cache, LiteSpeed Cache, SiteGround Optimizer, etc).
 */
if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
nocache_headers();
if(!is_user_logged_in()){wp_safe_redirect(gc_page_url('premium-login'));exit;} $u=wp_get_current_user();$tiers=function_exists('gc_tiers')?gc_tiers():array(1=>array('name'=>'Tier 1','price'=>1900,'certificate'=>0),2=>array('name'=>'Tier 2','price'=>5000,'certificate'=>0),3=>array('name'=>'Tier 3','price'=>25000,'certificate'=>0));get_header(); ?>
<main class="gc-section gc-certificates-page"><div class="gc-container">
<header class="gc-certificates-head"><span class="gc-kicker">Member Hub</span><h1>My Certificates</h1><p>Each completed tier has its own certificate with a unique serial number. Complete the required lessons for a tier to unlock its original certificate.</p></header>
<?php $gc_custom_name = get_user_meta( $u->ID, 'gc_certificate_name', true ); $gc_cert_name_saved = isset( $_GET['gc_cert_name'] ) && 'saved' === $_GET['gc_cert_name']; ?>
<div class="gc-cert-name-box" id="gc-cert-name">
	<h2>Name on your certificate</h2>
	<p>By default we print your registered account name. Choose a different name instead — useful for a formal/full version, or to fix a typo in your registered name.</p>
	<div class="gc-form-notice gc-form-notice--ok" id="gc-cert-name-msg" style="<?php echo $gc_cert_name_saved ? '' : 'display:none;'; ?>"><?php echo $gc_cert_name_saved ? 'Saved — your certificates will now use this name.' : ''; ?></div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form" id="gc-cert-name-form">
		<input type="hidden" name="action" value="gc_save_certificate_name">
		<input type="hidden" name="gc_cert_ajax" value="1" id="gc_cert_ajax_flag">
		<?php wp_nonce_field( 'gc_cert_name_save', 'gc_cert_name_nonce' ); ?>
		<div class="gc-cert-name-choice">
			<label><input type="radio" name="gc_cert_name_choice" value="registered" <?php checked( ! $gc_custom_name ); ?>> Use my registered name — <strong><?php echo esc_html( $u->display_name ); ?></strong></label>
			<label><input type="radio" name="gc_cert_name_choice" value="custom" <?php checked( (bool) $gc_custom_name ); ?>> Use a different name</label>
		</div>
		<div class="gc-form-row" id="gc-cert-custom-row" style="<?php echo $gc_custom_name ? '' : 'display:none;'; ?>">
			<label for="gc_cert_custom_name">Name to print on certificate</label>
			<input type="text" id="gc_cert_custom_name" name="gc_cert_custom_name" value="<?php echo esc_attr( $gc_custom_name ); ?>" placeholder="e.g. Adewale Samuel Ayobami" autocomplete="name">
		</div>
		<button class="gc-form-submit" type="submit" id="gc-cert-name-submit" style="width:auto;padding:12px 26px;">Save Name</button>
	</form>
</div>
<script>
(function(){
	var radios = document.querySelectorAll('input[name="gc_cert_name_choice"]');
	var row = document.getElementById('gc-cert-custom-row');
	var form = document.getElementById('gc-cert-name-form');
	var msg = document.getElementById('gc-cert-name-msg');
	var btn = document.getElementById('gc-cert-name-submit');
	if (!row) return;
	radios.forEach(function(r){
		r.addEventListener('change', function(){
			var checked = document.querySelector('input[name="gc_cert_name_choice"]:checked');
			row.style.display = checked && checked.value === 'custom' ? '' : 'none';
		});
	});
	if (!form || !btn) return;
	form.addEventListener('submit', function(e){
		e.preventDefault();
		btn.disabled = true;
		btn.textContent = 'Saving…';
		if (msg) { msg.style.display = 'none'; msg.className = 'gc-form-notice'; }
		var fd = new FormData(form);
		fd.set('action', 'gc_save_certificate_name');
		fd.set('gc_cert_ajax', '1');
		fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
			method: 'POST',
			body: fd,
			credentials: 'same-origin'
		}).then(function(r){ return r.json(); }).then(function(data){
			btn.disabled = false;
			btn.textContent = 'Save Name';
			if (data && data.success) {
				if (msg) {
					msg.textContent = (data.data && data.data.message) ? data.data.message : 'Saved — your certificates will now use this name.';
					msg.className = 'gc-form-notice gc-form-notice--ok';
					msg.style.display = '';
				}
			} else {
				var err = (data && data.data && data.data.message) ? data.data.message : 'Could not save. Please try again.';
				if (msg) {
					msg.textContent = err;
					msg.className = 'gc-form-notice gc-form-notice--error';
					msg.style.display = '';
				}
			}
		}).catch(function(){
			btn.disabled = false;
			btn.textContent = 'Save Name';
			// Fallback: classic form post without AJAX flag
			var flag = document.getElementById('gc_cert_ajax_flag');
			if (flag) flag.remove();
			form.submit();
		});
	});
})();
</script>
<div class="gc-certificate-list">
<?php foreach($tiers as $n=>$t):$progress=function_exists('gc_tier_progress')?gc_tier_progress($u->ID,$n):array('done'=>0,'total'=>0,'percent'=>0);$aid=function_exists('gc_tier_template_id')?gc_tier_template_id($n):0;$has=$aid&&wp_get_attachment_url($aid);$gc_override=(bool)get_user_meta($u->ID,'gc_cert_override_tier_'.$n,true);$gc_unlocked=((int)$progress['percent']>=100)||$gc_override;
/* Unique serial per tier: GCN-YYYY-USERID-T{n} — Tier 1, 2, 3, 4 never share the same number */
$serial = 'GCN-' . date('Y') . '-' . str_pad((string)$u->ID, 5, '0', STR_PAD_LEFT) . '-T' . (int)$n;
?>
<article class="gc-certificate-card">
<div><span class="gc-certificate-tier"><?php echo esc_html($t['name']);?></span><h2><?php echo esc_html($t['name']);?> Certificate</h2><p><?php echo (int)$progress['done'];?> of <?php echo (int)$progress['total'];?> required lessons completed · <?php echo (int)$progress['percent'];?>%<?php echo $gc_override && (int)$progress['percent']<100 ? ' · <strong>Access granted by admin</strong>' : ''; ?><?php if($gc_unlocked): ?> · Serial <code><?php echo esc_html($serial); ?></code><?php endif; ?></p></div>
<div class="gc-certificate-card-actions">
<?php if(!$gc_unlocked): ?><span class="gc-cert-locked">Locked — complete the course</span>
<?php elseif(!$has): ?><span class="gc-cert-locked">Template not assigned yet</span>
<?php else: ?><a class="gc-button gc-button-gold" href="<?php echo esc_url(gc_certificate_download_url($n));?>">Download <?php echo esc_html($t['name']);?> Certificate</a><a class="gc-button gc-button-light" href="<?php echo esc_url(add_query_arg('gc_print_tier',(int)$n,get_permalink()));?>">Print</a><?php endif;?>
</div></article>
<?php endforeach;?>
</div>
</div></main>
<?php
if(isset($_GET['gc_print_tier'])):$n=absint($_GET['gc_print_tier']);$t=isset($tiers[$n])?$tiers[$n]:null;$aid=$t?gc_tier_template_id($n):0;$url=$aid?wp_get_attachment_url($aid):'';$p=$t?gc_tier_progress($u->ID,$n):array('percent'=>0);$gc_override=(bool)get_user_meta($u->ID,'gc_cert_override_tier_'.$n,true);$name=get_user_meta($u->ID,'gc_certificate_name',true)?:$u->display_name; if($t&&(($p['percent']>=100)||$gc_override)):
$print_serial = 'GCN-' . date('Y') . '-' . str_pad((string)$u->ID, 5, '0', STR_PAD_LEFT) . '-T' . (int)$n;
?>
<div class="gc-print-certificate">
<div class="gc-print-paper<?php echo $url ? ' has-art' : ''; ?>"<?php if ( $url ) : ?> style="background-image:url('<?php echo esc_url( $url ); ?>')"<?php endif; ?>>
<?php if ( ! $url ) : ?>
  <div class="gc-print-inner">
    <div class="gc-print-brand">Game Changers Nigeria</div>
    <div class="gc-print-kicker">Certificate of Completion</div>
    <p class="gc-print-line">This certifies that</p>
    <div class="gc-print-name"><?php echo esc_html( $name ); ?></div>
    <p class="gc-print-line">has successfully completed</p>
    <div class="gc-print-programme">Leadership Development Programme</div>
    <div class="gc-print-tier">Tier <?php echo (int) $n; ?></div>
    <div class="gc-print-meta">
      <span><?php echo esc_html( current_time( 'F j, Y' ) ); ?></span>
      <span><?php echo esc_html( $print_serial ); ?></span>
    </div>
  </div>
<?php else : ?>
  <div class="gc-print-name gc-print-name--on-art"><?php echo esc_html( $name ); ?></div>
  <div class="gc-print-meta gc-print-meta--on-art"><?php echo esc_html( current_time( 'F j, Y' ) ); ?> · <?php echo esc_html( $print_serial ); ?></div>
<?php endif; ?>
</div>
</div>
<script>
window.addEventListener('load', function () {
  var go = function () { setTimeout(function () { window.print(); }, 200); };
  if (document.fonts && document.fonts.ready) { document.fonts.ready.then(go).catch(go); }
  else { go(); }
});
</script>
<?php endif;endif;get_footer(); ?>
<style>
.gc-cert-name-box{background:#fff;border:1px solid var(--gc-line);border-radius:14px;padding:28px;max-width:640px;margin-bottom:36px}.gc-cert-name-box h2{font:700 22px 'Playfair Display',serif;margin:0 0 6px}.gc-cert-name-box>p{color:var(--gc-muted);font-size:13px;margin:0 0 18px}.gc-cert-name-choice{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}.gc-cert-name-choice label{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;cursor:pointer}.gc-cert-name-choice input[type=radio]{accent-color:var(--gc-green)}.gc-certificates-head{max-width:760px;margin-bottom:42px}.gc-certificates-head h1{font:900 clamp(42px,5vw,64px)/1.05 'Playfair Display',serif;margin:12px 0}.gc-certificates-head p{color:var(--gc-muted);font-size:16px}.gc-certificate-list{display:grid;gap:16px;max-width:900px}.gc-certificate-card{background:#fff;border:1px solid var(--gc-line);border-radius:14px;padding:28px;display:flex;justify-content:space-between;gap:28px;align-items:center}.gc-certificate-card h2{font:700 26px 'Playfair Display',serif;margin:9px 0 4px}.gc-certificate-card p{color:var(--gc-muted);font-size:13px;margin:0}.gc-certificate-card code{font:600 11px 'Space Mono',monospace;background:rgba(0,107,63,.08);padding:2px 6px;border-radius:4px;color:var(--gc-green)}.gc-certificate-tier{font:700 9px 'Space Mono',monospace;color:var(--gc-green);letter-spacing:.16em;text-transform:uppercase}.gc-certificate-card-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.gc-cert-locked{font-size:12px;color:var(--gc-muted);padding:12px 0}.gc-form-notice--error{background:rgba(180,40,40,.08);border:1px solid rgba(180,40,40,.25);color:#8b1a1a;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}.gc-form-notice--ok{background:rgba(0,107,63,.08);border:1px solid rgba(0,107,63,.25);color:#0b2417;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}.gc-print-certificate{display:none}
.gc-print-paper{
  position:relative;width:100%;max-width:1000px;margin:0 auto;
  aspect-ratio:1000/707;background:#fff center/100% 100% no-repeat;
  -webkit-print-color-adjust:exact;print-color-adjust:exact;
  box-sizing:border-box
}
.gc-print-paper.has-art{background-color:#fff}
.gc-print-inner{
  position:absolute;inset:8% 10%;display:flex;flex-direction:column;
  align-items:center;justify-content:center;text-align:center
}
.gc-print-brand{font:700 11px/1.2 'Space Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:#006b3f;margin-bottom:10px}
.gc-print-kicker{font:600 12px/1.3 Inter,Arial,sans-serif;letter-spacing:.12em;text-transform:uppercase;color:#66756a;margin-bottom:18px}
.gc-print-line{margin:0;font-size:14px;color:#5a6b5f}
.gc-print-name{
  margin:14px 0;font:italic 700 clamp(28px,5vw,52px)/1.15 'Playfair Display',Georgia,serif;
  color:#0b2417;max-width:90%;word-wrap:break-word;text-align:center
}
.gc-print-name--on-art{
  position:absolute;left:8%;right:8%;top:37%;margin:0;
  font:italic 700 clamp(28px,5.2vw,58px)/1.15 'Playfair Display',Georgia,serif;
  color:#0b2417;text-shadow:0 1px 0 rgba(0,0,0,.04)
}
.gc-print-programme{margin:12px 0 8px;font:700 clamp(16px,2.5vw,22px)/1.3 'Playfair Display',Georgia,serif;color:#0d1a0f}
.gc-print-tier{font:700 12px/1.4 'Space Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:#006b3f;margin-bottom:22px}
.gc-print-meta{display:flex;flex-wrap:wrap;gap:12px 28px;justify-content:center;font:600 11px/1.4 'Space Mono',monospace;color:#5a6b5f}
.gc-print-meta--on-art{
  position:absolute;left:15%;right:15%;top:69%;margin:0;display:block;text-align:center;
  font:700 10px/1.4 'Space Mono',monospace;letter-spacing:.04em;color:#5a6b5f
}
@media(max-width:700px){
  .gc-certificate-card{display:block}
  .gc-certificate-card-actions{justify-content:flex-start;margin-top:18px}
  .gc-certificate-card-actions .gc-button{width:100%}
}
@media print{
  @page{size:landscape;margin:0}
  html,body{margin:0!important;padding:0!important;background:#fff!important}
  body *{visibility:hidden}
  .gc-print-certificate,.gc-print-certificate *{visibility:visible}
  .gc-print-certificate{display:block!important;position:fixed;inset:0;padding:0!important;z-index:99999}
  .gc-print-paper{width:100vw!important;height:100vh!important;max-width:none!important;aspect-ratio:auto!important;border:0!important}
  .gc-site-header,.gc-footer,.gc-certificates-page,.gc-nav{display:none!important}
}

</style>
