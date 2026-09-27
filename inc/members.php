<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } function gc_register_member_cpt() { register_post_type( 'gc_member', array( 'labels' => array( 'name' => __( 'Members', 'game-changers' ), 'singular_name' => __( 'Member', 'game-changers' ), 'all_items' => __( 'All Members', 'game-changers' ), 'search_items' => __( 'Search Members', 'game-changers' ), ), 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-groups', 'menu_position' => 24, 'capability_type' => 'page', 'supports' => array( 'title' ), ) ); } add_action( 'init', 'gc_register_member_cpt' ); function gc_generate_membership_tag() { $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; do { $code = 'GC-'; for ( $i = 0; $i < 6; $i++ ) { $code .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ]; } } while ( gc_get_member_by_tag( $code ) ); return $code; } function gc_get_member_by_tag( $tag ) { if ( empty( $tag ) ) { return null; } $found = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_query' => array( array( 'key' => 'gc_member_tag', 'value' => strtoupper( trim( $tag ) ), 'compare' => '=', ), ), ) ); return ! empty( $found ) ? $found[0] : null; } function gc_count_referrals( $member_id ) { $q = new WP_Query( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'gc_member_referrer_id', 'value' => $member_id, ), ), ) ); return $q->found_posts; } function gc_member_columns( $columns ) { return array( 'cb' => $columns['cb'], 'title' => __( 'Name', 'game-changers' ), 'gc_tag' => __( 'Membership Tag', 'game-changers' ), 'gc_state' => __( 'State', 'game-changers' ), 'gc_email' => __( 'Email / Phone', 'game-changers' ), 'gc_referrer' => __( 'Referred By', 'game-changers' ), 'gc_refcount' => __( 'Direct Referrals', 'game-changers' ), 'date' => __( 'Joined', 'game-changers' ), ); } add_filter( 'manage_gc_member_posts_columns', 'gc_member_columns' ); function gc_member_column_content( $column, $post_id ) { switch ( $column ) { case 'gc_tag': echo '<code>' . esc_html( get_post_meta( $post_id, 'gc_member_tag', true ) ) . '</code>'; break; case 'gc_state': echo esc_html( get_post_meta( $post_id, 'gc_member_state', true ) ?: '—' ); break; case 'gc_email': $email = get_post_meta( $post_id, 'gc_member_email', true ); $phone = get_post_meta( $post_id, 'gc_member_phone', true ); echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—'; echo $phone ? '<br>' . esc_html( $phone ) : ''; break; case 'gc_referrer': $ref_id = (int) get_post_meta( $post_id, 'gc_member_referrer_id', true ); if ( $ref_id ) { echo '<a href="' . esc_url( get_edit_post_link( $ref_id ) ) . '">' . esc_html( get_the_title( $ref_id ) ) . '</a>'; echo ' <code>' . esc_html( get_post_meta( $ref_id, 'gc_member_tag', true ) ) . '</code>'; } else { $raw = get_post_meta( $post_id, 'gc_member_referrer_tag_raw', true ); echo $raw ? esc_html( $raw ) . ' ' . esc_html__( '(unmatched)', 'game-changers' ) : '—'; } break; case 'gc_refcount': echo (int) gc_count_referrals( $post_id ); break; } } add_action( 'manage_gc_member_posts_custom_column', 'gc_member_column_content', 10, 2 ); function gc_member_details_meta_box() { add_meta_box( 'gc_member_details', __( 'Member Details', 'game-changers' ), 'gc_member_details_meta_box_html', 'gc_member', 'normal', 'high' ); } add_action( 'add_meta_boxes', 'gc_member_details_meta_box' ); function gc_member_details_meta_box_html( $post ) { $rows = array( __( 'Membership Tag', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_tag', true ), __( 'Email', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_email', true ), __( 'Phone', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_phone', true ), __( 'Town/City', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_town', true ), __( 'LGA', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_lga', true ), __( 'State', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_state', true ), __( 'Interest', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_interest', true ), __( 'Bank Name', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_bank_name', true ), __( 'Account Name', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_account_name', true ), __( 'Account Number', 'game-changers' ) => get_post_meta( $post->ID, 'gc_member_account_number', true ), ); $ref_id = (int) get_post_meta( $post->ID, 'gc_member_referrer_id', true ); $ref_raw = get_post_meta( $post->ID, 'gc_member_referrer_tag_raw', true ); echo '<table class="widefat" style="border:none;"><tbody>'; foreach ( $rows as $label => $value ) { echo '<tr><td style="width:180px;font-weight:600;">' . esc_html( $label ) . '</td><td>' . esc_html( $value ?: '—' ) . '</td></tr>'; } echo '<tr><td style="font-weight:600;">' . esc_html__( 'Referred By', 'game-changers' ) . '</td><td>'; if ( $ref_id ) { echo '<a href="' . esc_url( get_edit_post_link( $ref_id ) ) . '">' . esc_html( get_the_title( $ref_id ) ) . '</a> (' . esc_html( get_post_meta( $ref_id, 'gc_member_tag', true ) ) . ')'; } else { echo esc_html( $ref_raw ? $ref_raw . ' ' . __( '(unmatched)', 'game-changers' ) : '—' ); } echo '</td></tr>'; echo '<tr><td style="font-weight:600;">' . esc_html__( 'Direct Referrals', 'game-changers' ) . '</td><td>' . (int) gc_count_referrals( $post->ID ) . '</td></tr>'; echo '</tbody></table>'; } function gc_register_account_details_page() { add_submenu_page( 'edit.php?post_type=gc_member', __( 'Account Details', 'game-changers' ), __( 'Account Details', 'game-changers' ), 'edit_posts', 'gc-account-details', 'gc_render_account_details_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Premium Members', 'game-changers' ), __( 'Premium Members', 'game-changers' ), 'edit_posts', 'gc-premium-members', 'gc_render_premium_members_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Partnership', 'game-changers' ), __( 'Partnership', 'game-changers' ), 'edit_posts', 'gc-partnership', 'gc_render_partnership_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Member Email', 'game-changers' ), __( 'Member Email', 'game-changers' ), 'edit_posts', 'gc-member-email', 'gc_render_member_email_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Enlisting Members', 'game-changers' ), __( 'Enlisting Members', 'game-changers' ), 'edit_posts', 'gc-enlisting-members', 'gc_render_enlisting_members_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Member Tags', 'game-changers' ), __( 'Member Tags', 'game-changers' ), 'edit_posts', 'gc-member-tags', 'gc_render_member_tags_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Mentor Applications', 'game-changers' ), __( 'Mentor Applications', 'game-changers' ), 'edit_posts', 'gc-mentor-applications', 'gc_render_mentor_applications_page' ); add_submenu_page( 'edit.php?post_type=gc_member', __( 'Certificates', 'game-changers' ), __( 'Certificates', 'game-changers' ), 'edit_posts', 'gc-certificates', 'gc_render_certificates_page' ); } add_action( 'admin_menu', 'gc_register_account_details_page' ); function gc_render_certificates_page() { if ( isset( $_POST['gc_cert_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_cert_nonce'] ) ), 'gc_cert_save' ) ) { $user_id = absint( $_POST['gc_cert_user_id'] ?? 0 ); $file_id = absint( $_POST['gc_cert_file_id'] ?? 0 ); if ( $user_id ) { if ( $file_id ) { update_user_meta( $user_id, 'gc_member_certificate_id', $file_id ); } else { delete_user_meta( $user_id, 'gc_member_certificate_id' ); } echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Certificate saved.', 'game-changers' ) . '</p></div>'; } } $premium_users = get_users( array( 'role' => 'premium_member', 'orderby' => 'display_name' ) ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Certificates', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Upload a personalised certificate for any Premium Member who has completed their course — it appears on their dashboard immediately, instead of the shared template.', 'game-changers' ); ?></p>
		<?php if ( empty( $premium_users ) ) : ?>
			<p><?php esc_html_e( 'No approved Premium Members yet.', 'game-changers' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
						<th><?php esc_html_e( 'Progress', 'game-changers' ); ?></th>
						<th><?php esc_html_e( 'Certificate', 'game-changers' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $premium_users as $u ) : $progress = function_exists( 'gc_user_lesson_progress' ) ? gc_user_lesson_progress( $u->ID ) : array( 'percent' => 0 ); $cert_id = (int) get_user_meta( $u->ID, 'gc_member_certificate_id', true ); $cert_url = $cert_id ? wp_get_attachment_url( $cert_id ) : ''; ?>
						<tr>
							<td><?php echo esc_html( $u->display_name ); ?><br><span style="color:#999;font-size:12px;"><?php echo esc_html( $u->user_email ); ?></span></td>
							<td><?php echo esc_html( $progress['percent'] ); ?>%</td>
							<td>
								<form method="post" class="gc-cert-form">
									<input type="hidden" name="gc_cert_user_id" value="<?php echo esc_attr( $u->ID ); ?>">
									<input type="hidden" name="gc_cert_file_id" class="gc-cert-file-id" value="<?php echo esc_attr( $cert_id ); ?>">
									<?php wp_nonce_field( 'gc_cert_save', 'gc_cert_nonce' ); ?>
									<span class="gc-cert-file-name"><?php echo $cert_url ? esc_html( basename( $cert_url ) ) : esc_html__( 'No certificate uploaded.', 'game-changers' ); ?></span><br>
									<button type="button" class="button gc-cert-upload-btn" style="margin:6px 6px 0 0;"><?php esc_html_e( 'Select File', 'game-changers' ); ?></button>
									<button type="submit" class="button button-primary" style="margin-top:6px;"><?php esc_html_e( 'Save', 'game-changers' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<script>
	jQuery(function($){
		$('.gc-cert-upload-btn').on('click', function(e){
			e.preventDefault();
			var row = $(this).closest('form');
			var frame = wp.media({ title: '<?php echo esc_js( __( 'Select Certificate File', 'game-changers' ) ); ?>', button: { text: '<?php echo esc_js( __( 'Use this file', 'game-changers' ) ); ?>' }, multiple: false });
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				row.find('.gc-cert-file-id').val(att.id);
				row.find('.gc-cert-file-name').text(att.filename);
			});
			frame.open();
		});
	});
	</script>
	<?php
} function gc_render_mentor_applications_page() { $submissions = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'gc_form_type_label', 'value' => 'Mentorship Offer', ), ), ) ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mentor Applications', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Everyone who has submitted the "Mentor with Us" form — what they wrote about their expertise and how they want to mentor.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:160px;"><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Email', 'game-changers' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Phone', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'What They Can Mentor On', 'game-changers' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No mentor applications yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $submissions as $s ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></td>
							<td><?php $e = get_post_meta( $s->ID, 'gc_email', true ); ?><a href="mailto:<?php echo esc_attr( $e ); ?>"><?php echo esc_html( $e ?: '—' ); ?></a></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_phone', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( $s->post_content ?: '—' ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $s->post_date ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_member_tags_page() { $rows = array(); $members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1 ) ); foreach ( $members as $m ) { $tag = get_post_meta( $m->ID, 'gc_member_tag', true ); if ( ! $tag ) { continue; } $rows[] = array( 'name' => $m->post_title, 'tag' => $tag, 'type' => __( 'Free (Academy)', 'game-changers' ), 'edit' => get_edit_post_link( $m->ID ), ); } $premium_users = get_users( array( 'role' => 'premium_member' ) ); foreach ( $premium_users as $u ) { $tag = get_user_meta( $u->ID, 'gc_premium_referral_tag', true ); if ( ! $tag ) { continue; } $rows[] = array( 'name' => $u->display_name, 'tag' => $tag, 'type' => __( 'Premium', 'game-changers' ), 'edit' => get_edit_user_link( $u->ID ), ); } ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Member Tags', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Every membership tag on the site, free and Premium, with the name it belongs to.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Tag', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Type', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No tags yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $r['edit'] ); ?>"><?php echo esc_html( $r['name'] ); ?></a></td>
							<td><code><?php echo esc_html( $r['tag'] ); ?></code></td>
							<td><?php echo esc_html( $r['type'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_member_email_page() { $rows = array(); $submissions = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => -1, ) ); foreach ( $submissions as $s ) { $email = get_post_meta( $s->ID, 'gc_email', true ); if ( ! $email ) { continue; } $rows[] = array( 'name' => $s->post_title, 'email' => $email, 'phone' => get_post_meta( $s->ID, 'gc_phone', true ), 'source' => get_post_meta( $s->ID, 'gc_form_type_label', true ) ?: __( 'Form Submission', 'game-changers' ), 'date' => $s->post_date, 'edit' => get_edit_post_link( $s->ID ), ); } $members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1, ) ); foreach ( $members as $m ) { $email = get_post_meta( $m->ID, 'gc_member_email', true ); if ( ! $email ) { continue; } $rows[] = array( 'name' => $m->post_title, 'email' => $email, 'phone' => get_post_meta( $m->ID, 'gc_member_phone', true ), 'source' => __( 'Academy Enlistment', 'game-changers' ), 'date' => $m->post_date, 'edit' => get_edit_post_link( $m->ID ), ); } usort( $rows, function( $a, $b ) { return strtotime( $b['date'] ) <=> strtotime( $a['date'] ); } ); $seen = array(); $rows = array_values( array_filter( $rows, function( $r ) use ( &$seen ) { $key = strtolower( trim( $r['email'] ) ); if ( isset( $seen[ $key ] ) ) { return false; } $seen[ $key ] = true; return true; } ) ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Member Email', 'game-changers' ); ?></h1>
		<p><?php printf( esc_html__( 'Every registered email across the site — %d total.', 'game-changers' ), count( $rows ) ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:200px;"><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th style="width:220px;"><?php esc_html_e( 'Email', 'game-changers' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Phone', 'game-changers' ); ?></th>
					<th style="width:160px;"><?php esc_html_e( 'Registered Via', 'game-changers' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No registrations yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $r['edit'] ); ?>"><?php echo esc_html( $r['name'] ); ?></a></td>
							<td><a href="mailto:<?php echo esc_attr( $r['email'] ); ?>"><?php echo esc_html( $r['email'] ); ?></a></td>
							<td><?php echo esc_html( $r['phone'] ?: '—' ); ?></td>
							<td><?php echo esc_html( $r['source'] ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $r['date'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_enlisting_members_page() { $members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', ) ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Enlisting Members', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Everyone who has enlisted through the free Join the Academy page.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Tag', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Email / Phone', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Town/City', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'LGA', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'State', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Interest', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $members ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No enlistments yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $members as $m ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $m->ID ) ); ?>"><?php echo esc_html( $m->post_title ); ?></a></td>
							<td><code><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_tag', true ) ); ?></code></td>
							<td>
								<?php echo esc_html( get_post_meta( $m->ID, 'gc_member_email', true ) ?: '—' ); ?><br>
								<?php echo esc_html( get_post_meta( $m->ID, 'gc_member_phone', true ) ?: '' ); ?>
							</td>
							<td><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_town', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_lga', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_state', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $m->ID, 'gc_member_interest', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $m->post_date ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_partnership_page() { $submissions = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'gc_form_type_label', 'value' => 'Partnership Enquiry', ), ), ) ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Partnership Enquiries', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Everyone who has submitted the Partner With Us form, and what they wrote about how they want to partner.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:160px;"><?php esc_html_e( 'Name / Organisation', 'game-changers' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Email', 'game-changers' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Phone', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Partnership Proposal', 'game-changers' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No partnership enquiries yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $submissions as $s ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_email', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_phone', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( wp_trim_words( $s->post_content, 40 ) ?: '—' ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $s->post_date ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_premium_members_page() { $submissions = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'gc_form_type_label', 'value' => 'Premium Membership', ), ), ) ); if ( isset( $_GET['gc_approved'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Premium login created and emailed to the member.', 'game-changers' ) . '</p></div>'; } ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Premium Members', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Everyone who has submitted the Premium Membership form, with their contact and bank details. Confirm payment yourself (Selar or bank transfer) before approving — that is what actually creates their dashboard login.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Email', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Bank', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Account Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Account Number', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Referral Tag', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Dashboard Login', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Assigned Mentor', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr><td colspan="10"><?php esc_html_e( 'No Premium Membership submissions yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $submissions as $s ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_email', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_phone', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_bank_name', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_account_name', true ) ?: '—' ); ?></td>
							<td><code><?php echo esc_html( get_post_meta( $s->ID, 'gc_account_number', true ) ?: '—' ); ?></code></td>
							<td><?php echo esc_html( get_post_meta( $s->ID, 'gc_referrer_tag_raw', true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $s->post_date ) ) ); ?></td>
							<td><?php if ( function_exists( 'gc_render_approve_button' ) ) { gc_render_approve_button( $s->ID ); } ?></td>
							<td>
								<?php
 $gc_approved_user = get_post_meta( $s->ID, 'gc_premium_approved', true ); if ( $gc_approved_user && function_exists( 'gc_render_mentor_assign_control' ) ) { gc_render_mentor_assign_control( (int) $gc_approved_user ); } else { echo '<span style="color:#999;font-size:12px;">' . esc_html__( 'Approve login first', 'game-changers' ) . '</span>'; } ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_render_account_details_page() { $rows = array(); $submissions = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => -1, ) ); foreach ( $submissions as $s ) { $bank = get_post_meta( $s->ID, 'gc_bank_name', true ); $name = get_post_meta( $s->ID, 'gc_account_name', true ); $num = get_post_meta( $s->ID, 'gc_account_number', true ); if ( ! $bank && ! $name && ! $num ) { continue; } $rows[] = array( 'who' => $s->post_title, 'source' => get_post_meta( $s->ID, 'gc_form_type_label', true ), 'bank' => $bank, 'name' => $name, 'num' => $num, 'date' => $s->post_date, 'edit' => get_edit_post_link( $s->ID ), ); } $members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => -1, ) ); foreach ( $members as $m ) { $bank = get_post_meta( $m->ID, 'gc_member_bank_name', true ); $name = get_post_meta( $m->ID, 'gc_member_account_name', true ); $num = get_post_meta( $m->ID, 'gc_member_account_number', true ); if ( ! $bank && ! $name && ! $num ) { continue; } $rows[] = array( 'who' => $m->post_title, 'source' => __( 'Academy Enlistment', 'game-changers' ), 'bank' => $bank, 'name' => $name, 'num' => $num, 'date' => $m->post_date, 'edit' => get_edit_post_link( $m->ID ), ); } usort( $rows, function( $a, $b ) { return strtotime( $b['date'] ) <=> strtotime( $a['date'] ); } ); ?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Account Details — All Forms', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Every bank account number submitted across the site — Premium Membership, Become a Member, Mentor with Us, Partner With Us, and Join the Academy — in one place.', 'game-changers' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Submitted Via', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Bank', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Account Name', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Account Number', 'game-changers' ); ?></th>
					<th><?php esc_html_e( 'Date', 'game-changers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No account details submitted yet.', 'game-changers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $r['edit'] ); ?>"><?php echo esc_html( $r['who'] ); ?></a></td>
							<td><?php echo esc_html( $r['source'] ?: '—' ); ?></td>
							<td><?php echo esc_html( $r['bank'] ?: '—' ); ?></td>
							<td><?php echo esc_html( $r['name'] ?: '—' ); ?></td>
							<td><code><?php echo esc_html( $r['num'] ?: '—' ); ?></code></td>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $r['date'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
} function gc_member_admin_search( $query ) { global $pagenow; if ( ! is_admin() || 'edit.php' !== $pagenow || empty( $query->query['post_type'] ) || 'gc_member' !== $query->query['post_type'] || empty( $query->query_vars['s'] ) ) { return; } $search = $query->query_vars['s']; $query->set( 's', '' ); $query->set( 'meta_query', array( 'relation' => 'OR', array( 'key' => 'gc_member_tag', 'value' => $search, 'compare' => 'LIKE' ), array( 'key' => 'gc_member_email', 'value' => $search, 'compare' => 'LIKE' ), array( 'key' => 'gc_member_phone', 'value' => $search, 'compare' => 'LIKE' ), ) ); add_filter( 'get_meta_sql', 'gc_member_search_meta_or_title' ); } add_action( 'pre_get_posts', 'gc_member_admin_search' ); function gc_member_search_meta_or_title( $sql ) { global $wpdb; remove_filter( 'get_meta_sql', 'gc_member_search_meta_or_title' ); if ( ! empty( $_GET['s'] ) ) { $like = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%'; $sql['where'] = " AND ( {$wpdb->posts}.post_title LIKE '{$like}' OR ( " . ltrim( $sql['where'], ' AND' ) . ' ) )'; } return $sql; } function gc_handle_enlistment_submit() { if ( ! isset( $_POST['gc_enlist_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_enlist_nonce'] ) ), 'gc_enlist' ) ) { wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'game-changers' ) ); } $redirect = ! empty( $_POST['gc_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['gc_redirect'] ) ) : home_url( '/' ); $name = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) ); $phone = sanitize_text_field( wp_unslash( $_POST['gc_phone'] ?? '' ) ); $state = sanitize_text_field( wp_unslash( $_POST['gc_state'] ?? '' ) ); $town = sanitize_text_field( wp_unslash( $_POST['gc_town'] ?? '' ) ); $lga = sanitize_text_field( wp_unslash( $_POST['gc_lga'] ?? '' ) ); $interest = sanitize_text_field( wp_unslash( $_POST['gc_interest'] ?? '' ) ); $ref_raw = sanitize_text_field( wp_unslash( $_POST['gc_referral_tag'] ?? '' ) ); $bank = sanitize_text_field( wp_unslash( $_POST['gc_bank_name'] ?? '' ) ); $acct_name = sanitize_text_field( wp_unslash( $_POST['gc_account_name'] ?? '' ) ); $acct_num = sanitize_text_field( wp_unslash( $_POST['gc_account_number'] ?? '' ) ); if ( empty( $name ) || ! is_email( $email ) ) { wp_safe_redirect( add_query_arg( 'gc_enlist', 'error', $redirect ) . '#gc-enlist-form' ); exit; }
	if ( ! function_exists( 'gc_email_is_premium_tier_member' ) || ! gc_email_is_premium_tier_member( $email ) ) {
		wp_safe_redirect( add_query_arg( 'gc_enlist', 'notpremium', $redirect ) . '#gc-enlist-form' );
		exit;
	}
	$had_bank_already = function_exists( 'gc_get_payout_account_by_email' ) && gc_get_payout_account_by_email( $email );
	$existing_members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => 'gc_member_email', 'meta_value' => $email ) );
	if ( ! empty( $existing_members ) ) {
		$member_id = (int) $existing_members[0]->ID;
		wp_update_post( array( 'ID' => $member_id, 'post_title' => $name ) );
	} else {
		$member_id = wp_insert_post( array( 'post_type' => 'gc_member', 'post_title' => $name, 'post_status' => 'publish', 'post_content' => $interest, ) );
	}
	if ( ! $member_id || is_wp_error( $member_id ) ) { wp_safe_redirect( add_query_arg( 'gc_enlist', 'error', $redirect ) . '#gc-enlist-form' ); exit; } $tag = get_post_meta( $member_id, 'gc_member_tag', true );
	if ( ! $tag ) {
		$tag = gc_generate_membership_tag();
		update_post_meta( $member_id, 'gc_member_tag', $tag );
	}
	/* Free membership WP account — same credentials used later for Premium upgrade */
	$pass = isset( $_POST['gc_password'] ) ? (string) wp_unslash( $_POST['gc_password'] ) : '';
	$pass2 = isset( $_POST['gc_password_confirm'] ) ? (string) wp_unslash( $_POST['gc_password_confirm'] ) : '';
	$user_id = 0;
	if ( strlen( $pass ) >= 8 && $pass === $pass2 ) {
		$existing = email_exists( $email );
		if ( $existing ) {
			$user_id = (int) $existing;
			// Do not change password of existing accounts silently
		} else {
			$user_id = wp_create_user( $email, $pass, $email );
			if ( is_wp_error( $user_id ) ) {
				$user_id = 0;
			} else {
				wp_update_user( array(
					'ID'           => $user_id,
					'display_name' => $name,
					'first_name'   => strtok( trim( $name ), ' ' ),
					'role'         => 'free_member',
				) );
				$user_obj = new WP_User( $user_id );
				$user_obj->set_role( 'free_member' );
				update_user_meta( $user_id, 'gc_membership_status', 'free' );
				update_user_meta( $user_id, 'gc_member_cpt_id', $member_id );
				if ( $phone ) {
					update_user_meta( $user_id, 'gc_phone', $phone );
				}
				if ( $state ) {
					update_user_meta( $user_id, 'gc_state', $state );
				}
			}
		}
		if ( $user_id ) {
			update_post_meta( $member_id, 'gc_member_user_id', $user_id );
			update_post_meta( $member_id, 'gc_member_status', 'free' );
		}
	}
 update_post_meta( $member_id, 'gc_member_email', $email ); update_post_meta( $member_id, 'gc_member_phone', $phone ); update_post_meta( $member_id, 'gc_member_state', $state ); if ( $town ) { update_post_meta( $member_id, 'gc_member_town', $town ); } if ( $lga ) { update_post_meta( $member_id, 'gc_member_lga', $lga ); } update_post_meta( $member_id, 'gc_member_interest', $interest ); if ( function_exists( 'gc_save_payout_account' ) ) {
		$uid_for_bank = email_exists( $email ) ? (int) email_exists( $email ) : 0;
		gc_save_payout_account( $email, $bank, $acct_name, $acct_num, $uid_for_bank );
		// Link member row only if still empty after save attempt
		if ( ! get_post_meta( $member_id, 'gc_member_account_number', true ) && ! $had_bank_already && $acct_num ) {
			update_post_meta( $member_id, 'gc_member_bank_name', $bank );
			update_post_meta( $member_id, 'gc_member_account_name', $acct_name );
			update_post_meta( $member_id, 'gc_member_account_number', preg_replace( '/\D+/', '', (string) $acct_num ) );
		}
	} elseif ( ! $had_bank_already ) {
		if ( $bank ) { update_post_meta( $member_id, 'gc_member_bank_name', $bank ); }
		if ( $acct_name ) { update_post_meta( $member_id, 'gc_member_account_name', $acct_name ); }
		if ( $acct_num ) { update_post_meta( $member_id, 'gc_member_account_number', $acct_num ); }
	} if ( ! empty( $ref_raw ) ) { update_post_meta( $member_id, 'gc_member_referrer_tag_raw', $ref_raw ); $referrer = gc_get_member_by_tag( $ref_raw ); if ( $referrer ) { update_post_meta( $member_id, 'gc_member_referrer_id', $referrer->ID ); } } $submission_id = wp_insert_post( array( 'post_type' => 'gc_submission', 'post_title' => sprintf( '%s — %s', $name, __( 'Academy Application', 'game-changers' ) ), 'post_status' => 'publish', 'post_content' => $interest, ) ); if ( $submission_id && ! is_wp_error( $submission_id ) ) { update_post_meta( $submission_id, 'gc_form_type_label', __( 'Academy Application', 'game-changers' ) ); update_post_meta( $submission_id, 'gc_email', $email ); update_post_meta( $submission_id, 'gc_phone', $phone ); } $to = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' ); $subject = sprintf( __( 'New Academy Enlistment — %s', 'game-changers' ), $name ); $body = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nTown/City: " . ( $town ?: '—' ) . "\nLGA: " . ( $lga ?: '—' ) . "\nState: {$state}\nInterest: {$interest}\nMembership Tag: {$tag}\nReferred by tag: " . ( $ref_raw ?: '—' ) . "\nBank: " . ( $bank ?: '—' ) . "\nAccount Name: " . ( $acct_name ?: '—' ) . "\nAccount Number: " . ( $acct_num ?: '—' ); wp_mail( $to, $subject, $body ); if ( function_exists( 'gc_send_member_welcome_email' ) ) { gc_send_member_welcome_email( $email, $name ); } if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) && ! is_user_logged_in() ) {
		wp_set_current_user( (int) $user_id );
		wp_set_auth_cookie( (int) $user_id, true );
	}
	if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) && function_exists( 'gc_page_url' ) ) {
		$redirect = add_query_arg(
			array( 'gc_enlist' => 'success', 'gc_tag' => rawurlencode( $tag ) ),
			gc_page_url( 'premium-dashboard' )
		);
	} else {
		$redirect = add_query_arg( array( 'gc_enlist' => 'success', 'gc_tag' => rawurlencode( $tag ), ), $redirect ) . '#gc-enlist-form';
	}
	wp_safe_redirect( $redirect );
	exit;
} add_action( 'admin_post_gc_submit_enlistment', 'gc_handle_enlistment_submit' ); add_action( 'admin_post_nopriv_gc_submit_enlistment', 'gc_handle_enlistment_submit' ); function gc_is_registered_email( $email ) { if ( ! is_email( $email ) ) { return false; } $email = strtolower( trim( $email ) ); if ( email_exists( $email ) ) { return true; } $member_match = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'gc_member_email', 'value' => $email, 'compare' => '=' ) ), ) ); if ( ! empty( $member_match ) ) { return true; } $submission_match = get_posts( array( 'post_type' => 'gc_submission', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'gc_email', 'value' => $email, 'compare' => '=' ) ), ) ); return ! empty( $submission_match ); } function gc_handle_ebook_lead() { if ( ! isset( $_POST['gc_ebook_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_ebook_nonce'] ) ), 'gc_ebook_lead' ) ) { wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'game-changers' ) ); } $redirect = ! empty( $_POST['gc_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['gc_redirect'] ) ) : home_url( '/' ); $name = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) ); if ( empty( $name ) || ! is_email( $email ) ) { wp_safe_redirect( add_query_arg( 'gc_ebook', 'error', $redirect ) ); exit; } if ( ! gc_is_registered_email( $email ) ) { wp_safe_redirect( add_query_arg( 'gc_ebook', 'notregistered', $redirect ) ); exit; } $file_id = (int) get_theme_mod( 'gc_free_ebook_file' ); if ( ! $file_id ) { wp_safe_redirect( add_query_arg( 'gc_ebook', 'nofile', $redirect ) ); exit; } $post_id = wp_insert_post( array( 'post_type' => 'gc_submission', 'post_title' => $name . ' — ' . __( 'Free Ebook', 'game-changers' ), 'post_status' => 'publish', ) ); if ( $post_id && ! is_wp_error( $post_id ) ) { update_post_meta( $post_id, 'gc_form_type_label', 'Free Ebook' ); update_post_meta( $post_id, 'gc_email', $email ); } $token = wp_generate_password( 32, false ); set_transient( 'gc_ebook_token_' . $token, array( 'email' => $email, 'name' => $name ), 30 * MINUTE_IN_SECONDS ); $verify_url = add_query_arg( 'gc_ebook_verify', $token, home_url( '/' ) ); $subject = __( 'Confirm your email to get the free ebook', 'game-changers' ); $body = "Hello " . strtok( trim( $name ), ' ' ) . ",\n\n"; $body .= "Click the link below to confirm this is your email address and download the 7-Day Activation Challenge ebook:\n\n"; $body .= "{$verify_url}\n\n"; $body .= "This link expires in 30 minutes and works once.\n\nAfter activation, join free membership to continue with 30-day short nuggets.\n\n"; $body .= "Strategic regards,\n" . ( function_exists( 'gc_signature_name' ) ? gc_signature_name() : 'Game Changers Nigeria' ); if ( function_exists( 'gc_dispatch_welcome_email' ) ) { gc_dispatch_welcome_email( $email, $subject, $body ); } else { wp_mail( $email, $subject, $body ); } wp_safe_redirect( add_query_arg( 'gc_ebook', 'checkemail', $redirect ) ); exit; } add_action( 'admin_post_gc_submit_ebook', 'gc_handle_ebook_lead' ); add_action( 'admin_post_nopriv_gc_submit_ebook', 'gc_handle_ebook_lead' ); function gc_handle_ebook_verify() { if ( empty( $_GET['gc_ebook_verify'] ) ) { return; } $token = sanitize_text_field( wp_unslash( $_GET['gc_ebook_verify'] ) ); $data = get_transient( 'gc_ebook_token_' . $token ); if ( ! $data ) { wp_die( esc_html__( 'This confirmation link has expired or was already used. Please request the ebook again.', 'game-changers' ), 403 ); } delete_transient( 'gc_ebook_token_' . $token ); $file_id = (int) get_theme_mod( 'gc_free_ebook_file' ); if ( ! $file_id ) { wp_die( esc_html__( 'The ebook file is not uploaded yet — please check back soon.', 'game-changers' ) ); } wp_safe_redirect( wp_get_attachment_url( $file_id ) ); exit; } add_action( 'template_redirect', 'gc_handle_ebook_verify' ); 