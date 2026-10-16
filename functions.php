<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Polyfills for older PHP (prevent critical errors on shared hosting) */
if ( ! function_exists( 'array_key_first' ) ) {
	function array_key_first( array $arr ) {
		foreach ( $arr as $key => $unused ) {
			return $key;
		}
		return null;
	}
}

/**
 * Safe theme include — missing file logs instead of fatal white-screen.
 * WordPress critical errors are usually uncaught fatals; this prevents
 * one missing/corrupt include from taking the whole site down.
 */
if ( ! function_exists( 'gc_safe_require' ) ) {
	function gc_safe_require( $relative ) {
		$path = get_template_directory() . $relative;
		if ( is_readable( $path ) ) {
			require_once $path;
			return true;
		}
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Game Changers theme] Missing or unreadable: ' . $path );
		}
		return false;
	}
}
 if ( ! defined( 'GC_THEME_VERSION' ) ) { $_gc_ver = '2.18.1'; if ( function_exists( 'wp_get_theme' ) ) { $_t = wp_get_theme(); if ( $_t && $_t->get( 'Version' ) ) { $_gc_ver = $_t->get( 'Version' ); } } define( 'GC_THEME_VERSION', $_gc_ver ); } gc_safe_require( '/inc/customizer.php' ); gc_safe_require( '/inc/chapters.php' ); gc_safe_require( '/inc/default-pages.php' ); gc_safe_require( '/inc/forms.php' ); gc_safe_require( '/inc/members.php' ); gc_safe_require( '/inc/emails.php' ); gc_safe_require( '/inc/commerce.php' );
gc_safe_require( '/inc/premium-access.php' ); gc_safe_require( '/inc/cohorts.php' ); gc_safe_require( '/inc/projects.php' ); gc_safe_require( '/inc/opportunities.php' ); gc_safe_require( '/inc/events.php' ); gc_safe_require( '/inc/platform.php' ); gc_safe_require( '/inc/dashboard.php' ); gc_safe_require( '/inc/workflows.php' ); gc_safe_require( '/inc/engagement-schema.php' ); gc_safe_require( '/inc/engagement-content.php' ); gc_safe_require( '/inc/engagement-engine.php' ); gc_safe_require( '/inc/engagement-scheduler.php' ); gc_safe_require( '/inc/engagement-frontend.php' ); gc_safe_require( '/inc/engagement-import.php' ); function gc_theme_setup() { load_theme_textdomain( 'game-changers', get_template_directory() . '/languages' ); add_theme_support( 'title-tag' ); add_theme_support( 'post-thumbnails' ); add_image_size( 'gc-hero-photo', 700, 700, true ); add_image_size( 'gc-avatar', 360, 360, true ); add_image_size( 'gc-logo', 270, 90, false ); add_theme_support( 'custom-logo', array( 'height' => 90, 'width' => 270, 'flex-height' => true, 'flex-width' => false, ) ); add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', ) ); add_theme_support( 'responsive-embeds' ); add_theme_support( 'automatic-feed-links' ); add_theme_support( 'wp-block-styles' ); add_theme_support( 'align-wide' ); register_nav_menus( array( 'primary' => __( 'Primary Navigation', 'game-changers' ), 'footer' => __( 'Footer Navigation', 'game-changers' ), ) ); } add_action( 'after_setup_theme', 'gc_theme_setup' ); function gc_enqueue_assets() { wp_enqueue_style( 'gc-google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=optional', array(), null ); wp_enqueue_style( 'gc-style', get_stylesheet_uri(), array( 'gc-google-fonts' ), GC_THEME_VERSION ); if ( file_exists( get_template_directory() . '/assets/js/main.js' ) ) { wp_enqueue_script( 'gc-main', get_template_directory_uri() . '/assets/js/main.js', array(), GC_THEME_VERSION, true ); wp_localize_script( 'gc-main', 'gcAjax', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gc_member_nonce' ), ) ); } } add_action( 'wp_enqueue_scripts', 'gc_enqueue_assets' ); function gc_register_widget_areas() { register_sidebar( array( 'name' => __( 'Footer Column 1', 'game-changers' ), 'id' => 'footer-1', 'before_widget' => '<div class="footer-widget">', 'after_widget' => '</div>', 'before_title' => '<div class="footer-col-head">', 'after_title' => '</div>', ) ); register_sidebar( array( 'name' => __( 'Footer Column 2', 'game-changers' ), 'id' => 'footer-2', 'before_widget' => '<div class="footer-widget">', 'after_widget' => '</div>', 'before_title' => '<div class="footer-col-head">', 'after_title' => '</div>', ) ); } add_action( 'widgets_init', 'gc_register_widget_areas' ); function gc_register_post_types() { register_post_type( 'gc_chapter', array( 'labels' => array( 'name' => __( 'Chapters', 'game-changers' ), 'singular_name' => __( 'Chapter', 'game-changers' ), 'add_new_item' => __( 'Add New Chapter', 'game-changers' ), 'edit_item' => __( 'Edit Chapter', 'game-changers' ), 'all_items' => __( 'All Chapters', 'game-changers' ), 'parent_item_colon' => __( 'Parent Chapter:', 'game-changers' ), ), 'public' => true, 'hierarchical' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'chapters' ), 'menu_icon' => 'dashicons-groups', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ), 'show_in_rest' => true, ) ); register_post_type( 'gc_cohort', array( 'labels' => array( 'name' => __( 'Cohorts', 'game-changers' ), 'singular_name' => __( 'Cohort', 'game-changers' ), 'all_items' => __( 'All Cohorts', 'game-changers' ), 'add_new_item' => __( 'Add New Cohort', 'game-changers' ), ), 'public' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'academy/cohorts' ), 'menu_icon' => 'dashicons-welcome-learn-more', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 'show_in_rest' => true, ) ); register_post_type( 'gc_project', array( 'labels' => array( 'name' => __( 'Impact Projects', 'game-changers' ), 'singular_name' => __( 'Impact Project', 'game-changers' ), 'all_items' => __( 'All Projects', 'game-changers' ), 'add_new_item' => __( 'Add New Project', 'game-changers' ), ), 'public' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'impact/projects' ), 'menu_icon' => 'dashicons-lightbulb', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 'show_in_rest' => true, ) ); register_post_type( 'gc_opportunity', array( 'labels' => array( 'name' => __( 'Opportunities', 'game-changers' ), 'singular_name' => __( 'Opportunity', 'game-changers' ), 'all_items' => __( 'All Opportunities', 'game-changers' ), 'add_new_item' => __( 'Add New Opportunity', 'game-changers' ), ), 'public' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'opportunities' ), 'menu_icon' => 'dashicons-awards', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 'show_in_rest' => true, ) ); register_post_type( 'gc_event', array( 'labels' => array( 'name' => __( 'Events', 'game-changers' ), 'singular_name' => __( 'Event', 'game-changers' ), 'all_items' => __( 'All Events', 'game-changers' ), 'add_new_item' => __( 'Add New Event', 'game-changers' ), ), 'public' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'events' ), 'menu_icon' => 'dashicons-calendar-alt', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 'show_in_rest' => true, ) ); register_post_type( 'gc_track', array( 'labels' => array( 'name' => __( 'Curriculum Tracks', 'game-changers' ), 'singular_name' => __( 'Track', 'game-changers' ), 'add_new_item' => __( 'Add New Track', 'game-changers' ), 'edit_item' => __( 'Edit Track', 'game-changers' ), 'all_items' => __( 'All Tracks', 'game-changers' ), ), 'public' => true, 'has_archive' => false, 'rewrite' => array( 'slug' => 'academy-track' ), 'menu_icon' => 'dashicons-welcome-learn-more', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 'show_in_rest' => true, ) ); } add_action( 'init', 'gc_register_post_types' ); function gc_ajax_member_login() { check_ajax_referer( 'gc_member_nonce', 'gc_nonce' ); $creds = array( 'user_login' => sanitize_user( wp_unslash( $_POST['username'] ?? '' ) ), 'user_password' => $_POST['password'] ?? '', 'remember' => true, ); if ( empty( $creds['user_login'] ) || empty( $creds['user_password'] ) ) { wp_send_json_error( array( 'message' => __( 'Username and password are required.', 'game-changers' ) ) ); } $user = wp_signon( $creds, is_ssl() ); if ( is_wp_error( $user ) ) { wp_send_json_error( array( 'message' => $user->get_error_message() ) ); } wp_send_json_success( array( 'message' => __( 'Login successful.', 'game-changers' ), 'redirect' => home_url( '/dashboard/' ), ) ); } add_action( 'wp_ajax_nopriv_gc_member_login', 'gc_ajax_member_login' ); function gc_ajax_member_register() { check_ajax_referer( 'gc_member_nonce', 'gc_nonce' ); $username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); $password = $_POST['password'] ?? ''; if ( empty( $username ) || empty( $email ) || empty( $password ) ) { wp_send_json_error( array( 'message' => __( 'All fields are required.', 'game-changers' ) ) ); } if ( username_exists( $username ) || email_exists( $email ) ) { wp_send_json_error( array( 'message' => __( 'That username or email is already registered.', 'game-changers' ) ) ); } if ( ! is_email( $email ) ) { wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'game-changers' ) ) ); } $user_id = wp_create_user( $username, $password, $email ); if ( is_wp_error( $user_id ) ) { wp_send_json_error( array( 'message' => $user_id->get_error_message() ) ); } $user = new WP_User( $user_id ); $user->set_role( 'subscriber' ); wp_new_user_notification( $user_id, null, 'user' ); wp_send_json_success( array( 'message' => __( 'Registration successful. Please check your email.', 'game-changers' ), ) ); } add_action( 'wp_ajax_nopriv_gc_member_register', 'gc_ajax_member_register' ); function gc_disable_emojis() { remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); remove_action( 'wp_print_styles', 'print_emoji_styles' ); remove_action( 'admin_print_scripts', 'print_emoji_detection_script' ); remove_action( 'admin_print_styles', 'print_emoji_styles' ); } add_action( 'init', 'gc_disable_emojis' ); remove_action( 'wp_head', 'wp_generator' ); add_filter( 'xmlrpc_enabled', '__return_false' ); function gc_custom_excerpt_length( $length ) { return 30; } add_filter( 'excerpt_length', 'gc_custom_excerpt_length' ); function gc_submit_page_body_class($classes){ $slug = is_page() ? get_post_field('post_name', get_queried_object_id()) : ''; if (in_array($slug,array('enlistments','mentor-with-us','partner-with-us','academy-apply','verify-certificate'),true)) $classes[]='gc-submit-page'; return $classes; } add_filter('body_class','gc_submit_page_body_class'); add_action('after_switch_theme', function(){ flush_rewrite_rules(false); });
/**
 * Uploading new theme files does NOT reset WordPress's cached URL routing
 * table on its own — only switching themes, or manually resaving
 * Settings → Permalinks, does that. Whenever a post type's rewrite
 * structure changes (like making Chapters hierarchical for local groups),
 * a stale routing table can 404 unrelated pages after a form redirect.
 * This runs once per code change and rebuilds it automatically, so no
 * manual "Permalinks → Save" step is ever required after an update.
 */
add_action( 'init', function() {
	$marker = 'gc_rewrite_flushed_v2_11_0';
	if ( ! get_option( $marker ) ) {
		flush_rewrite_rules( false );
		update_option( $marker, 1 );
	}
}, 999 ); gc_safe_require( '/inc/chapter-leaders.php' ); gc_safe_require( '/inc/chapter-membership.php' );  gc_safe_require( '/inc/join-flow.php' );
gc_safe_require( '/inc/resources-library.php' );
gc_safe_require( '/inc/free-path.php' ); gc_safe_require( '/inc/admin-control.php' ); gc_safe_require( '/inc/pwa.php' );
gc_safe_require( '/inc/wallet.php' ); 
/**
 * GC Tier growth points + admin controls + upgrade delta
 * Tier 1 = 500, Tier 2 = 2500, Tier 3 = 7500 (admin can change)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Default growth points per tier (admin overrides via option). */
function gc_default_tier_growth_points() {
	return array(
		1 => 500,
		2 => 2500,
		3 => 7500,
		4 => 25000,
		5 => 75000,
	);
}

/** Get growth points for a tier (from admin option, else defaults). */
function gc_get_tier_growth_points( $tier ) {
	$tier = absint( $tier );
	$saved = get_option( 'gc_tier_growth_points', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$defaults = gc_default_tier_growth_points();
	if ( isset( $saved[ $tier ] ) && $saved[ $tier ] !== '' ) {
		return absint( $saved[ $tier ] );
	}
	return isset( $defaults[ $tier ] ) ? (int) $defaults[ $tier ] : 0;
}

/**
 * Award / adjust tier points when member joins or upgrades.
 * On upgrade: only adds the difference (T2 - T1), not full T2 again.
 */
function gc_apply_tier_growth_points( $user_id, $new_tier, $old_tier = 0 ) {
	$user_id  = absint( $user_id );
	$new_tier = max( 1, absint( $new_tier ) );
	$old_tier = absint( $old_tier );
	if ( ! $user_id ) {
		return;
	}

	$new_pts = gc_get_tier_growth_points( $new_tier );
	$old_pts = $old_tier ? gc_get_tier_growth_points( $old_tier ) : 0;
	$delta   = max( 0, $new_pts - $old_pts );

	$prev_tier_pts = (int) get_user_meta( $user_id, 'gc_tier_points', true );
	// If first time, set to new tier points; if upgrade, add delta.
	if ( $old_tier < 1 && $prev_tier_pts < 1 ) {
		$current_tier_pts = $new_pts;
	} else {
		$current_tier_pts = $prev_tier_pts + $delta;
	}
	update_user_meta( $user_id, 'gc_tier_points', $current_tier_pts );

	// Add only the growth delta so challenge + referral points stay intact.
	$add = max( 0, $current_tier_pts - $prev_tier_pts );
	if ( $add > 0 ) {
		$total = (int) get_user_meta( $user_id, 'gc_total_points', true ) + $add;
		update_user_meta( $user_id, 'gc_total_points', $total );
		if ( function_exists( 'gc_share_tier_delta_with_referrers' ) ) {
			gc_share_tier_delta_with_referrers( $user_id, $add );
		}
	}
}



/** Before migrate in join-flow, store previous tier (safe even if already hooked). */
/** Award tier growth points when premium tier is set or increased (once). */
add_filter( 'update_user_metadata', function( $check, $user_id, $meta_key, $meta_value ) {
	if ( 'gc_premium_tier' !== $meta_key ) {
		return $check;
	}
	static $busy = false;
	if ( $busy ) {
		return $check;
	}
	$old = (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	$new = (int) $meta_value;
	if ( $new < 1 ) {
		return $check;
	}
	// First assign or real upgrade only (member gets tier points)
	if ( $old < 1 || $new > $old ) {
		$busy = true;
		if ( function_exists( 'gc_apply_tier_growth_points' ) ) {
			gc_apply_tier_growth_points( $user_id, $new, $old > 0 ? $old : 0 );
		}
		// Referrer earns when invitee upgrades — even years after free join.
		if ( function_exists( 'gc_award_referral_on_tier_upgrade' ) ) {
			gc_award_referral_on_tier_upgrade( $user_id, $new, $old );
		}
		$busy = false;
	}
	return $check;
}, 10, 4 );

/**
 * When a referred member upgrades free → paid (or to a higher tier),
 * pay the referrer the extra 1/3 (and L2 1/5) for the tier delta.
 * No time limit — works no matter how long they stayed free.
 */
function gc_award_referral_on_tier_upgrade( $new_user_id, $new_tier, $old_tier = 0 ) {
	$new_user_id = absint( $new_user_id );
	$new_tier    = absint( $new_tier );
	$old_tier    = absint( $old_tier );
	if ( ! $new_user_id || $new_tier < 1 ) {
		return;
	}
	// Already paid referrer for this exact tier level?
	$paid_upto = (int) get_user_meta( $new_user_id, 'gc_referral_paid_tier', true );
	if ( $new_tier <= $paid_upto ) {
		return;
	}

	$referrer_id = (int) get_user_meta( $new_user_id, 'gc_referred_by_user', true );
	if ( ! $referrer_id ) {
		// Resolve from tag if stored at free join
		$tag = get_user_meta( $new_user_id, 'gc_referred_by_tag', true );
		if ( $tag && function_exists( 'gc_get_premium_user_by_tag' ) ) {
			$ru = gc_get_premium_user_by_tag( $tag );
			if ( $ru ) {
				$referrer_id = (int) $ru->ID;
				update_user_meta( $new_user_id, 'gc_referred_by_user', $referrer_id );
			}
		}
	}
	if ( ! $referrer_id || $referrer_id === $new_user_id ) {
		return;
	}

	// Award delta: full tier share if first paid, else only increase between tiers
	$from = max( $paid_upto, $old_tier );
	if ( function_exists( 'gc_award_referral_points' ) ) {
		// Award for new tier; function uses full tier base — adjust by subtracting already paid base
		if ( function_exists( 'gc_get_tier_growth_points' ) ) {
			$base_new = (int) gc_get_tier_growth_points( $new_tier );
			$base_old = $from >= 1 ? (int) gc_get_tier_growth_points( $from ) : 0;
			$base_delta = max( 0, $base_new - $base_old );
		} else {
			$base_delta = 0;
		}
		if ( $base_delta > 0 ) {
			$direct = (int) floor( $base_delta / 3 );
			if ( $direct > 0 ) {
				$ref_pts = (int) get_user_meta( $referrer_id, 'gc_referral_points', true ) + $direct;
				update_user_meta( $referrer_id, 'gc_referral_points', $ref_pts );
				update_user_meta( $referrer_id, 'gc_total_points', (int) get_user_meta( $referrer_id, 'gc_total_points', true ) + $direct );
			}
			// L2: referrer of the referrer gets 1/5 of delta
			$l2 = (int) get_user_meta( $referrer_id, 'gc_referred_by_user', true );
			if ( $l2 && $l2 !== $referrer_id && $l2 !== $new_user_id ) {
				$net = (int) floor( $base_delta / 5 );
				if ( $net > 0 ) {
					update_user_meta( $l2, 'gc_referral_l2_points', (int) get_user_meta( $l2, 'gc_referral_l2_points', true ) + $net );
					update_user_meta( $l2, 'gc_total_points', (int) get_user_meta( $l2, 'gc_total_points', true ) + $net );
				}
			}
		} else {
			// Fallback: full award via existing function once
			gc_award_referral_points( $referrer_id, $new_user_id, $new_tier );
		}
	}
	update_user_meta( $new_user_id, 'gc_referral_paid_tier', $new_tier );
}


/**
 * Daily challenge: track challenge points only when completion awards points > 0.
 * Do not rewrite total from tier+challenge only (preserves other activity points).
 */
add_action( 'gc_activity_recorded', function( $user_id, $activity_type, $content_id, $points ) {
	if ( 'daily_challenge' !== $activity_type ) {
		return;
	}
	$points = (int) $points;
	if ( $points < 1 ) {
		return; // not completed / zero points — do not count
	}
	$chal = (int) get_user_meta( $user_id, 'gc_challenge_points', true ) + $points;
	update_user_meta( $user_id, 'gc_challenge_points', $chal );
	// gc_total_points already updated inside gc_record_activity — do not overwrite
}, 10, 4 );

/** Admin: Settings → GC Tier Points */
add_action( 'admin_menu', function() {
	add_options_page(
		'Tier Growth Points',
		'Tier Growth Points',
		'manage_options',
		'gc-tier-growth-points',
		'gc_tier_growth_points_page'
	);
} );

function gc_tier_growth_points_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['gc_save_tier_points'] ) && check_admin_referer( 'gc_tier_points_save' ) ) {
		$rows = isset( $_POST['gc_tp'] ) ? (array) $_POST['gc_tp'] : array();
		$out  = array();
		foreach ( $rows as $n => $v ) {
			$out[ absint( $n ) ] = absint( $v );
		}
		update_option( 'gc_tier_growth_points', $out );
		echo '<div class="updated"><p>Tier growth points saved.</p></div>';
	}
	$defaults = gc_default_tier_growth_points();
	$saved    = get_option( 'gc_tier_growth_points', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$tiers = function_exists( 'gc_tiers' ) ? gc_tiers() : $defaults;
	echo '<div class="wrap"><h1>Tier Growth Points</h1>';
	echo '<p>When a member joins or upgrades, these points are applied. Upgrade only adds the difference.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'gc_tier_points_save' );
	echo '<table class="widefat striped"><thead><tr><th>Tier</th><th>Name</th><th>Growth points</th></tr></thead><tbody>';
	foreach ( $tiers as $n => $v ) {
		$n = absint( $n );
		$val = isset( $saved[ $n ] ) ? absint( $saved[ $n ] ) : ( isset( $defaults[ $n ] ) ? $defaults[ $n ] : 0 );
		$name = is_array( $v ) ? ( $v['name'] ?? ( 'Tier ' . $n ) ) : ( 'Tier ' . $n );
		echo '<tr><td><strong>Tier ' . esc_html( $n ) . '</strong></td>';
		echo '<td>' . esc_html( $name ) . '</td>';
		echo '<td><input type="number" min="0" name="gc_tp[' . esc_attr( $n ) . ']" value="' . esc_attr( $val ) . '"></td></tr>';
	}
	echo '</tbody></table>';
	submit_button( 'Save points' );
	echo '<input type="hidden" name="gc_save_tier_points" value="1"></form></div>';
}

/** Admin members table: show Tier points + Challenge points */
add_action( 'admin_footer', function() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	// Soft enhancement on users list
} );

add_filter( 'manage_users_columns', function( $cols ) {
	$cols['gc_tier_pts'] = 'Tier pts';
	$cols['gc_chal_pts'] = 'Challenge pts';
	$cols['gc_ref_pts'] = 'Ref 1/3';
	$cols['gc_l2_pts'] = 'Ref 1/5';
	$cols['gc_total_pts'] = 'Total pts';
	return $cols;
} );

add_filter( 'manage_users_custom_column', function( $val, $col, $user_id ) {
	if ( 'gc_tier_pts' === $col ) {
		return (string) (int) get_user_meta( $user_id, 'gc_tier_points', true );
	}
	if ( 'gc_chal_pts' === $col ) {
		return (string) (int) get_user_meta( $user_id, 'gc_challenge_points', true );
	}
	if ( 'gc_ref_pts' === $col ) {
		return (string) (int) get_user_meta( $user_id, 'gc_referral_points', true );
	}
	if ( 'gc_l2_pts' === $col ) {
		return (string) (int) get_user_meta( $user_id, 'gc_referral_l2_points', true );
	}
	if ( 'gc_total_pts' === $col ) {
		return (string) (int) get_user_meta( $user_id, 'gc_total_points', true );
	}
	return $val;
}, 10, 3 );

/**
 * One-time: fill tier_points meta for display when member already has a tier
 * but breakdown was never stored (avoids all zeros under a real total).
 */
function gc_backfill_points_breakdown( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! function_exists( 'gc_get_tier_growth_points' ) ) {
		return;
	}
	if ( function_exists( 'gc_rebuild_challenge_points_from_ledger' ) ) {
		gc_rebuild_challenge_points_from_ledger( $user_id );
	}
	if ( function_exists( 'gc_rebuild_all_point_buckets' ) ) {
		gc_rebuild_all_point_buckets( $user_id );
	}

	// Administrators: store highest tier so Hub matches real access.
	if ( user_can( $user_id, 'administrator' ) && function_exists( 'gc_tiers' ) ) {
		$keys = array_keys( gc_tiers() );
		if ( $keys ) {
			$max_t = (int) max( $keys );
			if ( (int) get_user_meta( $user_id, 'gc_premium_tier', true ) < $max_t ) {
				update_user_meta( $user_id, 'gc_premium_tier', $max_t );
			}
		}
	}

	$tier = function_exists( 'gc_user_membership_tier' )
		? (int) gc_user_membership_tier( $user_id )
		: (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	if ( $tier < 1 ) {
		return;
	}

	$expected = (int) gc_get_tier_growth_points( $tier );
	$tier_pts = (int) get_user_meta( $user_id, 'gc_tier_points', true );
	$synced   = get_user_meta( $user_id, 'gc_tier_pts_in_total_v3', true );

	if ( $synced !== '1' ) {
		// First correct sync: put full tier points into total on top of existing activity.
		update_user_meta( $user_id, 'gc_tier_points', $expected );
		$total = (int) get_user_meta( $user_id, 'gc_total_points', true ) + $expected;
		update_user_meta( $user_id, 'gc_total_points', $total );
		update_user_meta( $user_id, 'gc_tier_pts_in_total_v3', '1' );
	} elseif ( $tier_pts < $expected ) {
		// Later upgrade path: only add the difference.
		$add = $expected - $tier_pts;
		update_user_meta( $user_id, 'gc_tier_points', $expected );
		if ( $add > 0 ) {
			update_user_meta( $user_id, 'gc_total_points', (int) get_user_meta( $user_id, 'gc_total_points', true ) + $add );
			if ( function_exists( 'gc_share_tier_delta_with_referrers' ) ) {
				gc_share_tier_delta_with_referrers( $user_id, $add );
			}
		}
	}

	// Floor: total never below sum of known buckets.
	$tier_pts      = (int) get_user_meta( $user_id, 'gc_tier_points', true );
	$challenge_pts = (int) get_user_meta( $user_id, 'gc_challenge_points', true );
	$ref_pts       = (int) get_user_meta( $user_id, 'gc_referral_points', true );
	$l2_pts        = (int) get_user_meta( $user_id, 'gc_referral_l2_points', true );
	$bucket        = $tier_pts + $challenge_pts + $ref_pts + $l2_pts;
	$total         = (int) get_user_meta( $user_id, 'gc_total_points', true );
	if ( $total < $bucket ) {
		update_user_meta( $user_id, 'gc_total_points', $bucket );
	}
}

/**
 * When tier points increase, give direct referrer 1/3 of the *delta*
 * and their upline 1/5 of the delta (2 generations only).
 */
function gc_share_tier_delta_with_referrers( $user_id, $delta ) {
	$user_id = absint( $user_id );
	$delta   = (int) $delta;
	if ( ! $user_id || $delta < 1 ) {
		return;
	}
	$referrer = (int) get_user_meta( $user_id, 'gc_referred_by', true );
	if ( ! $referrer || $referrer === $user_id ) {
		return;
	}
	$direct = (int) floor( $delta / 3 );
	if ( $direct > 0 ) {
		update_user_meta( $referrer, 'gc_referral_points', (int) get_user_meta( $referrer, 'gc_referral_points', true ) + $direct );
		update_user_meta( $referrer, 'gc_total_points', (int) get_user_meta( $referrer, 'gc_total_points', true ) + $direct );
	}
	$upline = (int) get_user_meta( $referrer, 'gc_referred_by', true );
	if ( $upline && $upline !== $referrer && $upline !== $user_id ) {
		$indirect = (int) floor( $delta / 5 );
		if ( $indirect > 0 ) {
			update_user_meta( $upline, 'gc_referral_l2_points', (int) get_user_meta( $upline, 'gc_referral_l2_points', true ) + $indirect );
			update_user_meta( $upline, 'gc_total_points', (int) get_user_meta( $upline, 'gc_total_points', true ) + $indirect );
		}
	}
}


/**
 * Invite quotas to unlock the next tier (no payment required when met).
 * Defaults match Leadership Program:
 *   Tier 2 ← 5 people joined at Tier 1+
 *   Tier 3 ← 3 people joined at Tier 2+
 *   Tier 4 ← 3 at Tier 3+
 *   Tier 5 ← 3 at Tier 4+
 * Future tiers: each next tier needs 3 invites at (tier-1)+ unless overridden in option gc_tier_invite_rules.
 */
function gc_default_tier_invite_rules() {
	return array(
		2 => array( 'from_tier' => 1, 'count' => 5 ),
		3 => array( 'from_tier' => 2, 'count' => 3 ),
		4 => array( 'from_tier' => 3, 'count' => 3 ),
		5 => array( 'from_tier' => 4, 'count' => 3 ),
	);
}

function gc_tier_invite_rules() {
	$saved = get_option( 'gc_tier_invite_rules', null );
	if ( is_array( $saved ) && ! empty( $saved ) ) {
		$out = array();
		foreach ( $saved as $tier => $row ) {
			$out[ absint( $tier ) ] = array(
				'from_tier' => absint( $row['from_tier'] ?? 1 ),
				'count'     => max( 1, absint( $row['count'] ?? 3 ) ),
			);
		}
		ksort( $out );
		return $out;
	}
	$rules = gc_default_tier_invite_rules();
	// Future tiers from membership tiers list: tier N needs 3 invites from tier N-1
	if ( function_exists( 'gc_tiers' ) ) {
		foreach ( array_keys( gc_tiers() ) as $t ) {
			$t = absint( $t );
			if ( $t >= 2 && ! isset( $rules[ $t ] ) ) {
				$rules[ $t ] = array( 'from_tier' => $t - 1, 'count' => 3 );
			}
		}
	}
	ksort( $rules );
	return $rules;
}

/** Count how many people this user referred who joined at >= $from_tier. */
function gc_count_referrals_at_tier( $user_id, $from_tier ) {
	$user_id    = absint( $user_id );
	$from_tier  = absint( $from_tier );
	$log        = (array) get_user_meta( $user_id, 'gc_referral_log', true );
	$count      = 0;
	$seen       = array();
	foreach ( $log as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$uid = absint( $row['user_id'] ?? 0 );
		$t   = absint( $row['tier'] ?? 0 );
		if ( $uid && $t >= $from_tier && ! isset( $seen[ $uid ] ) ) {
			$seen[ $uid ] = true;
			$count++;
		}
	}
	return $count;
}

/**
 * If invite quotas are met, auto-upgrade the member to the highest unlocked tier.
 * Does not downgrade. Awards tier-point deltas via existing meta filter.
 */
function gc_maybe_auto_upgrade_by_invites( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! function_exists( 'gc_migrate_user_to_premium' ) ) {
		return 0;
	}
	$current = function_exists( 'gc_user_membership_tier' )
		? (int) gc_user_membership_tier( $user_id )
		: (int) get_user_meta( $user_id, 'gc_premium_tier', true );
	if ( $current < 1 ) {
		$current = 0;
	}
	$rules   = gc_tier_invite_rules();
	$target  = $current;
	foreach ( $rules as $tier => $rule ) {
		$tier = absint( $tier );
		if ( $tier <= $current ) {
			continue;
		}
		$need = (int) $rule['count'];
		$from = (int) $rule['from_tier'];
		$have = gc_count_referrals_at_tier( $user_id, $from );
		if ( $have >= $need ) {
			$target = $tier;
		} else {
			break; // must unlock in order
		}
	}
	if ( $target > $current ) {
		gc_migrate_user_to_premium( $user_id, $target, 'INVITE-QUOTA-T' . $target );
		update_user_meta( $user_id, 'gc_last_invite_upgrade', array(
			'from' => $current,
			'to'   => $target,
			'time' => current_time( 'mysql' ),
		) );
		return $target;
	}
	return 0;
}



/** One-time site flag so each member recalculates tier/total on next Hub visit. */
add_action( 'init', function() {
	if ( get_option( 'gc_points_reconcile_v3' ) === '1' ) {
		return;
	}
	// Clear per-user "already synced" so v2 backfill can add missing tier into total.
	// Done lazily in gc_backfill_points_breakdown when meta missing — force by deleting option marker only.
	update_option( 'gc_points_reconcile_v3', '1' );
}, 30 );


/** After logout, always land on the homepage. */
function gc_logout_redirect_home( $redirect_to, $requested, $user ) {
	return home_url( '/' );
}
add_filter( 'logout_redirect', 'gc_logout_redirect_home', 20, 3 );


/**
 * Ensure daily_challenge awards points (legacy installs had points => 0).
 * Also rebuild challenge bucket from activity ledger when meta is empty.
 */
function gc_fix_challenge_points_config() {
	$cfg = get_option( 'gc_points_config', array() );
	if ( ! is_array( $cfg ) ) { $cfg = array(); }
	$changed = false;
	if ( empty( $cfg['daily_challenge'] ) || (int) ( $cfg['daily_challenge']['points'] ?? 0 ) < 1 ) {
		$cfg['daily_challenge'] = array( 'label' => 'Daily Challenge', 'points' => 5, 'daily_cap' => 1 );
		$changed = true;
	}
	if ( empty( $cfg['daily_lesson'] ) || (int) ( $cfg['daily_lesson']['points'] ?? 0 ) < 1 ) {
		$cfg['daily_lesson'] = array( 'label' => '5-Minute Lesson', 'points' => 5, 'daily_cap' => 1 );
		$changed = true;
	}
	if ( $changed ) {
		update_option( 'gc_points_config', $cfg, false );
	}
}
add_action( 'init', 'gc_fix_challenge_points_config', 5 );

/**
 * Rebuild gc_challenge_points from the activity ledger so the Hub shows the truth.
 */
function gc_rebuild_challenge_points_from_ledger( $user_id ) {
	global $wpdb;
	$user_id = absint( $user_id );
	if ( ! $user_id || ! function_exists( 'gc_activity_table' ) ) {
		return;
	}
	if ( function_exists( 'gc_activity_table_exists' ) && ! gc_activity_table_exists() ) {
		return;
	}
	$table = gc_activity_table();
	$sum   = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(points_awarded),0) FROM {$table} WHERE user_id = %d AND activity_type = %s",
		$user_id,
		'daily_challenge'
	) );
	$count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND activity_type = %s",
		$user_id,
		'daily_challenge'
	) );
	// Old rows may have points_awarded = 0 — credit 5 each so Challenge column is not stuck at 0
	if ( $count > 0 && $sum < ( $count * 5 ) ) {
		$sum = $count * 5;
	}
	update_user_meta( $user_id, 'gc_challenge_points', $sum );
}


/** Ensure user has a referral tag (all tiers including free). */
function gc_ensure_referral_tag( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return '';
	}
	$tag = get_user_meta( $user_id, 'gc_premium_referral_tag', true );
	if ( $tag ) {
		return $tag;
	}
	$user = get_userdata( $user_id );
	$base = $user ? preg_replace( '/[^a-z0-9]/i', '', $user->user_login ) : 'GC';
	$tag  = strtoupper( substr( $base, 0, 4 ) ) . $user_id;
	update_user_meta( $user_id, 'gc_premium_referral_tag', $tag );
	return $tag;
}


/**
 * Rebuild visible point buckets so earned points sit in the right place.
 */
function gc_rebuild_all_point_buckets( $user_id ) {
	global $wpdb;
	$user_id = absint( $user_id );
	if ( ! $user_id || ! function_exists( 'gc_activity_table' ) ) {
		return;
	}
	if ( function_exists( 'gc_activity_table_exists' ) && ! gc_activity_table_exists() ) {
		return;
	}
	$table = gc_activity_table();

	// Challenge points from ledger
	$chal = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(points_awarded),0) FROM {$table} WHERE user_id = %d AND activity_type = %s",
		$user_id,
		'daily_challenge'
	) );
	if ( $chal > (int) get_user_meta( $user_id, 'gc_challenge_points', true ) ) {
		update_user_meta( $user_id, 'gc_challenge_points', $chal );
	}

	// Total from ledger if higher (does not lower manually adjusted totals)
	$ledger_total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(points_awarded),0) FROM {$table} WHERE user_id = %d",
		$user_id
	) );
	$tier = (int) get_user_meta( $user_id, 'gc_tier_points', true );
	$ref  = (int) get_user_meta( $user_id, 'gc_referral_points', true );
	$l2   = (int) get_user_meta( $user_id, 'gc_referral_l2_points', true );
	$chal = (int) get_user_meta( $user_id, 'gc_challenge_points', true );
	$bucket = $tier + $chal + $ref + $l2;
	$total  = (int) get_user_meta( $user_id, 'gc_total_points', true );
	$floor  = max( $bucket, $ledger_total );
	if ( $total < $floor ) {
		update_user_meta( $user_id, 'gc_total_points', $floor );
	}
}
