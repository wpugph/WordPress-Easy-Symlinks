<?php
/**
 * Main plugin class file.
 *
 * @package Easy Symlinks WP/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Easy_Symlinks {

	/**
	 * The single instance of Easy_Symlinks.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $instance = null;

	/**
	 * Local instance of Easy_Symlinks_Admin_API
	 *
	 * @var Easy_Symlinks_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Check write wrapper.
	 *
	 * @return boolean
	 */
	public function check_write() {
		$checkwrite = new Easy_Symlinks_Functions();
		return $checkwrite->check_fs_writable();
	}

	/**
	 * Add a Pantheon commit/deploy reminder notice if on Pantheon.
	 *
	 * @return void
	 */
	private function add_pantheon_reminder() {
		if ( ! isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			return;
		}
		add_settings_error(
			'SymlinkError',
			'pantheon_reminder',
			'<strong>&#9888; ' . esc_html__( 'Action required:', 'easy-symlinks' ) . '</strong> ' . esc_html__( 'Commit these changes in the Pantheon dashboard and deploy to Test and Live environments.', 'easy-symlinks' ),
			'warning'
		);
	}

	/**
	 * Save new symlinks.
	 *
	 * @return void
	 */
	public function savenew() {
		$links = new Easy_Symlinks_Functions();
		$nonce = sanitize_text_field( wp_create_nonce( 'savenew' ) );

		if ( isset( $_GET['settings-updated'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce, 'savenew' ) ) ) ) {
				$updated = sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );
				$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

				if ( 'true' === $updated && ( 'add' === $tab || '' === $tab ) ) {
					// Remove WP default "Settings saved." notice.
					global $wp_settings_errors;
					$wp_settings_errors = array();
					delete_transient( 'settings_errors' );

					$result = $links->save_symlinks();
					if ( false === $result ) {
						add_settings_error( 'SymlinkError', 'symlink_exists', __( 'Symlink already exists at that path. Remove it first before creating a new one.', 'easy-symlinks' ), 'error' );
					} else {
						add_settings_error( 'SymlinkError', 'symlink_created', __( 'Symlink created successfully.', 'easy-symlinks' ), 'updated' );
						$this->add_pantheon_reminder();
					}
				}
			}
		}
	}

	/**
	 * Check if tab is allowed to delete link.
	 *
	 * @return void
	 */
	public function deletelink() {
		$links = new Easy_Symlinks_Functions();
		$nonce = sanitize_text_field( wp_create_nonce( 'deletelink' ) );

		if ( isset( $_GET['settings-updated'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce, 'deletelink' ) ) ) ) {
				if ( isset( $_GET['tab'] ) ) {
					$updated = sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );
					$tab     = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					if ( ( 'true' === $updated ) && ( 'delete' === $tab ) ) {
						global $wp_settings_errors;
						$wp_settings_errors = array();
						delete_transient( 'settings_errors' );

						$result = $links->delete_symlink();
						if ( $result ) {
							add_settings_error( 'SymlinkError', 'symlink_deleted', __( 'Symlink deleted successfully.', 'easy-symlinks' ), 'updated' );
							$this->add_pantheon_reminder();
						} else {
							add_settings_error( 'SymlinkError', 'symlink_delete_failed', __( 'Failed to delete symlink.', 'easy-symlinks' ), 'error' );
						}
					}
				}
			}
		}
	}

	/**
	 * Apply preset symlinks.
	 *
	 * @return void
	 */
	public function apply_presets() {
		if ( ! isset( $_POST['caes_action'] ) || 'apply_presets' !== $_POST['caes_action'] ) {
			return;
		}

		if ( ! isset( $_POST['caes_presets_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['caes_presets_nonce'] ) ), 'caes_apply_presets' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$links   = new Easy_Symlinks_Functions();
		$selected = isset( $_POST['caes_presets'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['caes_presets'] ) ) : array();

		if ( empty( $selected ) ) {
			add_settings_error( 'SymlinkError', 'no_presets', __( 'No presets selected. Please check at least one plugin preset to apply.', 'easy-symlinks' ), 'error' );
			return;
		}

		$total_created   = 0;
		$total_converted = 0;
		$total_skipped   = 0;
		$total_failed    = 0;

		foreach ( $selected as $preset_key ) {
			$result           = $links->apply_preset( $preset_key );
			$total_created   += $result['created'];
			$total_converted += $result['converted'];
			$total_skipped   += $result['skipped'];
			$total_failed    += $result['failed'];
		}

		$messages = array();
		if ( $total_created > 0 ) {
			/* translators: %d: number of symlinks created */
			$messages[] = sprintf( _n( '%d symlink created', '%d symlinks created', $total_created, 'easy-symlinks' ), $total_created );
		}
		if ( $total_converted > 0 ) {
			/* translators: %d: number of files converted to symlinks */
			$messages[] = sprintf( _n( '%d converted (moved existing files to target)', '%d converted (moved existing files to target)', $total_converted, 'easy-symlinks' ), $total_converted );
		}
		if ( $total_skipped > 0 ) {
			/* translators: %d: number of symlinks skipped */
			$messages[] = sprintf( _n( '%d skipped (already a symlink)', '%d skipped (already symlinks)', $total_skipped, 'easy-symlinks' ), $total_skipped );
		}
		if ( $total_failed > 0 ) {
			/* translators: %d: number of symlinks that failed */
			$messages[] = sprintf( _n( '%d failed', '%d failed', $total_failed, 'easy-symlinks' ), $total_failed );
		}

		$type = $total_failed > 0 ? 'error' : 'updated';
		add_settings_error( 'SymlinkError', 'presets_applied', implode( '. ', $messages ) . '.', $type );

		if ( ( $total_created > 0 || $total_converted > 0 ) && 0 === $total_failed ) {
			$this->add_pantheon_reminder();
		}
	}

	/**
	 * Remove preset symlinks.
	 *
	 * @return void
	 */
	public function remove_presets() {
		if ( ! isset( $_POST['caes_action'] ) || 'remove_presets' !== $_POST['caes_action'] ) {
			return;
		}

		if ( ! isset( $_POST['caes_presets_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['caes_presets_nonce'] ) ), 'caes_remove_presets' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$links    = new Easy_Symlinks_Functions();
		$selected = isset( $_POST['caes_presets'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['caes_presets'] ) ) : array();

		if ( empty( $selected ) ) {
			add_settings_error( 'SymlinkError', 'no_presets', __( 'No presets selected. Please check at least one plugin preset to remove.', 'easy-symlinks' ), 'error' );
			return;
		}

		$total_removed = 0;
		$total_skipped = 0;

		foreach ( $selected as $preset_key ) {
			$result         = $links->remove_preset( $preset_key );
			$total_removed += $result['removed'];
			$total_skipped += $result['skipped'];
		}

		$messages = array();
		if ( $total_removed > 0 ) {
			/* translators: %d: number of symlinks removed */
			$messages[] = sprintf( _n( '%d symlink removed', '%d symlinks removed', $total_removed, 'easy-symlinks' ), $total_removed );
		}
		if ( $total_skipped > 0 ) {
			/* translators: %d: number of symlinks skipped */
			$messages[] = sprintf( _n( '%d skipped (not a symlink)', '%d skipped (not symlinks)', $total_skipped, 'easy-symlinks' ), $total_skipped );
		}

		if ( empty( $messages ) ) {
			$messages[] = __( 'No symlinks to remove.', 'easy-symlinks' );
		}

		add_settings_error( 'SymlinkError', 'presets_removed', implode( '. ', $messages ) . '.', 'updated' );

		if ( $total_removed > 0 ) {
			$this->add_pantheon_reminder();
		}
	}

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		if ( 'settings_page_' . $this->token . '_settings' !== $hook ) {
			return;
		}

		wp_register_script( $this->token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->token . '-admin' );

		wp_register_style( $this->token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->version );
		wp_enqueue_style( $this->token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'easy-symlinks', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'easy-symlinks';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Easy_Symlinks is forbidden' ) ), esc_attr( $this->version ) );

	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Easy_Symlinks is forbidden' ) ), esc_attr( $this->version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function log_version_number() {
		update_option( $this->token . 'version', $this->version );
	} // End log_version_number ()

	/**
	 * Load scripts only on designated page.
	 *
	 * @return void
	 */
	public function wp_admin_scripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
	}

	/**
	 * Constructor function.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '2.0.0' ) {
		$this->version = $version;
		$this->token   = 'easy_symlinks';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		$this->wp_admin_scripts();

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Easy_Symlinks_Admin_API();
		}
		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		add_action( 'admin_init', array( $this, 'savenew' ), 99999999 );
		add_action( 'admin_init', array( $this, 'deletelink' ), 99999999 );
		add_action( 'admin_init', array( $this, 'apply_presets' ), 99999999 );
		add_action( 'admin_init', array( $this, 'remove_presets' ), 99999999 );

	} // End __construct ()

	/**
	 * Main Easy_Symlinks Instance
	 *
	 * Ensures only one instance of Easy_Symlinks is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object Easy_Symlinks instance
	 * @see Easy_Symlinks()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $file = '', $version = '2.0.0' ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $file, $version );
		}

		return self::$instance;
	} // End instance ()

}
