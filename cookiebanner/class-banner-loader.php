<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_banner_loader" ) ) {
	class cmplz_banner_loader {
		private static $_this;
		public $cookies = array();

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			if ( ! is_admin() && !cmplz_is_pagebuilder_preview() ) {
				if ( get_option( 'cmplz_wizard_completed_once' ) && $this->site_needs_cookie_warning() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), PHP_INT_MAX - 50 );
					add_filter( 'script_loader_tag', array( $this, 'add_asyncdefer_attribute' ), 10, 2 );
					add_action( 'wp_head', array( $this, 'cookiebanner_css' ) );
					add_action( 'wp_footer', array( $this, 'cookiebanner_html' ) );
                    $this->dynamic_gtm_enqueue();
				} else {
					add_action( 'wp_print_footer_scripts', array( $this, 'inline_cookie_script_no_warning' ), 10, 2 );
				}
			}

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				add_action( 'wp_footer', array( $this, 'detect_conflicts' ), PHP_INT_MAX );
				add_action( 'wp_ajax_cmplz_store_console_errors', array( $this, 'store_console_errors' ) );
			}

			add_action( 'cmplz_statistics_script', array( $this, 'get_statistics_script' ), 10 );
			add_action( 'cmplz_tagmanager_script', array( $this, 'get_tagmanager_script' ), 10 );
			add_action( 'cmplz_before_statistics_script', array( $this, 'add_gtag_js' ), 10 );
			add_action( 'cmplz_before_statistics_script', array( $this, 'add_clicky_js' ), 10 );
			add_filter( 'cmplz_consenttype', array( $this, 'maybe_filter_consenttype' ), 10, 2 );
		}

		static function this() {
			return self::$_this;
		}

        public function dynamic_gtm_enqueue() {
			if (cmplz_get_option('gtm_code_head') === 'yes') {
				$hook = cmplz_get_option('gtm_code_head') ? 'wp_head' : 'wp_print_footer_scripts';
				$hookPriority = cmplz_get_option('gtm_code_head') ? 0 : PHP_INT_MAX - 50;
				add_action($hook, array($this, 'inline_cookie_script'), $hookPriority);
			} else {
				add_action( 'wp_print_footer_scripts', array( $this, 'inline_cookie_script' ), PHP_INT_MAX - 50 );
			}
        }

		public function wizard_completed_once() {
			return get_option( 'cmplz_wizard_completed_once' );
		}

		/**
		 * Front end javascript error detection.
		 * Only for site admins
		 */

		public function detect_conflicts() {

			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			if ( wp_doing_ajax() || is_admin() ) {
				return;
			}

			if ( ! $this->site_needs_cookie_warning() ) {
				return;
			}

			//don't fire on the back-end
			if ( is_preview() || cmplz_is_pagebuilder_preview() || isset( $_GET["cmplz_safe_mode"] ) ) {
				return;
			}

			if ( cmplz_get_option( 'disable_cookie_block' ) ) {
				return;
			}

			//not when scan runs
			if ( isset( $_GET['complianz_scan_token'] ) ) {
				return;
			}

			/* Do not fix mixed content when call is coming from wp_api or from xmlrpc or feed */
			if ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) {
				return;
			}

			if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
				return;
			}

			$checked_count = (int) get_transient( 'cmplz_checked_for_js_count' );
			if ( $checked_count > 5 ) {
				return;
			}

			$nonce = wp_create_nonce( 'cmplz-detect-errors' );
			?>
			<script>
				var request = new XMLHttpRequest();
				var error_occurred = false;
				window.onerror = function (msg, url, lineNo, columnNo, error) {
					error_occurred = true;

					var request = new XMLHttpRequest();
					request.open('POST', '<?php echo add_query_arg(
						array(
							'type'   => 'errors',
							'nonce'  => $nonce,
							'action' => 'cmplz_store_console_errors'
						),
						admin_url( 'admin-ajax.php' )
					)
						?>', true);
					var data = [];
					data.push(msg);
					data.push(lineNo);
					data.push(url.substring(0, url.indexOf('?')));
					request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
					request.send(data);
				};

				//if no error occurred after 3 seconds, send a reset signal
				setTimeout(function () {
					if (!error_occurred) {
						var request = new XMLHttpRequest();
						request.open('POST', '<?php echo add_query_arg(
							array(
								'type'   => 'errors',
								'nonce'  => $nonce,
								'action' => 'cmplz_store_console_errors'
							),
							admin_url( 'admin-ajax.php' )
						)
							?>', true);
						var data = [];
						data.push('no-errors');
						request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
						request.send(data);
					}

				}, 3000);
			</script>
			<?php
		}


		/**
		 * Store detected errors
		 * Only for site admins
		 */

		public function store_console_errors() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			if ( ! $this->site_needs_cookie_warning() ) {
				return;
			}

			/**
			 * limit to one request each two minutes.
			 */

			$checked_count = (int) get_transient( 'cmplz_checked_for_js_count' );
			if ( $checked_count > 5 ) {
				return;
			}

			set_transient( 'cmplz_checked_for_js_count', $checked_count + 1, 5 * MINUTE_IN_SECONDS );
			$success = false;
			if ( isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'cmplz-detect-errors' ) ) {
				if ( isset( $_POST['no-errors'] ) ) {
					update_option( 'cmplz_detected_console_errors', false );
					$success = true;
				} else {
					$errors = [];
					foreach ( $_POST as $key => $value ) {
						// Sanitize each value
						$sanitized_value = sanitize_text_field( $value );

						if ( count( $errors ) > 0 && strpos( $sanitized_value, 'runReadyTrigger' ) === false ) {
							$errors[] = explode( ',', str_replace( site_url(), '', $sanitized_value ) );
							if ( isset( $errors[1] ) && $errors[1] > 1 ) {
								update_option( 'cmplz_detected_console_errors', $errors );
							}
							$success = true;
						}
					}
				}
			}

			$response = json_encode( array(
				'success' => $success,
			) );
			header( "Content-Type: application/json" );
			echo $response;
			exit;
		}

		/**
		 * When special data is processed, Canada requires optin consenttype
		 *
		 * @param string $consenttype
		 * @param $region
		 *
		 * @return string $consenttype
		 */

		public function maybe_filter_consenttype( string $consenttype, $region ): string {
			if ( $region === 'ca'
				 && cmplz_site_shares_data()
				 && cmplz_get_option( 'sensitive_information_processed' ) === 'yes'
			) {
				$consenttype = 'optin';
			} elseif ( $region === 'ca'
					   && cmplz_get_option( 'ca_targets_quebec' ) === 'yes' ) {
				$consenttype = 'optin';
			}
			if ( $region === 'au'
				 && cmplz_site_shares_data()
				 && cmplz_get_option( 'sensitive_information_processed' ) === 'yes'
				 && cmplz_uses_marketing_cookies()
			) {
				$consenttype = 'optin';
			}

			return $consenttype;
		}

		/**
		 * Get the cookie domain, without https or end slash
		 *
		 * @return string
		 */

		public function get_cookie_domain() {
			$domain = str_replace( array( 'http://', 'https://' ), '', cmplz_get_option( 'cookie_domain' ) );
			if ( substr( $domain, - 1 ) === '/' ) {
				$domain = substr( $domain, 0, - 1 );
			}

			return apply_filters( 'cmplz_cookie_domain', $domain );
		}


		/**
		 * Check if we want to notify about Google Fonts
		 * @return bool
		 */
		public function show_google_fonts_notice(): bool {
			if ( ! cmplz_uses_thirdparty( 'google-fonts' ) ) {
				return false;
			}

			return ! defined( "CMPLZ_SELF_HOSTED_PLUGIN_ACTIVE" ) && strpos( get_locale(), 'de_' ) !== false;
		}

		/**
		 * Get prefix for our Complianz cookies
		 *
		 * @return string
		 */
		public function get_cookie_prefix() {
			if ( is_multisite() && is_main_site() && ! cmplz_get_option( 'set_cookies_on_root' ) ) {
				return 'cmplz_rt_';
			}

			return 'cmplz_';
		}

		/**
		 * Defer complianz.js
		 *
		 * @param string $tag
		 * @param string $handle
		 *
		 * @return string
		 */
		public function add_asyncdefer_attribute( $tag, $handle ) {
			if ( $handle === 'cmplz-cookiebanner' || $handle === 'cmplz-tcf' ) {
				return preg_replace( '/^<script /', '<script defer ', $tag );
			}

			return $tag;
		}

		/**
		 * Enqueue cookie banner javascript
		 *
		 * @return void
		 */
		public function enqueue_assets() {
			$minified       = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$banner         = cmplz_get_cookiebanner( apply_filters( 'cmplz_user_banner_id', cmplz_get_default_banner_id() ) );
			$cookiesettings = $banner->get_front_end_settings();
			$deps           = array();
			if ( cmplz_tcf_active() ) {
				$deps[] = 'cmplz-tcf';
			}
			if ( cmplz_uses_thirdparty( 'instagram' ) && get_option( 'cmplz_post_scribe_required' ) ) {
				$deps[] = 'cmplz-postscribe';
				$v      = filemtime( CMPLZ_PATH . "assets/js/postscribe.min.js" );
				wp_enqueue_script( 'cmplz-postscribe', CMPLZ_URL . "assets/js/postscribe.min.js", array( 'jquery' ), $v, true );
			}
			$v = filemtime( CMPLZ_PATH . "cookiebanner/js/complianz$minified.js" );
			wp_enqueue_script( 'cmplz-cookiebanner', CMPLZ_URL . "cookiebanner/js/complianz$minified.js", $deps, $v, true );
			wp_localize_script( 'cmplz-cookiebanner', 'complianz', $cookiesettings );
		}

		/**
		 * Inline css to default hide the banner until fully loaded
		 * @return void
		 */
		public function cookiebanner_css() {
			?>
			<style>.cmplz-hidden {
					display: none !important;
				}</style><?php
		}

		/**
		 * Load the cookie banner html for each consenttype
		 */
		public function cookiebanner_html() {


			global $post;
			$type = '';
			if ( $post && ( $post->ID ?? false ) ) {
				if ( preg_match( COMPLIANZ::$document->get_shortcode_pattern( "gutenberg" ), $post->post_content, $matches ) ) {
					$type   = $matches[1];
					$region = cmplz_get_region_from_legacy_type( $type );
					$type   = str_replace( '-' . $region, '', $type );
				} elseif ( preg_match( COMPLIANZ::$document->get_shortcode_pattern(), $post->post_content, $matches ) ) {
					$type = $matches[1];
				} elseif ( preg_match( COMPLIANZ::$document->get_shortcode_pattern( "classic", true ), $post->post_content, $matches ) ) {
					$type   = $matches[1];
					$region = cmplz_get_region_from_legacy_type( $type );
					$type   = str_replace( '-' . $region, '', $type );
				}
			}

			//to prevent WCAG errors, we skip banner html on the cookie statement. But not if TCF enabled, because TCF requires it.
			if ( $type === 'cookie-statement' && ! cmplz_tcf_active() ) {
				return;
			}

			$consent_types           = cmplz_get_used_consenttypes();
			$banner_ids              = cmplz_ab_testing_enabled() ? wp_list_pluck( cmplz_get_cookiebanners(), 'ID' ) : [ cmplz_get_default_banner_id() ];
			$path                    = trailingslashit( CMPLZ_PATH ) . 'cookiebanner/templates/';
			$manage_consent_template = cmplz_get_template( "manage-consent.php", false, $path );
			$banner_html             = '';
			$manage_consent_html     = '';
			foreach ( $consent_types as $consent_type ) {
				$banner_template = cmplz_get_template( "cookiebanner.php", array( 'consent_type' => $consent_type ), $path );
				foreach ( $banner_ids as $banner_id ) {
					$temp_banner_html         = $banner_template;
					$temp_manage_consent_html = $manage_consent_template;
					$banner                   = cmplz_get_cookiebanner( $banner_id );
					$cookie_settings          = $banner->get_html_settings();
					foreach ( $cookie_settings as $fieldname => $value ) {
						if ( isset( $value['text'] ) ) {
							$value = $value['text'];
						}
						if ( is_array( $value ) ) {
							continue;
						}
						if ( $fieldname !== 'logo' ) {
							$value = nl2br( $value );
						}
						$temp_banner_html         = str_replace( '{' . $fieldname . '}', $value, $temp_banner_html );
						$temp_manage_consent_html = str_replace( '{' . $fieldname . '}', $value, $temp_manage_consent_html );
					}
					$banner_html         .= $temp_banner_html;
					$manage_consent_html .= $temp_manage_consent_html;
				}
			}

			$comment = apply_filters( 'cmplz_document_comment', "\n"
																. "<!-- Consent Management powered by Complianz | GDPR/CCPA Cookie Consent https://wordpress.org/plugins/complianz-gdpr -->"
																. "\n" );
			echo $comment .
				 '<div id="cmplz-cookiebanner-container">' . apply_filters( "cmplz_banner_html", $banner_html ) . '</div>
					<div id="cmplz-manage-consent" data-nosnippet="true">' . apply_filters( "cmplz_manage_consent_html", $manage_consent_html ) . '</div>';
		}


		/**
		 * On multisite, we want to get the policy consistent across sites
		 *
		 * @return int
		 */

		public function get_active_policy_id() {
			//if !multisite, get normal option
			if ( !is_multisite() ) {
				return get_option( 'complianz_active_policy_id', 1 );
			}

			//multisite. If set_cookies_on_root, get site option, otherwise get normal option
			if ( cmplz_get_option( 'set_cookies_on_root' ) ) {
				return get_site_option( 'complianz_active_policy_id', 1 );
			}

			return get_option( 'complianz_active_policy_id', 1 );
		}

		/**
		 * Upgrade the activate policy id with one
		 * The active policy id is used to track if the user has consented to the latest policy changes.
		 * If changes were made, the policy is increased, and user should consent again.
		 *
		 * On multisite, we want to get the policy consistent across sites
		 */

		public function upgrade_active_policy_id() {
			$policy_id = $this->get_active_policy_id();
			$policy_id ++;

			if ( !is_multisite() ) {
				update_option( 'complianz_active_policy_id', $policy_id );
			}

			//multisite. If set_cookies_on_root, get site option, otherwise get normal option
			if ( cmplz_get_option( 'set_cookies_on_root' ) ) {
				update_site_option( 'complianz_active_policy_id', $policy_id );
			} else {
				update_option( 'complianz_active_policy_id', $policy_id );
			}
		}

		/**
		 * Check if we're in a subfolder setup (home_url consists of domain+path, e.g. domain.com/sub)
		 *
		 * @return string $path //$path should at least contain a '/', for root application.
		 */

		public function get_cookie_path() {
			//if cookies are to be set on the root, don't send a path
			if ( cmplz_get_option( 'set_cookies_on_root' )
				 || function_exists( 'pll__' )
				 || function_exists( 'icl_translate' )
				 || class_exists( 'TRP_Translate_Press', false )
			) {
				return apply_filters( 'cmplz_cookie_path', '/' );
			}

			$domain      = home_url();
			$parse       = parse_url( $domain );
			$root_domain = $parse['host'];
			$path        = str_replace( array( 'http://', 'https://', $root_domain ), '', $domain );

			return apply_filters( 'cmplz_cookie_path', trailingslashit( $path ) );
		}


		/**
		 * The category that is passed to the statistics script determine if these are executed immediately or not.
		 *
		 * @return string
		 **/

		public function get_statistics_category() {
			//if a cookie warning is needed for the stats we don't add a native class, so it will be disabled by the cookie blocker by default
			$category        = 'statistics';
			$uses_tagmanager = cmplz_get_option( 'compile_statistics' ) === 'google-tag-manager' ? true : false;
            $matomo          = cmplz_get_option( 'compile_statistics' ) === 'matomo' ? true : false;
            $clarity          = cmplz_get_option( 'compile_statistics' ) === 'clarity' ? true : false;

			//without tag manager, set as functional if no cookie warning required for stats
			if ( ! $uses_tagmanager && ! $this->cookie_warning_required_stats() ) {
				$category = 'functional';
			}

			//tag manager always fires as functional
			if ( $uses_tagmanager ) {
				$category = 'functional';
			}

			if ( $matomo && cmplz_get_option( 'matomo_anonymized' ) === 'yes' ) {
				$category = 'functional';
			}

            if ( $clarity && cmplz_get_option( 'clarity_consent_mode' ) === 'yes' ) {
                $category = 'functional';
            }

			/*
			 * Run Tag Manager or gtag by default if consent mode is enabled
			 */
			if ( cmplz_consent_mode() ) {
				$category = 'functional';
			}

			return apply_filters( 'cmplz_statistics_category', $category );
		}

		public function inline_cookie_script() {
			//based on the script classes, the statistics will get added on consent, or without consent
			$category    = $this->get_statistics_category();
			$statistics = cmplz_get_option( 'compile_statistics' );
			$aw_code    = cmplz_get_option( 'aw_code' );
			$additional_marketing = array_map( 'trim', explode( ',', cmplz_get_option( "additional_gtags_marketing" ) ) );
            $template = "gtag('config', '{tag}' );";
            $additional_tags = '';
			foreach ($additional_marketing as $marketing){
                $additional_tags .= str_replace( '{tag}', esc_attr($marketing), $template );
			}
			$configured_by_complianz = cmplz_get_option( 'configuration_by_complianz' ) === 'yes';
			do_action( 'cmplz_before_statistics_script' );

			/**
			 * Tag manager needs to be included as text/javascript (omitted as it's default), as it always needs to fire.
			 * All other scripts will be included with the appropriate tags, and fired when possible
			 */

			$stats_comment = '<!-- Statistics script Complianz GDPR/CCPA -->' . "\n";
			if ( $configured_by_complianz && $statistics !== 'no' ) {

				if ( $statistics === 'google-tag-manager' || $statistics === 'matomo-tag-manager' ) {
					ob_start();
					do_action( 'cmplz_tagmanager_script' );
					$statistics_script = ob_get_clean();
					if ( ! empty( $statistics_script ) ) {
						echo $stats_comment;
						?>
						<script data-category="<?php echo esc_attr( $category ) ?>">
							<?php echo $statistics_script; ?>
						</script><?php
					}
				} else {
					ob_start();
					do_action( 'cmplz_statistics_script' );
					$statistics_script = ob_get_clean();
					if ( ! empty( $statistics_script ) ) {
						echo $stats_comment;
						?>
						<script <?php echo $category === 'functional' ? '' : 'type="text/plain"' ?>
							data-category="<?php echo esc_attr( $category ) ?>"><?php echo $statistics_script; ?></script><?php
					}
				}
				if ( ! empty( $aw_code ) && $statistics === 'google-analytics' ) {
					$script = str_replace( '{aw_code}', $aw_code, cmplz_get_template( "statistics/gtag-remarketing.js" ) );
                    $script = str_replace( '{additional_tags}', $additional_tags, $script );
					//remarketing with consent mode should be executed without consent, as consent mode handles the consent
                    //BUT if basic mode is enabled, it should be marketing anyway.
					if ( cmplz_consent_mode() && cmplz_get_option( "gtag-basic-consent-mode" ) !== 'yes' ) {
						?>
						<script data-category="functional"><?php echo $script; ?></script><?php
					} else {
						?>
						<script type="text/plain" data-category="marketing"><?php echo $script; ?></script><?php
					}
				}
			}

			if ( cmplz_get_option( 'disable_cookie_block' ) == 1 ) {
				return;
			}

			$scripts = get_option( "complianz_options_custom-scripts" );
			if ( ! is_array( $scripts ) || ! isset( $scripts['add_script'] ) || ! is_array( $scripts['add_script'] ) ) {
				return;
			}

			$added_scripts = array_filter( $scripts['add_script'], function ( $script ) {
				return $script['enable'] == 1;
			} );

			$added_scripts = apply_filters( 'cmplz_added_scripts', $added_scripts );
			foreach ( $added_scripts as $script ) {
				if ( ! isset( $script['editor'] ) || empty( $script['editor'] ) ) {
					continue;
				}

				echo "<!-- Script Center {$script['category']} script Complianz GDPR/CCPA -->\n";
				$async = $script['async'] == 1 ? 'async' : '';
				?>
				<script <?php echo $async ?> type="text/plain"
											 data-category="<?php echo esc_attr( $script['category'] ) ?>">
                    <?php echo $script['editor'] ?>

				</script>
				<?php
			}
		}

		/**
		 * Insert the gtag.js script required if gtag.js is used
		 *
		 * @hooked cmplz_before_statistics_script
		 * @since  4.7.8
		 */
		public function add_gtag_js() {
			if ( cmplz_get_option( 'configuration_by_complianz' ) === 'no' ) {
				return;
			}

			$statistics = cmplz_get_option( 'compile_statistics' );
			$gtag_code  = esc_attr( cmplz_get_option( "ua_code" ) );
			if ( $statistics === 'google-analytics' && ! empty( $gtag_code ) ) {
				$category = $this->get_statistics_category();
				?>
				<script async data-category="<?php echo $category ?>"
						src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gtag_code ?>"></script><?php
			}
		}

		/**
		 * Add generic clicky js script
		 */

		public function add_clicky_js() {
			$statistics = cmplz_get_option( 'compile_statistics' );
			if ( $statistics === 'clicky' ) {
				$category = $this->get_statistics_category();
				?>
				<script async <?php echo $category === 'functional' ? '' : 'type="text/plain"' ?>
						data-category="<?php echo $category ?>" src="//static.getclicky.com/js"></script>
				<?php
			}
		}

		/**
		 * Inline scripts which do not require a warning
		 */

		public function inline_cookie_script_no_warning() {
			do_action( 'cmplz_before_statistics_script' );
			?>
			<script data-category="functional">
				<?php do_action( 'cmplz_statistics_script' );?>
				<?php do_action( 'cmplz_tagmanager_script' );?>
			</script>
			<?php
		}

		/**
		 *
		 * @hooked cmplz_tagmanager_script
		 *
		 * */

		public function get_tagmanager_script() {
			if ( cmplz_get_option( 'configuration_by_complianz' ) !== 'yes' ) {
				return;
			}
			$script     = '';
			$statistics = cmplz_get_option( 'compile_statistics' );
			if ( $statistics === 'google-tag-manager' ) {
				$template = cmplz_get_option('cmplz-tm-template') === 'yes' ? '-template' : '';
				$consent_mode = cmplz_consent_mode() ? "-consent-mode$template" : '';

				$script = cmplz_get_template( "statistics/google-tag-manager$consent_mode.js" );
				$script = str_replace( '{gtm_code}', esc_attr( cmplz_get_option( "gtm_code" ) ), $script );
			} elseif ( $statistics === 'matomo-tag-manager' ) {
				$script = cmplz_get_template( 'statistics/matomo-tag-manager.js' );
				$script = str_replace( '{container_id}', esc_attr( cmplz_get_option( 'matomo_container_id' ) ), $script );
				$script = str_replace( '{matomo_url}', esc_url_raw( trailingslashit( cmplz_get_option( 'matomo_tag_url' ) ) ), $script );
            }
            echo apply_filters( 'cmplz_script_filter', $script );

		}

		/**
		 *
		 * @hooked cmplz_statistics_script
		 *
		 *
		 * */

		public function get_statistics_script() {
			if ( cmplz_get_option( 'configuration_by_complianz' ) === 'no' ) {
				return;
			}

			$statistics = cmplz_get_option( 'compile_statistics' );
			$script     = '';
			if ( $statistics === 'google-analytics' ) {
				$consent_mode = cmplz_consent_mode() ? '-consent-mode' : '';
				$consent_mode = $consent_mode !== '' && cmplz_get_option( "gtag-basic-consent-mode" ) === 'yes' ? $consent_mode . '-basic' : $consent_mode;
				$code         = esc_attr( cmplz_get_option( "ua_code" ) );
				$template = "gtag('config', '{tag}' );";
                $additional_stats = array_map( 'trim', explode( ',', cmplz_get_option( "additional_gtags_stats" ) ) );
				$additional_tags = '';
				foreach ($additional_stats as $stat){
					$stat = trim($stat); 
					if (empty($stat)) {
						 continue; 
						}
					$additional_tags .= str_replace( '{tag}', esc_attr($stat), $template );
				}

				$anonymize_ip = $this->google_analytics_always_block_ip() ? "'anonymize_ip': true" : "";
				if ( substr( strtoupper( $code ), 0, 2 ) === 'G-' ) {
					$anonymize_ip = '';
				}
				$enable_tcf_support = cmplz_tcf_active() ? 'true' : 'false';
				$ads_data_redaction = cmplz_get_option( "cmplz-gtag-ads_data_redaction" )==='yes'  ? 'true' : 'false';
				$urlpassthrough = cmplz_get_option( "cmplz-gtag-urlpassthrough" )==='yes' ? 'true' : 'false';
				$script       = cmplz_get_template( "statistics/gtag$consent_mode.js" );
				$script       = str_replace(
                        array('{G_code}', '{additional_tags}', '{anonymize_ip}', '{enable_tcf_support}', '{ads_data_redaction}', '{url_passthrough}'),
                        array($code, $additional_tags, $anonymize_ip, $enable_tcf_support, $ads_data_redaction, $urlpassthrough), $script );

			} elseif ( $statistics === 'matomo' ) {
				$cookieless = ( cmplz_get_option( 'matomo_anonymized' ) === 'yes' ) ? '-cookieless' : '';
				$script     = cmplz_get_template( "statistics/matomo$cookieless.js" );
				$script     = str_replace( '{site_id}', esc_attr( cmplz_get_option( 'matomo_site_id' ) ), $script );
				$script     = str_replace( '{matomo_url}', esc_url_raw( trailingslashit( cmplz_get_option( 'matomo_url' ) ) ), $script );
			} elseif ( $statistics === 'clicky' ) {
				$script = cmplz_get_template( 'statistics/clicky.js' );
				$script = str_replace( '{site_ID}', esc_attr( cmplz_get_option( 'clicky_site_id' ) ), $script );
			} elseif ( $statistics === 'clarity' ) {
                $is_consent_for_anonymous_stats = cmplz_get_option( 'consent_for_anonymous_stats' ) === 'yes';
                $is_clarity_consent_mode = cmplz_get_option( 'clarity_consent_mode' ) === 'yes';
                $clarity_script = $is_clarity_consent_mode && $is_consent_for_anonymous_stats ? '-consent-mode' : '';
                $script = cmplz_get_template("statistics/clarity$clarity_script.js");
                $script = str_replace('{site_ID}', esc_attr(cmplz_get_option('clarity_id')), $script);
			} elseif ( $statistics === 'yandex' ) {
				$script         = cmplz_get_template( 'statistics/yandex.js' );
				$data_layer     = cmplz_get_option( 'yandex_ecommerce' ) === 'yes';
				$data_layer_str = '';
				if ( $data_layer ) {
					$data_layer_str = 'ecommerce:"dataLayer"';
				}
				$script = str_replace( array( '{yandex_id}', '{ecommerce}' ), array(
					cmplz_get_option( 'yandex_id' ),
					$data_layer_str
				), $script );
			}
			echo apply_filters( 'cmplz_script_filter', $script );
		}

		/**
		 *
		 * Get the domain from the current site url
		 *
		 * @return bool|string $domain
		 */

		public function get_domain() {
			$url   = site_url();
			$parse = parse_url( $url );
			if ( ! isset( $parse['host'] ) ) {
				return false;
			}

			return $parse['host'];
		}

		/**
		 * get boolean string for database purposes
		 *
		 * @param bool $boolean
		 *
		 * @return string
		 */

		private function bool_string( $boolean ) {
			$bool = (bool) $boolean;

			return $bool ? 'TRUE' : 'FALSE';
		}

		/**
		 * Check a string for third party services
		 *
		 * @param string $html
		 * @param bool $single_key //return a single string instead of array
		 *
		 * @return array|string $thirdparty
		 *
		 * */

		public function parse_for_thirdparty_services(
			$html, $single_key = false
		) {

			$thirdparty = array();
			$thirdparty_markers
						= COMPLIANZ::$config->thirdparty_service_markers;
			foreach ( $thirdparty_markers as $key => $markers ) {
				foreach ( $markers as $marker ) {
					if ( $single_key && strpos( $html, $marker ) !== false ) {
						return $key;
					} else if ( strpos( $html, $marker ) !== false
								&& ! in_array( $key, $thirdparty )
					) {
						$thirdparty[] = $key;
					}
				}
			}
			if ( $single_key ) {
				return false;
			}

			return $thirdparty;
		}

		/**
		 * Check the webpage html output for social media markers.
		 *
		 * @param string $html
		 * @param bool $single_key
		 *
		 * @return array|bool|string $social_media_key
		 */

		public function parse_for_social_media( $html, $single_key = false ) {
			$social_media         = array();
			$social_media_markers = COMPLIANZ::$config->social_media_markers;
			foreach ( $social_media_markers as $key => $markers ) {
				foreach ( $markers as $marker ) {
					if ( $single_key && strpos( $html, $marker ) !== false ) {
						return $key;
					}

					if ( strpos( $html, $marker ) !== false
						 && ! in_array( $key, $social_media )
					) {
						$social_media[] = $key;
					}
				}
			}

			if ( $single_key ) {
				return false;
			}

			return $social_media;
		}

		public function get_cookies_by_service( $settings = array() ) {
			$cookies            = $this->get_cookies( $settings );
			$grouped_by_service = array();
			$topServiceID       = 0;
			foreach ( $cookies as $cookie ) {
				$serviceID                                      = $cookie->serviceID ?: 999999999;
				$topServiceID                                   = $serviceID > $topServiceID ? $serviceID : $topServiceID;
				$purpose                                        = $cookie->purpose === 0 || strlen( $cookie->purpose ) == 0
					? __( 'Purpose pending investigation', 'complianz-gdpr' )
					: $cookie->purpose;
				$grouped_by_service[ $serviceID ][ $purpose ][] = $cookie;
			}

			//move misc to end of array
			$misc = isset( $grouped_by_service[999999999] )
				? $grouped_by_service[999999999] : false;
			unset( $grouped_by_service[999999999] );
			if ( $misc ) {
				$grouped_by_service[ $topServiceID + 1 ] = $misc;
			}

			return $grouped_by_service;
		}

		/**
		 * Get list of active cookies on site
		 *
		 * @return array|object|null
		 */

		public function get_cookies( $settings = array() ) {
			global $wpdb;
			$cookies = $this->cookies;

			if ( count( $cookies ) === 0 ) {
				$cookies = get_transient( 'cmplz_cookies' );
				if ( ! $cookies ) {
					$cookies      = [];
					$table_exists = get_option( 'cmplz_cookietable_version' );
					if ( $table_exists ) {
						$cookies = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookies" );
					}
					set_transient( 'cmplz_cookies', $cookies, MINUTE_IN_SECONDS );
				}

				$this->cookies = $cookies;
			}

			$defaults = array(
				'ignored'           => 'all',
				'new'               => false,
				'language'          => false,
				'isMembersOnly'     => false,
				'hideEmpty'         => false,
				'showOnPolicy'      => 'all',
				'lastUpdatedDate'   => false,
				'deleted'           => false,
				'isOwnDomainCookie' => 'all',
			);

			$settings = wp_parse_args( $settings, $defaults );
			if ( $settings['isMembersOnly'] !== 'all' ) {
				//filter the $cookies array to only get cookies where isMembersOnly === $settings['isMembersOnly']
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->isMembersOnly == $settings['isMembersOnly'];
				} );
			}

			if ( $settings['showOnPolicy'] !== 'all' ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->showOnPolicy == $settings['showOnPolicy'];
				} );
			}

			if ( $settings['ignored'] !== 'all' ) {
				$cookies = array_filter( $cookies, function ( $cookie ) use ( $settings ) {
					return $cookie->ignored == $settings['ignored'];
				} );
			}

			if ( $settings['isOwnDomainCookie'] !== 'all' ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->isOwnDomainCookie == $settings['isOwnDomainCookie'];
				} );
			}
			if ( ! $settings['language'] ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) {
					return $cookie->isTranslationFrom == false;
				} );
			} else if ( $settings['language'] !== 'all' ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->language === $settings['language'];
				} );
			}

			if ( $settings['hideEmpty'] ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) {
					return $cookie->name !== '';
				} );
			}

			if ( ! $settings['deleted'] ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) {
					return $cookie->deleted != true;
				} );
			}
			if ( isset( $settings['sync'] ) ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->sync == $settings['sync'];
				} );
			}

			if ( $settings['new'] ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) {
					return $cookie->firstAddDate > get_option( 'cmplz_cookie_data_verified_date' );
				} );
			}

			if ( $settings['lastUpdatedDate'] ) {
				$cookies = array_filter( $cookies, static function ( $cookie ) use ( $settings ) {
					return $cookie->lastUpdatedDate < $settings['lastUpdatedDate'] || $cookie->lastUpdatedDate === false || $cookie->lastUpdatedDate === 0;
				} );
			}

			//make sure service data is added
			foreach ( $cookies as $index => $cookie ) {
				$cookie            = new CMPLZ_COOKIE( $cookie );
				$cookies[ $index ] = $cookie;
			}

			return $cookies;
		}

		/**
		 * convert a slug to a normal readable name, without -, and with uppercase start of each word
		 *
		 * @param $slug
		 *
		 * @return string
		 */
		public function convert_slug_to_name( $slug ) {
			$a = explode( '-', $slug );
			$a = array_map( 'ucfirst', $a );

			return implode( ' ', $a );
		}


		/**
		 * Get list of detected services
		 *
		 * @param $category 'social', 'utility', 'other'
		 *
		 * @return array $services
		 * @since 1.0
		 *
		 */

		public function get_services( $settings = array() ) {
			global $wpdb;
			$result = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}cmplz_cookies'" );
			if ( empty( $result ) ) {
				return array();
			}

			$defaults = array(
				'language'                      => false,
				'hideEmpty'                     => false,
				'category'                      => 'all',
				'lastUpdatedDate'               => false,
				'includeServicesWithoutCookies' => false,
			);
			$settings = wp_parse_args( $settings, $defaults );
			$sql      = ' 1=1 ';

			if ( ! $settings['language'] ) {
				$sql .= ' and isTranslationFrom = false ';
			} else if ( $settings['language'] !== 'all' ) {
				$lang = cmplz_sanitize_language( $settings['language'] );
				$sql  .= " AND language = '$lang' ";
			}

			if ( $settings['hideEmpty'] ) {
				$sql .= " AND name <>'' ";
			}

			if ( isset( $settings['sync'] ) ) {
				$sql .= ' AND sync = '
						. $this->bool_string( $settings['sync'] );
			}

			if ( $settings['category'] !== 'all' ) {
				$sql .= $wpdb->prepare( " AND category = %s",
					$settings['category'] );
			}

			$no_cookies_where = $sql;
			if ( $settings['lastUpdatedDate'] ) {
				$sql .= $wpdb->prepare( ' AND (lastUpdatedDate < %s OR lastUpdatedDate=FALSE OR lastUpdatedDate = 0 )',
					(int) $settings['lastUpdatedDate'] );
			}
			$sql      = "select * from {$wpdb->prefix}cmplz_services where " . $sql;
			$services = $wpdb->get_results( $sql );

			if ( $settings['includeServicesWithoutCookies'] ) {
				$sql                 = "select * from ( select * from {$wpdb->prefix}cmplz_services where NOT ID in (select DISTINCT services.ID from {$wpdb->prefix}cmplz_services as services inner join {$wpdb->prefix}cmplz_cookies on services.ID = {$wpdb->prefix}cmplz_cookies.serviceID)) as services where $no_cookies_where";
				$services_no_cookies = $wpdb->get_results( $sql );
				$service_ids         = wp_list_pluck( $services, 'ID' );
				foreach ( $services_no_cookies as $service_no_cookies ) {
					if ( ! in_array( $service_no_cookies->ID, $service_ids ) ) {
						$services[] = $service_no_cookies;
					}
				}
			}

			return $services;
		}


		/**
		 * Get an array of languages used on this site in format array('en' => 'en')
		 *
		 * @param bool $count
		 *
		 * @return int|array
		 */

		public function get_supported_languages( $count = false ) {
			$site_locale = cmplz_sanitize_language( get_locale() );
			$languages   = array( $site_locale => $site_locale );

			//QTranslate
			if ( defined( 'QTX_VERSION' ) ) {
				$enabled_languages = get_option( 'qtranslate_enabled_languages' );
				if ( is_array( $enabled_languages ) ) {
					foreach ( $enabled_languages as $language_code ) {
						if ( ! in_array( $language_code, $languages, true ) ) {
							$languages[ $language_code ] = $language_code;
						}
					}
				}
			}

			if ( function_exists( 'icl_register_string' ) ) {
				$wpml = apply_filters( 'wpml_active_languages', null,
					array( 'skip_missing' => 0 ) );
				if ( ! is_array( $wpml ) ) {
					$wpml = [];
				}
				/**
				 * WPML has changed the index from 'language_code' to 'code' so
				 * we check for both.
				 */
				$wpml_test_index = reset( $wpml );
				if ( isset( $wpml_test_index['language_code'] ) ) {
					$wpml = wp_list_pluck( $wpml, 'language_code' );
				} elseif ( isset( $wpml_test_index['code'] ) ) {
					$wpml = wp_list_pluck( $wpml, 'code' );
				} else {
					$wpml = array();
				}
				$languages = array_merge( $wpml, $languages );
			}

			/**
			 * TranslatePress support
			 * There does not seem to be an easy accessible API to get the languages, so we retrieve from the settings directly
			 */

			if ( class_exists( 'TRP_Translate_Press' ) ) {
				$trp_settings = get_option( 'trp_settings', array() );
				if ( isset( $trp_settings['translation-languages'] ) ) {
					$trp_languages = $trp_settings['translation-languages'];
					foreach ( $trp_languages as $language_code ) {
						$key = substr( $language_code, 0, 2 );
						if ( ! in_array( $key, $languages, true ) ) {
							$languages[ $key ] = $key;
						}
					}
				}
			}

			//make sure the en is always available.
			if ( ! in_array( 'en', $languages ) ) {
				$languages['en'] = 'en';
			}

			if ( $count ) {
				return count( $languages );
			}

			return array_keys( $languages );
		}

		/**
		 * Get the last cookie scan date in unix or human time format
		 *
		 * @param bool $unix
		 *
		 * @return bool|int|string
		 */

		public function get_last_cookie_scan_date( $unix = false ) {
			$last_scan_date = get_option( 'cmplz_last_cookie_scan' );

			if ( $unix ) {
				return $last_scan_date;
			}

			if ( $last_scan_date ) {
				$date = cmplz_localize_date( $last_scan_date );
				$time = date( get_option( 'time_format' ), $last_scan_date );
				$date = cmplz_sprintf( __( "%s at %s", 'complianz-gdpr' ), $date, $time );
			} else {
				$date = false;
			}

			return $date;
		}


		/**
		 * Get the last cookie sync date in unix or human time format
		 *
		 * @return string
		 */

		public function get_last_cookie_sync_date() {
			$last_sync_date = get_option( 'cmplz_last_cookie_sync' );
			if ( $last_sync_date ) {
				$date = cmplz_localize_date( $last_sync_date );
			} else {
				$date = __( '(not synced yet)', 'complianz-gdpr' );
			}

			return $date;
		}

		/**
		 * Update the cookie policy date
		 */

		public function update_cookie_policy_date() {
			update_option( 'cmplz_publish_date', time() );

			//also reset the email notification, so it will get sent next year.
			update_option( 'cmplz_update_legal_documents_mail_sent', false );
		}

		public function use_cdb_api() {
			$use_api = cmplz_get_option( 'use_cdb_api' ) !== 'no';

			return apply_filters( 'cmplz_use_cdb_api', $use_api );
		}


		/**
		 * Check if site uses Google Analytics
		 *
		 * @return bool
		 * */

		public function uses_google_analytics() {
			$statistics = cmplz_get_option( 'compile_statistics' );
			if ( $statistics === 'google-analytics' ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if tm is used
		 *
		 * @return bool
		 */

		public function uses_google_tagmanager() {

			$statistics = cmplz_get_option( 'compile_statistics' );

			if ( $statistics === 'google-tag-manager' ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if matomo is used
		 *
		 * @return bool
		 */

		public function uses_matomo() {
			$statistics = cmplz_get_option( 'compile_statistics' );

			return $statistics === 'matomo';
		}


		public function analytics_configured() {
			//if the user has chosen to configure it himself, we consider it to be configured.
			if ( cmplz_get_option( 'configuration_by_complianz' ) === 'no' ) {
				return true;
			}
			$UA_code = cmplz_get_option( 'ua_code' );

			return $UA_code !== '';
		}

		public function tagmanager_configured() {
			//if the user has chosen to configure it himself, we consider it to be configured.
			if ( cmplz_get_option( 'configuration_by_complianz' ) === 'no' ) {
				return true;
			}
			$gtm_code = cmplz_get_option( 'gtm_code' );

			return $gtm_code !== '';
		}

		public function matomo_configured() {
			//if the user has chosen to configure it himself, we consider it to be configured.
			if ( cmplz_get_option( 'configuration_by_complianz' ) === 'no' ) {
				return true;
			}

			$matomo_url = cmplz_get_option( 'matomo_url' );
			$site_id    = cmplz_get_option( 'matomo_site_id' );

			return $matomo_url !== '' && $site_id !== '';
		}


		/**
		 * Check if the site needs a cookie banner. Pass a region to check cookie banner requirement for a specific region
		 *
		 * @param string|bool $region
		 *
		 * @return bool
		 * *@since 1.2
		 *
		 */
		public function site_needs_cookie_warning( $region = false ) {

			/**
			 * default is false
			 */
			$needs_warning = false;
			if ( $region && ! cmplz_has_region( $region ) ) {

				/**
				 * if we do not target this region, we don't show a banner for that region
				 */
				$needs_warning = false;
			} else if ( ( ! $region || $region === 'us' ) && cmplz_has_region( 'us' ) ) {
				/**
				 * for the US, a cookie warning is always required
				 * if a region other than US is passed, we check the region's requirements
				 * if US is passed, we always need a banner
				 */
				$needs_warning = true;
			} else if ( $this->site_shares_data() ) {
				/**
				 * site shares data
				 */
				$needs_warning = true;
			} else if ( $this->cookie_warning_required_stats() ) {
				/**
				 * does the config of the statistics require a cookie warning?
				 */
				$needs_warning = true;
			}

			$url                  = $_SERVER['REQUEST_URI'];
			$excluded_posts_array = get_option( 'cmplz_excluded_posts_array', array() );
			if ( ! empty( $excluded_posts_array ) ) {
				foreach ( $excluded_posts_array as $excluded_slug ) {
					if ( strpos( $url, $excluded_slug ) !== false ) {
						return false;
					}
				}
			}

			return apply_filters( 'cmplz_site_needs_cookiewarning', $needs_warning );
		}

		/**
		 * Check if the site needs a cookie banner considering statistics only
		 *
		 * @param $region bool|string
		 *
		 * @return bool
		 * @since 1.0
		 *
		 */

		public function cookie_warning_required_stats( $region = false ) {

			if ( cmplz_get_option( 'consent_for_anonymous_stats' ) === 'yes' ) {
				return apply_filters( 'cmplz_cookie_warning_required_stats', true );
			}

			if ( $region && isset( COMPLIANZ::$config->regions[ $region ] ) ) {
				if ( COMPLIANZ::$config->regions[ $region ]['statistics_consent'] === 'no' ) {
					return apply_filters( 'cmplz_cookie_warning_required_stats', false );
				}

				if ( COMPLIANZ::$config->regions[ $region ]['statistics_consent'] === 'always' ) {
					return apply_filters( 'cmplz_cookie_warning_required_stats', true );
				}

				if ( COMPLIANZ::$config->regions[ $region ]['statistics_consent'] === 'when_not_anonymous' ) {
					if ( cmplz_get_option( 'eu_consent_regions' ) === 'yes' ) {
						return apply_filters( 'cmplz_cookie_warning_required_stats', true );
					}

					if ( $this->statistics_privacy_friendly() ) {
						return apply_filters( 'cmplz_cookie_warning_required_stats', false );
					}

					return apply_filters( 'cmplz_cookie_warning_required_stats', true );
				}

				return apply_filters( 'cmplz_cookie_warning_required_stats', false );
			}

			/**
			 * if region is not provided. Generic check
			 */

			if ( $this->statistics_privacy_friendly() && $this->consent_required_for_anonymous_stats() ) {
				return apply_filters( 'cmplz_cookie_warning_required_stats', true );
			}

			//if we're here, we don't need stats if they're set up privacy friendly
			return apply_filters( 'cmplz_cookie_warning_required_stats', ! $this->statistics_privacy_friendly() );
		}

		/**
		 * Check if consent is required for anonymous statistics
		 *
		 * @return bool
		 */

		public function consent_required_for_anonymous_stats() {
			$active_regions = COMPLIANZ::$config->active_regions();
			if ( in_array( 'always', array_column( $active_regions, 'statistics_consent' ) ) ) {
				return true;
			}

			$when_not_anonymous = array_search( 'when_not_anonymous', array_column( $active_regions, 'statistics_consent' ) );
			$uses_google        = $this->uses_google_analytics() || $this->uses_google_tagmanager();
			if ( $when_not_anonymous && $uses_google && cmplz_get_option( 'consent_for_anonymous_stats' ) === 'yes' ) {
				return true;
			}

			return false;
		}

		/**
		 * Add the selected statistics service as a service, and check for doubles
		 */

		public function maybe_add_statistics_service() {
			$selected_stat_service = cmplz_get_option( 'compile_statistics' );
			if ( $selected_stat_service === 'google-analytics'
				 || $selected_stat_service === 'matomo'
				 || $selected_stat_service === 'google-tag-manager'
			) {
				$service_name = $this->convert_slug_to_name( $selected_stat_service );
				$service      = new CMPLZ_SERVICE( $service_name );

				if ( ! $service->ID ) {
					//Add new service
					$service = new CMPLZ_SERVICE();
					$service->add( $service_name,
						$this->get_supported_languages(),
						false );
				}
			}
		}

		/**
		 * Determine if statistics are used in a privacy friendly way
		 *
		 * @return bool
		 */

		public function statistics_privacy_friendly() {
			$statistics = cmplz_get_option( 'compile_statistics' );

			//no statistics at all, it's privacy friendly
			if ( $statistics === 'no' ) {
				return apply_filters( 'cmplz_statistics_privacy_friendly', true );
			}

			//not anonymous stats.
			if ( $statistics === 'yes' ) {
				return apply_filters( 'cmplz_statistics_privacy_friendly', false );
			}

			$tagmanager                                = $statistics === 'google-tag-manager';
			$matomo                                    = $statistics === 'matomo';
			$google_analytics                          = $statistics === 'google-analytics';
            $clicky                                    = $statistics === 'clicky';
			$accepted_google_data_processing_agreement = false;
			$ip_anonymous                              = false;
			$no_sharing                                = false;

			if ( $clicky ) {
				return apply_filters( 'cmplz_statistics_privacy_friendly', false );
			}

			if ( $matomo ) {
				return apply_filters( 'cmplz_statistics_privacy_friendly', false );
			}

			if ( $google_analytics || $tagmanager ) {
				$thirdparty = $google_analytics ? cmplz_get_option( 'compile_statistics_more_info' ) : cmplz_get_option( 'compile_statistics_more_info_tag_manager' );
				if ( ! is_array( $thirdparty ) ) {
					$thirdparty = [];
				}
				$accepted_google_data_processing_agreement = in_array( 'accepted', $thirdparty, true );
				$ip_anonymous                              = in_array( 'ip-addresses-blocked', $thirdparty, true );
				$no_sharing                                = in_array( 'no-sharing', $thirdparty, true );
			}

			if ( ( $tagmanager || $google_analytics )
				 && ( ! $accepted_google_data_processing_agreement
					  || ! $ip_anonymous
					  || ! $no_sharing )
			) {
				return apply_filters( 'cmplz_statistics_privacy_friendly', false );
			}

			//everything set up privacy friendly!
			return apply_filters( 'cmplz_statistics_privacy_friendly', true );
		}


		/**
		 * Check if ip is always blocked
		 * @return bool
		 */
		public function google_analytics_always_block_ip() {
			$statistics       = cmplz_get_option( 'compile_statistics' );
			$google_analytics = $statistics === 'google-analytics';
			if ( $google_analytics ) {
				$thirdparty = cmplz_get_option( 'compile_statistics_more_info' );
				if ( ! is_array( $thirdparty ) ) {
					$thirdparty = [];
				}
				if ( in_array( 'ip-addresses-blocked', $thirdparty, true ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if this website shares data with third parties, used for recommendations, cookiebanner check and canada policies
		 *
		 * @return bool
		 */

		public function site_shares_data() {
			//TCF always shares data
			if ( cmplz_tcf_active() ) {
				return true;
			}

			if ( $this->uses_google_tagmanager() ) {
				return true;
			}

			if ( cmplz_uses_marketing_cookies() ) {
				return true;
			}

			/**
			 * Script Center
			 */
			$blocked_scripts     = cmplz_get_transient( 'cmplz_blocked_scripts' );
			$blocked_scripts     = $blocked_scripts ?: COMPLIANZ::$cookie_blocker->blocked_scripts();
			$thirdparty_scripts  = is_array( $blocked_scripts ) && count( $blocked_scripts ) > 0;
			$ad_cookies          = ( cmplz_get_option( 'uses_ad_cookies' ) === 'yes' ) ? true : false;
			$social_media        = ( cmplz_get_option( 'uses_social_media' ) === 'yes' ) ? true : false;
			$thirdparty_services = ( cmplz_get_option( 'uses_thirdparty_services' ) === 'yes' ) ? true : false;
			if (
				$thirdparty_scripts
				|| $ad_cookies
				|| $social_media
				|| $thirdparty_services
			) {
				return true;
			}

			//get all used cookies
			$args = array(
				'isTranslationFrom' => false,
				'ignored'           => false,
			);

			if ( ! $this->statistics_privacy_friendly() ) {
				return true;
			}
			$cookies = $this->get_cookies( $args );
			if ( empty( $cookies ) ) {
				return false;
			}

			foreach ( $cookies as $cookie ) {
				$service = new CMPLZ_SERVICE( $cookie->serviceID );
				if ( $service->secondParty || $service->thirdParty ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if non functional cookies are used on this site
		 * @return bool
		 */
		public function uses_non_functional_cookies() {
			if ( $this->uses_google_tagmanager() ) {
				return true;
			}

			//get all used cookies
			$args    = array(
				'isTranslationFrom' => false,
				'ignored'           => false,
			);
			$cookies = $this->get_cookies( $args );
			if ( empty( $cookies ) ) {
				return false;
			}

			foreach ( $cookies as $cookie ) {
				$cookie_service = ! empty( $cookie->service ) ? COMPLIANZ::$cookie_blocker->sanitize_service_name( $cookie->service ) : '';
				$has_optinstats = cmplz_uses_consenttype( 'optinstats' );
				if ( $cookie_service === 'google-analytics'
					 || $cookie_service === 'matomo'
				) {
					if ( $has_optinstats ) {
						return true;
					}
					if ( ! $this->statistics_privacy_friendly() ) {
						return true;
					}
				}
				if ( strpos( strtolower( $cookie->purpose ), 'functional' ) === false ) {
					return true;
				}
			}

			return false;
		}


		public function uses_only_functional_cookies() {
			return ! $this->uses_non_functional_cookies();
		}


		/**
		 * Check if this website uses cookies from a specific service
		 *
		 * @param $service
		 *
		 * @return bool
		 */

		public function site_uses_cookie_from_service( $service ) {
			$args    = array(
				'isTranslationFrom' => false,
				'ignored'           => false,
			);
			$cookies = $this->get_cookies( $args );
			if ( ! empty( $cookies ) ) {
				foreach ( $cookies as $cookie_name => $label ) {
					//get identifier for this cookie name
					$cookie         = new CMPLZ_COOKIE( $cookie_name );
					$cookie_service = COMPLIANZ::$cookie_blocker->sanitize_service_name( $cookie->service );
					if ( $service === $cookie_service ) {
						return true;
					}
				}
			}

			return false;
		}
	}

} //class closure
