<?php
/**
 * Plugin Name: Easy Symlinks
 * Version: 1.0.5
 * Plugin URI: http://wordpress.org/plugins/easy-symlinks
 * Description: Manage symbolic links from the WordPress admin. Create and delete symlinks without CLI access, ideal for Pantheon environments.
 * Author: Carl Alberto
 * Author URI: https://carlalberto.code.blog/
 * Requires at least: 4.9
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Tested PHP up to: 8.4
 *
 * Text Domain: easy-symlinks
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Carl Alberto
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-easy-symlinks.php';
require_once 'includes/class-easy-symlinks-settings.php';
require_once 'includes/lib/class-easy-symlinks-admin-api.php';
require_once 'includes/lib/class-easy-symlinks-functions.php';

/**
 * Returns the main instance of Easy_Symlinks to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Easy_Symlinks
 */
function easy_symlinks() {
	$instance = Easy_Symlinks::instance( __FILE__, '1.0.5' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Easy_Symlinks_Settings::instance( $instance );
	}

	return $instance;
}

easy_symlinks();
