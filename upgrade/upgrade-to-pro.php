<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Legacy file name retained for backward compatibility.
/**
 * Self-hosted "upgrade to pro" AJAX installer.
 *
 * @package complianz-gdpr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Really Simple SSL ships the same installer as rsp_upgrade_to_pro, with TLS verification disabled on the
// license activation request. A Complianz-specific class name keeps this patched copy from being skipped
// when that plugin loads its copy first.
if ( ! class_exists( 'cmplz_upgrade_to_pro' ) ) {
	// phpcs:disable PEAR.NamingConventions.ValidClassName -- Lowercase class name, matching the plugin's other classes.
	/**
	 * Allows plugins to use their own update API.
	 *
	 * @author Easy Digital Downloads
	 * @version 1.7
	 */
	class cmplz_upgrade_to_pro {
		/**
		 * Asset version for cache busting.
		 *
		 * @var int
		 */
		private $version = 1;

		/**
		 * Vendor API base URL.
		 *
		 * @var string
		 */
		private $api_url = '';

		/**
		 * License key from the request.
		 *
		 * @var string
		 */
		private $license = '';

		/**
		 * Product item ID from the request.
		 *
		 * @var string
		 */
		private $item_id = '';

		/**
		 * Target plugin file (basename).
		 *
		 * @var string
		 */
		private $slug = '';

		/**
		 * Timeout in seconds for the API reachability check.
		 *
		 * @var int
		 */
		private $health_check_timeout = 5;

		/**
		 * Human-readable plugin name.
		 *
		 * @var string
		 */
		private $plugin_name = '';

		/**
		 * Constant indicating the pro plugin is active.
		 *
		 * @var string
		 */
		private $plugin_constant = '';

		/**
		 * Ordered install steps shown in the modal.
		 *
		 * @var array
		 */
		private $steps;

		/**
		 * Option name prefix for the current plugin.
		 *
		 * @var string
		 */
		private $prefix;

		/**
		 * URL of the plugin dashboard.
		 *
		 * @var string
		 */
		private $dashboard_url;

		/**
		 * URL of the manual install instructions.
		 *
		 * @var string
		 */
		private $instructions;

		/**
		 * URL of the vendor account page.
		 *
		 * @var string
		 */
		private $account_url;

		/**
		 * Class constructor.
		 */
		public function __construct() {

			if ( isset( $_GET['license'] ) ) {
				$this->license = sanitize_title( wp_unslash( $_GET['license'] ) );
			}

			if ( isset( $_GET['item_id'] ) ) {
				$this->item_id = sanitize_title( wp_unslash( $_GET['item_id'] ) );
			}

			if ( isset( $_GET['plugin'] ) ) {
				// This copy only installs Complianz. Other plugins' upgrade links are left to their own installer.
				if ( 'cmplz_pro' !== sanitize_title( wp_unslash( $_GET['plugin'] ) ) ) {
					return;
				}

				$this->slug            = is_multisite() ? 'complianz-gdpr-premium-multisite/complianz-gpdr-premium.php' : 'complianz-gdpr-premium/complianz-gpdr-premium.php';
				$this->plugin_name     = 'Complianz';
				$this->plugin_constant = 'cmplz_premium';
				$this->prefix          = 'cmplz_';
				$this->api_url         = 'https://complianz.io';
				$this->dashboard_url   = add_query_arg( array( 'page' => 'complianz' ), admin_url( 'admin.php' ) );
				$this->account_url     = cmplz_get_referral_url( 'articles', 'upgrade-account', 'https://complianz.io/account' );
				$this->instructions    = cmplz_get_referral_url( 'articles', 'upgrade-instructions', 'https://complianz.io/help/wordpress/configuration/how-to-install-complianz-gdpr-premium-plugin/' );
			}

			$this->steps = array(
				array(
					'action'  => 'cmplz_upgrade_destination_clear',
					'doing'   => __( 'Checking if plugin folder exists...', 'complianz-gdpr' ),
					'success' => __( 'Able to create destination folder', 'complianz-gdpr' ),
					'error'   => __( 'Destination folder already exists', 'complianz-gdpr' ),
					'type'    => 'folder',
				),
				array(
					'action'  => 'cmplz_upgrade_activate_license',
					'doing'   => __( 'Validating license...', 'complianz-gdpr' ),
					'success' => __( 'License valid', 'complianz-gdpr' ),
					'error'   => __( 'License invalid', 'complianz-gdpr' ),
					'type'    => 'license',
				),
				array(
					'action'  => 'cmplz_upgrade_package_information',
					'doing'   => __( 'Retrieving package information...', 'complianz-gdpr' ),
					'success' => __( 'Package information retrieved', 'complianz-gdpr' ),
					'error'   => __( 'Failed to gather package information', 'complianz-gdpr' ),
					'type'    => 'package',
				),
				array(
					'action'  => 'cmplz_upgrade_install_plugin',
					'doing'   => __( 'Installing plugin...', 'complianz-gdpr' ),
					'success' => __( 'Plugin installed', 'complianz-gdpr' ),
					'error'   => __( 'Failed to install plugin', 'complianz-gdpr' ),
					'type'    => 'install',
				),
				array(
					'action'  => 'cmplz_upgrade_activate_plugin',
					'doing'   => __( 'Activating plugin...', 'complianz-gdpr' ),
					'success' => __( 'Plugin activated', 'complianz-gdpr' ),
					'error'   => __( 'Failed to activate plugin', 'complianz-gdpr' ),
					'type'    => 'activate',
				),
			);

			// Set up hooks.
			$this->init();
		}

		/**
		 * Get an attribute of the cross-promoted plugin suggestion.
		 *
		 * @param string $attr Suggestion array key to return (e.g. 'title', 'install_url').
		 *
		 * @return string
		 */
		private function get_suggested_plugin( $attr ) {
			$current_plugin         = false;
			$plugin_to_be_installed = false;
			if ( isset( $_GET['plugin'] ) && 'cmplz_pro' === $_GET['plugin'] ) {
				$plugin_to_be_installed = 'complianz-gdpr';
			} elseif ( isset( $_GET['plugin'] ) && 'rsssl_pro' === $_GET['plugin'] ) {
				$plugin_to_be_installed = 'really-simple-ssl';
			}

			$path = __FILE__;
			if ( strpos( $path, 'really-simple-ssl' ) !== false ) {
				$current_plugin = 'really-simple-ssl';
			} elseif ( strpos( $path, 'complianz' ) !== false ) {
				$current_plugin = 'complianz-gdpr';
			}
			$dir_url = plugin_dir_url( __FILE__ ) . 'img/';

			$fallback_suggestion = array(
				'icon_url'          => $dir_url . 'really-simple-ssl.png',
				'constant'          => 'rsssl_version',
				'title'             => 'Really Simple Security',
				'description_short' => __( 'SSL & Security', 'complianz-gdpr' ),
				'disabled'          => '',
				'button_text'       => __( 'Install', 'complianz-gdpr' ),
				'slug'              => 'really-simple-ssl',
				'description'       => __( 'Really Simple Security - Lightweight plugin, heavyweight features.', 'complianz-gdpr' ),
				'install_url'       => 'ssl%20really%20simple%20plugins%20complianz+HSTS&tab=search&type=term',
			);

			$suggestion = $fallback_suggestion;

			if ( 'really-simple-ssl' === $plugin_to_be_installed ) {
				$suggestion = array(
					'icon_url'          => $dir_url . 'complianz-gdpr.png',
					'constant'          => 'CMPLZ_VERSION',
					'title'             => 'Complianz GDPR/CCPA',
					'description_short' => 'GDPR/CCPA Privacy Suite',
					'disabled'          => '',
					'button_text'       => __( 'Install', 'complianz-gdpr' ),
					'slug'              => 'complianz-gdpr',
					'description'       => __( 'Configure your Cookie Notice, Cookie Consent and Cookie Policy with our Wizard and Site Scan. Supports GDPR, DSGVO, TTDSG, LGPD, POPIA, RGPD, CCPA and PIPEDA.', 'complianz-gdpr' ),
					'install_url'       => 'complianz+gdpr+POPIA&tab=search&type=term',
				);
				if ( 'complianz-gdpr' === $current_plugin ) {
					$suggestion = $fallback_suggestion;
				}
			}

			if ( 'complianz-gdpr' === $plugin_to_be_installed ) {
				$suggestion = array(
					'icon_url'          => $dir_url . 'really-simple-ssl.png',
					'constant'          => 'rsssl_version',
					'title'             => 'Really Simple Security',
					'description_short' => __( 'SSL & Security', 'complianz-gdpr' ),
					'disabled'          => '',
					'button_text'       => __( 'Install', 'complianz-gdpr' ),
					'slug'              => 'really-simple-ssl',
					'description'       => __( 'Really Simple Security - Lightweight plugin, heavyweight features.', 'complianz-gdpr' ),
					'install_url'       => 'ssl%20really%20simple%20plugins%20complianz+HSTS&tab=search&type=term',
				);
				if ( 'really-simple-ssl' === $current_plugin ) {
					$suggestion = $fallback_suggestion;
				}
			}

			$admin_url                 = is_multisite() ? network_admin_url( 'plugin-install.php?s=' ) : admin_url( 'plugin-install.php?s=' );
			$suggestion['install_url'] = $admin_url . $suggestion['install_url'];
			if ( defined( $suggestion['constant'] ) ) {
				$suggestion['install_url'] = '#';
				$suggestion['button_text'] = __( 'Installed', 'complianz-gdpr' );
				$suggestion['disabled']    = 'disabled';
			}

			return $suggestion[ $attr ];
		}

		/**
		 * Set up WordPress filters to hook into WP's update process.
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'remove_shared_installer_hooks' ) );
			add_action( 'admin_footer', array( $this, 'print_install_modal' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_cmplz_upgrade_destination_clear', array( $this, 'process_ajax_destination_clear' ) );
			add_action( 'wp_ajax_cmplz_upgrade_activate_license', array( $this, 'process_ajax_activate_license' ) );
			add_action( 'wp_ajax_cmplz_upgrade_package_information', array( $this, 'process_ajax_package_information' ) );
			add_action( 'wp_ajax_cmplz_upgrade_install_plugin', array( $this, 'process_ajax_install_plugin' ) );
			add_action( 'wp_ajax_cmplz_upgrade_activate_plugin', array( $this, 'process_ajax_activate_plugin' ) );
		}

		/**
		 * Stop another plugin's copy of the shared installer from handling this Complianz upgrade.
		 *
		 * Really Simple SSL ships the same installer as rsp_upgrade_to_pro. Its copy also handles
		 * plugin=cmplz_pro and sends the license key with TLS verification disabled. Both copies load on
		 * install_pro requests, so remove its hooks and let this copy run the upgrade on its own. admin_init
		 * runs after every plugin has loaded, and before the page assets, the modal and the AJAX handlers.
		 *
		 * @return void
		 */
		public function remove_shared_installer_hooks() {
			global $wp_filter;

			$hooks = array(
				'admin_footer',
				'admin_enqueue_scripts',
				'wp_ajax_rsp_upgrade_destination_clear',
				'wp_ajax_rsp_upgrade_activate_license',
				'wp_ajax_rsp_upgrade_package_information',
				'wp_ajax_rsp_upgrade_install_plugin',
				'wp_ajax_rsp_upgrade_activate_plugin',
			);
			foreach ( $hooks as $hook ) {
				if ( ! isset( $wp_filter[ $hook ] ) ) {
					continue;
				}
				foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
					foreach ( $callbacks as $callback ) {
						if ( is_array( $callback['function'] ) && is_a( $callback['function'][0], 'rsp_upgrade_to_pro' ) ) {
							remove_action( $hook, $callback['function'], $priority );
						}
					}
				}
			}
		}

		/**
		 * Enqueue javascript
		 *
		 * @param string $hook Current admin page hook suffix.
		 *
		 * @todo minification
		 */
		public function enqueue_assets( $hook ) {
			if ( 'plugins.php' === $hook && isset( $_GET['install_pro'] ) ) {
				$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
				wp_register_style( 'cmplz-upgrade-css', plugin_dir_url( __FILE__ ) . "upgrade-to-pro$minified.css", false, $this->version );
				wp_enqueue_style( 'cmplz-upgrade-css' );
				wp_enqueue_script( 'cmplz-upgrade-ajax-js', plugin_dir_url( __FILE__ ) . "ajax$minified.js", array(), $this->version, true );
				wp_enqueue_script( 'cmplz-upgrade-js', plugin_dir_url( __FILE__ ) . "upgrade-to-pro$minified.js", array(), $this->version, true );
				wp_localize_script(
					'cmplz-upgrade-js',
					'rsp_upgrade',
					array(
						'steps'          => $this->steps,
						'admin_url'      => admin_url( 'admin-ajax.php' ),
						'token'          => wp_create_nonce( 'upgrade_to_pro_nonce' ),
						'cmplz_nonce'    => wp_create_nonce( 'complianz_save' ),
						'finished_title' => __( 'Installation finished', 'complianz-gdpr' ),
					)
				);
			}
		}

		/**
		 * Calls the API and, if successful, returns the object delivered by the API.
		 *
		 * @uses get_bloginfo()
		 * @uses wp_remote_post()
		 * @uses is_wp_error()
		 *
		 * @return false|object
		 */
		private function api_request() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}
			global $edd_plugin_url_available;

			// Do a quick status check on this domain if we haven't already checked it.
			$store_hash = md5( $this->api_url );
			if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
				$test_url_parts                          = wp_parse_url( $this->api_url );
				$port                                    = ! empty( $test_url_parts['port'] ) ? ':' . $test_url_parts['port'] : '';
				$host                                    = ! empty( $test_url_parts['host'] ) ? $test_url_parts['host'] : '';
				$test_url                                = 'https://' . $host . $port;
				$response                                = wp_remote_get(
					$test_url,
					array(
						'timeout'   => $this->health_check_timeout,
						'sslverify' => true,
					)
				);
				$edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
			}

			if ( false === $edd_plugin_url_available[ $store_hash ] ) {
				return false;
			}

			if ( trailingslashit( home_url() ) === $this->api_url ) {
				return false; // Don't allow a plugin to ping itself.
			}

			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => ! empty( $this->license ) ? $this->license : '',
				'item_id'    => isset( $this->item_id ) ? $this->item_id : false,
				'url'        => home_url(),
			);
			$request    = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);
			if ( ! is_wp_error( $request ) ) {
				$request = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( $request && isset( $request->sections ) ) {
				$request->sections = maybe_unserialize( $request->sections );
			} else {
				$request = false;
			}

			if ( $request && isset( $request->banners ) ) {
				$request->banners = maybe_unserialize( $request->banners );
			}

			if ( $request && isset( $request->icons ) ) {
				$request->icons = maybe_unserialize( $request->icons );
			}

			if ( ! empty( $request->sections ) ) {
				foreach ( $request->sections as $key => $section ) {
					$request->$key = (array) $section;
				}
			}

			return $request;
		}

		/**
		 * Prints a modal with bullets for each step of the install process
		 */
		public function print_install_modal() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			if ( cmplz_admin_logged_in() && isset( $_GET['install_pro'] ) && isset( $_GET['license'] ) && isset( $_GET['item_id'] ) && isset( $_GET['plugin'] ) ) {
				$dashboard_url = $this->dashboard_url;
				$plugins_url   = admin_url( 'plugins.php' );
				?>
				<div id="rsp-step-template">
					<div class="rsp-install-step {step}">
						<div class="rsp-step-color">
							<div class="rsp-grey rsp-bullet"></div>
						</div>
						<div class="rsp-step-text">
							<span>{doing}</span>
						</div>
					</div>
				</div>
				<div id="rsp-plugin-suggestion-template">
					<div class="rsp-recommended"><?php esc_html_e( 'Recommended by Really Simple Plugins', 'complianz-gdpr' ); ?></div>
					<div class="rsp-plugin-suggestion">
						<div class="rsp-icon"><img alt="suggested plugin icon" src="<?php echo esc_url( $this->get_suggested_plugin( 'icon_url' ) ); ?>"></div>
						<div class="rsp-summary">
							<div class="rsp-title"><?php echo esc_html( $this->get_suggested_plugin( 'title' ) ); ?></div>
							<div class="rsp-description_short"><?php echo esc_html( $this->get_suggested_plugin( 'description_short' ) ); ?></div>
							<div class="rsp-rating">
							<?php
								$plugin_info = $this->get_plugin_info( $this->get_suggested_plugin( 'slug' ) );

							if ( ! is_wp_error( $plugin_info ) && ! empty( $plugin_info->rating ) ) {
								wp_star_rating(
									array(
										'rating' => $plugin_info->rating,
										'type'   => 'percent',
										'number' => $plugin_info->num_ratings,
									)
								);
							}
							?>
								</div>
						</div>
						<div class="rsp-description"><?php echo esc_html( $this->get_suggested_plugin( 'description' ) ); ?></div>
						<div class="rsp-install-button"><a class="button-secondary" <?php echo esc_attr( $this->get_suggested_plugin( 'disabled' ) ); ?> href="<?php echo esc_url( $this->get_suggested_plugin( 'install_url' ) ); ?>"><?php echo esc_html( $this->get_suggested_plugin( 'button_text' ) ); ?></a></div>
					</div>
				</div>
				<div class="rsp-modal-transparent-background">
					<div class="rsp-install-plugin-modal">
						<h3><?php echo esc_html__( 'Installing', 'complianz-gdpr' ) . ' ' . esc_html( $this->plugin_name ); ?></h3>
						<div class="rsp-progress-bar-container">
							<div class="rsp-progress rsp-grey">
								<div class="rsp-bar rsp-green" style="width:0%"></div>
							</div>
						</div>
						<div class="rsp-install-steps">

						</div>
						<div class="rsp-footer">
							<a href="<?php echo esc_url( $dashboard_url ); ?>" role="button" class="button-primary rsp-yellow rsp-hidden rsp-btn rsp-visit-dashboard">
								<?php esc_html_e( 'Visit Dashboard', 'complianz-gdpr' ); ?>
							</a>
							<a href="<?php echo esc_url( $plugins_url ); ?>" role="button" class="button-primary rsp-red rsp-hidden rsp-btn rsp-cancel">
								<?php esc_html_e( 'Cancel', 'complianz-gdpr' ); ?>
							</a>
							<div class="rsp-error-message rsp-folder rsp-package rsp-install rsp-activate rsp-hidden"><span><?php esc_html_e( 'An error occurred:', 'complianz-gdpr' ); ?></span>&nbsp;<?php echo wp_kses_post( sprintf( /* translators: %1$s: opening link tag, %2$s: closing link tag. */ __( 'Install %1$sManually%2$s.', 'complianz-gdpr' ) . '&nbsp;', '<a target="_blank" href="' . esc_url( $this->instructions ) . '">', '</a>' ) ); ?></div>
							<div class="rsp-error-message rsp-license rsp-hidden"><span><?php esc_html_e( 'An error occurred:', 'complianz-gdpr' ); ?></span>&nbsp;<?php echo wp_kses_post( sprintf( /* translators: %1$s: opening link tag, %2$s: closing link tag. */ __( 'Check your %1$slicense%2$s.', 'complianz-gdpr' ) . '&nbsp;', '<a target="_blank" href="' . esc_url( $this->account_url ) . '">', '</a>' ) ); ?></div>
						</div>
					</div>
				</div>
				<?php
			}
		}


		/**
		 * Retrieve plugin info for rating use
		 *
		 * @uses plugins_api() Get the plugin data
		 *
		 * @param  string $slug The WP.org directory repo slug of the plugin.
		 *
		 * @version 1.0
		 */
		private function get_plugin_info( $slug = '' ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$plugin_info = get_transient( 'rsp_' . $slug . '_plugin_info' );
			if ( empty( $plugin_info ) ) {
				$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $plugin_info ) ) {
					set_transient( 'rsp_' . $slug . '_plugin_info', $plugin_info, WEEK_IN_SECONDS );
				}
			}
			return $plugin_info;
		}

		/**
		 * Ajax GET request
		 *
		 * Checks if the destination folder already exists
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'plugin' (This will set $this->slug (Ex. 'really-simple-ssl-pro/really-simple-ssl-pro.php'), based on which plugin)
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_destination_clear() {
			$error    = false;
			$response = array(
				'success' => false,
			);

			if ( ! current_user_can( 'activate_plugins' ) ) {
				$error = true;
			}

			if ( ! isset( $_GET['token'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) ) {
				$error = true;
			}

			if ( ! $error ) {
				if ( defined( $this->plugin_constant ) ) {
					deactivate_plugins( $this->slug );
				}

				$file = trailingslashit( WP_CONTENT_DIR ) . 'plugins/' . $this->slug;
				if ( file_exists( $file ) ) {
					$dir     = dirname( $file );
					$new_dir = $dir . '_' . time();
					set_transient( 'cmplz_upgrade_dir', $new_dir, WEEK_IN_SECONDS );
					rename( $dir, $new_dir );
					// prevent uninstalling code by previous plugin.
					wp_delete_file( trailingslashit( $new_dir ) . 'uninstall.php' );
				}
			}

			if ( ! $error && file_exists( $file ) ) {
				$error    = true;
				$response = array(
					'success' => false,
					'message' => __( 'Could not rename folder!', 'complianz-gdpr' ),
				);
			}

			if ( ! $error && isset( $_GET['token'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) && isset( $_GET['plugin'] ) ) {
				if ( ! file_exists( WP_PLUGIN_DIR . '/' . $this->slug ) ) {
					$response = array(
						'success' => true,
					);
				}
			}

			wp_send_json( $response );
		}


		/**
		 * Ajax GET request
		 *
		 * Links the license on the website to this site
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'license'
		 * - 'item_id'
		 *
		 * (Without this link you cannot download the pro package from the website)
		 *
		 * Echoes array [license status, response message]
		 */
		public function process_ajax_activate_license() {
			$error    = false;
			$response = array(
				'success' => false,
				'message' => '',
			);

			if ( ! current_user_can( 'activate_plugins' ) ) {
				$error = true;
			}

			if ( ! $error && isset( $_GET['token'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) && isset( $_GET['license'] ) && isset( $_GET['item_id'] ) ) {
				$license  = sanitize_title( wp_unslash( $_GET['license'] ) );
				$item_id  = (int) $_GET['item_id'];
				$response = $this->validate( $license, $item_id );
				update_site_option( $this->prefix . 'auto_installed_license', $license );
			}

			wp_send_json( $response );
		}


		/**
		 * Activate the license on the websites url at EDD
		 *
		 * Stores values in database:
		 * - {$this->pro_prefix}license_activations_left
		 * - {$this->pro_prefix}license_expires
		 * - {$this->pro_prefix}license_activation_limit
		 *
		 * @param string $license License key.
		 * @param int    $item_id Product item ID.
		 *
		 * @return array [license status, response message]
		 */
		private function validate( $license, $item_id ): array {
			$message = '';
			$success = false;

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return array(
					'success' => $success,
					'message' => $message,
				);
			}

			// data to send in our API request.
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => $item_id,
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// make sure the response came back okay.
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.', 'complianz-gdpr' );
				}
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( false === $license_data->success ) {
					switch ( $license_data->error ) {
						case 'expired':
							$message = sprintf(
								// translators: %s: license expiry date.
								__( 'Your license key expired on %s.', 'complianz-gdpr' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							);
							break;
						case 'disabled':
						case 'revoked':
							$message = __( 'Your license key has been disabled.', 'complianz-gdpr' );
							break;
						case 'missing':
							$message = __( 'Missing license.', 'complianz-gdpr' );
							break;
						case 'invalid':
							$message = __( 'Invalid license.', 'complianz-gdpr' );
							break;
						case 'site_inactive':
							$message = __( 'Your license is not active for this URL.', 'complianz-gdpr' );
							break;
						case 'item_name_mismatch':
							$message = __( 'This appears to be an invalid license key for this plugin.', 'complianz-gdpr' );
							break;
						case 'no_activations_left':
							$message = __( 'Your license key has reached its activation limit.', 'complianz-gdpr' );
							break;
						default:
							$message = __( 'An error occurred, please try again.', 'complianz-gdpr' );
							break;
					}
					// in case of failure, rename back to default.
					$new_dir = get_transient( 'cmplz_upgrade_dir' );
					if ( $new_dir ) {
						if ( file_exists( $new_dir ) ) {
							$default_file = trailingslashit( WP_CONTENT_DIR ) . 'plugins/' . $this->slug;
							$default_dir  = dirname( $default_file );
							rename( $new_dir, $default_dir );
						}
					}
				} else {
					$success = 'valid' === $license_data->license;
				}
			}

			return array(
				'success' => $success,
				'message' => $message,
			);
		}


		/**
		 * Ajax GET request
		 *
		 * Do an API request to get the download link where to download the pro package
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'license'
		 * - 'item_id'
		 *
		 * Echoes array [success, download_link]
		 */
		public function process_ajax_package_information() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			if ( isset( $_GET['token'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) && isset( $_GET['license'] ) && isset( $_GET['item_id'] ) ) {
				$api = $this->api_request();
				if ( $api && isset( $api->download_link ) ) {
					$response = array(
						'success'       => true,
						'download_link' => $api->download_link,
					);
				} else {
					$response = array(
						'success'       => false,
						'download_link' => '',
					);
				}
				wp_send_json( $response );

			}
		}


		/**
		 * Ajax GET request
		 *
		 * Download and install the plugin. The download URL is resolved
		 * server-side from the vendor API (api_request()->download_link),
		 * never taken from the request, so a caller cannot point the
		 * installer at an arbitrary package.
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_install_plugin() {
			$message = '';

			if ( ! current_user_can( 'install_plugins' ) ) {
				return array(
					'success' => false,
					'message' => $message,
				);
			}

			if ( isset( $_GET['token'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) ) {

				$api = $this->api_request();
				if ( ! $api || empty( $api->download_link ) ) {
					$response = array(
						'success' => false,
						'message' => __( 'Failed to gather package information', 'complianz-gdpr' ),
					);
					wp_send_json( $response );
				}

				$download_link = esc_url_raw( $api->download_link );
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

				$skin     = new WP_Ajax_Upgrader_Skin();
				$upgrader = new Plugin_Upgrader( $skin );
				$result   = $upgrader->install( $download_link );

				if ( $result ) {
					$response = array(
						'success' => true,
					);
				} else {
					if ( is_wp_error( $result ) ) {
						$message = $result->get_error_message();
					}
					$response = array(
						'success' => false,
						'message' => $message,
					);
				}

				wp_send_json( $response );
			}
		}


		/**
		 * Ajax GET request
		 *
		 * Do an API request to get the download link where to download the pro package
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'plugin' (This will set $this->slug (Ex. 'really-simple-ssl-pro/really-simple-ssl-pro.php'), based on which plugin)
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_activate_plugin() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ( isset( $_GET['token'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'upgrade_to_pro_nonce' ) && isset( $_GET['plugin'] ) ) {
				$networkwide = is_multisite();
				$result      = activate_plugin( $this->slug, '', $networkwide );
				if ( ! is_wp_error( $result ) ) {
					$response = array(
						'success' => true,
					);
				} else {
					$response = array(
						'success' => false,
					);
				}
				wp_send_json( $response );
			}
		}
	}
	// phpcs:enable PEAR.NamingConventions.ValidClassName

	$cmplz_upgrade_to_pro = new cmplz_upgrade_to_pro();
}
