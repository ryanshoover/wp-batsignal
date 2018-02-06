<?php
/**
 * Plugin Name: WP Batsignal
 * Description: Been hacked? Call WordPress Batman to save the day!
 * Author: Ryan Hoover
 * Version: 0.1.1
 * Author URI: https://ryan.hoover.ws
 *
 * @package wp-batsignal
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

define( 'WP_BATSIGNAL_PATH', plugin_dir_path( __FILE__ ) );

// Include our command class.
require_once WP_BATSIGNAL_PATH . 'php/class-wp-batsignal-command.php';

// Register the command with WP-CLI.
WP_CLI::add_command( 'batsignal', 'WP_BatSignal_Command' );
