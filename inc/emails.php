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
	return 'hello@' . $host;
}
add_filter( 'wp_mail_from', 'gc_mail_from_address', 999 );
 function gc_send_member_welcome_email( $to, $full_name ) { if ( ! is_email( $to ) ) { return; } $first_name = trim( strtok( trim( $full_name ), ' ' ) ); if ( empty( $first_name ) ) { $first_name = 'there'; } $subject = __( 'Welcome to Game Changers Nigeria', 'game-changers' ); $body = "Hello {$first_name},\n\n"; $body .= "Welcome to Game Changers Nigeria.\n\n"; $body .= "Nigeria doesn't lack good people; we lack organization. By completing your profile and stepping into this ecosystem, you have officially chosen to move from raw outrage to structured, value-driven action.\n\n"; $body .= "Thank you for being a part of the movement. Let's build the future together.\n\n"; $body .= "Strategic regards,\n"; $body .= gc_signature_name(); gc_dispatch_welcome_email( $to, $subject, $body ); } function gc_send_partner_welcome_email( $to, $partner_name ) { if ( ! is_email( $to ) ) { return; } $name = trim( $partner_name ); if ( empty( $name ) ) { $name = 'there'; } $subject = __( 'Welcome as a Strategic Partner — Game Changers Nigeria', 'game-changers' ); $body = "Hello {$name} team,\n\n"; $body .= "Nigeria doesn't lack visionary minds or resources; we lack the coordinated structures to scale them. By locking arms with Game Changers Nigeria, you are choosing to move past fragmented efforts and step into a unified ecosystem built for systemic transformation.\n\n"; $body .= "We are incredibly proud to welcome you as an official strategic partner.\n\n"; $body .= "Our collaboration isn't just about alignment on paper — it is about combining our strengths to transition from raw outrage to structured, value-driven execution on a national scale. Together, we are building the framework that turns potential into reality.\n\n"; $body .= "Strategic regards,\n"; $body .= gc_signature_name(); gc_dispatch_welcome_email( $to, $subject, $body ); } function gc_dispatch_welcome_email( $to, $subject, $body ) { $reply_to = function_exists( 'gc_contact_email' ) ? gc_contact_email() : get_option( 'admin_email' ); $from = apply_filters( 'wp_mail_from', 'hello@' . ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'gamechangersnigeria.org' ) );
	$headers = array(
		'From: Game Changers Nigeria <' . $from . '>',
		'Reply-To: Game Changers Nigeria <' . $reply_to . '>',
	);
	wp_mail( $to, $subject, $body, $headers ); } 