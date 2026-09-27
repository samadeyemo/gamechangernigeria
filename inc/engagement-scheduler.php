<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — DAILY SCHEDULER
 *
 * Content creation and daily publishing are separate, as required:
 * admins build up the library in wp-admin whenever they like; this file is
 * the only thing that decides what "today's" challenge/lesson is, and it
 * runs on its own via WP-Cron. No admin has to publish anything each
 * morning — and if the cron tick is ever missed, gc_get_todays_challenge()
 * / gc_get_todays_lesson() compute it on demand the first time they're
 * called that day, so the homepage is never stuck on stale content.
 */

function gc_schedule_engagement_cron() {
	if ( ! wp_next_scheduled( 'gc_daily_engagement_tick' ) ) {
		// Fire slightly after local midnight; gc_today() below still governs
		// which calendar day content maps to, using the configured timezone.
		wp_schedule_event( strtotime( 'tomorrow 00:05:00' ), 'daily', 'gc_daily_engagement_tick' );
	}
}
add_action( 'after_switch_theme', 'gc_schedule_engagement_cron' );
add_action( 'init', 'gc_schedule_engagement_cron' );

add_action( 'gc_daily_engagement_tick', 'gc_publish_daily_content' );
add_action( 'gc_daily_engagement_tick', 'gc_expire_opportunities' );

/**
 * Picks today's challenge/lesson and caches the choice in an option keyed
 * by date, so repeated page loads don't re-run the query and every visitor
 * that day sees the same content.
 */
function gc_publish_daily_content() {
	gc_resolve_todays_content( 'gc_challenge', 'gc_active_challenge_today' );
	gc_resolve_todays_content( 'gc_daily_lesson', 'gc_active_lesson_today' );
}

/**
 * Core rotation rule: walk the active pool in Day order and don't repeat an
 * item until the whole pool has been shown once ("avoid repeat until the
 * active pool is exhausted"). A content item can also be pinned to an exact
 * calendar date via gc_content_scheduled_date, which always wins for that
 * date.
 */
function gc_resolve_todays_content( $post_type, $option_key ) {
	$today = gc_today();
	$cached = get_option( $option_key, array() );
	if ( ! empty( $cached['date'] ) && $cached['date'] === $today && ! empty( $cached['post_id'] ) && 'publish' === get_post_status( $cached['post_id'] ) ) {
		return (int) $cached['post_id'];
	}

	// 1. An item explicitly pinned to today always wins.
	$pinned = get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => 'gc_content_scheduled_date', 'value' => $today, 'compare' => '=' ),
		),
	) );
	if ( ! empty( $pinned ) ) {
		$post_id = $pinned[0]->ID;
		update_option( $option_key, array( 'date' => $today, 'post_id' => $post_id ) );
		return $post_id;
	}

	// 2. Otherwise walk the active pool in Day order and pick the next one
	//    that hasn't been shown since the pool last fully cycled.
	$active_ids = get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => 'gc_content_day',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => 'gc_content_active', 'value' => '0', 'compare' => '!=' ),
		),
	) );

	if ( empty( $active_ids ) ) {
		delete_option( $option_key );
		return 0;
	}

	$launch_settings = get_option( 'gc_engagement_settings', array() );
	$launch_date     = ! empty( $launch_settings['launch_date'] ) ? $launch_settings['launch_date'] : $today;
	$days_since_launch = (int) floor( ( strtotime( $today ) - strtotime( $launch_date ) ) / DAY_IN_SECONDS );
	$days_since_launch = max( 0, $days_since_launch );

	$index   = $days_since_launch % count( $active_ids );
	$post_id = $active_ids[ $index ];

	update_option( $option_key, array( 'date' => $today, 'post_id' => $post_id ) );
	return $post_id;
}

function gc_get_todays_challenge() {
	$id = gc_resolve_todays_content( 'gc_challenge', 'gc_active_challenge_today' );
	return $id ? get_post( $id ) : null;
}

function gc_get_todays_lesson() {
	$id = gc_resolve_todays_content( 'gc_daily_lesson', 'gc_active_lesson_today' );
	return $id ? get_post( $id ) : null;
}

/**
 * "Expired opportunities automatically leave the active experience."
 * gc_opportunity_deadline already exists as a meta field on gc_opportunity
 * (inc/opportunities.php) — this just enforces it daily rather than relying
 * on someone noticing and unpublishing manually.
 */
function gc_expire_opportunities() {
	$today = gc_today();
	$expired = get_posts( array(
		'post_type'      => 'gc_opportunity',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'gc_opportunity_deadline', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ),
		),
	) );
	foreach ( $expired as $post_id ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		update_post_meta( $post_id, 'gc_opportunity_expired', 1 );
	}
}

/**
 * Only Active opportunities that haven't passed their deadline should ever
 * be shown on the front end — use this wherever gc_opportunity is queried
 * for public display.
 */
function gc_active_opportunities_args( $extra_args = array() ) {
	$defaults = array(
		'post_type'      => 'gc_opportunity',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);
	return array_merge( $defaults, $extra_args );
}
