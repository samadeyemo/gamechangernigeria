<?php
if(!defined('ABSPATH'))exit; function gc_default_tiers(){return array(1=>array('name'=>'Foundations Circle','price'=>5000,'certificate'=>0),2=>array('name'=>'Influence Builder','price'=>25000,'certificate'=>0),3=>array('name'=>'Impact Architect','price'=>75000,'certificate'=>0),4=>array('name'=>'Strategic Partner','price'=>250000,'certificate'=>0),5=>array('name'=>'Legacy Council','price'=>750000,'certificate'=>0));} function gc_tiers(){ $saved=get_option('gc_membership_tiers',array()); if(!is_array($saved)||empty($saved))$saved=gc_default_tiers(); $out=array(); foreach($saved as $n=>$v){$n=absint($n);if(!$n)continue;$out[$n]=array('name'=>sanitize_text_field($v['name']??('Tier '.$n)),'price'=>absint($v['price']??0),'certificate'=>absint($v['certificate']??0));}ksort($out);return $out; } function gc_tier_prices(){ $o=array();foreach(gc_tiers() as $n=>$v)$o[$n]=(int)$v['price'];return $o; } function gc_tier_label($tier){$tiers=gc_tiers();$t=isset($tiers[$tier])?(int)$tier:(int)array_key_first($tiers);$v=$tiers[$t];return $v['name'].' — ₦'.number_format_i18n($v['price']);} function gc_tier_template_id($tier){ $t=gc_tiers();$tier=(int)$tier;return isset($t[$tier])&&!empty($t[$tier]['certificate'])?(int)$t[$tier]['certificate']:0; } add_action('admin_menu',function(){add_options_page('Membership Tiers','Membership Tiers','manage_options','gc-membership-tiers','gc_membership_tiers_settings');}); add_action('admin_enqueue_scripts',function($hook){if($hook==='settings_page_gc-membership-tiers')wp_enqueue_media();}); function gc_auto_assign_certificate_templates(){ $tiers=get_option('gc_membership_tiers',array()); if(!is_array($tiers)||empty($tiers))$tiers=gc_default_tiers(); $changed=false; foreach(array(1=>array('tier 1','tier1','cert1','certificate 1','certificate1'),2=>array('tier 2','tier2','cert2','certificate 2','certificate2')) as $n=>$needles){ if(!empty($tiers[$n]['certificate']))continue; $attachments=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>200,'post_mime_type'=>array('image/jpeg','image/png'),'orderby'=>'date','order'=>'DESC')); foreach($attachments as $att){ $file=(string)get_attached_file($att->ID); $hay=strtolower($att->post_title.' '.($file?basename($file):'')); $match=false; foreach($needles as $needle){ if(strpos($hay,strtolower($needle))!==false){$match=true;break;} } if(!$match && $file){$sz=@getimagesize($file); if($sz && (int)$sz[0]>=900 && (int)$sz[1]>=600 && abs(($sz[0]/$sz[1])-(1000/707))<0.08){$match = strpos($hay,'cert')!==false || strpos($hay,'certificate')!==false;}} if($match){$tiers[$n]['certificate']=$att->ID;$changed=true;break;} } } if($changed)update_option('gc_membership_tiers',$tiers); } add_action('admin_init','gc_auto_assign_certificate_templates',8); add_action('admin_init',function(){if(!current_user_can('manage_options')||empty($_POST['gc_save_tiers']))return;if(!isset($_POST['gc_tiers_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gc_tiers_nonce'])),'gc_save_tiers'))return;$rows=$_POST['gc_tier']??array();$out=array();foreach($rows as $n=>$v){$n=absint($n);if(!$n)continue;$name=sanitize_text_field(wp_unslash($v['name']??('Tier '.$n)));$out[$n]=array('name'=>$name?:'Tier '.$n,'price'=>absint($v['price']??0),'certificate'=>absint($v['certificate']??0));}if(empty($out))$out=gc_default_tiers();ksort($out);update_option('gc_membership_tiers',$out);}); function gc_membership_tiers_settings(){if(!current_user_can('manage_options'))return;$tiers=gc_tiers();?>
<div class="wrap"><h1>Game Changers Membership Tiers</h1><p>Edit each tier independently. Every tier has its own price and its own original certificate template. Add future tiers without editing PHP.</p><form method="post"><input type="hidden" name="gc_save_tiers" value="1"><?php wp_nonce_field('gc_save_tiers','gc_tiers_nonce');?><table class="widefat striped" id="gc-tier-table"><thead><tr><th>#</th><th>Tier name</th><th>Price (₦)</th><th>Original certificate template</th><th></th></tr></thead><tbody>
<?php foreach($tiers as $n=>$v):$aid=(int)$v['certificate'];$thumb=$aid?wp_get_attachment_image_url($aid,'thumbnail'):'';?><tr data-tier="<?php echo esc_attr($n);?>"><td><strong>Tier <?php echo esc_html($n);?></strong></td><td><input class="regular-text" name="gc_tier[<?php echo esc_attr($n);?>][name]" value="<?php echo esc_attr($v['name']);?>"></td><td><input type="number" min="0" name="gc_tier[<?php echo esc_attr($n);?>][price]" value="<?php echo esc_attr($v['price']);?>"></td><td><input type="hidden" class="gc-cert-id" name="gc_tier[<?php echo esc_attr($n);?>][certificate]" value="<?php echo esc_attr($aid);?>"><div style="display:flex;align-items:center;gap:10px"><span class="gc-cert-preview" style="display:block;width:92px;height:65px;background:#f2f2f2 center/cover no-repeat;<?php echo $thumb?'background-image:url('.esc_url($thumb).');':'';?>"></span><span><strong class="gc-cert-name"><?php echo $aid?esc_html(get_the_title($aid)):'No template assigned';?></strong><br><button type="button" class="button gc-cert-pick">Upload / Choose Original</button></span></div></td><td><button type="button" class="button-link-delete gc-tier-remove">Remove</button></td></tr><?php endforeach;?></tbody></table><p><button type="button" class="button" id="gc-tier-add">+ Add Tier</button> <?php submit_button('Save Membership Tiers','primary','',false);?></p></form><div style="background:#fff;border:1px solid #ccd0d4;padding:16px;max-width:900px"><strong>Certificate rule:</strong> Tier 1 uses only the Tier 1 artwork, Tier 2 uses only the Tier 2 artwork, and each future tier gets its own separate template slot. The original artwork is not redesigned. If you already uploaded a file containing “Tier 1”/“Cert1” or “Tier 2”/“Cert2”, the system will try to connect it automatically.</div></div>
<script>jQuery(function($){var frame;function bindPick(b){var row=b.closest('tr');frame=wp.media({title:'Original certificate artwork',button:{text:'Use this original'},multiple:false,library:{type:'image'}});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();row.find('.gc-cert-id').val(a.id);row.find('.gc-cert-name').text(a.title||a.filename);row.find('.gc-cert-preview').css('background-image','url('+(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)+')')});frame.open()}$('#gc-tier-add').on('click',function(){var nums=[];$('#gc-tier-table tbody tr').each(function(){nums.push(parseInt($(this).data('tier'),10)||0)});var n=Math.max.apply(null,nums.concat([0]))+1;$('#gc-tier-table tbody').append('<tr data-tier="'+n+'"><td><strong>Tier '+n+'</strong></td><td><input class="regular-text" name="gc_tier['+n+'][name]" value="Tier '+n+'"></td><td><input type="number" min="0" name="gc_tier['+n+'][price]" value="0"></td><td><input type="hidden" class="gc-cert-id" name="gc_tier['+n+'][certificate]" value="0"><div style="display:flex;align-items:center;gap:10px"><span class="gc-cert-preview" style="display:block;width:92px;height:65px;background:#f2f2f2 center/cover no-repeat"></span><span><strong class="gc-cert-name">No template assigned</strong><br><button type="button" class="button gc-cert-pick">Upload / Choose Original</button></span></div></td><td><button type="button" class="button-link-delete gc-tier-remove">Remove</button></td></tr>')});$(document).on('click','.gc-tier-remove',function(){$(this).closest('tr').remove()});$(document).on('click','.gc-cert-pick',function(){bindPick($(this))})});</script><?php } add_action('admin_init',function(){foreach(array('gc_bank_name','gc_bank_account_name','gc_bank_account_number','gc_paystack_public_key','gc_paystack_secret_key','gc_donation_note') as $k)register_setting('gc_payments',$k,array('sanitize_callback'=>$k==='gc_paystack_secret_key'?'sanitize_text_field':($k==='gc_bank_account_number'?'sanitize_text_field':'sanitize_textarea_field')));}); function gc_tier_progress($user_id,$tier){ $ids=array();foreach(get_posts(array('post_type'=>'gc_lesson','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids')) as $id){$lt=(int)(get_post_meta($id,'gc_lesson_tier',true)?:1);if($lt<=$tier)$ids[]=$id;} $done=array_intersect((array)get_user_meta($user_id,'gc_completed_lessons',true),$ids);$total=count($ids);$n=count($done); return array('done'=>$n,'total'=>$total,'percent'=>$total?round($n/$total*100):0); } function gc_certificate_download_url($tier){
	// FIXED: this used to point at ?gc_download_certificate=N, which hit TWO
	// competing hand-rolled PDF generators (this file's gc_send_certificate_pdf,
	// and inc/platform.php's older gc_certificate_pdf which always runs first
	// and always wins). That older one ignores the tier passed in the URL,
	// re-encodes the certificate artwork as a low-fidelity JPEG (or drops it
	// entirely), and writes raw UTF-8 text (the ₦ sign, the — dash) straight
	// into a plain WinAnsi PDF font with no encoding declared — which is
	// exactly what produced the blank/wrong-colour certificate with the
	// garbled "Tier 3  ƒ25,000" header. The Print flow on page-certificate.php
	// already renders the real, full-colour tier artwork correctly and fast
	// (it's just the browser's native print-to-PDF), so Download now reuses
	// that exact same path instead of the broken generators.
	return add_query_arg( 'gc_print_tier', (int) $tier, gc_page_url( 'certificate' ) );
}
/**
 * Renders the tier-by-tier "Leadership Development Pathway" card row —
 * one card per membership tier showing lesson progress, and a "View
 * Certificate" link once that tier hits 100%. Reads the same gc_tiers()
 * / gc_tier_progress() / gc_certificate_download_url() used everywhere
 * else certificates are handled, so it never drifts out of sync.
 */
function gc_render_chapter_pathway_widget( $user_id ) {
	$tiers = gc_tiers();
	if ( empty( $tiers ) ) { return; }
	echo '<div class="gc-pathway-widget"><div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">Your journey</span><h2>Leadership Development Pathway</h2></div></div><div class="gc-pathway-grid">';
	foreach ( $tiers as $n => $tier ) {
		$progress = gc_tier_progress( $user_id, $n );
		$done     = 100 <= (int) $progress['percent'];
		echo '<article class="gc-pathway-card">';
		echo '<span class="gc-pathway-level">Level ' . esc_html( $n ) . '</span>';
		echo '<h3>' . esc_html( $tier['name'] ) . '</h3>';
		echo '<p>' . esc_html( $progress['done'] ) . ' of ' . esc_html( $progress['total'] ) . ' required lessons completed</p>';
		echo '<div class="gc-pathway-bar"><span style="width:' . (int) $progress['percent'] . '%"></span></div>';
		if ( $done ) {
			echo '<span class="gc-pathway-badge gc-pathway-badge--done">✓ Completed</span>';
			echo '<a class="gc-text-link" href="' . esc_url( gc_certificate_download_url( $n ) ) . '">View Certificate →</a>';
		} else {
			echo '<span class="gc-pathway-badge">' . (int) $progress['percent'] . '% complete</span>';
		}
		echo '</article>';
	}
	echo '</div></div>';
}
/**
 * Lets a logged-in member choose the exact name printed on their
 * certificates — either their registered account name, or a different
 * name they type themselves (e.g. to fix a typo or use a formal/full
 * version). Applies to both the PDF download and the on-screen print view,
 * since both already read the 'gc_certificate_name' user meta.
 */
function gc_handle_save_certificate_name() {
	$is_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! empty( $_POST['gc_cert_ajax'] );
	if ( ! is_user_logged_in() ) {
		if ( $is_ajax ) {
			wp_send_json_error( array( 'message' => __( 'Please log in first.', 'game-changers' ) ), 403 );
		}
		wp_die( esc_html__( 'Please log in first.', 'game-changers' ), 403 );
	}
	if ( empty( $_POST['gc_cert_name_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_cert_name_nonce'] ) ), 'gc_cert_name_save' ) ) {
		if ( $is_ajax ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Refresh and try again.', 'game-changers' ) ), 403 );
		}
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}
	$uid    = get_current_user_id();
	$choice = sanitize_key( wp_unslash( $_POST['gc_cert_name_choice'] ?? 'registered' ) );
	$saved_name = '';
	if ( 'custom' === $choice ) {
		$custom = sanitize_text_field( wp_unslash( $_POST['gc_cert_custom_name'] ?? '' ) );
		if ( $custom ) {
			update_user_meta( $uid, 'gc_certificate_name', $custom );
			$saved_name = $custom;
		} else {
			delete_user_meta( $uid, 'gc_certificate_name' );
			$saved_name = wp_get_current_user()->display_name;
		}
	} else {
		delete_user_meta( $uid, 'gc_certificate_name' );
		$saved_name = wp_get_current_user()->display_name;
	}
	if ( $is_ajax ) {
		wp_send_json_success( array(
			'message' => __( 'Saved — your certificates will now use this name.', 'game-changers' ),
			'name'    => $saved_name,
		) );
	}
	wp_safe_redirect( add_query_arg( 'gc_cert_name', 'saved', wp_get_referer() ?: gc_page_url( 'certificate' ) ) . '#gc-cert-name' );
	exit;
}
add_action( 'admin_post_gc_save_certificate_name', 'gc_handle_save_certificate_name' );
add_action( 'wp_ajax_gc_save_certificate_name', 'gc_handle_save_certificate_name' ); function gc_pdf_text($text){return str_replace(array('\\','(',')',"\r","\n"),array('\\\\','\\(','\\)',' ',' '),wp_strip_all_tags($text));} function gc_send_certificate_pdf($tier){ if(!is_user_logged_in()||!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'gc_certificate_'.(int)$tier))wp_die('Invalid certificate request.',403); $uid=get_current_user_id();$tiers=gc_tiers();$tier=(int)$tier;if(!isset($tiers[$tier]))wp_die('Certificate tier not found.',404); $p=gc_tier_progress($uid,$tier);if((int)$p['percent']<100)wp_die('Complete the required lessons first.',403); $aid=gc_tier_template_id($tier);$path=$aid?get_attached_file($aid):false;if(!$path||!file_exists($path))wp_die('The original certificate artwork has not been assigned to this tier yet.',404); $mime=get_post_mime_type($aid);if(!in_array($mime,array('image/jpeg','image/jpg'),true))wp_die('Please assign the original JPEG certificate artwork for this tier.',400); $img=@file_get_contents($path);if($img===false)wp_die('Certificate artwork could not be read.',500); $size=@getimagesize($path);if(!$size)wp_die('Invalid certificate artwork.',400); $w=841.89;$h=595.28;$iw=(float)$size[0];$ih=(float)$size[1];$scale=min($w/$iw,$h/$ih);$dw=$iw*$scale;$dh=$ih*$scale;$x=($w-$dw)/2;$y=($h-$dh)/2; $name=get_user_meta($uid,'gc_certificate_name',true)?:wp_get_current_user()->display_name;$date=current_time('F j, Y');$num='GCN-'.date('Y').'-'.str_pad((string)$uid,5,'0',STR_PAD_LEFT).'-T'.$tier; $objects=array();$objects[1]='<< /Type /Catalog /Pages 2 0 R >>';$objects[2]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>';$objects[4]='<< /Type /XObject /Subtype /Image /Width '.(int)$iw.' /Height '.(int)$ih.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($img).' >>'."\nstream\n".$img."\nendstream"; $stream="q\n".round($dw,2)." 0 0 ".round($dh,2)." ".round($x,2)." ".round($y,2)." cm\n/Im1 Do\nQ\n";
	/*
	 * The tier artwork now has "Successfully Completed" and the course
	 * title baked directly into it, with a blank band reserved above
	 * that for the recipient's name. So the name is the ONLY dynamic
	 * text drawn over the "Successfully Completed" line — anything
	 * else there would duplicate what the artwork already says.
	 * Helvetica-Bold has no embedded width table here, so the name is
	 * approximately centred using a standard average-glyph-width
	 * estimate (~0.58 x font size) rather than left-anchored, so it
	 * reads as "boldly" placed regardless of how long the name is.
	 */
	$name_txt   = gc_pdf_text( $name );
	$name_size  = 34;
	$name_w     = strlen( $name_txt ) * $name_size * 0.58;
	$name_x     = max( 40, ( $w - $name_w ) / 2 );
	$name_y     = 368;
	$stream .= "BT /F1 {$name_size} Tf 0.04 0.28 0.15 rg 1 0 0 1 " . round( $name_x, 1 ) . " {$name_y} Tm (" . $name_txt . ") Tj ET\n";
	$foot_txt  = gc_pdf_text( $date . '   ·   Certificate No. ' . $num );
	$foot_size = 9;
	$foot_w    = strlen( $foot_txt ) * $foot_size * 0.5;
	$foot_x    = max( 40, ( $w - $foot_w ) / 2 );
	$stream .= "BT /F1 {$foot_size} Tf 0.38 0.35 0.3 rg 1 0 0 1 " . round( $foot_x, 1 ) . " 178 Tm (" . $foot_txt . ") Tj ET\n"; $objects[3]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] /Resources << /XObject << /Im1 4 0 R >> /Font << /F1 5 0 R >> >> /Contents 6 0 R >>'; $objects[5]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';$objects[6]='<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream"; $pdf="%PDF-1.4\n";$offsets=array(0);for($i=1;$i<=6;$i++){$offsets[$i]=strlen($pdf);$pdf.=$i." 0 obj\n".$objects[$i]."\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 7\n0000000000 65535 f \n";for($i=1;$i<=6;$i++)$pdf.=str_pad((string)$offsets[$i],10,'0',STR_PAD_LEFT)." 00000 n \n";$pdf.="trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF"; nocache_headers();header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="Game-Changers-'.$tiers[$tier]['name'].'-Certificate.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;exit; } add_action('template_redirect',function(){if(isset($_GET['gc_download_certificate']))gc_send_certificate_pdf(absint($_GET['gc_download_certificate']));},3); add_action('admin_menu',function(){add_options_page('Game Changers Payments','GC Payments','manage_options','gc-payments','gc_payments_settings');}); function gc_payments_settings(){if(!current_user_can('manage_options'))return;?><div class="wrap"><h1>Game Changers Payments</h1><p>Keep these details private. Paystack secret keys must never be printed on the public site.</p><form method="post" action="options.php"><?php settings_fields('gc_payments');?><table class="form-table"><tr><th>Bank name</th><td><input class="regular-text" name="gc_bank_name" value="<?php echo esc_attr(get_option('gc_bank_name',''));?>"></td></tr><tr><th>Account name</th><td><input class="regular-text" name="gc_bank_account_name" value="<?php echo esc_attr(get_option('gc_bank_account_name',''));?>"></td></tr><tr><th>Account number</th><td><input class="regular-text" name="gc_bank_account_number" value="<?php echo esc_attr(get_option('gc_bank_account_number',''));?>"></td></tr><tr><th>Paystack public key</th><td><input class="regular-text" name="gc_paystack_public_key" value="<?php echo esc_attr(get_option('gc_paystack_public_key',''));?>"></td></tr><tr><th>Paystack secret key</th><td><input class="regular-text" type="password" name="gc_paystack_secret_key" value="<?php echo esc_attr(get_option('gc_paystack_secret_key',''));?>"></td></tr><tr><th>Donation note</th><td><textarea class="large-text" name="gc_donation_note" rows="3"><?php echo esc_textarea(get_option('gc_donation_note','Support the work of Game Changers Nigeria.'));?></textarea></td></tr></table><?php submit_button();?></form></div><?php } function gc_payment_box($amount=0,$reference=''){ $bank=array(get_option('gc_bank_name',''),get_option('gc_bank_account_name',''),get_option('gc_bank_account_number',''));$key=get_option('gc_paystack_public_key','');?><div class="gc-pay-box"><?php if($amount):?><strong>₦<?php echo esc_html(number_format_i18n($amount));?></strong><?php endif;?><div class="gc-pay-actions"><button type="button" class="gc-button gc-button-light" data-gc-bank="1">Pay by bank transfer</button><?php if($key&&$amount):?><button type="button" class="gc-button gc-button-gold" data-gc-paystack="1" data-amount="<?php echo esc_attr($amount);?>" data-ref="<?php echo esc_attr($reference);?>">Pay with Paystack</button><?php endif;?></div><dialog class="gc-bank-dialog" data-gc-bank-dialog><button type="button" data-gc-close aria-label="Close">×</button><h3>Bank transfer</h3><p>Transfer to the account below, then keep your payment evidence for confirmation.</p><dl><dt>Bank</dt><dd><?php echo esc_html($bank[0]?:'Add bank name in GC Payments');?></dd><dt>Account name</dt><dd><?php echo esc_html($bank[1]?:'Add account name in GC Payments');?></dd><dt>Account number</dt><dd><?php echo esc_html($bank[2]?:'Add account number in GC Payments');?></dd></dl></dialog></div><?php if($key&&$amount):?><script src="https://js.paystack.co/v2/inline.js"></script><script>(function(){document.querySelectorAll('[data-gc-paystack]').forEach(function(b){b.addEventListener('click',function(){if(!window.PaystackPop)return;var p=new PaystackPop();p.newTransaction({key:<?php echo wp_json_encode($key);?>,email:<?php echo wp_json_encode(is_user_logged_in()?wp_get_current_user()->user_email:'');?>,amount:Number(b.dataset.amount)*100,reference:b.dataset.ref||'',onSuccess:function(t){location.href='<?php echo esc_url(admin_url('admin-post.php'));?>?action=gc_paystack_verify&reference='+encodeURIComponent(t.reference);}});});});})();</script><?php endif;?><script>(function(){document.querySelectorAll('[data-gc-bank]').forEach(function(b){b.addEventListener('click',function(){var d=b.parentNode.parentNode.querySelector('[data-gc-bank-dialog]');if(d&&d.showModal)d.showModal()});});document.querySelectorAll('[data-gc-close]').forEach(function(b){b.addEventListener('click',function(){b.closest('dialog').close()})});})();</script><?php } function gc_certificate_override_profile_fields( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	echo '<h2>Certificate Access</h2><table class="form-table"><tr><th>Grant certificate access</th><td>';
	foreach ( gc_tiers() as $n => $t ) {
		$checked = (bool) get_user_meta( $user->ID, 'gc_cert_override_tier_' . $n, true );
		echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="gc_cert_override_tier_' . esc_attr( $n ) . '" value="1" ' . checked( $checked, true, false ) . '> Allow this member to print/download the ' . esc_html( $t['name'] ) . ' certificate even if under 100% (e.g. completed the requirement through a physical/in-person class)</label>';
	}
	echo '</td></tr></table>';
}
add_action( 'show_user_profile', 'gc_certificate_override_profile_fields' );
add_action( 'edit_user_profile', 'gc_certificate_override_profile_fields' );

function gc_save_certificate_override_profile_fields( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user_id ) ) { return; }
	foreach ( gc_tiers() as $n => $t ) {
		$key = 'gc_cert_override_tier_' . $n;
		if ( ! empty( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, 1 );
		} else {
			delete_user_meta( $user_id, $key );
		}
	}
}
add_action( 'personal_options_update', 'gc_save_certificate_override_profile_fields' );
add_action( 'edit_user_profile_update', 'gc_save_certificate_override_profile_fields' );

/**
 * Admin report: every registered member, grouped by chapter, with their
 * progress on every certificate tier — so an admin can see at a glance who
 * is close to finishing, without opening each profile individually.
 */
add_action( 'admin_menu', function () {
	add_menu_page( 'Member Progress by Chapter', 'Member Progress', 'manage_options', 'gc-member-progress', 'gc_member_progress_report_page', 'dashicons-groups', 26 );
} );

function gc_member_progress_report_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$chapters = get_posts( array( 'post_type' => 'gc_chapter', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$members  = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
	$tiers    = gc_tiers();

	// Group gc_member records by their assigned chapter (gc_member_chapter_id).
	$by_chapter = array( 0 => array() ); // 0 = "No chapter assigned"
	foreach ( $chapters as $c ) { $by_chapter[ $c->ID ] = array(); }
	foreach ( $members as $m ) {
		$chapter_id = (int) get_post_meta( $m->ID, 'gc_member_chapter_id', true );
		if ( ! isset( $by_chapter[ $chapter_id ] ) ) { $chapter_id = 0; }
		$by_chapter[ $chapter_id ][] = $m;
	}

	echo '<div class="wrap"><h1>Member Progress by Chapter</h1><p>Certificate-tier completion for every member, grouped by their chapter.</p>';

	foreach ( $by_chapter as $chapter_id => $chapter_members ) {
		if ( empty( $chapter_members ) ) { continue; }
		$chapter_name = $chapter_id ? get_the_title( $chapter_id ) : 'No chapter assigned';
		echo '<h2 style="margin-top:34px;">' . esc_html( $chapter_name ) . ' <span style="font-weight:400;color:#666;">(' . count( $chapter_members ) . ' member' . ( count( $chapter_members ) === 1 ? '' : 's' ) . ')</span></h2>';
		echo '<table class="widefat striped"><thead><tr><th>Member</th><th>Email</th>';
		foreach ( $tiers as $n => $t ) { echo '<th>' . esc_html( $t['name'] ) . '</th>'; }
		echo '</tr></thead><tbody>';

		foreach ( $chapter_members as $m ) {
			$member_email = get_post_meta( $m->ID, 'gc_member_email', true );
			$user         = $member_email ? get_user_by( 'email', $member_email ) : false;
			$user_id      = $user ? $user->ID : 0;
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $m->ID ) ) . '">' . esc_html( $m->post_title ) . '</a></td><td>' . esc_html( $user ? $user->user_email : ( $member_email ?: '—' ) ) . '</td>';
			foreach ( $tiers as $n => $t ) {
				if ( ! $user_id ) { echo '<td>—</td>'; continue; }
				$progress = gc_tier_progress( $user_id, $n );
				$override = (bool) get_user_meta( $user_id, 'gc_cert_override_tier_' . $n, true );
				$label    = (int) $progress['percent'] . '%';
				if ( (int) $progress['percent'] >= 100 ) {
					echo '<td><strong style="color:#146c3a;">' . esc_html( $label ) . ' ✓</strong></td>';
				} elseif ( $override ) {
					echo '<td>' . esc_html( $label ) . ' <span style="color:#146c3a;">(admin access granted)</span></td>';
				} else {
					echo '<td>' . esc_html( $label ) . '</td>';
				}
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	echo '</div>';
}
function gc_add_cohort_payment_fields(){gc_platform_meta_box('gc_cohort','gc_cohort_payment','Cohort Payment',array('gc_cohort_fee'=>array('label'=>'Cohort fee (₦)','type'=>'number'),'gc_cohort_payment_required'=>array('label'=>'Payment required before enrollment','type'=>'checkbox')));} add_action('add_meta_boxes','gc_add_cohort_payment_fields'); function gc_get_user_premium_tier_v3($uid){if(user_can($uid,'administrator'))return max(array_keys(gc_tiers()));$t=(int)get_user_meta($uid,'gc_premium_tier',true);$tiers=gc_tiers();return isset($tiers[$t])?$t:min(array_keys($tiers));} add_action('template_redirect',function(){if(is_singular('gc_track')&&!is_user_logged_in()){$after=get_permalink();wp_safe_redirect(add_query_arg('gc_after',rawurlencode($after),gc_page_url('premium-login')));exit;}}); add_action('admin_menu',function(){add_menu_page('GC Activity','GC Activity','manage_options','gc-activity','gc_activity_page','dashicons-chart-line',4);},30); function gc_activity_page(){if(!current_user_can('manage_options'))return;$subs=get_posts(array('post_type'=>'gc_submission','post_status'=>array('private','publish'),'posts_per_page'=>20,'orderby'=>'date','order'=>'DESC'));$chap=get_posts(array('post_type'=>'gc_chapter','post_status'=>'publish','posts_per_page'=>50,'orderby'=>'title','order'=>'ASC'));?><div class="wrap"><h1>Game Changers Activity</h1><p>Recent applications, member/course requests and chapter activity.</p><h2>Recent submissions</h2><table class="widefat striped"><thead><tr><th>Date</th><th>Person</th><th>Form</th><th>Email</th><th>Status</th></tr></thead><tbody><?php foreach($subs as $s):?><tr><td><?php echo esc_html(get_the_date('',$s));?></td><td><?php echo esc_html($s->post_title);?></td><td><?php echo esc_html(get_post_meta($s->ID,'gc_form_type_label',true));?></td><td><?php echo esc_html(get_post_meta($s->ID,'gc_email',true));?></td><td><?php echo esc_html(get_post_meta($s->ID,'gc_workflow_status',true)?:'new');?></td></tr><?php endforeach;?></tbody></table><h2>Chapters</h2><table class="widefat striped"><thead><tr><th>Chapter</th><th>State</th><th>Capital</th><th>Leader</th><th>WhatsApp</th></tr></thead><tbody><?php foreach($chap as $c):?><tr><td><?php echo esc_html($c->post_title);?></td><td><?php echo esc_html(get_post_meta($c->ID,'gc_chapter_state',true));?></td><td><?php echo esc_html(get_post_meta($c->ID,'gc_chapter_capital',true));?></td><td><?php echo esc_html(get_post_meta($c->ID,'gc_chapter_leader_user_id',true));?></td><td><?php echo get_post_meta($c->ID,'gc_whatsapp_link',true)?'Yes':'—';?></td></tr><?php endforeach;?></tbody></table></div><?php } add_action('admin_post_gc_paystack_verify','gc_verify_paystack');add_action('admin_post_nopriv_gc_paystack_verify','gc_verify_paystack'); function gc_verify_paystack(){$ref=sanitize_text_field(wp_unslash($_GET['reference']??''));$key=get_option('gc_paystack_secret_key','');if(!$ref||!$key)wp_die('Payment verification is not configured.');$r=wp_remote_get('https://api.paystack.co/transaction/verify/'.rawurlencode($ref),array('timeout'=>20,'headers'=>array('Authorization'=>'Bearer '.$key)));if(is_wp_error($r))wp_die('Could not verify payment.');$body=json_decode(wp_remote_retrieve_body($r),true);if(empty($body['data']['status'])||$body['data']['status']!=='success')wp_die('Payment was not successful.');$u=is_user_logged_in()?wp_get_current_user():null;$id=wp_insert_post(array('post_type'=>'gc_submission','post_status'=>'private','post_title'=>'Paystack Payment — '.$ref));if($id){update_post_meta($id,'gc_form_type_label','Paystack Payment');update_post_meta($id,'gc_payment_reference',$ref);update_post_meta($id,'gc_payment_amount',isset($body['data']['amount'])?$body['data']['amount']/100:0);update_post_meta($id,'gc_workflow_status','paid');if($u)update_post_meta($id,'gc_user_id',$u->ID);}wp_safe_redirect($u?gc_page_url('premium-dashboard'):home_url('/'));exit;} add_action('admin_menu',function(){add_menu_page('Academy Content','Academy Content','edit_posts','gc-academy-content','gc_academy_admin_hub','dashicons-welcome-learn-more',5);},25); function gc_academy_admin_hub(){if(!current_user_can('edit_posts'))return;$links=array('Courses / Lessons'=>'edit.php?post_type=gc_lesson','Premium Documents'=>'edit.php?post_type=gc_premium_doc','Cohorts'=>'edit.php?post_type=gc_cohort','Applications'=>'edit.php?post_type=gc_submission');echo '<div class="wrap"><h1>Academy Content</h1><p>Manage course lessons, documents, cohorts and applications from one place.</p><div style="display:grid;gap:12px;max-width:700px">';foreach($links as $t=>$u)echo '<a class="button button-primary" style="padding:12px;height:auto" href="'.esc_url(admin_url($u)).'">'.esc_html($t).' →</a>';echo '</div></div>';} 
function gc_ensure_five_tiers() {
	$tiers = get_option( 'gc_tiers', array() );
	if ( ! is_array( $tiers ) ) { $tiers = array(); }
	$defaults = gc_default_tiers();
	$changed = false;
	foreach ( $defaults as $n => $d ) {
		if ( empty( $tiers[ $n ] ) ) {
			$tiers[ $n ] = $d;
			$changed = true;
		}
	}
	if ( $changed ) {
		update_option( 'gc_tiers', $tiers );
	}
}
add_action( 'admin_init', 'gc_ensure_five_tiers', 5 );
add_action( 'after_switch_theme', 'gc_ensure_five_tiers', 5 );
