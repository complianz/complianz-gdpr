<?php
defined( 'ABSPATH' ) or die( );

if ( ! class_exists( "cmplz_admin" ) ) {
	class cmplz_admin {
		private static $_this;
		public $error_message = "";
		public $success_message = "";

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			$plugin = CMPLZ_PLUGIN;
			add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ) );
			add_action( "in_plugin_update_message-{$plugin}", array( $this, 'plugin_update_message'), 10, 2 );
			//add_filter( "auto_update_plugin", array( $this, 'override_auto_updates'), 99, 2 );

			//multisite
			add_filter( "network_admin_plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ) );
			add_filter( 'cmplz_option_cookie_domain', array($this, 'filter_cookie_domain'), 10, 2);

			//admin notices
			add_action( 'wp_ajax_cmplz_dismiss_admin_notice', array( $this, 'dismiss_warning' ) );
			add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'insert_dismiss_admin_notice_script' ) );
			add_action( 'admin_init', array( $this, 'activation' ) );
			add_action( 'upgrader_process_complete', array( $this, 'run_table_init_hook'), 10, 1);
			add_action( 'wp_initialize_site', array( $this, 'run_table_init_hook'), 10, 1);
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * On Multisite site creation, run table init hook as well.
		 * @return void
		 */
		public function run_table_init_hook(){
			do_action( 'cmplz_install_tables' );
			//we need to run table creation across subsites as well.
			if ( is_multisite() ) {
				$sites = get_sites();
				if (count($sites)>0) {
					foreach ($sites as $site) {
						switch_to_blog($site->blog_id);
						do_action( 'cmplz_install_tables' );
						restore_current_blog();
					}
				}
			}
		}

		public function activation(){
			if ( !cmplz_admin_logged_in() ){
				return;
			}

			if ( get_option( 'cmplz_run_activation' ) ) {
				update_option('cmplz_activation_time', time(), false );
				cmplz_update_option_no_hooks( 'use_cdb_api', 'yes' );
				COMPLIANZ::$documents_admin->preload_privacy_info();
				$this->run_table_init_hook();
				delete_option( 'cmplz_run_activation' );
			}
		}

		/**
		 * Hooked into ajax call to dismiss a warning
		 * @hooked wp_ajax_cmplz_dismiss_warning
		 */

		public function dismiss_warning() {
			$error   = false;

			if ( !cmplz_user_can_manage() ) {
				$error = true;
			}

			if ( !isset($_POST['id']) ) {
				$error = true;
			}

			if ( !$error ) {
				$warning_id = sanitize_title($_POST['id']);
				$dismissed_warnings = get_option( 'cmplz_dismissed_warnings', array() );
				if ( !in_array($warning_id, $dismissed_warnings) ) {
					$dismissed_warnings[] = $warning_id;
				}
				update_option('cmplz_dismissed_warnings', $dismissed_warnings, false );
				delete_transient('complianz_warnings');
				delete_transient('complianz_warnings_admin_notices');
			}

			$out = array(
					'success' => ! $error,
			);

			die( json_encode( $out ) );
		}

		/**
		 * Sanitize the cookiedomain
		 * @param string $fieldname
		 * @param string $fieldvalue
		 *
		 * @return string|string[]
		 */

		public function filter_cookie_domain( $fieldvalue, $fieldname ){
			if ( ! cmplz_user_can_manage() ) {
				return $fieldvalue;
			}

			//sanitize the cookie domain
			return str_replace(array("https://", "http://", "www."), "", $fieldvalue);
		}

		/**
		 * check for website hardening plugins
		 *
		 * @return bool
		 */
		public function no_security_plugin_active(){
			//create switch statement
			if (defined('rsssl_version')) return false; //really simple security
			if (defined('WORDFENCE_VERSION')) return false; //wordfence
			if (class_exists('ITSEC_Core')) return false; //ithemes
			if (class_exists('AIO_WP_Security')) return false; // All in one security
			if (class_exists('SG_Security')) return false; // Siteground security
			if (defined('DEFENDER_VERSION')) return false;
			if (defined('SUCURISCAN_INIT')) return false;
			if (defined('JETPACK__VERSION')) return false;
			if (defined('BULLETPROOF_VERSION')) return false;
			if (class_exists('MCWPSettings')) return false;
			if (function_exists('GOTMLS_install')) return false; //anti malware security and brute force firewall

			return true;
		}

		/**
		 * Add a major changes notice to the plugin updates message
		 * @param $plugin_data
		 * @param $response
		 */
		public function plugin_update_message($plugin_data, $response){
//			if ( strpos($response->slug , 'complianz') !==false && $response->new_version === '7.0.0' && !cmplz_get_option("beta") ) {
//				echo '<br /><b>' . '&nbsp'.cmplz_sprintf(__("This is a major release and while tested thoroughly you might experience conflicts or lost data. We recommend you back up your data before updating and check your configuration after update.", "complianz-gdpr").'</b>','<a target="_blank" href="https://complianz.io/upgrade-to-complianz-7-0/">','</a>');
//			}

			if ( strpos($response->slug , 'complianz') !==false && strpos($response->new_version, 'beta.')!==false && cmplz_get_option("beta") ) {
				echo '<br /><b>' . '&nbsp'.__("It is highly recommended that you back up your data before updating to the Beta version. Beta versions are not intended for production environments or critical systems. They are best suited for users who are willing to explore new features and provide feedback.", "complianz-gdpr").'</b>';
			}
		}
		/**
		 * If this update is to 6, don't auto update
		 * Deactivated as of 6.0
		 *
		 * @param $update
		 * @param $item
		 *
		 * @return false|mixed
		 */
		public function override_auto_updates( $update, $item ) {
			if ( isset( $item->slug ) && strpos($item->slug , 'complianz') !==false && version_compare($item->new_version, '6.0.0', '>=') ) {
				return false;
			}
			return $update;
		}

		/**
		 * Enqueue some assets
		 *
		 * @param $hook
		 */
		public function enqueue_assets( $hook ) {
			if ( strpos( $hook, 'complianz' ) === false) {
				return;
			}
			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl = is_rtl() ? 'rtl/' : '';
			$url = trailingslashit(CMPLZ_URL) . "assets/css/{$rtl}admin{$min}.css";
			$path = trailingslashit(CMPLZ_PATH) . "assets/css/{$rtl}admin{$min}.css";
			wp_enqueue_style( 'complianz-admin', $url, ['wp-components'], filemtime($path) );
		}

		/**
		 * Add custom link to plugins overview page
		 *
		 * @hooked plugin_action_links_$plugin
		 *
		 * @param array $links
		 *
		 * @return array $links
		 */

		public function plugin_settings_link( $links ) {
			$settings_link = '<a href="'
			                 . admin_url( "admin.php?page=complianz" )
			                 . '" class="cmplz-settings-link">'
			                 . __( "Settings", 'complianz-gdpr' ) . '</a>';
			array_unshift( $links, $settings_link );

			$support_link = defined( 'cmplz_free' )
				? "https://wordpress.org/support/plugin/complianz-gdpr"
				: "https://complianz.io/support";
			$faq_link     = '<a target="_blank" href="' . $support_link . '">'
			                . __( 'Support', 'complianz-gdpr' ) . '</a>';
			array_unshift( $links, $faq_link );

			if ( ! defined( 'cmplz_premium' ) ) {
				$upgrade_link
					= '<a style="color:#2DAAE1;font-weight:bold" target="_blank" href="https://complianz.io/l/pricing">'
					  . __( 'Upgrade to premium', 'complianz-gdpr' ) . '</a>';
				array_unshift( $links, $upgrade_link );
			}

			return $links;
		}

		/**
		 * Insert some ajax script to dismiss the admin notice
		 *
		 * @since  2.0
		 *
		 * @access public
		 *
		 * type: dismiss, later
		 *
		 */

		public function insert_dismiss_admin_notice_script() {
			$ajax_nonce = wp_create_nonce( "cmplz_dismiss_admin_notice" );
			?>
			<script type='text/javascript'>
				jQuery(document).ready(function ($) {
					$(".cmplz-admin-notice.notice.is-dismissible").on("click", ".notice-dismiss, .cmplz-btn-dismiss-notice", function (event) {
						var id = $('.cmplz-admin-notice').data('admin_notice_id');
						var data = {
							'action': 'cmplz_dismiss_admin_notice',
							'id': id,
							'token': '<?php echo $ajax_nonce; ?>'
						};
						$.post(ajaxurl, data, function (response) {
							$(".cmplz-admin-notice.notice.is-dismissible").remove();
						});
					});
				});
			</script>
			<?php
		}

		/**
		 * Show an admin notice from our warnings list
		 * @return void
		 */
		public function show_admin_notice(){
			//delete_transient( 'complianz_warnings' );
			if ( cmplz_get_option( 'disable_notifications' ) ) {
				return;
			}

			$warnings = $this->get_warnings( [ 'admin_notices' => true] );
			if (count($warnings)==0) {
				return;
			}

			//only one admin notice at the same time.
			$keys = array_keys($warnings);
			$id = $keys[0];
			$warning = $warnings[$id];
			$this->admin_notice($warning, $id);
		}

		/**
		 * @param array $warning
		 */
		public function admin_notice( $warning, $id='' ) {
			if (!isset($warning['open'])) {
				return;
			}
			/**
			 * Prevent notice from being shown on Gutenberg page, as it strips off the class we need for the ajax callback.
			 *
			 * */

			$screen = get_current_screen();
			if ( $screen && $screen->parent_base === 'edit' ) {
				return;
			}
			?>
			<style>
				#message.cmplz-admin-notice {
					margin-left:10px !important;
				}
				.cmplz-admin-notice-container {
					display:flex;
				}
				.cmplz-admin-notice-logo {
					margin:20px 10px;
				}
				.cmplz-admin-notice-content {
					margin: 20px 30px;
				}
			</style>
			<div id="message"
				 class="updated fade notice is-dismissible cmplz-admin-notice really-simple-plugins"
				 data-admin_notice_id="<?php echo $id?>"
				 style="border-left:4px solid #333">
				<div class="cmplz-admin-notice-container">
					<div class="cmplz-admin-notice-logo"><img width=80px"
															  src="<?php echo CMPLZ_URL ?>assets/images/icon-logo.svg"
															  alt="logo">
					</div>
					<div class="cmplz-admin-notice-content">
						<p><?php echo wp_kses_post($warning['open']) ?>
						<?php
							if (isset($warning['url'])) {
								$target = strpos( $warning['url'], 'complianz.io' )!==false ? 'target="_blank"' : '';
								?><a href="<?php echo esc_url_raw($warning['url'])?>" <?php echo $target?>><?php esc_html_e(__("Read more", "complianz-gdpr"))?></a><?php
							}
						?>
						</p>
						<br /><button class="cmplz-btn-dismiss-notice button-secondary"><?php esc_html_e(__("Dismiss","complianz-gdpr"))?></button>
					</div>
				</div>
			</div>
			<?php

		}

		/**
		 * get a list of applicable warnings.
		 *
		 * @param array $args
		 *
		 * @return array
		 */

		public function get_warnings( $args = array() ) {
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}
			$disable_notifications = cmplz_get_option( 'disable_notifications' );
			$defaults = array(
				'cache' => true,
				'status' => 'all',
				'plus_ones' => false,
				'progress_items_only' => false,
				'admin_notices' => false,
			);
			$args = wp_parse_args($args, $defaults);

//			if ($disable_notifications) {
//				$args['status'] = 'urgent';
//			}
			$admin_notice =  $args['admin_notices'] ? '_admin_notices' : '';

			$cache = $args['cache'];
			if ( cmplz_is_logged_in_rest() ) {
				$cache = false;
			}
			$warnings = $cache ? get_transient( 'complianz_warnings'.$admin_notice ) : false;
			//re-check if there are no warnings, or if the transient has expired
			if ( ! $warnings ) {
				$warnings = [];
				$warning_type_defaults = array(
					'plus_one' => false,
					'warning_condition' => '_true_',
					'success_conditions' => array(),
					'relation' => 'OR',
					'status' => 'open',
					'dismissible' => true,
					'include_in_progress' => false,
					'admin_notice' => false,
				);

				$warning_types = cmplz_load_warning_types();
				if (empty($warning_types)) {
					return [];
				}


				foreach ($warning_types as $id => $warning_type) {
					$warning_types[$id] = wp_parse_args($warning_type, $warning_type_defaults );
				}

				$dismissed_warnings = get_option('cmplz_dismissed_warnings', array() );
				foreach ( $warning_types as $id => $warning ) {
					if ( in_array( sanitize_title($id), $dismissed_warnings) ) {
						continue;
					}

					if ( $args['admin_notices'] && !$warning['admin_notice']){
						continue;
					}

					if ( !$args['admin_notices'] && $warning['admin_notice']){
						continue;
					}

					$show_warning = $this->validate_function($warning['warning_condition']);
					if ( !$show_warning ) {
						continue;
					}

					$relation = $warning['relation'];
					if ( $relation === 'AND' ) {
						$success = TRUE;
					} else {
						$success = FALSE;
					}
					foreach ( $warning[ 'success_conditions']  as $func) {
						$condition = $this->validate_function($func);
						if ( $relation === 'AND' ) {
							$success = $success && $condition;
						} else {
							$success = $success || $condition;
						}
					}

					if ( !$success ) {
						if ( isset( $warning['open']) ) {
							$warning['message'] = $warning['open'];
							$warning['status'] = 'open';
							$warnings[$id] = $warning;
						} else if (isset( $warning['urgent']) ) {
							$warning['message'] = $warning['urgent'];
							$warning['status'] = 'urgent';
							$warnings[$id] = $warning;
						} else if (isset( $warning['premium']) ) {
							$warning['message'] = $warning['premium'];
							$warning['status'] = 'premium';
							$warnings[$id] = $warning;
						}
					} else {
						if (isset( $warning['completed']) ) {
							$warning['message'] = $warning['completed'];
							$warning['status'] = 'completed';
							$warning['plus_one'] = false;
							$warnings[$id] = $warning;
						}
					}
				}
				set_transient( 'complianz_warnings'.$admin_notice, $warnings, HOUR_IN_SECONDS );
			}

			//filtering outside cache if, to make sure all warnings are saved for the cache.
			//filter by status
			if ($args['status'] !== 'all' ) {
				$filter_statuses = is_array($args['status']) ? $args['status'] : array($args['status']);
				foreach ($warnings as $id => $warning ) {
					if ( !in_array( $warning['status'], $filter_statuses) ) {
						unset( $warnings[$id] );
					}
				}
			}

			//filter by plus ones
			if ($args['plus_ones']) {
				//if notifications disabled, we return an empty array when the plus ones are requested.
				if ( $disable_notifications ) {
					return array();
				}

				foreach ($warnings as $id => $warning ) {
					//prevent notices on upgrade to 5.0
					if ( !isset( $warning['plus_one'])) continue;

					if ( !$warning['plus_one'] ){
						unset($warnings[$id]);
					}
				}
			}

			//filter for progress bar
			if ($args['progress_items_only']) {
				foreach ($warnings as $id => $warning ) {
					//prevent notices on upgrade to 5.0
					if ( !isset( $warning['include_in_progress'])) continue;

					if ( !$warning['include_in_progress'] ){
						unset($warnings[$id]);
					}
				}
			}

			//sort so warnings are on top
			$completed = array();
			$open = array();
			$urgent = array();

			if ( ! empty( $warnings ) ) {
				foreach ( $warnings as $key => $warning ) {
					if ( isset($warning['status']) && $warning['status'] === 'urgent' ) {
						$urgent[$key] = $warning;
					} elseif ( isset($warning['status']) && $warning['status'] === 'open' ) {
						$open[$key] = $warning;
					} else {
						$completed[$key] = $warning;
					}
				}
			}

			return $urgent + $open + $completed;
		}

		/**
		 * Get output of function, in format 'function', or 'class()->sub()->function'
		 * We can pass one variable to the function
		 * @param string $func
		 * @return string|bool
		 */

		private function validate_function( $func ){
			$invert = false;
			if (strpos($func, 'NOT ') !== FALSE ) {
				$func = str_replace('NOT ', '', $func);
				$invert = true;
			}

			if ( empty($func) ) {
				return true;
			}

			if ( strpos($func, 'get_option_') !== false ) {
				$field  = str_replace( 'get_option_', '', $func );
				$output = get_option( $field );
			} else if ( preg_match( '/get_value_(.*)==(.*)/i', $func, $matches)) {
				$fieldname = $matches[1];
				$value = $matches[2];
				$output = cmplz_get_option( $fieldname ) === $value;
			} else if ( $func === '_true_') {
				$output = true;
			} else if ( $func === '_false_' ) {
				$output = false;
			} else {
				if ( preg_match( '/(.*)->(.*)/i', $func, $matches)) {
					if (preg_match( '/(.*)->(.*)\((.*)\)/i', $func, $sub_matches )) {
						$class = $sub_matches[1];
						$function = $sub_matches[2];
						$variable = $sub_matches[3];
						$output = COMPLIANZ::${$class}->$function($variable);
					} else {
						$class = $matches[1];
						$function = $matches[2];
						$output = COMPLIANZ::${$class}->$function();
					}
				} else if ( preg_match( '/(.*)\((.*)\)/i', $func, $matches ) ) {
					$func = $matches[1];
					$variable = $matches[2];
					$output = $func($variable);
				} else{
					$output = $func();
				}
			}

			if ( $invert ) {
				$output = !$output;
			}

			return $output;
		}


	}
} //class closure
