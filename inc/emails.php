<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } function gc_signature_name() { return gc_opt( 'gc_signature_name', 'Adewale Adewumi' ); } function gc_mail_from_name( $name ) { return 'Game Changers Nigeria'; }
add_filter( 'wp_mail_from_name', 'gc_mail_from_name', 999 );
function gc_mail_from_address( $email ) {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! $host ) {
		$host = 'gamechangersnigeria.org';
	}
	$host = preg_replace( '/^www\./', '', $host );
	// Prefer configured contact / admin domain mailbox — never wordpress@
	$preferred = get_option( 'gc_mail_from_email', '' );
	if ( is_email( $preferred ) ) {
		return $preferred;
	}
	$contact = function_exists( 'gc_contact_email' ) ? gc_contact_email() : '';
	if ( is_email( $contact ) && false === stripos( $contact, 'wordpress@' ) ) {
		return $contact;
	}
	return 'info@' . $host;
}
add_filter( 'wp_mail_from', 'gc_mail_from_address', 999 );
 
function gc_send_member_welcome_email( $to, $full_name ) {
	if ( ! is_email( $to ) ) {
		return;
	}
	$first_name = trim( strtok( trim( (string) $full_name ), ' ' ) );
	if ( empty( $first_name ) ) {
		$first_name = 'Leader';
	}
	$hub = function_exists( 'gc_page_url' ) ? gc_page_url( 'member-hub' ) : home_url( '/member-hub/' );
	$login = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : home_url( '/premium-login/' );
	$subject = 'Welcome to Game Changers Nigeria';
	$body  = "Dear {$first_name},\n\n";
	$body .= "Welcome to Game Changers Nigeria.\n\n";
	$body .= "You have taken a meaningful step into a national community of purpose-driven leaders who learn, connect, serve, and create measurable change.\n\n";
	$body .= "Your free membership gives you access to:\n";
	$body .= "• Daily leadership nuggets\n";
	$body .= "• The 7-Day Activation Challenge\n";
	$body .= "• Member Hub resources for free members\n\n";
	$body .= "Log in and start here:\n{$hub}\n\n";
	$body .= "If you ever need help with your account or payment, use Payment & account help on the website.\n\n";
	$body .= "We are glad you are here. Lead with intention.\n\n";
	$body .= "Warm regards,\n";
	$body .= "Game Changers Nigeria\n";
	gc_dispatch_welcome_email( $to, $subject, $body );
}

function gc_send_premium_welcome_email( $to, $full_name, $tier = 1 ) {
	if ( ! is_email( $to ) ) {
		return;
	}
	$first_name = trim( strtok( trim( (string) $full_name ), ' ' ) );
	if ( empty( $first_name ) ) {
		$first_name = 'Leader';
	}
	$tier = max( 1, absint( $tier ) );
	$tier_label = 'Tier ' . $tier;
	if ( function_exists( 'gc_tiers' ) ) {
		$tiers = gc_tiers();
		if ( ! empty( $tiers[ $tier ]['name'] ) ) {
			$tier_label = $tiers[ $tier ]['name'] . ' (Tier ' . $tier . ')';
		}
	}
	$dash = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );
	$login = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-login' ) : home_url( '/premium-login/' );
	$subject = 'Welcome to Premium — Game Changers Nigeria';
	$body  = "Dear {$first_name},\n\n";
	$body .= "Welcome to the Game Changers Nigeria premium family.\n\n";
	$body .= "Your payment is confirmed and your membership is active as:\n";
	$body .= "{$tier_label}\n\n";
	$body .= "As a premium member you unlock structured leadership growth: courses, certificates, badges, referral opportunities, and deeper community impact tools.\n\n";
	$body .= "Your Member Dashboard:\n{$dash}\n\n";
	$body .= "Sign in (same email and password you created):\n{$login}\n\n";
	$body .= "Thank you for investing in your growth and in the movement. We look forward to walking this path with you.\n\n";
	$body .= "Strategic regards,\n";
	$body .= "Game Changers Nigeria\n";
	gc_dispatch_welcome_email( $to, $subject, $body );
}

function gc_send_partner_welcome_email( $to, $partner_name ) { if ( ! is_email( $to ) ) { return; } $name = trim( $partner_name ); if ( empty( $name ) ) { $name = 'there'; } $subject = __( 'Welcome as a Strategic Partner — Game Changers Nigeria', 'game-changers' ); $body = "Hello {$name} team,\n\n"; $body .= "Nigeria doesn't lack visionary minds or resources; we lack the coordinated structures to scale them. By locking arms with Game Changers Nigeria, you are choosing to move past fragmented efforts and step into a unified ecosystem built for systemic transformation.\n\n"; $body .= "We are incredibly proud to welcome you as an official strategic partner.\n\n"; $body .= "Our collaboration isn't just about alignment on paper — it is about combining our strengths to transition from raw outrage to structured, value-driven execution on a national scale. Together, we are building the framework that turns potential into reality.\n\n"; $body .= "Strategic regards,\n"; $body .= gc_signature_name(); gc_dispatch_welcome_email( $to, $subject, $body ); } function gc_dispatch_welcome_email( $to, $subject, $body ) {
	$reply_to = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' );
	$host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org';
	$host = preg_replace( '/^www\./', '', $host );
	$from = apply_filters( 'wp_mail_from', 'info@' . $host );
	if ( ! is_email( $from ) || false !== stripos( $from, 'wordpress@' ) ) {
		$from = 'info@' . $host;
	}
	$headers = array(
		'From: Game Changers Nigeria <' . $from . '>',
		'Reply-To: Game Changers Nigeria <' . ( is_email( $reply_to ) ? $reply_to : $from ) . '>',
		'Content-Type: text/plain; charset=UTF-8',
	);
	wp_mail( $to, $subject, $body, $headers );
} 