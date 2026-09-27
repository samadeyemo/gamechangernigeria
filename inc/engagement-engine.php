<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ENGAGEMENT ENGINE — CORE
 *
 * gc_record_activity() is the single entry point every part of the site
 * must call for a qualifying activity (challenge, 5-min lesson, course
 * lesson, community/chapter/event activity). Everything downstream —
 * points, streak, badges, level, missions, notifications — happens here,
 * once, from that one record. Nothing else in the theme should award
 * points or touch the streak directly.
 *
 *   MEMBER COMPLETES CHALLENGE
 *        -> ACTIVITY RECORDED (this file)
 *        -> POINTS AWARDED
 *        -> STREAK UPDATED
 *        -> BADGE REQUIREMENT CHECKED
 *        -> LEVEL UPDATED
 *        -> MISSION UPDATED
 *        -> MEMBER HUB UPDATED (reads the results below)
 *
 * A login alone is never passed to this function, so it can never count
 * toward a streak — that requirement is enforced simply by never wiring
 * gc_record_activity() to a login hook.
 */

function gc_today( $format = 'Y-m-d' ) {
	$settings = get_option( 'gc_engagement_settings', array() );
	$tz       = ! empty( $settings['timezone'] ) ? $settings['timezone'] : 'Africa/Lagos';
	try {
		$dt = new DateTime( 'now', new DateTimeZone( $tz ) );
	} catch ( Exception $e ) {
		$dt = new DateTime( 'now' );
	}
	return $dt->format( $format );
}

/**
 * @param int    $user_id       WordPress user ID (the member).
 * @param string $activity_type One of the keys in gc_points_config: daily_challenge,
 *                               daily_lesson, course_lesson, community_contribution,
 *                               chapter_activity, event_attendance, course_completion.
 * @param int    $content_id    The related post ID (challenge, lesson, event, etc). 0 if none.
 * @param string $source        Free-text context, e.g. 'homepage', 'member_hub', 'chapter_dashboard'.
 * @return array{success:bool, message:string, points:int, streak:int, new_badges:array, leveled_up:bool}
 */
function gc_record_activity( $user_id, $activity_type, $content_id = 0, $source = '' ) {
	global $wpdb;

	$user_id       = absint( $user_id );
	$content_id    = absint( $content_id );
	$activity_type = sanitize_key( $activity_type );

	$result = array(
		'success'    => false,
		'message'    => '',
		'points'     => 0,
		'streak'     => (int) get_user_meta( $user_id, 'gc_streak_current', true ),
		'new_badges' => array(),
		'leveled_up' => false,
	);

	if ( ! $user_id ) {
		$result['message'] = __( 'No member found for this action.', 'game-changers' );
		return $result;
	}

	$points_config = get_option( 'gc_points_config', array() );
	if ( empty( $points_config[ $activity_type ] ) ) {
		$result['message'] = __( 'Unknown activity type.', 'game-changers' );
		return $result;
	}

	$table = gc_activity_table();
	$today = gc_today();

	// --- Duplicate-prevention rules -------------------------------------
	// "Do not award duplicate daily points for the same activity."
	$daily_cap = ! empty( $points_config[ $activity_type ]['daily_cap'] );
	if ( $daily_cap ) {
		$already = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND activity_type = %s AND activity_date = %s",
			$user_id, $activity_type, $today
		) );
		if ( $already ) {
			$result['message'] = __( 'Already completed today — come back tomorrow.', 'game-changers' );
			return $result;
		}
	} elseif ( $content_id ) {
		// Non-daily-capped activities (community/chapter/event) still can't
		// be double-recorded for the exact same piece of content.
		$already = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND activity_type = %s AND content_id = %d",
			$user_id, $activity_type, $content_id
		) );
		if ( $already ) {
			$result['message'] = __( 'This activity has already been recorded.', 'game-changers' );
			return $result;
		}
	}

	// --- Points -----------------------------------------------------------
	$points = isset( $points_config[ $activity_type ]['points'] ) ? (int) $points_config[ $activity_type ]['points'] : 0;
	if ( $content_id ) {
		$override = get_post_meta( $content_id, 'gc_content_points', true );
		if ( '' !== $override && is_numeric( $override ) ) {
			$points = (int) $override;
		}
	}

	// Today's lesson + challenge together are worth 5 points total.
	if ( in_array( $activity_type, array( 'daily_lesson', 'daily_challenge' ), true ) ) {
		$already = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(points_awarded),0) FROM {$table}
			 WHERE user_id = %d AND activity_date = %s
			 AND activity_type IN ('daily_lesson','daily_challenge')",
			$user_id,
			$today
		) );
		$points = min( $points, max( 0, 5 - $already ) );
	}

	// --- Write the ledger row (the single source of truth) ---------------
	$wpdb->insert( $table, array(
		'user_id'        => $user_id,
		'activity_type'  => $activity_type,
		'content_id'     => $content_id ?: null,
		'points_awarded' => $points,
		'activity_date'  => $today,
		'source'         => sanitize_text_field( $source ),
		'meta'           => wp_json_encode( array( 'recorded_via' => $source ) ),
		'created_at'     => current_time( 'mysql' ),
	), array( '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ) );

	// --- Points total -------------------------------------------------------
	$total_points = (int) get_user_meta( $user_id, 'gc_total_points', true ) + $points;
	update_user_meta( $user_id, 'gc_total_points', $total_points );

	// --- Streak (any qualifying activity counts, once per day) -----------
	$streak_result = gc_update_streak_for_user( $user_id, $today );

	// --- Level --------------------------------------------------------------
	$leveled_up = gc_update_level_for_user( $user_id, $total_points );

	// --- Missions -----------------------------------------------------------
	gc_update_missions_for_user( $user_id, $activity_type, $today );

	// --- Badges (checked last, since they can depend on all of the above) --
	$new_badges = gc_check_badges_for_user( $user_id );

	do_action( 'gc_activity_recorded', $user_id, $activity_type, $content_id, $points );

	$result['success']    = true;
	$result['message']    = __( 'Activity recorded.', 'game-changers' );
	$result['points']     = $points;
	$result['streak']     = $streak_result['current'];
	$result['new_badges'] = $new_badges;
	$result['leveled_up'] = $leveled_up;
	return $result;
}

/**
 * Streak logic: a login alone never reaches this function. Any one
 * qualifying activity on a calendar day advances the streak once; a second
 * qualifying activity the same day does not advance it again.
 */
function gc_update_streak_for_user( $user_id, $today ) {
	$last    = get_user_meta( $user_id, 'gc_streak_last_date', true );
	$current = (int) get_user_meta( $user_id, 'gc_streak_current', true );
	$longest = (int) get_user_meta( $user_id, 'gc_streak_longest', true );

	$settings     = get_option( 'gc_engagement_settings', array() );
	$grace_days   = ! empty( $settings['grace_day_enabled'] ) ? 1 : 0; // disabled by default, per Automation Rules
	$yesterday    = date( 'Y-m-d', strtotime( $today . ' -1 day' ) );
	$grace_window = date( 'Y-m-d', strtotime( $today . ' -' . ( 1 + $grace_days ) . ' day' ) );

	if ( $last === $today ) {
		// Already counted today — no change.
	} elseif ( '' === $last || $last === $yesterday || ( $grace_days && $last >= $grace_window ) ) {
		$current = $current + 1;
	} else {
		$current = 1; // missed day(s): streak resets, per Automation Rules
	}

	$longest = max( $longest, $current );

	update_user_meta( $user_id, 'gc_streak_current', $current );
	update_user_meta( $user_id, 'gc_streak_longest', $longest );
	update_user_meta( $user_id, 'gc_streak_last_date', $today );

	return array( 'current' => $current, 'longest' => $longest );
}

function gc_update_level_for_user( $user_id, $total_points ) {
	$levels        = get_option( 'gc_levels_config', array() );
	$previous_level = get_user_meta( $user_id, 'gc_level', true );
	$new_level      = $previous_level;

	foreach ( $levels as $level ) {
		if ( $total_points >= (int) $level['min'] && $total_points <= (int) $level['max'] ) {
			$new_level = $level['label'];
			break;
		}
	}

	if ( $new_level && $new_level !== $previous_level ) {
		update_user_meta( $user_id, 'gc_level', $new_level );
		do_action( 'gc_member_leveled_up', $user_id, $new_level, $previous_level );
		return true;
	}
	return false;
}

/**
 * Weekly/monthly mission progress. A mission with activity_type "any" counts
 * every qualifying activity; otherwise only the matching type counts.
 */
function gc_update_missions_for_user( $user_id, $activity_type, $today ) {
	$missions = get_option( 'gc_missions_config', array() );
	foreach ( $missions as $mission ) {
		if ( 'any' !== $mission['activity_type'] && $mission['activity_type'] !== $activity_type ) {
			continue;
		}
		$period_key = 'weekly' === $mission['period']
			? date( 'oW', strtotime( $today ) )   // ISO year+week, e.g. "202639"
			: date( 'Ym', strtotime( $today ) );  // year+month, e.g. "202609"

		$meta_key = 'gc_mission_' . $mission['slug'] . '_' . $period_key;
		$progress = (int) get_user_meta( $user_id, $meta_key, true ) + 1;
		update_user_meta( $user_id, $meta_key, $progress );

		$completed_key = $meta_key . '_completed';
		if ( $progress >= (int) $mission['target_count'] && ! get_user_meta( $user_id, $completed_key, true ) ) {
			update_user_meta( $user_id, $completed_key, 1 );
			update_user_meta( $user_id, 'gc_total_points', (int) get_user_meta( $user_id, 'gc_total_points', true ) + (int) $mission['points'] );
			do_action( 'gc_mission_completed', $user_id, $mission );
		}
	}
}

/**
 * Badge counts are derived straight from the activity ledger + streak/point
 * user meta, never tracked separately — same "one source of truth" rule.
 */
function gc_check_badges_for_user( $user_id ) {
	global $wpdb;
	$table   = gc_activity_table();
	$badges  = get_option( 'gc_badges_config', array() );
	$earned  = get_user_meta( $user_id, 'gc_badges', true );
	$earned  = is_array( $earned ) ? $earned : array();
	$newly   = array();

	$counts_cache = array();
	$count_for = function( $type ) use ( $wpdb, $table, $user_id, &$counts_cache ) {
		if ( ! isset( $counts_cache[ $type ] ) ) {
			$counts_cache[ $type ] = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND activity_type = %s",
				$user_id, $type
			) );
		}
		return $counts_cache[ $type ];
	};

	$total_activities = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT activity_date) FROM {$table} WHERE user_id = %d", $user_id
	) );
	$total_points  = (int) get_user_meta( $user_id, 'gc_total_points', true );
	$longest_streak = (int) get_user_meta( $user_id, 'gc_streak_longest', true );

	foreach ( $badges as $badge ) {
		if ( isset( $earned[ $badge['slug'] ] ) ) {
			continue;
		}
		$met = false;
		switch ( $badge['type'] ) {
			case 'total_activities':
				$met = $total_activities >= (int) $badge['target'];
				break;
			case 'streak_days':
				$met = $longest_streak >= (int) $badge['target'];
				break;
			case 'total_points':
				$met = $total_points >= (int) $badge['target'];
				break;
			case 'count_daily_challenge':
				$met = $count_for( 'daily_challenge' ) >= (int) $badge['target'];
				break;
			case 'count_daily_lesson':
				$met = $count_for( 'daily_lesson' ) >= (int) $badge['target'];
				break;
			case 'count_community_contribution':
				$met = $count_for( 'community_contribution' ) >= (int) $badge['target'];
				break;
			case 'count_chapter_activity':
				$met = $count_for( 'chapter_activity' ) >= (int) $badge['target'];
				break;
		}
		if ( $met ) {
			$earned[ $badge['slug'] ] = current_time( 'mysql' );
			$newly[] = $badge;
		}
	}

	if ( $newly ) {
		update_user_meta( $user_id, 'gc_badges', $earned );
		foreach ( $newly as $badge ) {
			do_action( 'gc_badge_earned', $user_id, $badge );
		}
	}

	return $newly;
}

/** Convenience read helpers for templates / Member Hub. */
function gc_has_completed_today( $user_id, $activity_type ) {
	global $wpdb;
	$table = gc_activity_table();
	return (bool) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND activity_type = %s AND activity_date = %s",
		$user_id, $activity_type, gc_today()
	) );
}

function gc_member_progress( $user_id ) {
	$badges = get_user_meta( $user_id, 'gc_badges', true );
	return array(
		'points'         => (int) get_user_meta( $user_id, 'gc_total_points', true ),
		'level'          => get_user_meta( $user_id, 'gc_level', true ) ?: __( 'New Game Changer', 'game-changers' ),
		'streak_current' => (int) get_user_meta( $user_id, 'gc_streak_current', true ),
		'streak_longest' => (int) get_user_meta( $user_id, 'gc_streak_longest', true ),
		'badges'         => is_array( $badges ) ? $badges : array(),
	);
}

/**
 * --- Notifications -------------------------------------------------------
 * Wired to the actions fired above, gated by the admin's on/off setting.
 * Uses wp_mail so it works whether or not the site's transactional-email
 * helpers (gc_dispatch_welcome_email etc.) are present.
 */
function gc_notifications_enabled() {
	$settings = get_option( 'gc_engagement_settings', array() );
	return ! empty( $settings['notifications_enabled'] );
}

function gc_notify_badge_earned( $user_id, $badge ) {
	if ( ! gc_notifications_enabled() ) { return; }
	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) { return; }
	$label = isset( $badge['label'] ) ? $badge['label'] : __( 'Badge', 'game-changers' );
	$from  = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
	$headers = array(
		'From: Game Changers Nigeria <' . $from . '>',
		'Reply-To: Game Changers Nigeria <' . $from . '>',
	);
	$subject = sprintf( __( 'You earned the "%s" badge — Game Changers Nigeria', 'game-changers' ), $label );
	$body    = sprintf(
		__( "Hello %s,

You earned the \"%s\" badge on Game Changers Nigeria.

Open the Member Hub to see your progress and keep building.

— Game Changers Nigeria", 'game-changers' ),
		$user->display_name,
		$label
	);
	wp_mail( $user->user_email, $subject, $body, $headers );
}
add_action( 'gc_badge_earned', 'gc_notify_badge_earned', 10, 2 );

function gc_notify_level_up( $user_id, $new_level, $previous_level ) {
	if ( ! gc_notifications_enabled() ) { return; }
	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) { return; }
	$from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
	$headers = array(
		'From: Game Changers Nigeria <' . $from . '>',
		'Reply-To: Game Changers Nigeria <' . $from . '>',
	);
	$subject = sprintf( __( 'You leveled up to %s — Game Changers Nigeria', 'game-changers' ), $new_level );
	$body = sprintf(
		__( "Hello %s,\n\nYou reached the \"%s\" level on Game Changers Nigeria.\n\nOpen the Member Hub to continue your path.\n\n— Game Changers Nigeria", 'game-changers' ),
		$user->display_name,
		$new_level
	);
	wp_mail( $user->user_email, $subject, $body, $headers );
}
add_action( 'gc_member_leveled_up', 'gc_notify_level_up', 10, 3 );
