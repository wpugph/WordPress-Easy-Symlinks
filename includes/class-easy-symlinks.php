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
				if ( isset( $_GET['tab'] ) ) {
					$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					if ( ( 'true' === $updated ) && ( ( 'add' === $tab ) || ( null === $tab ) ) ) {
						$links->save_symlinks();
					}
				} else {
					if ( ( 'true' === $updated ) ) {
						$links->save_symlinks();
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
						$links->delete_symlink();
					}
				}
			}
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
		wp_register_script( $this->token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->token . '-admin' );
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
		global $pagenow;
		if ( 'options-general.php' === $pagenow ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		}
	}

	/**
	 * Constructor function.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.3' ) {
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
	public static function instance( $file = '', $version = '1.0.3' ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $file, $version );
		}

		return self::$instance;
	} // End instance ()

}
