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

if ( ! function_exists( 'wp_new_user_notification' ) ) {

    /**
     * Overrides the core wp_new_user_notification() function to send a more appropriate message.
     * 
     * @param  $user_id int The user just being created.
     * @param  $plaintext_pass The plaintext password for the new user.
     */
    function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {

        // Set content type to html.
        add_filter( 'wp_mail_content_type', 'healthy_wpmail_content_type' );

        // Grab data about the newly created user.
        $user = new WP_User( $user_id );

        // The user email.
        $user_email = sanitize_email( $user -> user_email );

        // The user login name.
        $user_login = sanitize_text_field( $user -> user_login );

        // Our home page url.
        $home_url = esc_url( get_bloginfo( 'url' ) );

        // Our logo, assumed to be in a standard location in the theme.
        $logo_src = esc_url( get_bloginfo( 'template_directory' ) ).'/images/logo_200.png';

        // Accent color.
        $accent_color = '#002856';

        // The recipient of the admin email.
        $admin_to = sanitize_email( get_option( 'admin_email' ) );

        // The subject of the admin email.
        $admin_subject = esc_html__( 'New User Created', 'healthy-email-customizations' );

        // admin email
        $admin_message  = sprintf( esc_html__( 'A new user has been created: %s', 'healthy-email-customizations' ), $user_email );

        // The subject line of the welcome email.
        $welcome_subject = esc_html__( 'Welcome to The Healty Futures Challenge!', 'healthy-email-customizations' );

        // Thanks text.
        $thanks = esc_html__( 'Thanks for signing up!', 'healthy-email-customizations' );

        // UN text.
        $un = esc_html__( 'Your user name:', 'healthy-email-customizations' );


        // PW text.
        $pw = esc_html__( 'Your password:', 'healthy-email-customizations' );


        // Get started text.
        $start = esc_html__( 'Get started by logging in here:', 'healthy-email-customizations' );


        $welcome_message = "
        <p><center><a href='$home_url'><img height=200 width=200 src='$logo_src' alt='$subject'></a></center></p>
        <h2><center><a style='color: $accent_color' href='$home_url'>$welcome_subject</a><center></h2>
        <p>$thanks</p>
        <p>$un <b>$user_login</b></p>
        <p>$pw <b>$plaintext_pass</b></p>
        <p>$start <i><a style='color: $accent_color' href='$home_url'>$home_url</a></i></p>
        ";
        
        // Send a welcome email to the new user.
        if ( ! wp_mail( $user_email, $welcome_subject, $welcome_message ) ) {
            wp_die( "There has been a problem. 77" );
        }

        // Send the admin email.
        if( ! wp_mail( $admin_to, $admin_subject, $admin_message ) ) {
            wp_die( "There has been a problem. 82" );
        }

        // Remove html content type so as not to cause unexpected problems with other scripted emails.
        remove_filter ( 'wp_mail_content_type', 'healthy_wpmail_content_type' );
    }
}

/**
* Allow html emails.
* 
* @return string The html content type.
*/
function healthy_wpmail_content_type() {
    return 'text/html';
}

?>