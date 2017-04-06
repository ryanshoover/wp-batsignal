<?php
/*
Plugin Name: WP Batsignal
Description: Been hacked? Call WordPress Batman to save the day!
Author: Ryan Hoover
Version: 0.1
Author URI: https://ryan.hoover.ws
*/

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

define( 'WP_BATSIGNAL_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Some files need to be manually loaded
 */
require_once dirname( __FILE__ ) . '/php/class-wp-batsignal.php';
