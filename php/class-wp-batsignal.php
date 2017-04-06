<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin, huh?' );
}

class WP_BatSignal_Command extends WP_CLI_Command {

	protected $failed = false;

	/**
	 * Been hacked? Call WordPress Batman to save the day!
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$this->send_batsignal();

		$this->reset_user_passwords();

		$this->roll_salts();

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'send-emails' ) ) {
			$this->send_emails();
		}

		$this->send_result();
	}

	protected function send_batsignal() {
		$num = rand( 1, 3 );

		$batsignal = file_get_contents( WP_BATSIGNAL_PATH . "ascii/bat-signal-{$num}.txt" );

		WP_CLI::log( $batsignal );
	}

	protected function send_result() {
		$image = $this->failed ? 'joker' : 'batman';

		$txt = file_get_contents( WP_BATSIGNAL_PATH . "ascii/{$image}.txt" );

		WP_CLI::log( $txt );

		if ( ! $this->failed ) {
			WP_CLI::success( 'WordPress Batman has saved your site! Sleep well, good citizen.' );
		} else {
			WP_CLI::error( 'WordPress Batman has failed! Nefarious criminals have overrun your site.' );
		}
	}

	protected function reset_user_passwords() {
		global $wpdb;

		$res = $wpdb->query( "UPDATE $wpdb->users SET `user_pass` = ''" );

		if ( false !== $res ) {
			WP_CLI::success( "Reset $res user's passwords" );
		} else {
			WP_CLI::warning( 'Problem resetting user passwords' );
			$this->failed = true;
		}
	}

	protected function roll_salts() {
		$salt_keys = array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT'
			);

		$api  = 'https://api.wordpress.org/secret-key/1.1/salt/';

		$wp_config_path = false;
		$wp_salts_path = false;

		$salts = file_get_contents( $api );

		if ( ! $salts ) {
			WP_CLI::warning( 'Problem fetching new salts from api.wordpress.org' );
			$this->failed = true;
			return;
		}

		/**
		 * Find the wp-config path either in ABSPATH or one directory above (for a nested install)
		 * Borrowed from wp-load.php
		 */
		if ( file_exists( ABSPATH . 'wp-config.php') ) {
			$wp_config_path = ABSPATH . 'wp-config.php';
		} elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		/**
		 * Salts are often placed in a wp-salts.php file.
		 * Check to see if we have one of those
		 */
		if ( file_exists( ABSPATH . 'wp-salts.php') ) {
			$wp_salts_path = ABSPATH . 'wp-salts.php';
		} elseif ( file_exists( dirname( ABSPATH ) . '/wp-salts.php' ) ) {
			$wp_salts_path = dirname( ABSPATH ) . '/wp-salts.php';
		}

		$file_path = $wp_salts_path ?: $wp_config_path;

		if ( ! $file_path ) {
			WP_CLI::warning( 'Problem finding wp-config' );
			$this->failed = true;
			return;
		}

		$contents = file_get_contents( $file_path );

		$salts_regex = '/define\(\s?[\'"](?:' . implode( '|', $salt_keys ) . ')[\'"],\s*[\'"][^\'"]+[\'"]\);\s?' . PHP_EOL . '/';

		preg_match( $salts_regex, $contents, $salt_matches, PREG_OFFSET_CAPTURE );

		$offset = ! empty( $salt_matches ) ? $salt_matches[0][1] : strlen( $contents );

		$contents = preg_replace( $salts_regex, '', $contents );

		$contents = substr( $contents, 0, $offset ) . $salts . PHP_EOL . substr( $contents, $offset + 1 );

		$res = file_put_contents( $file_path, $contents );

		if ( $res ) {
			WP_CLI::success( 'Rolled the salts' );
		} else {
			WP_CLI::warning( 'Problem writing new salts to wp-config' );
			$this->failed = true;
		}
	}

	protected function send_emails() {
		global $wpdb;

		$users = $wpdb->get_results( "SELECT `user_email` FROM $wpdb->users" );

		$to = get_option( 'admin_email' );

		$subject = sprintf( 'WordPress Batman has protected you on %s', get_bloginfo() );

		$message  = '';
		$message .= 'You\'ve been saved from nefarious criminals by WordPress Batman.' . PHP_EOL . PHP_EOL;
		$message .= 'Please reset your password on ' . get_bloginfo();

		$headers = [];

		foreach ( $users as $user ) {
			$headers[] = 'Bcc:' . $user->user_email;
		}

		$res = wp_mail( $to, $subject, $message, $headers );

		if ( $res ) {
			WP_CLI::success( 'Email sent to all users' );
		} else {
			WP_CLI::warning( 'Problem sending an email to all users' );
			$this->failed = true;
		}
	}
}

WP_CLI::add_command( 'batsignal', 'WP_BatSignal_Command' );
