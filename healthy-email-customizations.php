<?php

/*
Plugin Name: healthy Email Customizations
Description: Overrides pluggable functions related to the content and content-type of email messages sent from WordPress.
Version: 1.0
Author: Scott Fennell
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: healthy-email-customizations

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// OLD DNS: v=spf1 a mx ptr include:bluehost.com ip4:142.4.18.233 ?all


if ( ! function_exists( 'wp_new_user_notification' ) ) {

	/**
	 * Overrides the core wp_new_user_notification() function to send a more appropriate message.
	 * 
	 * @param  $user_id int The user just being created. 
	 */
	function wp_new_user_notification( $user_id ) {

		// Set content type to html.
		add_filter( 'wp_mail_content_type', 'healthy_wpmail_content_type' );

		global $wpdb, $wp_hasher;

		// Grab data about the newly created user.
		$user       = new WP_User( $user_id );
		$user_email = sanitize_email( $user -> user_email );

		$key = wp_generate_password( 20, false );
		
		do_action( 'retrieve_password_key', $user->user_login, $key );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
		    require_once ABSPATH . WPINC . '/class-phpass.php';
		    $wp_hasher = new PasswordHash( 8, true );
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

		$login_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user -> user_login ), 'login' );

		$welcome_subject = healthy_get_welcome_subject();
		$welcome_message = healthy_get_welcome_message( $user, $login_link );

		healthy_mail( $user_email, $welcome_subject, $welcome_message );
		
		// The recipient of the admin email.
		$admin_to = sanitize_email( get_option( 'admin_email' ) );

		// The subject of the admin email.
		$admin_subject = esc_html__( 'New User Created', 'healthy-email-customizations' );

		// admin email
		$admin_message  = sprintf( esc_html__( 'A new user has been created: %s', 'healthy-email-customizations' ), $user_email );
		
		$send = wp_mail( $admin_to, $admin_subject, $admin_message );

		// Remove html content type so as not to cause unexpected problems with other scripted emails.
		remove_filter ( 'wp_mail_content_type', 'healthy_wpmail_content_type' );

	}
}

function healthy_get_welcome_subject() {

	// The subject line of the welcome email.
	$welcome_subject = esc_html__( 'Welcome to The Healthy Futures Challenge!', 'healthy-email-customizations' );

	return $welcome_subject;

}

function healthy_get_welcome_message( $user, $login_link = '' ) {

	$user_id = absint( $user -> ID );

	// The user email.
	$user_email = sanitize_email( $user -> user_email );

	// The user login name.
	$user_login = sanitize_text_field( $user -> user_login );

	$school = '';
	if( function_exists( 'healthy_get_user_school' ) ) {
		$school = healthy_get_user_school( $user_id );
		if( ! empty( $school ) ) {
			$school = '<p>' . sprintf( esc_html__( 'Your school: <b>%s</b>', 'healthy-email-customizations' ), $school ) . '</p>';
		}
	}

	$team = '';
	if( function_exists( 'healthy_get_user_team' ) ) {
		$team = healthy_get_user_team( $user_id );
		if( ! empty( $team ) ) {
			$team = '<p>' . sprintf( esc_html__( 'Your team: <b>%s</b>', 'healthy-email-customizations' ), $team ) . '</p>';
		}
	}

	// Our home page url.
	$home_url = esc_url( get_bloginfo( 'url' ) );

	// Our logo, assumed to be in a standard location in the theme.
	$logo_src = esc_url( get_bloginfo( 'template_directory' ) ).'/images/logo_200.png';

	// Accent color.
	$accent_color = '#002856';

	// The subject line of the welcome email.
	$welcome_subject      = healthy_get_welcome_subject();
	$welcome_subject_attr = esc_attr( $welcome_subject );

	// Thanks text.
	$thanks = esc_html__( 'Thanks for signing up!', 'healthy-email-customizations' );

	// UN text.
	$un = esc_html__( 'Your user name:', 'healthy-email-customizations' );

	// PW text.
	$ll = esc_html__( 'Your login link:', 'healthy-email-customizations' );

	// Get started text.
	$start = esc_html__( 'Get started by logging in here:', 'healthy-email-customizations' );

	$out  = "<p><center><a href='$home_url'><img height=200 width=200 src='$logo_src' alt='$welcome_subject_attr'></a></center></p>";
	$out .= "<h2><center><a style='color: $accent_color' href='$home_url'>$welcome_subject</a><center></h2>";
	$out .= "<p>$thanks</p>";
	$out .= "<p>$un <b>$user_login</b></p>";
	$out .= "<p>$ll <b>$login_link</b></p>";
	$out .= "$school";
	$out .= "$team";
	$out .= "<p>$start <i><a style='color: $accent_color' href='$home_url'>$home_url</a></i></p>";
	
	return $out;

}

/**
* Allow html emails.
* 
* @return string The html content type.
*/
function healthy_wpmail_content_type() {
	return 'text/html';
}

function healthy_mail( $user_email, $welcome_subject, $welcome_message ) {

	wp_mail( $user_email, $welcome_subject, $welcome_message );

	/*
	require_once 'Mandrill.php'; //Not required with Composer
	$mandrill = new Mandrill( 'Xpk5Y9SLBC96eBpCNejzYw' );

	$from_email = 'healthyfutures@hfchallenge.org';

	$array = array(
        'html'        => $welcome_message,
        'text'        => strip_tags( $welcome_message ),
        'subject'     => $welcome_subject,
        'from_email'  => $from_email,
        'from_name'   => 'healthyfutures',
        'to'          => array(
            array(
                'email' => $user_email,
                'name'  => $user_email,
                'type'  => 'to'
            )
        ),
        'headers'             => array( 'Reply-To' => $from_email ),
        'important'           => false,
        'track_opens'         => null,
        'track_clicks'        => null,
        'auto_text'           => null,
        'auto_html'           => null,
        'inline_css'          => null,
        'url_strip_qs'        => null,
        'preserve_recipients' => null,
        'view_content_link'   => null,
        'bcc_address'         => null,
        'tracking_domain'     => null,
        'signing_domain'      => null,
        'return_path_domain'  => null,
        'merge'               => false,
        'merge_language'      => 'mailchimp',
    );

    $async   = false;
    $ip_pool = 'Main Pool';
    $send_at = FALSE;
    $result  = $mandrill -> messages -> send( $array, $async, $ip_pool, $send_at );
	*/
}

?>