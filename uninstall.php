<?php
/**
 * Plugin uninstall procedure.
 *
 * @package Easy Symlinks WP/Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

var_dump( ABSPATH );
var_dump( $_REQUEST );

$_REQUEST;