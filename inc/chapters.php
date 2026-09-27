<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } function gc_ng_state_capitals() { return array( 'Abia'=>'Umuahia','Adamawa'=>'Yola','Akwa Ibom'=>'Uyo','Anambra'=>'Awka','Bauchi'=>'Bauchi','Bayelsa'=>'Yenagoa','Benue'=>'Makurdi','Borno'=>'Maiduguri','Cross River'=>'Calabar','Delta'=>'Asaba','Ebonyi'=>'Abakaliki','Edo'=>'Benin City','Ekiti'=>'Ado-Ekiti','Enugu'=>'Enugu','FCT (Abuja)'=>'Abuja','Gombe'=>'Gombe','Imo'=>'Owerri','Jigawa'=>'Dutse','Kaduna'=>'Kaduna','Kano'=>'Kano','Katsina'=>'Katsina','Kebbi'=>'Birnin Kebbi','Kogi'=>'Lokoja','Kwara'=>'Ilorin','Lagos'=>'Ikeja','Nasarawa'=>'Lafia','Niger'=>'Minna','Ogun'=>'Abeokuta','Ondo'=>'Akure','Osun'=>'Osogbo','Oyo'=>'Ibadan','Plateau'=>'Jos','Rivers'=>'Port Harcourt','Sokoto'=>'Sokoto','Taraba'=>'Jalingo','Yobe'=>'Damaturu','Zamfara'=>'Gusau' ); } function gc_ng_states() { return array( 'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT (Abuja)', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara', ); } function gc_seed_state_chapters() { foreach ( gc_ng_states() as $state ) { $existing = get_page_by_title( $state, OBJECT, 'gc_chapter' ); if ( $existing ) { continue; } wp_insert_post( array( 'post_type' => 'gc_chapter', 'post_title' => $state, 'post_status' => 'publish', 'post_content' => sprintf( __( 'The %s Chapter is part of the Game Changers Nigeria grassroots network. Add a description of local activities here, and paste your WhatsApp group link in the box on the right so members can join.', 'game-changers' ), $state ), ) ); } } add_action( 'after_switch_theme', 'gc_seed_state_chapters' ); function gc_chapter_meta_box() { add_meta_box( 'gc_chapter_details', __( 'Chapter Details', 'game-changers' ), 'gc_chapter_meta_box_html', 'gc_chapter', 'side', 'high' ); } add_action( 'add_meta_boxes', 'gc_chapter_meta_box' ); function gc_chapter_meta_box_html( $post ) { wp_nonce_field( 'gc_chapter_save', 'gc_chapter_nonce' ); $whatsapp = get_post_meta( $post->ID, 'gc_whatsapp_link', true ); $organiser = get_post_meta( $post->ID, 'gc_chapter_organiser', true ); $state = get_post_meta( $post->ID, 'gc_chapter_state', true ) ?: $post->post_title; $capital = get_post_meta( $post->ID, 'gc_chapter_capital', true ) ?: ( gc_ng_state_capitals()[ $state ] ?? '' ); ?>
	<p>
		<label for="gc_whatsapp_link"><strong><?php esc_html_e( 'WhatsApp Group Link', 'game-changers' ); ?></strong></label><br>
		<input type="url" id="gc_whatsapp_link" name="gc_whatsapp_link" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="https://chat.whatsapp.com/..." style="width:100%; margin-top:6px;">
		<span style="display:block; color:#666; font-size:12px; margin-top:4px;">
			<?php esc_html_e( 'Paste the invite link from WhatsApp (Group Info → Invite via Link). Members tapping "Join Chapter" for this state will be sent here.', 'game-changers' ); ?>
		</span>
	</p>
	<p>
		<label for="gc_chapter_state"><strong><?php esc_html_e( 'State', 'game-changers' ); ?></strong></label><br>
		<select id="gc_chapter_state" name="gc_chapter_state" style="width:100%;margin-top:6px;">
			<?php foreach ( gc_ng_states() as $item ) : ?><option value="<?php echo esc_attr( $item ); ?>" <?php selected( $state, $item ); ?>><?php echo esc_html( $item ); ?></option><?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="gc_chapter_capital"><strong><?php esc_html_e( 'State Capital', 'game-changers' ); ?></strong></label><br>
		<input type="text" id="gc_chapter_capital" name="gc_chapter_capital" value="<?php echo esc_attr( $capital ); ?>" style="width:100%;margin-top:6px;">
	</p>
	<p>
		<label for="gc_chapter_organiser"><strong><?php esc_html_e( 'Local Organiser (optional)', 'game-changers' ); ?></strong></label><br>
		<input type="text" id="gc_chapter_organiser" name="gc_chapter_organiser" value="<?php echo esc_attr( $organiser ); ?>" style="width:100%; margin-top:6px;">
	</p>
	<p>
		<label for="gc_chapter_featured"><strong><?php esc_html_e( 'Feature on Homepage / Chapters Spotlight?', 'game-changers' ); ?></strong></label><br>
		<select id="gc_chapter_featured" name="gc_chapter_featured" style="width:100%;margin-top:6px;">
			<option value=""><?php esc_html_e( 'No', 'game-changers' ); ?></option>
			<option value="1" <?php selected( get_post_meta( $post->ID, 'gc_chapter_featured', true ), '1' ); ?>><?php esc_html_e( 'Yes', 'game-changers' ); ?></option>
		</select>
	</p>
	<?php if ( $post->post_parent ) : ?>
		<p style="color:#666;font-size:12px;"><?php esc_html_e( 'This is a Local Group. It inherits its State from its parent Chapter.', 'game-changers' ); ?></p>
	<?php endif; ?>
	<?php
} function gc_chapter_save_meta( $post_id ) { if ( ! isset( $_POST['gc_chapter_nonce'] ) || ! wp_verify_nonce( $_POST['gc_chapter_nonce'], 'gc_chapter_save' ) ) { return; } if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; } if ( ! current_user_can( 'edit_post', $post_id ) ) { return; } if ( isset( $_POST['gc_whatsapp_link'] ) ) { update_post_meta( $post_id, 'gc_whatsapp_link', esc_url_raw( wp_unslash( $_POST['gc_whatsapp_link'] ) ) ); } if ( isset( $_POST['gc_chapter_state'] ) ) { update_post_meta( $post_id, 'gc_chapter_state', sanitize_text_field( wp_unslash( $_POST['gc_chapter_state'] ) ) ); } if ( isset( $_POST['gc_chapter_capital'] ) ) { update_post_meta( $post_id, 'gc_chapter_capital', sanitize_text_field( wp_unslash( $_POST['gc_chapter_capital'] ) ) ); } if ( isset( $_POST['gc_chapter_organiser'] ) ) { update_post_meta( $post_id, 'gc_chapter_organiser', sanitize_text_field( wp_unslash( $_POST['gc_chapter_organiser'] ) ) ); } update_post_meta( $post_id, 'gc_chapter_featured', isset( $_POST['gc_chapter_featured'] ) && '1' === $_POST['gc_chapter_featured'] ? '1' : '' ); } add_action( 'save_post_gc_chapter', 'gc_chapter_save_meta' );

/* ────────────────────────────────────────────────────────────────
 * Chapter helpers: hierarchy, member counts, featured chapters
 * (mirrors the World Changers Network chapter model: a top-level
 * state Chapter can have child Local Groups, each Chapter tracks
 * its approved/pending member counts, and any Chapter can be
 * spotlighted.)
 * ──────────────────────────────────────────────────────────────── */
function gc_chapter_effective_state( $chapter_id ) {
	$chapter_id = (int) $chapter_id;
	$state = get_post_meta( $chapter_id, 'gc_chapter_state', true );
	if ( $state ) {
		return $state;
	}
	// Walk the FULL ancestor chain (State Chapter -> City/LGA Group -> Campus/Community
	// Group can be 2 levels deep), not just the immediate parent, so a Campus group
	// still correctly inherits the State set on its grandparent State Chapter.
	foreach ( get_post_ancestors( $chapter_id ) as $ancestor_id ) {
		$state = get_post_meta( $ancestor_id, 'gc_chapter_state', true );
		if ( $state ) {
			return $state;
		}
	}
	return get_the_title( $chapter_id );
}
/**
 * 0 = State Chapter, 1 = City/LGA Group, 2+ = Campus/Community Group.
 */
function gc_chapter_depth( $chapter_id ) {
	return count( get_post_ancestors( (int) $chapter_id ) );
}
/**
 * Human label for a chapter's OWN level.
 */
function gc_chapter_level_label( $chapter_id ) {
	$depth = gc_chapter_depth( $chapter_id );
	if ( 0 === $depth ) {
		return __( 'State Chapter', 'game-changers' );
	}
	if ( 1 === $depth ) {
		return __( 'City/LGA Group', 'game-changers' );
	}
	return __( 'Campus/Community Group', 'game-changers' );
}
/**
 * Human label for what a chapter's CHILDREN are called, one level down.
 */
function gc_chapter_child_level_label( $chapter_id ) {
	$depth = gc_chapter_depth( $chapter_id );
	if ( 0 === $depth ) {
		return __( 'City/LGA Group', 'game-changers' );
	}
	return __( 'Campus/Community Group', 'game-changers' );
}
function gc_get_chapter_local_groups( $chapter_id ) {
	return get_posts( array(
		'post_type'      => 'gc_chapter',
		'post_status'    => 'publish',
		'post_parent'    => (int) $chapter_id,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
}
function gc_get_chapter_members( $chapter_id, $status = 'approved' ) {
	$meta_query = array(
		array( 'key' => 'gc_member_chapter_id', 'value' => (int) $chapter_id, 'compare' => '=' ),
	);
	if ( $status ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array( 'key' => 'gc_member_status', 'value' => $status, 'compare' => '=' ),
		);
		if ( 'approved' === $status ) {
			// Legacy members (created before the approval workflow existed) have no
			// gc_member_status meta at all — treat them as approved, not stuck pending.
			$meta_query[1][] = array( 'key' => 'gc_member_status', 'compare' => 'NOT EXISTS' );
		}
	}
	return get_posts( array(
		'post_type'      => 'gc_member',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => $meta_query,
	) );
}
function gc_get_chapter_member_count( $chapter_id ) {
	return count( gc_get_chapter_members( $chapter_id, 'approved' ) );
}
function gc_get_chapter_pending_count( $chapter_id ) {
	return count( gc_get_chapter_members( $chapter_id, 'pending' ) );
}
function gc_get_chapter_leader_user( $chapter_id ) {
	$uid = (int) get_post_meta( (int) $chapter_id, 'gc_chapter_leader_user_id', true );
	return $uid ? get_userdata( $uid ) : false;
}
/**
 * A chapter's own public page already carries the "Request Chapter
 * Membership" join form (see single-gc_chapter.php) — so that page IS
 * the referral link. No separate tracking code needed: every join
 * submitted from it is already scoped to this exact chapter.
 */
function gc_get_chapter_referral_link( $chapter_id ) {
	$url = get_permalink( (int) $chapter_id );
	return $url ? $url . '#gc-join' : home_url( '/' );
}
/**
 * Everyone who has ever asked to join this specific chapter through
 * that link — approved or still pending — since every one of them
 * arrived via the chapter's own join form.
 */
function gc_get_chapter_referral_count( $chapter_id ) {
	return gc_get_chapter_member_count( $chapter_id ) + gc_get_chapter_pending_count( $chapter_id );
}
function gc_get_chapter_upcoming_events( $chapter_id, $limit = 3 ) {
	return get_posts( array(
		'post_type'      => 'gc_event',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_key'       => 'gc_event_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => 'gc_event_chapter_id', 'value' => (int) $chapter_id, 'compare' => '=' ),
			array( 'key' => 'gc_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=' ),
		),
	) );
}
function gc_count_chapter_upcoming_events( $chapter_id ) {
	return count( gc_get_chapter_upcoming_events( $chapter_id, -1 ) );
}
function gc_get_featured_chapters( $limit = 3 ) {
	return get_posts( array(
		'post_type'      => 'gc_chapter',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_query'     => array( array( 'key' => 'gc_chapter_featured', 'value' => '1', 'compare' => '=' ) ),
	) );
} function gc_get_all_chapters() { $posts = get_posts( array( 'post_type' => 'gc_chapter', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish', 'post_parent' => 0, ) ); $chapters = array(); foreach ( $posts as $post ) { $state = get_post_meta( $post->ID, 'gc_chapter_state', true ) ?: $post->post_title; $chapters[] = array( 'id' => $post->ID, 'state' => $state, 'capital' => get_post_meta( $post->ID, 'gc_chapter_capital', true ) ?: ( gc_ng_state_capitals()[ $state ] ?? '' ), 'title' => $post->post_title, 'permalink' => get_permalink( $post ), 'whatsapp' => get_post_meta( $post->ID, 'gc_whatsapp_link', true ), 'organiser' => get_post_meta( $post->ID, 'gc_chapter_organiser', true ), 'featured' => (bool) get_post_meta( $post->ID, 'gc_chapter_featured', true ), 'member_count' => gc_get_chapter_member_count( $post->ID ), 'local_groups' => count( gc_get_chapter_local_groups( $post->ID ) ), ); } return $chapters; } function gc_backfill_chapter_locations() { $map = gc_ng_state_capitals(); foreach ( get_posts(array('post_type'=>'gc_chapter','posts_per_page'=>-1,'post_status'=>'any')) as $chapter ) { $state = get_post_meta($chapter->ID,'gc_chapter_state',true) ?: $chapter->post_title; if ( isset($map[$state]) ) { update_post_meta($chapter->ID,'gc_chapter_state',$state); if ( ! get_post_meta($chapter->ID,'gc_chapter_capital',true) ) update_post_meta($chapter->ID,'gc_chapter_capital',$map[$state]); } } } add_action('admin_init','gc_backfill_chapter_locations'); add_action('admin_init', function(){ if (wp_count_posts('gc_chapter')->publish < count(gc_ng_states())) gc_seed_state_chapters(); }); add_filter('manage_gc_chapter_posts_columns',function($cols){$out=array();foreach($cols as $k=>$v){$out[$k]=$v;if($k==='title'){$out['gc_level']='Level';$out['gc_state']='State';$out['gc_capital']='Capital';$out['gc_organiser']='Organiser';$out['gc_members']='Members';$out['gc_pending']='Pending';$out['gc_featured']='Featured';}}return $out;}); add_action('manage_gc_chapter_posts_custom_column',function($col,$post_id){if($col==='gc_level')echo esc_html(gc_chapter_level_label($post_id));if($col==='gc_state')echo esc_html(gc_chapter_effective_state($post_id));if($col==='gc_capital')echo esc_html(get_post_meta($post_id,'gc_chapter_capital',true));if($col==='gc_organiser')echo esc_html(get_post_meta($post_id,'gc_chapter_organiser',true));if($col==='gc_members')echo (int) gc_get_chapter_member_count($post_id);if($col==='gc_pending'){$p=(int) gc_get_chapter_pending_count($post_id); echo $p ? '<strong style="color:#a02222;">'.$p.'</strong>' : '0';}if($col==='gc_featured')echo get_post_meta($post_id,'gc_chapter_featured',true)==='1' ? '★' : '—';},10,2); add_action('admin_menu',function(){add_submenu_page('edit.php?post_type=gc_chapter','Create a Chapter','+ Create Chapter','edit_posts','post-new.php?post_type=gc_chapter');}); 