<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — CONTENT IMPORTER
 *
 * Lets an admin bulk-load the content pack (or any future batch of
 * challenges/lessons) from a CSV with columns:
 *   Day, Year, Category, Phase, Title, Content, Points
 *
 * Re-running an import with the same Day+Year+post type UPDATES the
 * existing item instead of duplicating it, so the same file can be used to
 * correct content later.
 *
 * This is intentionally CSV rather than reading the .xlsx directly — a bare
 * WordPress install has no spreadsheet library, and CSV keeps the whole
 * import a plain-text, auditable operation with no extra dependencies.
 * Export each sheet of the source workbook to CSV once, then upload here.
 */

function gc_engagement_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=gc_challenge',
		__( 'Import Content Pack', 'game-changers' ),
		__( 'Import Content Pack', 'game-changers' ),
		'manage_options',
		'gc-import-content',
		'gc_render_import_page'
	);
	add_submenu_page(
		'edit.php?post_type=gc_challenge',
		__( 'Engagement Settings', 'game-changers' ),
		__( 'Engagement Settings', 'game-changers' ),
		'manage_options',
		'gc-engagement-settings',
		'gc_render_engagement_settings_page'
	);
}
add_action( 'admin_menu', 'gc_engagement_admin_menu' );

function gc_render_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$result = null;
	if ( isset( $_POST['gc_import_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_import_nonce'] ) ), 'gc_import_content' ) ) {
		$post_type = 'lessons' === ( $_POST['gc_import_kind'] ?? '' ) ? 'gc_daily_lesson' : 'gc_challenge';
		if ( ! empty( $_FILES['gc_import_file']['tmp_name'] ) ) {
			$result = gc_import_content_csv( $_FILES['gc_import_file']['tmp_name'], $post_type );
		} else {
			$result = array( 'error' => __( 'Please choose a CSV file.', 'game-changers' ) );
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Content Pack', 'game-changers' ); ?></h1>
		<p><?php esc_html_e( 'Upload a CSV with columns: Day, Year, Category, Phase, Title, Content, Points. Re-uploading with the same Day + Year updates the existing item rather than duplicating it.', 'game-changers' ); ?></p>

		<?php if ( $result ) : ?>
			<?php if ( ! empty( $result['error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $result['error'] ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success">
					<p><?php printf( esc_html__( 'Imported %1$d new and updated %2$d existing items. %3$d rows skipped.', 'game-changers' ), (int) $result['created'], (int) $result['updated'], (int) $result['skipped'] ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'gc_import_content', 'gc_import_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="gc_import_kind"><?php esc_html_e( 'Content Type', 'game-changers' ); ?></label></th>
					<td>
						<select name="gc_import_kind" id="gc_import_kind">
							<option value="challenges"><?php esc_html_e( 'Daily Challenges', 'game-changers' ); ?></option>
							<option value="lessons"><?php esc_html_e( '5-Minute Lessons', 'game-changers' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="gc_import_file"><?php esc_html_e( 'CSV File', 'game-changers' ); ?></label></th>
					<td><input type="file" name="gc_import_file" id="gc_import_file" accept=".csv" required></td>
				</tr>
			</table>
			<?php submit_button( __( 'Import', 'game-changers' ) ); ?>
		</form>
	</div>
	<?php
}

function gc_import_content_csv( $tmp_path, $post_type ) {
	$handle = fopen( $tmp_path, 'r' );
	if ( ! $handle ) {
		return array( 'error' => __( 'Could not read the uploaded file.', 'game-changers' ) );
	}

	$header = fgetcsv( $handle );
	if ( ! $header ) {
		fclose( $handle );
		return array( 'error' => __( 'The file appears to be empty.', 'game-changers' ) );
	}
	$header = array_map( function( $h ) { return strtolower( trim( $h ) ); }, $header );

	$created = 0;
	$updated = 0;
	$skipped = 0;

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$data = array_combine( $header, array_pad( $row, count( $header ), '' ) );
		if ( false === $data ) { $skipped++; continue; }

		$day     = isset( $data['day'] ) ? absint( $data['day'] ) : 0;
		$year    = isset( $data['year'] ) ? absint( $data['year'] ) : 1;
		$title   = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$content = isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '';
		$category = isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '';
		$phase    = isset( $data['phase'] ) ? sanitize_text_field( $data['phase'] ) : '';
		$points   = isset( $data['points'] ) && is_numeric( $data['points'] ) ? (int) $data['points'] : '';

		if ( ! $title || ! $day ) { $skipped++; continue; }

		$existing = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'gc_content_day', 'value' => $day, 'compare' => '=' ),
				array( 'key' => 'gc_content_year', 'value' => $year, 'compare' => '=' ),
			),
		) );

		$post_args = array(
			'post_type'    => $post_type,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
		);

		if ( ! empty( $existing ) ) {
			$post_args['ID'] = $existing[0];
			$post_id = wp_update_post( $post_args );
			$updated++;
		} else {
			$post_id = wp_insert_post( $post_args );
			$created++;
		}

		if ( ! $post_id || is_wp_error( $post_id ) ) { $skipped++; continue; }

		update_post_meta( $post_id, 'gc_content_day', $day );
		update_post_meta( $post_id, 'gc_content_year', $year );
		update_post_meta( $post_id, 'gc_content_phase', $phase );
		if ( '' !== $points ) {
			update_post_meta( $post_id, 'gc_content_points', $points );
		}
		if ( '' === get_post_meta( $post_id, 'gc_content_active', true ) ) {
			update_post_meta( $post_id, 'gc_content_active', 1 );
		}
		if ( $category ) {
			wp_set_object_terms( $post_id, $category, 'gc_content_category', false );
		}
	}

	fclose( $handle );
	return array( 'created' => $created, 'updated' => $updated, 'skipped' => $skipped );
}

function gc_render_engagement_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	if ( isset( $_POST['gc_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_settings_nonce'] ) ), 'gc_settings_save' ) ) {
		update_option( 'gc_engagement_settings', array(
			'timezone'              => sanitize_text_field( wp_unslash( $_POST['gc_timezone'] ?? 'Africa/Lagos' ) ),
			'grace_day_enabled'     => ! empty( $_POST['gc_grace_day_enabled'] ) ? 1 : 0,
			'notifications_enabled' => ! empty( $_POST['gc_notifications_enabled'] ) ? 1 : 0,
			'launch_date'           => sanitize_text_field( wp_unslash( $_POST['gc_launch_date'] ?? current_time( 'Y-m-d' ) ) ),
		) );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'game-changers' ) . '</p></div>';
	}

	$settings = get_option( 'gc_engagement_settings', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Engagement Settings', 'game-changers' ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'gc_settings_save', 'gc_settings_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="gc_timezone"><?php esc_html_e( 'Timezone', 'game-changers' ); ?></label></th>
					<td><input type="text" name="gc_timezone" id="gc_timezone" value="<?php echo esc_attr( $settings['timezone'] ?? 'Africa/Lagos' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="gc_launch_date"><?php esc_html_e( 'Rotation Start Date', 'game-changers' ); ?></label></th>
					<td><input type="date" name="gc_launch_date" id="gc_launch_date" value="<?php echo esc_attr( $settings['launch_date'] ?? current_time( 'Y-m-d' ) ); ?>"><p class="description"><?php esc_html_e( 'Day 1 of the active pool is shown on this date; it rotates forward one item per day from here.', 'game-changers' ); ?></p></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Grace Day', 'game-changers' ); ?></th>
					<td><label><input type="checkbox" name="gc_grace_day_enabled" <?php checked( ! empty( $settings['grace_day_enabled'] ) ); ?>> <?php esc_html_e( 'Allow one missed day without breaking a streak (disabled by default)', 'game-changers' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Notifications', 'game-changers' ); ?></th>
					<td><label><input type="checkbox" name="gc_notifications_enabled" <?php checked( ! empty( $settings['notifications_enabled'] ) ); ?>> <?php esc_html_e( 'Email members on badge earned / level up', 'game-changers' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
