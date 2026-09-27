<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } function gc_register_submission_cpt() { register_post_type( 'gc_submission', array( 'labels' => array( 'name' => __( 'Form Submissions', 'game-changers' ), 'singular_name' => __( 'Submission', 'game-changers' ), 'all_items' => __( 'All Submissions', 'game-changers' ), ), 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-email-alt', 'menu_position' => 25, 'capability_type' => 'page', 'supports' => array( 'title' ), 'show_in_admin_bar' => false, ) ); } add_action( 'init', 'gc_register_submission_cpt' ); function gc_submission_columns( $columns ) { $columns = array( 'cb' => $columns['cb'], 'title' => __( 'From', 'game-changers' ), 'gc_type' => __( 'Form', 'game-changers' ), 'gc_email' => __( 'Email', 'game-changers' ), 'gc_phone' => __( 'Phone', 'game-changers' ), 'date' => __( 'Date', 'game-changers' ), ); return $columns; } add_filter( 'manage_gc_submission_posts_columns', 'gc_submission_columns' ); function gc_submission_column_content( $column, $post_id ) { switch ( $column ) { case 'gc_type': echo esc_html( get_post_meta( $post_id, 'gc_form_type_label', true ) ); break; case 'gc_email': $email = get_post_meta( $post_id, 'gc_email', true ); echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—'; break; case 'gc_phone': echo esc_html( get_post_meta( $post_id, 'gc_phone', true ) ?: '—' ); break; } } add_action( 'manage_gc_submission_posts_custom_column', 'gc_submission_column_content', 10, 2 ); add_action('admin_menu',function(){ add_submenu_page('edit.php?post_type=gc_submission','Academy Applications','Academy Applications','edit_posts','edit.php?post_type=gc_submission&gc_form_filter=academy'); }); add_action('pre_get_posts',function($q){ if(!is_admin()||!$q->is_main_query()||empty($_GET['gc_form_filter'])||$_GET['gc_form_filter']!=='academy'||$q->get('post_type')!=='gc_submission')return; $q->set('meta_query',array(array('key'=>'gc_form_type_label','value'=>'Academy Application','compare'=>'='))); }); function gc_form_types() { return array( 'apply-now' => array( 'label' => __( 'Premium Membership', 'game-changers' ), 'heading' => __( 'Membership Details', 'game-changers' ), 'intro' => __( 'Fill this in to activate your Premium Membership. A coordinator will confirm by email or Telegram.', 'game-changers' ), 'extra' => __( 'Anything we should know about your goals?', 'game-changers' ), 'submit_txt' => __( 'Submit Membership Details', 'game-changers' ), ), 'become-a-member' => array( 'label' => __( 'Membership Enquiry', 'game-changers' ), 'heading' => __( 'Become a Member', 'game-changers' ), 'intro' => __( 'Join your state chapter and the national movement. Fill this in and we\'ll reach out with next steps.', 'game-changers' ), 'extra' => __( 'Which state / chapter are you closest to?', 'game-changers' ), 'submit_txt' => __( 'Submit Membership Request', 'game-changers' ), ), 'mentor-with-us' => array( 'label' => __( 'Mentorship Offer', 'game-changers' ), 'heading' => __( 'Mentor with Us', 'game-changers' ), 'intro' => __( 'Share your expertise with the next generation of leaders. Tell us about your background.', 'game-changers' ), 'extra' => __( 'What can you mentor on?', 'game-changers' ), 'submit_txt' => __( 'Submit Mentor Application', 'game-changers' ), ), 'partner-with-us' => array( 'label' => __( 'Partnership Enquiry', 'game-changers' ), 'heading' => __( 'Partner With Us', 'game-changers' ), 'intro' => __( 'Organisations, donors, and platforms — tell us how you\'d like to partner with Game Changers Nigeria.', 'game-changers' ), 'extra' => __( 'Organisation name & proposed partnership', 'game-changers' ), 'submit_txt' => __( 'Submit Partnership Request', 'game-changers' ), ), ); } function gc_render_inquiry_form( $slug ) { $types = gc_form_types(); if ( empty( $types[ $slug ] ) ) { return; } $f = $types[ $slug ]; $status = isset( $_GET['gc_form'] ) ? sanitize_key( wp_unslash( $_GET['gc_form'] ) ) : ''; ?>
	<div class="gc-form-card">
		<?php if ( 'success' === $status ) : ?>
			<div class="gc-form-notice gc-form-notice--ok">
				<?php esc_html_e( "Thank you — your submission was received. We'll be in touch soon.", 'game-changers' ); ?>
			</div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="gc-form-notice gc-form-notice--err">
				<?php esc_html_e( 'Please fill in your name, email and message, then try again.', 'game-changers' ); ?>
			</div>
		<?php endif; ?>
		<h2 class="gc-form-heading"><?php echo esc_html( $f['heading'] ); ?></h2>
		<p class="gc-form-intro"><?php echo esc_html( $f['intro'] ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form">
			<input type="hidden" name="action" value="gc_submit_inquiry">
			<input type="hidden" name="gc_form_type" value="<?php echo esc_attr( $slug ); ?>">
			<input type="hidden" name="gc_redirect" value="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( 'gc_inquiry_' . $slug, 'gc_inquiry_nonce' ); ?>
			<div class="gc-form-row">
				<label for="gc_name_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Full Name', 'game-changers' ); ?> *</label>
				<input type="text" id="gc_name_<?php echo esc_attr( $slug ); ?>" name="gc_name" required>
			</div>
			<div class="gc-form-row gc-form-row--split">
				<div>
					<label for="gc_email_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Email', 'game-changers' ); ?> *</label>
					<input type="email" id="gc_email_<?php echo esc_attr( $slug ); ?>" name="gc_email" required>
				</div>
				<div>
					<label for="gc_phone_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Phone / WhatsApp', 'game-changers' ); ?></label>
					<input type="tel" id="gc_phone_<?php echo esc_attr( $slug ); ?>" name="gc_phone">
				</div>
			</div>
			<div class="gc-form-row">
				<label for="gc_message_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $f['extra'] ); ?> *</label>
				<textarea id="gc_message_<?php echo esc_attr( $slug ); ?>" name="gc_message" rows="4" required></textarea>
			</div>
			<?php if ( 'apply-now' === $slug ) : ?>
				<div class="gc-form-row gc-form-row--split">
					<div>
						<label for="gc_bank_name_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Bank Name (optional)', 'game-changers' ); ?></label>
						<input type="text" id="gc_bank_name_<?php echo esc_attr( $slug ); ?>" name="gc_bank_name" placeholder="<?php esc_attr_e( 'e.g. GTBank', 'game-changers' ); ?>">
					</div>
					<div>
						<label for="gc_account_name_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Account Name (optional)', 'game-changers' ); ?></label>
						<input type="text" id="gc_account_name_<?php echo esc_attr( $slug ); ?>" name="gc_account_name">
					</div>
				</div>
				<div class="gc-form-row">
					<label for="gc_account_number_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Account Number (optional)', 'game-changers' ); ?></label>
					<input type="text" id="gc_account_number_<?php echo esc_attr( $slug ); ?>" name="gc_account_number" placeholder="<?php esc_attr_e( '10-digit NUBAN', 'game-changers' ); ?>">
				</div>
			<?php endif; ?>
			<?php if ( in_array( $slug, array( 'apply-now', 'become-a-member' ), true ) ) : ?>
			<div class="gc-form-row">
				<label for="gc_referral_tag_<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Referral Name / Membership Tag (optional)', 'game-changers' ); ?></label>
				<input type="text" id="gc_referral_tag_<?php echo esc_attr( $slug ); ?>" name="gc_referral_tag" placeholder="<?php esc_attr_e( 'e.g. GC-7K2QN9', 'game-changers' ); ?>">
				<div class="gc-referral-hint"><?php esc_html_e( 'If someone invited you, enter their membership tag so they get credit.', 'game-changers' ); ?></div>
			</div><?php endif; ?>
			<button type="submit" class="gc-form-submit"><?php echo esc_html( $f['submit_txt'] ); ?></button>
		</form>
		<p class="gc-form-alt">
			<?php esc_html_e( 'Prefer email?', 'game-changers' ); ?>
			<a href="mailto:<?php echo esc_attr( gc_contact_email() ); ?>"><?php echo esc_html( gc_contact_email() ); ?></a>
		</p>
	</div>
	<?php
} function gc_contact_email() { return gc_opt( 'gc_contact_email', get_option( 'admin_email' ) ); } function gc_handle_inquiry_submit() { $slug = isset( $_POST['gc_form_type'] ) ? sanitize_key( wp_unslash( $_POST['gc_form_type'] ) ) : ''; $types = gc_form_types(); if ( empty( $types[ $slug ] ) || ! isset( $_POST['gc_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_inquiry_nonce'] ) ), 'gc_inquiry_' . $slug ) ) { wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'game-changers' ) ); } $redirect = ! empty( $_POST['gc_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['gc_redirect'] ) ) : home_url( '/' ); $name = sanitize_text_field( wp_unslash( $_POST['gc_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['gc_email'] ?? '' ) ); $phone = sanitize_text_field( wp_unslash( $_POST['gc_phone'] ?? '' ) ); $message = sanitize_textarea_field( wp_unslash( $_POST['gc_message'] ?? '' ) ); $bank = sanitize_text_field( wp_unslash( $_POST['gc_bank_name'] ?? '' ) ); $acct_name = sanitize_text_field( wp_unslash( $_POST['gc_account_name'] ?? '' ) ); $acct_num = sanitize_text_field( wp_unslash( $_POST['gc_account_number'] ?? '' ) ); $ref_raw = sanitize_text_field( wp_unslash( $_POST['gc_referral_tag'] ?? '' ) ); if ( empty( $name ) || ! is_email( $email ) || empty( $message ) ) { wp_safe_redirect( add_query_arg( 'gc_form', 'error', $redirect ) . '#gc-form-anchor' ); exit; } $f = $types[ $slug ]; $post_id = wp_insert_post( array( 'post_type' => 'gc_submission', 'post_title' => sprintf( '%s — %s', $name, $f['label'] ), 'post_status' => 'publish', 'post_content' => $message, ) ); if ( $post_id && ! is_wp_error( $post_id ) ) { update_post_meta( $post_id, 'gc_form_type_label', $f['label'] ); update_post_meta( $post_id, 'gc_email', $email ); update_post_meta( $post_id, 'gc_phone', $phone ); if ( $bank ) { update_post_meta( $post_id, 'gc_bank_name', $bank ); } if ( $acct_name ) { update_post_meta( $post_id, 'gc_account_name', $acct_name ); } if ( $acct_num ) { update_post_meta( $post_id, 'gc_account_number', $acct_num ); } if ( $ref_raw ) { update_post_meta( $post_id, 'gc_referrer_tag_raw', $ref_raw ); if ( function_exists( 'gc_get_member_by_tag' ) ) { $referrer = gc_get_member_by_tag( $ref_raw ); if ( $referrer ) { update_post_meta( $post_id, 'gc_referrer_id', $referrer->ID ); } } } } $to = gc_contact_email(); $subject = sprintf( __( 'New %s — Game Changers Nigeria', 'game-changers' ), $f['label'] ); $body = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n\nMessage:\n{$message}\n\nSubmitted from: {$redirect}"; if ( $bank || $acct_name || $acct_num ) { $body .= "\n\nBank: " . ( $bank ?: '—' ) . "\nAccount Name: " . ( $acct_name ?: '—' ) . "\nAccount Number: " . ( $acct_num ?: '—' ); } if ( $ref_raw ) { $body .= "\nReferred by tag: {$ref_raw}"; } $headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' ); wp_mail( $to, $subject, $body, $headers ); if ( 'apply-now' === $slug && function_exists( 'gc_send_member_welcome_email' ) ) { gc_send_member_welcome_email( $email, $name ); } elseif ( 'partner-with-us' === $slug && function_exists( 'gc_send_partner_welcome_email' ) ) { gc_send_partner_welcome_email( $email, $name ); } wp_safe_redirect( add_query_arg( 'gc_form', 'success', $redirect ) . '#gc-form-anchor' ); exit; } add_action( 'admin_post_gc_submit_inquiry', 'gc_handle_inquiry_submit' ); add_action( 'admin_post_nopriv_gc_submit_inquiry', 'gc_handle_inquiry_submit' ); function gc_form_styles() { if ( ! is_page( array( 'apply-now', 'become-a-member', 'mentor-with-us', 'partner-with-us' ) ) ) { return; } ?>
	<style>
		.gc-form-card { max-width: 620px; margin: 0 auto; background: var(--card-bg); border: 1px solid var(--line); border-radius: 14px; padding: 36px clamp(20px,5vw,44px); }
		.gc-form-heading { font-family: 'Playfair Display', serif; font-size: clamp(24px,3vw,32px); color: var(--ink); margin-bottom: 10px; }
		.gc-form-intro { color: var(--muted); line-height: 1.7; margin-bottom: 26px; }
		.gc-form-row { margin-bottom: 18px; }
		.gc-form-row--split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		.gc-form label { display: block; font-size: 12px; font-weight: 600; letter-spacing: .04em; color: var(--ink); margin-bottom: 6px; }
		.gc-form input, .gc-form textarea {
			width: 100%; font-family: 'Inter', sans-serif; font-size: 14px; padding: 12px 14px;
			border: 1px solid var(--line); border-radius: 7px; background: #fff; color: var(--ink);
		}
		.gc-form input:focus, .gc-form textarea:focus { outline: none; border-color: var(--green); }
		.gc-form-submit {
			width: 100%; background: var(--green); color: #fff; border: none; padding: 14px 20px;
			border-radius: 7px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
			cursor: pointer; transition: background .2s;
		}
		.gc-form-submit:hover { background: var(--green-mid); }
		.gc-form-alt { text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }
		.gc-form-alt a { color: var(--green); font-weight: 600; }
		.gc-referral-hint { font-size: 11px; color: var(--muted); margin-top: 6px; }
		.gc-form-notice { padding: 12px 16px; border-radius: 7px; font-size: 13px; margin-bottom: 22px; }
		.gc-form-notice--ok { background: rgba(0,140,81,0.1); color: var(--green); border: 1px solid rgba(0,140,81,0.25); }
		.gc-form-notice--err { background: rgba(200,40,40,0.08); color: #a02222; border: 1px solid rgba(200,40,40,0.2); }
		.gc-selar-cta {
			display: block; text-align: center; background: var(--gold); color: var(--ink); font-weight: 700;
			text-transform: uppercase; letter-spacing: .06em; font-size: 13px; padding: 16px 20px; border-radius: 8px;
			text-decoration: none; margin-bottom: 26px; transition: background .2s;
		}
		.gc-selar-cta:hover { background: var(--gold-light); }
		.gc-selar-cta small { display: block; font-weight: 500; text-transform: none; letter-spacing: 0; margin-top: 4px; font-size: 11px; opacity: .75; }
	</style>
	<?php
} add_action( 'wp_head', 'gc_form_styles' ); 