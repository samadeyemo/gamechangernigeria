<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — CONTENT LIBRARY
 *
 * Two content types feed the Daily Loop:
 *   gc_challenge    — Daily Challenges
 *   gc_daily_lesson — Five-Minute Lessons
 *
 * NOTE: `gc_lesson` already exists in inc/premium-access.php for the paid
 * Academy video course. `gc_daily_lesson` is a deliberately different post
 * type so the two systems never collide.
 *
 * Admins manage everything from wp-admin: no developer needed to add more
 * content. The Daily Scheduler (inc/engagement-scheduler.php) is what turns
 * this library into "today's" content automatically.
 */

function gc_register_content_library_cpts() {
	register_post_type( 'gc_challenge', array(
		'labels' => array(
			'name'          => __( 'Daily Challenges', 'game-changers' ),
			'singular_name' => __( 'Daily Challenge', 'game-changers' ),
			'add_new_item'  => __( 'Add New Challenge', 'game-changers' ),
			'edit_item'     => __( 'Edit Challenge', 'game-changers' ),
			'all_items'     => __( 'All Challenges', 'game-changers' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'challenges' ),
		'menu_icon'    => 'dashicons-flag',
		'supports'     => array( 'title', 'editor' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'gc_daily_lesson', array(
		'labels' => array(
			'name'          => __( '5-Minute Lessons', 'game-changers' ),
			'singular_name' => __( '5-Minute Lesson', 'game-changers' ),
			'add_new_item'  => __( 'Add New Lesson', 'game-changers' ),
			'edit_item'     => __( 'Edit Lesson', 'game-changers' ),
			'all_items'     => __( 'All 5-Minute Lessons', 'game-changers' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'daily-lessons' ),
		'menu_icon'    => 'dashicons-clock',
		'supports'     => array( 'title', 'editor' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'gc_register_content_library_cpts' );

/**
 * Shared taxonomy for Category (Personal Growth, Career, Leadership, ...)
 * used by both content types so filtering/reporting stays consistent.
 */
function gc_register_content_taxonomies() {
	register_taxonomy( 'gc_content_category', array( 'gc_challenge', 'gc_daily_lesson' ), array(
		'labels'       => array( 'name' => __( 'Categories', 'game-changers' ) ),
		'public'       => true,
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'engagement-category' ),
	) );
}
add_action( 'init', 'gc_register_content_taxonomies' );

/**
 * Meta boxes — mirrors the Developer Import Guide columns (Day, Year,
 * Phase, Points) plus the admin-facing scheduling/status fields the brief
 * asked for (Active, Featured, Scheduled Date, Repeat Rule).
 */
function gc_content_library_meta_boxes() {
	foreach ( array( 'gc_challenge', 'gc_daily_lesson' ) as $post_type ) {
		gc_platform_meta_box( $post_type, 'gc_content_schedule', __( 'Scheduling & Points', 'game-changers' ), array(
			'gc_content_day'            => array( 'label' => __( 'Day # (1–730, sequence position)', 'game-changers' ), 'type' => 'number' ),
			'gc_content_year'           => array( 'label' => __( 'Year (1 or 2)', 'game-changers' ), 'type' => 'number' ),
			'gc_content_phase'          => array( 'label' => __( 'Phase (e.g. Foundation, Advanced Impact)', 'game-changers' ) ),
			'gc_content_points'         => array( 'label' => __( 'Points (blank = use default from Points settings)', 'game-changers' ), 'type' => 'number' ),
			'gc_content_scheduled_date' => array( 'label' => __( 'Force this exact date (optional, overrides rotation)', 'game-changers' ), 'type' => 'date' ),
			'gc_content_active'         => array( 'label' => __( 'Active (1 = in rotation, 0 = paused)', 'game-changers' ), 'type' => 'number' ),
			'gc_content_featured'       => array( 'label' => __( 'Featured (1/0)', 'game-changers' ), 'type' => 'number' ),
		) );
	}
}
add_action( 'add_meta_boxes', 'gc_content_library_meta_boxes' );

/**
 * Default new items to Active so a freshly-added challenge/lesson enters
 * rotation without an extra admin step.
 */
function gc_content_library_default_active( $post_id, $post ) {
	if ( ! in_array( $post->post_type, array( 'gc_challenge', 'gc_daily_lesson' ), true ) ) {
		return;
	}
	if ( '' === get_post_meta( $post_id, 'gc_content_active', true ) ) {
		update_post_meta( $post_id, 'gc_content_active', 1 );
	}
}
add_action( 'save_post', 'gc_content_library_default_active', 20, 2 );

/**
 * Admin list columns so an admin scanning "All Challenges" can see status,
 * points and where it sits in the schedule at a glance.
 */
function gc_content_library_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['gc_day']    = __( 'Day', 'game-changers' );
			$new['gc_points'] = __( 'Points', 'game-changers' );
			$new['gc_status'] = __( 'Status', 'game-changers' );
		}
	}
	return $new;
}
add_filter( 'manage_gc_challenge_posts_columns', 'gc_content_library_columns' );
add_filter( 'manage_gc_daily_lesson_posts_columns', 'gc_content_library_columns' );

function gc_content_library_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'gc_day':
			$day  = get_post_meta( $post_id, 'gc_content_day', true );
			$year = get_post_meta( $post_id, 'gc_content_year', true );
			echo $day ? 'Day ' . esc_html( $day ) . ( $year ? ' (Yr ' . esc_html( $year ) . ')' : '' ) : '—';
			break;
		case 'gc_points':
			$points = get_post_meta( $post_id, 'gc_content_points', true );
			echo $points ? esc_html( $points ) : esc_html__( 'default', 'game-changers' );
			break;
		case 'gc_status':
			$active   = get_post_meta( $post_id, 'gc_content_active', true );
			$featured = get_post_meta( $post_id, 'gc_content_featured', true );
			echo $active ? '<span style="color:#146c3a;">' . esc_html__( 'Active', 'game-changers' ) . '</span>' : '<span style="color:#999;">' . esc_html__( 'Paused', 'game-changers' ) . '</span>';
			if ( $featured ) {
				echo ' ★';
			}
			break;
	}
}
add_action( 'manage_gc_challenge_posts_custom_column', 'gc_content_library_column_content', 10, 2 );
add_action( 'manage_gc_daily_lesson_posts_custom_column', 'gc_content_library_column_content', 10, 2 );

function gc_content_library_sortable_columns( $columns ) {
	$columns['gc_day'] = 'gc_content_day';
	return $columns;
}
add_filter( 'manage_edit-gc_challenge_sortable_columns', 'gc_content_library_sortable_columns' );
add_filter( 'manage_edit-gc_daily_lesson_sortable_columns', 'gc_content_library_sortable_columns' );

function gc_content_library_sort_by_day( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'gc_content_day' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'gc_content_day' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'gc_content_library_sort_by_day' );
