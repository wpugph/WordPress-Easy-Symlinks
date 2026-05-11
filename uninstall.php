<?php
/**
 * Plugin uninstall procedure.
 *
 * @package Easy Symlinks WP/Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( 'on' === get_option( 'caes_delete_data_on_uninstall' ) ) {
	delete_option( 'caes_symlink_list' );
	delete_option( 'caes_symlink_list_lastdelete' );
	delete_option( 'caes_target' );
	delete_option( 'caes_link' );
	delete_option( 'caes_delete_data_on_uninstall' );
	delete_option( 'easy_symlinksversion' );
}
