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
	 * Get the status of a filesystem path.
	 *
	 * @param string $path Full filesystem path to check.
	 * @return string 'symlink', 'exists', or 'none'.
	 */
	public function get_link_status( $path ) {
		clearstatcache( true, $path );
		if ( is_link( $path ) ) {
			return 'symlink';
		}
		if ( is_dir( $path ) || is_file( $path ) ) {
			return 'exists';
		}
		return 'none';
	}

	/**
	 * Get the saved symlink list from the database.
	 *
	 * @return array
	 */
	private function get_symlink_list() {
		$list = maybe_unserialize( get_option( 'caes_symlink_list', false ) );
		return $list ? $list : array();
	}

	/**
	 * Save the symlink list to the database.
	 *
	 * @param array $list Symlink list to save.
	 * @return void
	 */
	private function save_symlink_list( $list ) {
		update_option( 'caes_symlink_list', maybe_serialize( $list ) );
	}

	/**
	 * Ensure the parent directory of a path exists.
	 *
	 * @param string $path Full filesystem path.
	 * @return void
	 */
	private function ensure_parent_dir( $path ) {
		$parent = dirname( $path );
		if ( ! is_dir( $parent ) ) {
			@wp_mkdir_p( $parent );
		}
	}

	/**
	 * Copy a file or directory to a destination.
	 *
	 * @param string $source Source path.
	 * @param string $dest   Destination path.
	 * @return boolean True if copy succeeded.
	 */
	private function copy_path( $source, $dest ) {
		if ( is_dir( $source ) ) {
			$this->copy_directory( $source, $dest );
			return is_dir( $dest );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		@copy( $source, $dest );
		return file_exists( $dest );
	}

	/**
	 * Remove a file or directory.
	 *
	 * @param string $path Path to remove.
	 * @return void
	 */
	private function remove_path( $path ) {
		if ( ! $path ) {
			return;
		}
		clearstatcache( true, $path );
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			$this->remove_directory( $path );
		} elseif ( file_exists( $path ) || is_link( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $path );
		}
	}

	/**
	 * Move a file or directory with cross-filesystem fallback.
	 *
	 * @param string $source Source path.
	 * @param string $dest   Destination path.
	 * @return boolean True if move succeeded.
	 */
	private function move_path( $source, $dest ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		$moved = @rename( $source, $dest );
		if ( $moved ) {
			return true;
		}

		$copied = $this->copy_path( $source, $dest );
		if ( $copied ) {
			$this->remove_path( $source );
			return true;
		}

		return false;
	}

	/**
	 * Get existing symlinks list from wp_options table.
	 *
	 * @return array
	 */
	public function get_symlinks() {
		$list = $this->get_symlink_list();
		return empty( $list ) ? null : $list;
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
		$original = $this->get_symlink_list();
		$todelete = maybe_unserialize( get_option( 'caes_symlink_list_lastdelete' ) );

		if ( empty( $todelete ) || empty( $original ) ) {
			return false;
		}

		$del = $todelete[0];

		if ( ! isset( $original[ $del ] ) ) {
			return false;
		}

		$path_todelete  = $original[ $del ];
		$path_todelete1 = strstr( $path_todelete, ' -> ', true );
		$full_path      = $homepath . $path_todelete1;

		unset( $original[ $del ] );
		$this->save_symlink_list( $original );

		if ( 'symlink' !== $this->get_link_status( $full_path ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		return @unlink( $full_path );
	}

	/**
	 * Save symlinks in db and create the symlink.
	 *
	 * @return boolean
	 */
	public function save_symlinks() {
		$homepath      = $this->get_wp_homepath();
		$original_list = $this->get_symlink_list();
		$source        = maybe_unserialize( get_option( 'caes_target' ) );
		$destination   = maybe_unserialize( get_option( 'caes_link' ) );
		$target        = $source;
		$link          = $homepath . $destination;

		if ( 'none' !== $this->get_link_status( $link ) ) {
			return false;
		}

		$this->ensure_parent_dir( $link );
		$this->create_folder( $target );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.symlink_symlink
		$created = @symlink( $target, $link );
		if ( ! $created ) {
			return false;
		}

		$original_list[] = $destination . ' -> ' . $source;
		$this->save_symlink_list( $original_list );

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
	 * Get available preset symlink configurations for detected plugins.
	 *
	 * @return array
	 */
	public function get_presets() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$presets = array();

		if ( is_plugin_active( 'wordfence/wordfence.php' ) || file_exists( WP_PLUGIN_DIR . '/wordfence/wordfence.php' ) ) {
			$presets['wordfence'] = array(
				'name'  => 'Wordfence',
				'links' => array(
					array(
						'target' => '../files/wflogs',
						'link'   => '/wp-content/wflogs',
					),
					array(
						'target' => '../files/private/wordfence-waf.php',
						'link'   => '/wordfence-waf.php',
					),
					array(
						'target' => '../files/private/.user.ini',
						'link'   => '/.user.ini',
					),
				),
			);
		}

		return $presets;
	}

	/**
	 * Apply a preset by creating all its symlinks.
	 *
	 * @param string $preset_key The preset key to apply.
	 * @return array { @type int $created, @type int $skipped, @type int $failed }
	 */
	public function apply_preset( $preset_key ) {
		$presets = $this->get_presets();
		$result  = array(
			'created'   => 0,
			'converted' => 0,
			'skipped'   => 0,
			'failed'    => 0,
		);

		if ( ! isset( $presets[ $preset_key ] ) ) {
			return $result;
		}

		$homepath      = $this->get_wp_homepath();
		$original_list = $this->get_symlink_list();

		foreach ( $presets[ $preset_key ]['links'] as $pair ) {
			$target = $pair['target'];
			$link   = $homepath . $pair['link'];
			$status = $this->get_link_status( $link );

			if ( 'symlink' === $status ) {
				++$result['skipped'];
				continue;
			}

			$converted = false;
			if ( 'exists' === $status ) {
				$converted = $this->convert_to_symlink( $link, $target, $homepath );
				if ( ! $converted ) {
					++$result['failed'];
					continue;
				}
			}

			if ( ! $converted ) {
				$this->ensure_parent_dir( $link );

				// Ensure the target path exists before symlinking.
				$target_abs = $this->resolve_target_path( $target, $homepath );
				if ( pathinfo( $target_abs, PATHINFO_EXTENSION ) ) {
					$this->ensure_parent_dir( $target_abs );
				} else {
					if ( 'none' === $this->get_link_status( $target_abs ) ) {
						@wp_mkdir_p( $target_abs );
					}
				}

				// phpcs:ignore WordPress.WP.AlternativeFunctions.symlink_symlink
				$created = @symlink( $target, $link );

				if ( ! $created ) {
					++$result['failed'];
					continue;
				}
			}

			if ( $converted ) {
				++$result['converted'];
			} else {
				++$result['created'];
			}

			$original_list[] = $pair['link'] . ' -> ' . $pair['target'];
		}

		$this->save_symlink_list( $original_list );

		return $result;
	}

	/**
	 * Remove all symlinks for a preset.
	 *
	 * @param string $preset_key The preset key to remove.
	 * @return array { @type int $removed, @type int $skipped }
	 */
	public function remove_preset( $preset_key ) {
		$presets = $this->get_presets();
		$result  = array(
			'removed' => 0,
			'skipped' => 0,
		);

		if ( ! isset( $presets[ $preset_key ] ) ) {
			return $result;
		}

		$homepath      = $this->get_wp_homepath();
		$original_list = $this->get_symlink_list();

		foreach ( $presets[ $preset_key ]['links'] as $pair ) {
			$link = $homepath . $pair['link'];

			if ( 'symlink' !== $this->get_link_status( $link ) ) {
				++$result['skipped'];
				continue;
			}

			$target_abs  = $this->resolve_target_path( $pair['target'], $homepath );
			$has_content = $target_abs && 'none' !== $this->get_link_status( $target_abs );

			// Safety: copy target contents to temp before touching anything.
			$temp_path = '';
			if ( $has_content ) {
				$temp_path = $homepath . 'wp-content/uploads/easy-symlinks-tmp/' . basename( $link ) . '-' . time();
				$this->ensure_parent_dir( $temp_path );
				$this->copy_path( $target_abs, $temp_path );
			}

			// Remove the symlink.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			$deleted = @unlink( $link );

			if ( ! $deleted ) {
				$this->remove_path( $temp_path );
				++$result['skipped'];
				continue;
			}

			// Restore files from target to original link location.
			if ( $has_content ) {
				$this->ensure_parent_dir( $link );
				$restored = $this->move_path( $target_abs, $link );

				if ( ! $restored && $temp_path ) {
					// Last resort: restore from temp backup.
					$this->move_path( $temp_path, $link );
				}

				$this->remove_path( $temp_path );
			}

			$value = $pair['link'] . ' -> ' . $pair['target'];
			$key   = array_search( $value, $original_list, true );
			if ( false !== $key ) {
				unset( $original_list[ $key ] );
			}
			++$result['removed'];
		}

		$this->save_symlink_list( array_values( $original_list ) );

		return $result;
	}

	/**
	 * Resolve a relative target path to an absolute path.
	 *
	 * @param string $target   Relative or absolute target path.
	 * @param string $homepath WordPress home path.
	 * @return string Absolute path.
	 */
	private function resolve_target_path( $target, $homepath ) {
		if ( '/' === $target[0] ) {
			$target_abs = $target;
		} else {
			$target_abs = rtrim( $homepath, '/' ) . '/' . $target;
		}

		$parts    = array();
		$segments = explode( '/', $target_abs );
		foreach ( $segments as $seg ) {
			if ( '..' === $seg ) {
				array_pop( $parts );
			} elseif ( '.' !== $seg && '' !== $seg ) {
				$parts[] = $seg;
			}
		}

		return '/' . implode( '/', $parts );
	}

	/**
	 * Convert an existing file/directory to a symlink by moving contents to target.
	 *
	 * @param string $link     Full path to the existing file/directory.
	 * @param string $target   Relative target path for the symlink.
	 * @param string $homepath WordPress home path.
	 * @return boolean True if conversion succeeded.
	 */
	private function convert_to_symlink( $link, $target, $homepath ) {
		$target_abs    = $this->resolve_target_path( $target, $homepath );
		$target_status = $this->get_link_status( $target_abs );

		$this->ensure_parent_dir( $target_abs );

		if ( 'none' === $target_status ) {
			$moved = $this->move_path( $link, $target_abs );
		} else {
			// Target already has content — merge and remove source.
			$this->copy_path( $link, $target_abs );
			$this->remove_path( $link );
			$moved = ( 'none' === $this->get_link_status( $link ) );
		}

		if ( ! $moved ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.symlink_symlink
		return @symlink( $target, $link );
	}

	/**
	 * Recursively copy a directory.
	 *
	 * @param string $source Source directory.
	 * @param string $dest   Destination directory.
	 * @return void
	 */
	private function copy_directory( $source, $dest ) {
		if ( ! is_dir( $dest ) ) {
			@wp_mkdir_p( $dest );
		}

		$dir = opendir( $source );
		if ( ! $dir ) {
			return;
		}

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$src_path  = $source . '/' . $file;
			$dest_path = $dest . '/' . $file;

			if ( is_dir( $src_path ) ) {
				$this->copy_directory( $src_path, $dest_path );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
				@copy( $src_path, $dest_path );
			}
		}

		closedir( $dir );
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory to remove.
	 * @return void
	 */
	private function remove_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( $item->isDir() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
				@rmdir( $item->getRealPath() );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $item->getRealPath() );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $dir );
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

		if ( ! preg_match( '/\/uploads\/\W?\K.*/', $target, $matches ) ) {
			return false;
		}

		$dir = $homepath . '/wp-content/uploads/' . $matches[0];

		if ( is_dir( $dir ) ) {
			return true;
		}

		return @wp_mkdir_p( $dir );
	}

}
