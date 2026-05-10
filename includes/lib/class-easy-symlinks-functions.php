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

		if ( ( null === $symlinklist ) || ( false === $symlinklist ) ) {
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
		// Remove this.
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

		$this->create_folder( $target );

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

	/**
	 * Check if filesystem is writable, disable gui if not.
	 *
	 * @return boolean
	 */
	public function check_fs_writable() {
		$homepathwritable = $this->get_wp_homepath();
		$return           = is_writable( $homepathwritable );
		return $return;
	}

	/**
	 * Detect the current hosting environment.
	 *
	 * @return array {
	 *     @type string $type        'pantheon' or 'local'.
	 *     @type string $environment Pantheon environment name or 'local'.
	 *     @type string $label       Human-readable environment label.
	 *     @type bool   $writable    Whether the filesystem is writable.
	 *     @type string $site_name   Pantheon site name if available.
	 *     @type string $connection  Pantheon connection mode (sftp/git) if detectable.
	 * }
	 */
	public function detect_environment() {
		$env = array(
			'type'        => 'local',
			'environment' => 'local',
			'label'       => 'Local Development',
			'writable'    => $this->check_fs_writable(),
			'site_name'   => '',
			'connection'  => '',
		);

		if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			$env['type']        = 'pantheon';
			$env['environment'] = sanitize_text_field( $_ENV['PANTHEON_ENVIRONMENT'] );
			$env['site_name']   = isset( $_ENV['PANTHEON_SITE_NAME'] ) ? sanitize_text_field( $_ENV['PANTHEON_SITE_NAME'] ) : '';

			$labels = array(
				'dev'  => 'Pantheon Dev',
				'test' => 'Pantheon Test',
				'live' => 'Pantheon Live',
			);
			$env['label'] = isset( $labels[ $env['environment'] ] )
				? $labels[ $env['environment'] ]
				: 'Pantheon Multidev (' . $env['environment'] . ')';

			if ( isset( $_ENV['PANTHEON_ENVIRONMENT_CONNECTION_MODE'] ) ) {
				$env['connection'] = sanitize_text_field( $_ENV['PANTHEON_ENVIRONMENT_CONNECTION_MODE'] );
			}
		}

		return $env;
	}

	/**
	 * Check if writable filesystem.
	 *
	 * @return array
	 */
	public function check_if_in_pantheon_writable_env() {
		$env = $this->detect_environment();

		if ( 'pantheon' === $env['type'] ) {
			if ( in_array( $env['environment'], array( 'test', 'live' ), true ) ) {
				$return['error']  = 'This plugin can not be used in Test and Live Read-only Environments in Pantheon';
				$return['status'] = false;
				return $return;
			} else {
				if ( $env['writable'] ) {
					$return['error']  = 'In Writable Environment';
					$return['status'] = true;
					return $return;
				} else {
					$return['error']  = 'Root folder not writable. Please check if your environment is in Git mode or switch SFTP mode.';
					$return['status'] = false;
					return $return;
				}
			}
		} else {
			if ( $env['writable'] ) {
				$return['error']  = 'In Writable filesystem';
				$return['status'] = true;
				return $return;
			} else {
				$return['error']  = 'Root folder not writable. Please check your filesystem if it is writable.';
				$return['status'] = false;
				return $return;
			}
		}
	}

	/**
	 * Create folder for symlinks.
	 *
	 * @param string $target Hook parameter.
	 *
	 * @return boolean
	 */
	public function create_folder( $target ) {
		$homepath = $this->get_wp_homepath();

		// Get the target folder name.
		if ( preg_match( '/\/uploads\/\W?\K.*/', $target, $matches ) ) {
			// Create target folder under uploads folder.
			$status = mkdir( $homepath . '/wp-content/uploads/' . $matches[0], 0777, true );
		}
		return $status;
	}

}
