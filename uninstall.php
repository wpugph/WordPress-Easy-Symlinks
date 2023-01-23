<?php
/**
 * Plugin uninstall procedure.
 *
 * @package Easy Symlinks WP/Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// loading up to get the home path
require_once ABSPATH . 'wp-admin/includes/file.php';
// load the easy-symlinks file so we can later get token and version
include_once 'easy-symlinks.php';

// unlink paths
$links = maybe_unserialize( get_option( 'caes_symlink_list' ) );
foreach ($links as $key => $value) {
	$link = strstr( $value, ' ->', true );
	unlink( get_home_path() . $link );
}

// remove options
delete_option( easy_symlinks()->token . 'version', easy_symlinks()->version );
delete_option( 'caes_symlink_list' );
delete_option( 'caes_symlink_list_lastdelete' );
delete_option( 'caes_target' );
delete_option( 'caes_link' );
