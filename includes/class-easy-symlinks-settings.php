<?php
/**
 * Settings class file.
 *
 * @package Easy Symlinks WP/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Easy_Symlinks_Settings {

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
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Load sanitation.
	 *
	 * @return string
	 */
	public function sanitize_thishtml() {
		$sanitisation = new Easy_Symlinks_Admin_API();
		return $sanitisation;
	}

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'caes_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'options', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Easy Symlinks', 'easy-symlinks' ),
				'menu_title'  => __( 'Easy Symlinks', 'easy-symlinks' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => '',
				'position'    => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->token . '_settings&tab=add">' . __( 'Settings', 'easy-symlinks' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$links          = new Easy_Symlinks_Functions();
		$symlinks       = $links->display_symlinks();
		$symlinks_array = $links->get_symlinks();

		if ( ( null === $symlinks_array ) || ( empty( $symlinks_array ) ) ) {

			$symlinks_array = array( '' => 'No symlinks saved yet, nothing to delete' );
			$desc           = 'No symlinks yet, please add one before you can delete.';
		} else {
			$desc = 'Select the symlink pair that you want to delete;';
		}

		$settings['presets'] = array(
			'title'       => __( 'Presets', 'easy-symlinks' ),
			'description' => __( 'Auto-detected plugins that need symlinks for Pantheon compatibility.', 'easy-symlinks' ),
			'fields'      => array(),
		);

		$settings['remove_presets'] = array(
			'title'       => __( 'Remove Presets', 'easy-symlinks' ),
			'description' => __( 'Remove preset symlinks that were previously applied.', 'easy-symlinks' ),
			'fields'      => array(),
		);

		$settings['add'] = array(
			'title'       => __( 'Add Symlinks', 'easy-symlinks' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'target',
					'label'       => __( 'Target', 'easy-symlinks' ),
					'description' => __( 'This should be existing, non-version controlled and in a writable path by your host like the wp-content/uploads. This should be a relative path to where your link is created. <br> ./uploads/cache if link is from /wp-content/cache <br> ./wp-content/uploads/rootfolder if link is from /rootfolder ', 'easy-symlinks' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'eg: ./uploads/cache', 'easy-symlinks' ),
					'callback'    => array( $this, 'validate_target' ),
				),
				array(
					'id'          => 'link',
					'label'       => __( 'Link', 'easy-symlinks' ),
					'description' => __( 'This should be non-existing as this one will be created. If the folder is existing, contents should be moved to the target first before symlinking.', 'easy-symlinks' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'eg: /wp-content/cache', 'easy-symlinks' ),
					'callback'    => array( $this, 'validate_link' ),
				),
			),
		);

		$settings['delete'] = array(
			'title'       => __( 'Delete Symlinks', 'easy-symlinks' ),
			'description' => $desc,
			'fields'      => array(
				array(
					'id'          => 'symlink_list_lastdelete',
					'label'       => __( 'Active symlinks', 'easy-symlinks' ),
					'description' => '',
					'type'        => 'select_multi',
					'options'     => $symlinks_array,
				),
			),
		);

		$settings['settings'] = array(
			'title'       => __( 'Settings', 'easy-symlinks' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'delete_data_on_uninstall',
					'label'       => __( 'Delete data on uninstall', 'easy-symlinks' ),
					'description' => __( 'Remove all plugin data from the database when the plugin is deleted.', 'easy-symlinks' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
			),
		);

		$settings = apply_filters( $this->parent->token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			// Check posted/selected tab.
			$current_section = '';

			$nonce = sanitize_text_field( wp_create_nonce( 'caes_nonce' ) );

			if ( isset( $_POST['tab'] ) ) {
				if ( wp_verify_nonce( $nonce, 'caes_nonce' ) ) {
					$current_section = sanitize_text_field( wp_unslash( $_POST['tab'] ) );
				}
			} else {
				if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
					$current_section = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html         = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		$sanitisation = new Easy_Symlinks_Admin_API();
		echo wp_kses( $html, $sanitisation->allowed_htmls );
	}

	/**
	 * Render the environment info banner.
	 *
	 * @param array $env Environment data from detect_environment().
	 * @return string HTML for the banner.
	 */
	private function render_environment_banner( $env ) {
		$is_pantheon = ( 'pantheon' === $env['type'] );
		$readonly    = $is_pantheon && in_array( $env['environment'], array( 'test', 'live' ), true );

		if ( $readonly ) {
			$notice_class = 'notice-error';
			$status_label = 'Read-only';
			$dashicon     = 'dashicons-lock';
		} elseif ( $env['writable'] ) {
			$notice_class = 'notice-success';
			$status_label = 'Writable';
			$dashicon     = 'dashicons-unlock';
		} else {
			$notice_class = 'notice-warning';
			$status_label = 'Not writable';
			$dashicon     = 'dashicons-warning';
		}

		$html  = '<div class="notice ' . $notice_class . '" style="padding:12px 16px;margin:15px 0;">';
		$html .= '<p style="margin:0 0 6px;font-size:14px;font-weight:600;">';
		$html .= '<span class="dashicons ' . $dashicon . '" style="margin-right:6px;vertical-align:text-bottom;"></span>';
		$html .= 'Environment: ' . esc_html( $env['label'] );
		$html .= '</p>';

		$html .= '<table style="border-collapse:collapse;margin:0;">';

		$html .= '<tr><td style="padding:2px 12px 2px 0;font-weight:500;">Type</td>';
		$html .= '<td style="padding:2px 0;">' . esc_html( $is_pantheon ? 'Pantheon' : 'Local / Non-Pantheon' ) . '</td></tr>';

		if ( $is_pantheon ) {
			$html .= '<tr><td style="padding:2px 12px 2px 0;font-weight:500;">Environment</td>';
			$html .= '<td style="padding:2px 0;">' . esc_html( $env['environment'] ) . '</td></tr>';

			if ( ! empty( $env['site_name'] ) ) {
				$html .= '<tr><td style="padding:2px 12px 2px 0;font-weight:500;">Site Name</td>';
				$html .= '<td style="padding:2px 0;">' . esc_html( $env['site_name'] ) . '</td></tr>';
			}

			if ( ! empty( $env['connection'] ) ) {
				$html .= '<tr><td style="padding:2px 12px 2px 0;font-weight:500;">Connection Mode</td>';
				$html .= '<td style="padding:2px 0;">' . esc_html( strtoupper( $env['connection'] ) ) . '</td></tr>';
			}
		}

		$html .= '<tr><td style="padding:2px 12px 2px 0;font-weight:500;">Filesystem</td>';
		$html .= '<td style="padding:2px 0;">' . esc_html( $status_label ) . '</td></tr>';

		$html .= '</table>';

		if ( $readonly ) {
			$html .= '<p style="margin:8px 0 0;"><strong>Symlinks cannot be created in read-only environments.</strong> Switch to a Dev or Multidev environment.</p>';
		} elseif ( ! $env['writable'] ) {
			if ( $is_pantheon ) {
				$html .= '<p style="margin:8px 0 0;">Filesystem is not writable. Switch to <strong>SFTP mode</strong> in the Pantheon dashboard.</p>';
			} else {
				$html .= '<p style="margin:8px 0 0;">Filesystem is not writable. Check your file permissions.</p>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the Presets tab content.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $mode  'apply' or 'remove'.
	 * @return string HTML output.
	 */
	private function render_presets_tab( $nonce, $mode = 'apply' ) {
		$links   = new Easy_Symlinks_Functions();
		$presets = $links->get_presets();
		$html    = '';

		// Show SFTP mode warning on Pantheon if not writable.
		$env = $links->detect_environment();
		if ( 'pantheon' === $env['type'] && ! $env['writable'] ) {
			$html .= '<div class="notice notice-warning" style="padding:12px 16px;margin:0 0 16px;">';
			$html .= '<p style="margin:0;"><strong>' . esc_html__( 'Warning:', 'easy-symlinks' ) . '</strong> ';
			$html .= esc_html__( 'Filesystem is not writable. Switch to SFTP mode in the Pantheon dashboard before applying or removing presets.', 'easy-symlinks' );
			$html .= '</p></div>';
		}

		if ( empty( $presets ) ) {
			$html .= '<p>' . esc_html__( 'No plugins detected that need symlinks. Install and activate a supported plugin (e.g. Wordfence) to see presets here.', 'easy-symlinks' ) . '</p>';
			return $html;
		}

		$is_remove    = ( 'remove' === $mode );
		$nonce_action = $is_remove ? 'caes_remove_presets' : 'caes_apply_presets';
		$form_action  = $is_remove ? 'remove_presets' : 'apply_presets';
		$button_text  = $is_remove ? __( 'Remove Selected Presets', 'easy-symlinks' ) : __( 'Apply Selected Presets', 'easy-symlinks' );
		$button_class = $is_remove ? 'button-primary caes-submit-delete' : 'button-primary';

		$html .= '<form method="post" action="">' . "\n";
		$html .= wp_nonce_field( $nonce_action, 'caes_presets_nonce', true, false );
		$html .= '<input type="hidden" name="caes_action" value="' . esc_attr( $form_action ) . '" />' . "\n";

		$homepath = $links->get_wp_homepath();

		foreach ( $presets as $key => $preset ) {
			$html .= '<div style="margin-bottom:24px;padding:16px;border:1px solid #c3c4c7;background:#f6f7f7;">' . "\n";
			$html .= '<label style="display:flex;align-items:center;gap:10px;font-size:15px;font-weight:600;margin-bottom:12px;">';
			$html .= '<input type="checkbox" name="caes_presets[]" value="' . esc_attr( $key ) . '" />';
			$html .= esc_html( $preset['name'] );
			$html .= '</label>' . "\n";

			$html .= '<table style="width:100%;border-collapse:collapse;">';
			$html .= '<tr style="border-bottom:1px solid #ddd;"><th style="text-align:left;padding:6px 8px;font-size:13px;color:#50575e;">Target</th><th style="text-align:left;padding:6px 8px;font-size:13px;color:#50575e;">Link</th><th style="text-align:left;padding:6px 8px;font-size:13px;color:#50575e;">Status</th></tr>';

			foreach ( $preset['links'] as $pair ) {
				$full_link   = $homepath . $pair['link'];
				$link_status = $links->get_link_status( $full_link );
				if ( 'symlink' === $link_status ) {
					$status = '<span style="color:#00a32a;">&#10003; Symlinked</span>';
				} elseif ( 'exists' === $link_status ) {
					$status_text = $is_remove ? 'Exists (not a symlink)' : 'Exists (will be moved &amp; symlinked)';
					$status      = '<span style="color:#dba617;">' . $status_text . '</span>';
				} else {
					$status = '<span style="color:#646970;">Not created</span>';
				}

				$html .= '<tr style="border-bottom:1px solid #eee;">';
				$html .= '<td style="padding:6px 8px;font-family:monospace;font-size:13px;">' . esc_html( $pair['target'] ) . '</td>';
				$html .= '<td style="padding:6px 8px;font-family:monospace;font-size:13px;">' . esc_html( $pair['link'] ) . '</td>';
				$html .= '<td style="padding:6px 8px;font-size:13px;">' . $status . '</td>';
				$html .= '</tr>';
			}

			$html .= '</table>';
			$html .= '</div>' . "\n";
		}

		$html .= '<p class="submit"><input name="Submit" type="submit" class="' . esc_attr( $button_class ) . '" value="' . esc_attr( $button_text ) . '" /></p>' . "\n";
		$html .= '</form>' . "\n";

		return $html;
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		$links = new Easy_Symlinks_Functions();
		$env   = $links->detect_environment();

		$sanitisation = new Easy_Symlinks_Admin_API();
		$writestatus  = $links->check_if_in_pantheon_writable_env();

		// Always render the page wrapper, heading, and environment banner.
		echo '<div class="wrap" id="' . esc_html( $this->parent->token ) . '_settings">' . "\n";
		echo '<h2>' . esc_html( __( 'Easy Symlinks Management', 'easy-symlinks' ) ) . '</h2>' . "\n";
		echo wp_kses( $this->render_environment_banner( $env ), $sanitisation->allowed_htmls );
		echo '<div class="caes-wrap">' . "\n";

		if ( ! $writestatus['status'] ) {
			echo '</div>';
			return;
		}

		// Build page HTML.
		$nonce = sanitize_text_field( wp_create_nonce( 'caes_nonce' ) );
		$html  = '';
		$tab   = '';

		// Proper nonce handling.
		if ( isset( $_GET['caes_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['caes_nonce'] ) ), 'caes_nonce' ) ) {
				if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
					$tab .= sanitize_text_field( wp_unslash( $_GET['tab'] ) );
				}
			}
		} else {
			if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
				$tab .= sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			}
		}

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<div class="caes-tabs">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'caes-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					$button_label = 'Apply Selected Presets';
					if ( 0 === $c ) {
						$class .= ' caes-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section === $_GET['tab'] ) {
						$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
						if ( 'delete' === $tab ) {
							$button_label = 'Delete Symlink';
						} elseif ( 'settings' === $tab ) {
							$button_label = 'Save Settings';
						} elseif ( 'presets' === $tab ) {
							$button_label = 'Apply Selected Presets';
						} elseif ( 'remove_presets' === $tab ) {
							$button_label = 'Remove Selected Presets';
						} else {
							$button_label = 'Save Symlink';
						}
						$class .= ' caes-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg(
					array(
						'tab'        => $section,
						'caes_nonce' => $nonce,
					)
				);

				if ( isset( $_GET['settings-updated'] ) ) {
					$updated = sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );

					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</div>' . "\n";
		}

		if ( 'presets' === $tab || ( '' === $tab && ! isset( $_GET['tab'] ) ) ) {
			$html .= $this->render_presets_tab( $nonce, 'apply' );
		} elseif ( 'remove_presets' === $tab ) {
			$html .= $this->render_presets_tab( $nonce, 'remove' );
		} else {
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				ob_start();
				settings_fields( $this->parent->token . '_settings' );
				do_settings_sections( $this->parent->token . '_settings' );
				$html .= ob_get_clean();

				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="caes_nonce" id="caes_nonce" value="' . esc_html( $nonce ) . '" />';
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$submit_class = 'button-primary';
			if ( 'delete' === $tab ) {
				$submit_class .= ' caes-submit-delete';
			}
					$html .= '<input name="Submit" type="submit" class="' . esc_attr( $submit_class ) . '" value="' . esc_attr( $button_label ) . '" />' . "\n";
				$html     .= '</p>' . "\n";
			$html         .= '</form>' . "\n";
		}
		$html             .= '</div>' . "\n";
		$html             .= '</div>' . "\n";

		echo wp_kses( $html, $sanitisation->allowed_htmls );
	}

	/**
	 * Main Easy_Symlinks_Settings Instance
	 *
	 * Ensures only one instance of Easy_Symlinks_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Easy_Symlinks()
	 * @param object $parent Object instance.
	 * @return object Easy_Symlinks_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $parent );
		}
		return self::$instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Easy_Symlinks_API is forbidden.' ) ), esc_attr( $this->parent->version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Easy_Symlinks_API is forbidden.' ) ), esc_attr( $this->parent->version ) );
	} // End __wakeup()

	/**
	 * Validation code for target
	 *
	 * @param string $data Contains the data that needs to be validated.
	 * @return string
	 */
	public function validate_target( $data ) {
		if ( ! isset( $_POST['caes_nonce'] ) ) {
			return $data;
		}

		if ( '' === $data ) {
			$this->validation_msg( __( 'Target can not be empty', 'easy-symlinks' ), 'error', 'target_empty' );
			return get_option( 'caes_target' );
		}

		return $data;
	}

	/**
	 * Validate caes link
	 *
	 * @param string $data Contains the data that needs to be validated.
	 * @return string
	 */
	public function validate_link( $data ) {
		if ( ! isset( $_POST['caes_nonce'] ) ) {
			return $data;
		}

		if ( '' === $data ) {
			$this->validation_msg( __( 'Link can not be empty', 'easy-symlinks' ), 'error', 'link_empty' );
			return get_option( 'caes_link' );
		}

		return $data;
	}

	/**
	 * Validation message function
	 *
	 * @param string $message Message for the error message.
	 * @param string $type Error, updated or added.
	 * @param string $code Unique error code.
	 * @return boolean
	 */
	public function validation_msg( $message, $type, $code = 'settings_updated' ) {
		add_settings_error(
			'SymlinkError',
			esc_attr( $code ),
			$message,
			$type
		);
		return true;
	}
}
