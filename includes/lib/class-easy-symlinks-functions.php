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
		update_option( 'caes_symlink_list', maybe_serialize( $original ) );

		if ( ! is_link( $full_path ) ) {
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
		$original_list = '';
		$original_list = maybe_unserialize( get_option( 'caes_symlink_list', false ) );
		$source        = maybe_unserialize( get_option( 'caes_target' ) );
		$destination   = maybe_unserialize( get_option( 'caes_link' ) );
		$target        = $source; // This should be existing.
		$link          = $homepath . $destination; // This is the one created.

		if ( is_link( $link ) || file_exists( $link ) ) {
			return false;
		}

		$link_parent = dirname( $link );
		if ( ! is_dir( $link_parent ) ) {
			wp_mkdir_p( $link_parent );
		}

		$this->create_folder( $target );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.symlink_symlink
		$created = @symlink( $target, $link );
		if ( ! $created ) {
			return false;
		}

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
						'target' => './wp-content/uploads/wflogs',
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
		$original_list = maybe_unserialize( get_option( 'caes_symlink_list', false ) );
		if ( ! $original_list ) {
			$original_list = array();
		}

		foreach ( $presets[ $preset_key ]['links'] as $pair ) {
			$target = $pair['target'];
			$link   = $homepath . $pair['link'];

			if ( is_link( $link ) ) {
				++$result['skipped'];
				continue;
			}

			$converted = false;
			if ( file_exists( $link ) ) {
				$converted = $this->convert_to_symlink( $link, $target, $homepath );
				if ( ! $converted ) {
					++$result['failed'];
					continue;
				}
			}

			if ( ! $converted ) {
				$link_parent = dirname( $link );
				if ( ! is_dir( $link_parent ) ) {
					wp_mkdir_p( $link_parent );
				}

				$this->create_folder( $target );

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

			$value = $pair['link'] . ' -> ' . $pair['target'];
			array_push( $original_list, $value );
		}

		update_option( 'caes_symlink_list', maybe_serialize( $original_list ) );

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
		$original_list = maybe_unserialize( get_option( 'caes_symlink_list', false ) );
		if ( ! $original_list ) {
			$original_list = array();
		}

		foreach ( $presets[ $preset_key ]['links'] as $pair ) {
			$link = $homepath . $pair['link'];

			if ( ! is_link( $link ) ) {
				++$result['skipped'];
				continue;
			}

			// Resolve the target absolute path to restore files from.
			$target_abs = $this->resolve_target_path( $pair['target'], $homepath );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			$deleted = @unlink( $link );

			if ( $deleted ) {
				// Restore original files from symlink target back to the link location.
				if ( $target_abs && ( file_exists( $target_abs ) || is_dir( $target_abs ) ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
					@rename( $target_abs, $link );
				}

				$value = $pair['link'] . ' -> ' . $pair['target'];
				$key   = array_search( $value, $original_list, true );
				if ( false !== $key ) {
					unset( $original_list[ $key ] );
				}
				++$result['removed'];
			} else {
				++$result['skipped'];
			}
		}

		update_option( 'caes_symlink_list', maybe_serialize( array_values( $original_list ) ) );

		return $result;
	}

	/**
	 * Convert an existing file/directory to a symlink by moving contents to target.
	 *
	 * @param string $link     Full path to the existing file/directory.
	 * @param string $target   Relative target path for the symlink.
	 * @param string $homepath WordPress home path.
	 * @return boolean True if conversion succeeded.
	 */
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
		$target_abs = $this->resolve_target_path( $target, $homepath );

		$target_parent = dirname( $target_abs );
		if ( ! is_dir( $target_parent ) ) {
			wp_mkdir_p( $target_parent );
		}

		if ( is_dir( $link ) ) {
			if ( ! is_dir( $target_abs ) ) {
				// Try rename first, fall back to copy+delete (cross-filesystem).
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				$moved = @rename( $link, $target_abs );
				if ( ! $moved ) {
					$this->copy_directory( $link, $target_abs );
					$this->remove_directory( $link );
					$moved = is_dir( $target_abs ) && ! is_dir( $link );
				}
			} else {
				$this->copy_directory( $link, $target_abs );
				$this->remove_directory( $link );
				$moved = ! is_dir( $link );
			}
		} else {
			if ( ! file_exists( $target_abs ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				$moved = @rename( $link, $target_abs );
				if ( ! $moved ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
					@copy( $link, $target_abs );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					@unlink( $link );
					$moved = file_exists( $target_abs ) && ! file_exists( $link );
				}
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $link );
				$moved = true;
			}
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
			wp_mkdir_p( $dest );
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
			}
		}

		update_option( 'caes_symlink_list', maybe_serialize( $original_list ) );

		return $result;
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

		return wp_mkdir_p( $dir );
	}

}
