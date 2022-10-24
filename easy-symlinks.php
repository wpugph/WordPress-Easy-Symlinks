<?php
/**
 * Plugin Name: Easy Symlinks
 * Version: 1.0.3
 * Plugin URI: http://wordpress.org/plugins/easy-symlinks
 * Description: Easy symlinking tool in WP. Best used for non-command line users. This can only track symlinks created within the application and excludes symlinks created from the filesystem and command line. Best used in Pantheon dev environments in SFTP mode.
 * Author: Carl Alberto
 * Author URI: https://carlalberto.code.blog/
 * Requires at least: 4.9
 * Tested up to: 6.0.3
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
	$instance = Easy_Symlinks::instance( __FILE__, '1.0.3' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Easy_Symlinks_Settings::instance( $instance );
	}

	return $instance;
}

easy_symlinks();
