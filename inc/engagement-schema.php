<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — SCHEMA
 *
 * One central, auditable activity ledger. Every qualifying action a member
 * takes (challenge, 5-minute lesson, course lesson, community/chapter/event
 * activity) writes exactly one row here. Points, streaks, badges, levels and
 * missions are all *derived* from this table rather than tracked separately,
 * per the "central member activity record" requirement.
 */

define( 'GC_ENGAGEMENT_DB_VERSION', '1.0.0' );

function gc_activity_table() {
	global $wpdb;
	return $wpdb->prefix . 'gc_activity_log';
}

function gc_engagement_install() {
	global $wpdb;
	$table           = gc_activity_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		activity_type VARCHAR(40) NOT NULL,
		content_id BIGINT UNSIGNED NULL,
		points_awarded INT NOT NULL DEFAULT 0,
		activity_date DATE NOT NULL,
		source VARCHAR(60) NULL,
		meta LONGTEXT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY activity_type (activity_type),
		KEY activity_date (activity_date),
		KEY user_type_date (user_id, activity_type, activity_date)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'gc_engagement_db_version', GC_ENGAGEMENT_DB_VERSION );
	gc_seed_engagement_defaults();
}

/**
 * Theme activation runs this once. We also self-heal on init (same pattern
 * already used for flush_rewrite_rules in functions.php) so an admin who
 * only uploads new files via FTP — without a full theme "switch" — still
 * gets the table and default config created automatically.
 */
add_action( 'after_switch_theme', 'gc_engagement_install' );
add_action( 'init', function() {
	if ( get_option( 'gc_engagement_db_version' ) !== GC_ENGAGEMENT_DB_VERSION ) {
		gc_engagement_install();
	}
}, 5 );

/**
 * Seed the configurable Points / Badges / Levels / Missions tables from the
 * initial content pack, but only if they have never been set — an admin's
 * later edits in wp-admin are never overwritten.
 */
function gc_seed_engagement_defaults() {
	if ( false === get_option( 'gc_points_config', false ) ) {
		update_option( 'gc_points_config', array(
			'daily_challenge'        => array( 'label' => 'Daily Challenge',        'points' => 0,   'daily_cap' => 1 ),
			'daily_lesson'           => array( 'label' => '5-Minute Lesson',        'points' => 5,   'daily_cap' => 1 ),
			'course_lesson'          => array( 'label' => 'Course Lesson',          'points' => 10,  'daily_cap' => 0 ),
			'community_contribution' => array( 'label' => 'Community Contribution','points' => 20,  'daily_cap' => 0 ),
			'chapter_activity'       => array( 'label' => 'Chapter Activity',       'points' => 50,  'daily_cap' => 0 ),
			'event_attendance'       => array( 'label' => 'Event Attendance',       'points' => 5,   'daily_cap' => 0 ),
			'course_completion'      => array( 'label' => 'Course Completion',      'points' => 100, 'daily_cap' => 0 ),
			'monthly_mission'        => array( 'label' => 'Monthly Mission',        'points' => 75,  'daily_cap' => 0 ),
		) );
	}

	if ( false === get_option( 'gc_badges_config', false ) ) {
		update_option( 'gc_badges_config', array(
			array( 'slug' => 'first-step',            'label' => 'First Step',              'type' => 'total_activities',       'target' => 1 ),
			array( 'slug' => 'streak-7',               'label' => '7-Day Game Changer',       'type' => 'streak_days',            'target' => 7 ),
			array( 'slug' => 'streak-30',              'label' => '30-Day Game Changer',      'type' => 'streak_days',            'target' => 30 ),
			array( 'slug' => 'streak-90',              'label' => '90-Day Game Changer',      'type' => 'streak_days',            'target' => 90 ),
			array( 'slug' => 'streak-365',             'label' => '365-Day Game Changer',     'type' => 'streak_days',            'target' => 365 ),
			array( 'slug' => 'challenge-builder',      'label' => 'Challenge Builder',        'type' => 'count_daily_challenge',  'target' => 10 ),
			array( 'slug' => 'challenge-master',       'label' => 'Challenge Master',         'type' => 'count_daily_challenge',  'target' => 50 ),
			array( 'slug' => 'learning-starter',       'label' => 'Learning Starter',         'type' => 'count_daily_lesson',     'target' => 10 ),
			array( 'slug' => 'learning-builder',       'label' => 'Learning Builder',         'type' => 'count_daily_lesson',     'target' => 50 ),
			array( 'slug' => 'community-builder',      'label' => 'Community Builder',        'type' => 'count_community_contribution', 'target' => 5 ),
			array( 'slug' => 'chapter-participant',    'label' => 'Chapter Participant',      'type' => 'count_chapter_activity', 'target' => 3 ),
			array( 'slug' => 'impact-builder',         'label' => 'Impact Builder',           'type' => 'total_points',           'target' => 1000 ),
			array( 'slug' => 'impact-leader',          'label' => 'Impact Leader',            'type' => 'total_points',           'target' => 5000 ),
		) );
	}

	if ( false === get_option( 'gc_levels_config', false ) ) {
		update_option( 'gc_levels_config', array(
			array( 'label' => 'New Game Changer',      'min' => 0,    'max' => 99 ),
			array( 'label' => 'Active Learner',        'min' => 100,  'max' => 299 ),
			array( 'label' => 'Community Builder',     'min' => 300,  'max' => 599 ),
			array( 'label' => 'Impact Builder',        'min' => 600,  'max' => 999 ),
			array( 'label' => 'Game Changer Leader',   'min' => 1000, 'max' => 2499 ),
			array( 'label' => 'Game Changer Champion', 'min' => 2500, 'max' => 4999 ),
			array( 'label' => 'Legacy Builder',        'min' => 5000, 'max' => 999999999 ),
		) );
	}

	if ( false === get_option( 'gc_missions_config', false ) ) {
		update_option( 'gc_missions_config', array(
			array(
				'slug'          => 'weekly-momentum',
				'label'         => 'Weekly Momentum',
				'period'        => 'weekly',
				'activity_type' => 'any',
				'target_count'  => 5,
				'points'        => 30,
			),
			array(
				'slug'          => 'monthly-mission',
				'label'         => 'Monthly Mission',
				'period'        => 'monthly',
				'activity_type' => 'any',
				'target_count'  => 20,
				'points'        => 75,
			),
		) );
	}

	if ( false === get_option( 'gc_engagement_settings', false ) ) {
		update_option( 'gc_engagement_settings', array(
			'timezone'            => 'Africa/Lagos',
			'grace_day_enabled'   => 0,
			'notifications_enabled' => 1,
			'launch_date'         => current_time( 'Y-m-d' ),
		) );
	}

	// Blog categories aligned to the site's information architecture, so
	// stories/blog posts can be organised without a developer. Existing
	// categories are left untouched — this only adds ones that are missing.
	if ( taxonomy_exists( 'category' ) ) {
		$default_categories = array( 'Learn', 'Act', 'Earn', 'Grow', 'Connect', 'Impact', 'Academy', 'Community', 'Opportunities', 'Chapters', 'Stories' );
		foreach ( $default_categories as $cat ) {
			if ( ! term_exists( $cat, 'category' ) ) {
				wp_insert_term( $cat, 'category' );
			}
		}
	}
}

/**
 * Force daily awards to 5 points (was 10 + 15 = 25 for a full day).
 * Runs once on existing sites so WP Admin settings and already-awarded
 * totals are brought in line — seed-on-empty would skip this otherwise.
 */
function gc_apply_five_point_daily_awards() {
	if ( get_option( 'gc_points_five_v1' ) === '1' ) {
		return;
	}

	$config = get_option( 'gc_points_config', array() );
	if ( is_array( $config ) && ! empty( $config ) ) {
		foreach ( array( 'daily_challenge', 'daily_lesson', 'event_attendance' ) as $key ) {
			if ( isset( $config[ $key ] ) && is_array( $config[ $key ] ) ) {
				$config[ $key ]['points'] = 5;
			}
		}
		update_option( 'gc_points_config', $config );
	}

	$daily_types = array( 'gc_challenge', 'gc_daily_lesson' );
	foreach ( $daily_types as $post_type ) {
		$items = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		foreach ( $items as $post_id ) {
			$override = get_post_meta( $post_id, 'gc_content_points', true );
			if ( '' !== $override && is_numeric( $override ) && (int) $override !== 5 ) {
				update_post_meta( $post_id, 'gc_content_points', 5 );
			}
		}
	}

	global $wpdb;
	$table = gc_activity_table();
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $exists ) {
		$rows = $wpdb->get_results(
			"SELECT id, user_id, points_awarded FROM {$table}
			 WHERE activity_type IN ('daily_challenge','daily_lesson','event_attendance')"
		);
		$deltas = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$old = (int) $row->points_awarded;
				if ( 5 === $old ) {
					continue;
				}
				$uid = (int) $row->user_id;
				$deltas[ $uid ] = ( $deltas[ $uid ] ?? 0 ) + ( 5 - $old );
				$wpdb->update(
					$table,
					array( 'points_awarded' => 5 ),
					array( 'id' => (int) $row->id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
		foreach ( $deltas as $uid => $delta ) {
			$total = max( 0, (int) get_user_meta( $uid, 'gc_total_points', true ) + $delta );
			update_user_meta( $uid, 'gc_total_points', $total );
			if ( function_exists( 'gc_update_level_for_user' ) ) {
				gc_update_level_for_user( $uid, $total );
			}
		}
	}

	update_option( 'gc_points_five_v1', '1' );
}
add_action( 'init', 'gc_apply_five_point_daily_awards', 20 );

/**
 * Today's Game Changer is worth 5 points total — awarded on the lesson.
 * The daily challenge still counts for streak/badges, but does not add extra points.
 */
function gc_apply_five_point_lesson_total() {
	if ( get_option( 'gc_points_five_v2' ) === '1' ) {
		return;
	}

	$config = get_option( 'gc_points_config', array() );
	if ( is_array( $config ) && ! empty( $config ) ) {
		if ( isset( $config['daily_lesson'] ) && is_array( $config['daily_lesson'] ) ) {
			$config['daily_lesson']['points'] = 5;
		}
		if ( isset( $config['daily_challenge'] ) && is_array( $config['daily_challenge'] ) ) {
			$config['daily_challenge']['points'] = 0;
		}
		update_option( 'gc_points_config', $config );
	}

	$lessons = get_posts( array(
		'post_type'      => 'gc_daily_lesson',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $lessons as $post_id ) {
		update_post_meta( $post_id, 'gc_content_points', 5 );
	}

	$challenges = get_posts( array(
		'post_type'      => 'gc_challenge',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $challenges as $post_id ) {
		update_post_meta( $post_id, 'gc_content_points', 0 );
	}

	global $wpdb;
	$table  = gc_activity_table();
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $exists ) {
		$groups = $wpdb->get_results(
			"SELECT user_id, activity_date
			 FROM {$table}
			 WHERE activity_type IN ('daily_lesson','daily_challenge')
			 GROUP BY user_id, activity_date"
		);
		$deltas = array();
		if ( $groups ) {
			foreach ( $groups as $group ) {
				$uid  = (int) $group->user_id;
				$date = $group->activity_date;
				$rows = $wpdb->get_results( $wpdb->prepare(
					"SELECT id, activity_type, points_awarded FROM {$table}
					 WHERE user_id = %d AND activity_date = %s
					 AND activity_type IN ('daily_lesson','daily_challenge')
					 ORDER BY FIELD(activity_type,'daily_lesson','daily_challenge'), id ASC",
					$uid,
					$date
				) );
				$awarded = 0;
				foreach ( $rows as $row ) {
					$want = 0;
					if ( 'daily_lesson' === $row->activity_type && $awarded < 5 ) {
						$want = 5;
					} elseif ( 'daily_challenge' === $row->activity_type && $awarded < 5 ) {
						// Only if there is no lesson row that day.
						$has_lesson = false;
						foreach ( $rows as $check ) {
							if ( 'daily_lesson' === $check->activity_type ) {
								$has_lesson = true;
								break;
							}
						}
						$want = $has_lesson ? 0 : 5;
					}
					$old = (int) $row->points_awarded;
					if ( $want !== $old ) {
						$deltas[ $uid ] = ( $deltas[ $uid ] ?? 0 ) + ( $want - $old );
						$wpdb->update(
							$table,
							array( 'points_awarded' => $want ),
							array( 'id' => (int) $row->id ),
							array( '%d' ),
							array( '%d' )
						);
					}
					$awarded += $want;
				}
			}
		}
		foreach ( $deltas as $uid => $delta ) {
			$total = max( 0, (int) get_user_meta( $uid, 'gc_total_points', true ) + $delta );
			update_user_meta( $uid, 'gc_total_points', $total );
			if ( function_exists( 'gc_update_level_for_user' ) ) {
				gc_update_level_for_user( $uid, $total );
			}
		}
	}

	update_option( 'gc_points_five_v2', '1' );
}
add_action( 'init', 'gc_apply_five_point_lesson_total', 21 );
