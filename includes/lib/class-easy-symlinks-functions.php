<?php
/**
 * Easy symlink
 *
 * @package Easy Symlinks/Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Easy Symlinks Functons.
 */
class Easy_Symlinks_Functions {

	/**
	 * The single instance of Easy_Symlinks_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Symlinks variable
	 *
	 * @var array
	 */
	public static $symlinks;

	/**
	 * Returns the homepath of the root of WP. Needs to be tested with multiple appserver compatibility.
	 *
	 * @return string
	 */
	public function get_wp_homepath() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$homepath = get_home_path();
		return $homepath;
	}

	/**
	 * Get existing symlinks list from wp_options table.
	 *
	 * @return array
	 */
	public function get_symlinks() {
		$symlinklist = '';
		$symlinklist = maybe_unserialize( get_option( 'caes_symlink_list' ) );
		if ( null === $symlinklist ) {
			return null;
		} else {
			return $symlinklist;
		}

	}

	/**
	 * Display existing symlinks.
	 *
	 * @return string
	 */
	public function display_symlinks() {
		$symlinks     = $this->get_symlinks();
		$symlinkslist = '';
		if ( $symlinks ) {
			foreach ( $symlinks as $symlink ) {
				// add validity check here if the existing symlinks are good
				// - filesystem do not match in db, list what is symlinked
				// - not an existing symlink in hte filesystem, delete and readd again.
				$symlinkslist .= $symlink . '<br>';
			}
			$return = '<h4>Existing Symlinks:</h4><br>' . $symlinkslist;
			return $return;
		} else {
			return '';
		}

	}

	/**
	 * Delete a symlink.
	 *
	 * @return booloen
	 */
	public function delete_symlink() {
		$homepath = $this->get_wp_homepath();
		$original = maybe_unserialize( get_option( 'caes_symlink_list' ) );
		$todelete = maybe_unserialize( get_option( 'caes_symlink_list_lastdelete' ) );

		$del            = $todelete[0];
		$path_todelete  = $original[ $del ];
		$path_todelete1 = strstr( $path_todelete, ' -> ', true );
		unset( $original[ $del ] );
		$option = 'caes_symlink_list';
		update_option( $option, maybe_serialize( $original ) );
		$return = unlink( $homepath . $path_todelete1 );
		return $return;
	}

	/**
	 * Save symlinks in db and create the symlink.
	 *
	 * @return boolean
	 */
	public function save_symlinks() {
		$homepath      = $this->get_wp_homepath();
		$original_list = '';
		$original_list = maybe_unserialize( get_option( 'caes_symlink_list', false ) );
		$source        = maybe_unserialize( get_option( 'caes_target' ) );
		$destination   = maybe_unserialize( get_option( 'caes_link' ) );
		$target        = $source; // This should be existing.
		$link          = $homepath . $destination; // This is the one created.

		symlink( $target, $link );
		// add additional checks here
		// check if already added
		// successfully add
		// fail error.
		$value = $destination . ' -> ' . $source;

		if ( $original_list ) {
			$new = array_push( $original_list, $value );
		} else {
			$original_list = array();
			$new           = array_push( $original_list, $value );
		}

		$option = 'caes_symlink_list';
		update_option( $option, maybe_serialize( $original_list ) );

		return true;
	}
}
