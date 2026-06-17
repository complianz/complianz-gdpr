<?php
/**
 * Class scan.
 *
 * @package Complianz
 */

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'cmplz_scan' ) ) {
	// phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital, PEAR.NamingConventions.ValidClassName.Invalid, Squiz.Commenting.ClassComment.Missing
	class cmplz_scan {
	// phpcs:enable PEAR.NamingConventions.ValidClassName.StartWithCapital, PEAR.NamingConventions.ValidClassName.Invalid, Squiz.Commenting.ClassComment.Missing

		/** Singleton instance.
		 *
		 * @var self
		 */
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die(
					sprintf(
						'%s is a singleton class and you cannot create a second instance.',
						get_class( $this )
					)
				);
			}
			self::$_this = $this;
			if ( cmplz_scan_in_progress() ) {
				add_action( 'wp_print_footer_scripts', array( $this, 'test_cookies' ), PHP_INT_MAX, 2 );
			}

			add_action( 'cmplz_every_day_hook', array( $this, 'track_cookie_changes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_footer', array( $this, 'run_cookie_scan' ) );
			add_filter( 'cmplz_do_action', array( $this, 'get_scan_progress' ), 10, 3 );
			add_filter( 'cmplz_do_action', array( $this, 'reset_scan' ), 11, 3 );
			add_filter( 'cmplz_every_day_hook', array( $this, 'background_remote_scan' ), 11, 3 );
			add_action( 'admin_init', array( $this, 'register_scan_post_columns' ) );
			add_filter( 'cmplz_warning_types', array( $this, 'add_scan_upsell_warnings' ) );
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * If the remote scan is active, or has started, and we're not on a complianz page, run this on cron in the background
		 *
		 * @return void
		 */
		public function background_remote_scan() {

			if ( ! wp_doing_cron() ) {
				return;
			}

			if ( isset( $_GET['page'] ) && 'complianz' === $_GET['page'] ) {
				return;
			}

			$url = $this->get_next_page_url();
			if ( ! $url ) {
				return;
			}

			if ( 'remote' === $url && ! COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
				// As the wsc cookie scan has a wait of 10 seconds on each request, we do this on cron.
				do_action( 'cmplz_remote_cookie_scan' );
			}
		}

		/**
		 * Check if there are any new cookies added
		 */
		public function track_cookie_changes() {
			// Cron exception: this runs on cmplz_every_day_hook — without it the
			// daily cookie change check can never fire (cron has no user).
			if ( ! cmplz_user_can_manage() && ! wp_doing_cron() ) {
				return;
			}

			// only run if all pages are scanned.
			if ( ! $this->scan_complete() ) {
				return;
			}
			// check if anything was changed.
			$new_cookies = COMPLIANZ::$banner_loader->get_cookies( array( 'new' => true ) );
			if ( count( $new_cookies ) > 0 ) {
				$this->set_cookies_changed();
			}
		}

		/**
		 * Set the cookies as having been changed
		 */
		public function set_cookies_changed() {
			update_option( 'cmplz_changed_cookies', 1, false );
		}

		/**
		 * Check if cookies have been changed
		 *
		 * @return bool
		 */
		public function cookies_changed() {
			return ( get_option( 'cmplz_changed_cookies' ) == 1 );
		}

		/**
		 * Delete the transient that contains the pages list
		 *
		 * @param int  $post_id     Post ID being saved (unused, required by hook signature).
		 * @param bool $post_after  Post state after save (unused).
		 * @param bool $post_before Post state before save (unused).
		 */
		public function clear_pages_list( int $post_id, $post_after = false, $post_before = false ) {
			delete_transient( 'cmplz_pages_list' );
		}

		/**
		 * Clean up duplicate cookie names
		 *
		 * @return void
		 */
		public function clear_double_cookienames() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			global $wpdb;

			$languages = COMPLIANZ::$banner_loader->get_supported_languages();
			// first, delete all cookies with a language not in the $languages array.
			$wpdb->query( "DELETE from {$wpdb->prefix}cmplz_cookies where language NOT IN ('" . implode( "','", $languages ) . "')" );
			foreach ( $languages as $language ) {
				$settings = array(
					'language'      => $language,
					'isMembersOnly' => 'all',
				);
				$cookies  = COMPLIANZ::$banner_loader->get_cookies( $settings );
				foreach ( $cookies as $cookie ) {
					$same_name_cookies
						= $wpdb->get_results(
							$wpdb->prepare(
								"select * from {$wpdb->prefix}cmplz_cookies where name = %s and language = %s and serviceID = %s ",
								$cookie->name,
								$language,
								$cookie->serviceID
							)
						);
					if ( count( $same_name_cookies ) > 1 ) {
						array_shift( $same_name_cookies );
						$IDS = wp_list_pluck( $same_name_cookies, 'ID' );
						$sql = implode( ' OR ID =', $IDS );
						$sql = "DELETE from {$wpdb->prefix}cmplz_cookies where ID=" . $sql;
						$wpdb->query( $sql );
					}
				}
				$settings = array(
					'language' => $language,
				);
				$services = COMPLIANZ::$banner_loader->get_services( $settings );
				foreach ( $services as $service ) {
					$same_name_services = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_services where name = %s and language = %s", $service->name, $language ) );
					if ( count( $same_name_services ) > 1 ) {
						array_shift( $same_name_services );
						$IDS = wp_list_pluck( $same_name_services, 'ID' );
						$sql = implode( ' OR ID =', $IDS );
						$sql = "DELETE from {$wpdb->prefix}cmplz_services where ID=" . $sql;
						$wpdb->query( $sql );
					}
				}
			}
		}

		/**
		 * Here we add scripts and styles for the wysywig editor on the backend
		 *
		 * @param string $hook Current admin page hook suffix.
		 * */
		public function enqueue_admin_assets( $hook ) {
			if ( isset( $_GET['page'] ) && 'complianz' === $_GET['page'] ) {
				// script to check for ad blockers.
				wp_enqueue_script( 'cmplz-ad-checker', CMPLZ_URL . 'assets/js/ads.js', array(), CMPLZ_VERSION, true );
			}

			if ( 'edit.php' === $hook ) {
				wp_add_inline_style(
					'wp-admin',
					// Cell wrapper.
					'.column-cmplz_scan{width:26ch;}'
					. '.cmplz-scan-cell{display:flex;align-items:center;flex-wrap:wrap;gap:6px 8px;min-height:28px;}'
					// Status pill.
					. '.cmplz-scan-status{display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:500;line-height:1.4;white-space:nowrap;}'
					. '.cmplz-scan-status span{width:7px;height:7px;border-radius:50%;flex-shrink:0;}'
					. '.cmplz-scan-status--done{color:#166534;background:#dcfce7;}'
					. '.cmplz-scan-status--done span{background:#16a34a;}'
					. '.cmplz-scan-status--queued{color:#92400e;background:#fef3c7;}'
					. '.cmplz-scan-status--queued span{background:#f59e0b;}'
					. '.cmplz-scan-status--pending{color:#475569;background:#f1f5f9;}'
					. '.cmplz-scan-status--pending span{background:#94a3b8;}'
					// Scan button compact.
					. '.cmplz-scan-cell .button-small{height:24px;min-height:24px;padding:0 8px;font-size:12px;line-height:22px;}'
					// Tooltip.
					. '.cmplz-has-tip{position:relative;}'
					. '.cmplz-has-tip::after{content:attr(data-cmplz-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);'
					. 'background:#1d2327;color:#fff;font-size:12px;line-height:1.4;white-space:normal;max-width:18ch;text-align:center;padding:4px 8px;border-radius:3px;'
					. 'pointer-events:none;opacity:0;transition:opacity .15s;z-index:9999;}'
					. '.cmplz-has-tip:hover::after{opacity:1;}'
					// Scan error inline message.
					. '.cmplz-scan-error{display:block;width:100%;font-size:11px;color:#b91c1c;margin-top:2px;}'
				);
			}
		}

		/**
		 * Get all cookies, and post back to site with ajax.
		 * This script is only inserted when a valid token is passed, so will never run for other visitors than the site admin
		 * */
		public function test_cookies() {
			if ( $this->scan_complete() ) {
				return;
			}

			if ( ! isset( $_GET['complianz_scan_token'] ) || ! isset( $_GET['complianz_id'] ) ) {
				return;
			}

			$token      = sanitize_title( $_GET['complianz_scan_token'] );
			$id         = sanitize_title( $_GET['complianz_id'] );
			$admin_url  = esc_url_raw( rest_url( 'complianz/v1/' ) );
			$nonce      = wp_create_nonce( 'wp_rest' );
			$javascript = cmplz_get_template( 'test-cookies.js' );
			$javascript = str_replace(
				array(
					'{admin_url}',
					'{token}',
					'{id}',
					'{nonce}',
				),
				array(
					esc_url_raw( $admin_url ),
					esc_attr( $token ),
					esc_attr( $id ),
					$nonce,
				),
				$javascript
			);
			?>
			<script>
				<?php echo $javascript; ?>
			</script>
			<?php
		}

		/**
		 * Insert an iframe to retrieve front-end cookies
		 * */
		public function run_cookie_scan(): void {
			if ( ! cmplz_admin_logged_in() ) {
				return;
			}

			if ( get_option( 'cmplz_activation_time' ) > strtotime( '-30 minutes' ) ) {
				return;
			}

			if ( defined( 'CMPLZ_DO_NOT_SCAN' ) && CMPLZ_DO_NOT_SCAN ) {
				return;
			}

			if ( ! cmplz_wsc_auth::wsc_is_authenticated() ) {
				return;
			}

			if ( isset( $_GET['complianz_scan_token'] ) ) {
				return;
			}
			// if the last cookie scan date is more than a month ago, we re-scan.
			$last_scan_date = COMPLIANZ::$banner_loader->get_last_cookie_scan_date( true );
			$scan_interval  = apply_filters( 'cmplz_scan_interval', 3 );
			$one_month_ago  = strtotime( '-' . $scan_interval . ' month' );
			if (
				( $one_month_ago > $last_scan_date )
				&& $this->scan_complete()
				&& ! $this->automatic_cookiescan_disabled()
			) {
				$this->reset_pages_list();
			}

			if ( ! $this->scan_complete() ) {
				if ( ! get_option( 'cmplz_synced_cookiedatabase_once' ) ) {
					update_option( 'cmplz_sync_cookies_complete', false );
					update_option( 'cmplz_sync_cookies_after_services_complete', false );
					update_option( 'cmplz_sync_services_complete', false );
					update_option( 'cmplz_synced_cookiedatabase_once', true );
				}

				// store the date.
				$timezone_offset = get_option( 'gmt_offset' );
				$time            = time() + ( 60 * 60 * $timezone_offset );
				update_option( 'cmplz_last_cookie_scan', $time );

				$url = $this->get_next_page_url();
				if ( ! $url || 'remote' === $url ) {
					return;
				}

				if ( strpos( $url, 'complianz_id' ) !== false ) {
					// get the html of this page.
					$response = wp_remote_get( $url );
					if ( ! is_wp_error( $response ) ) {
						$html = $response['body'];
						$this->parse_html( $html );
					}
				}

				// load in iframe so the scripts run.
				echo '<iframe id="cmplz_cookie_scan_frame" class="hidden" src="' . $url . '"></iframe>';
			}
		}

		private function parse_html( $html ) {
			$stored_social_media = cmplz_scan_detected_social_media();
			if ( ! $stored_social_media ) {
				$stored_social_media = array();
			}
			$social_media = COMPLIANZ::$banner_loader->parse_for_social_media( $html );
			$social_media = array_unique( array_merge( $stored_social_media, $social_media ), SORT_REGULAR );
			update_option( 'cmplz_detected_social_media', $social_media );

			$stored_thirdparty_services = cmplz_scan_detected_thirdparty_services();
			if ( ! $stored_thirdparty_services ) {
				$stored_thirdparty_services = array();
			}
			$thirdparty = $this->parse_for_thirdparty_services( $html );
			$thirdparty = array_unique( array_merge( $stored_thirdparty_services, $thirdparty ), SORT_REGULAR );
			update_option( 'cmplz_detected_thirdparty_services', $thirdparty );

			// parse for google analytics and tagmanager, but only if the wizard wasn't completed before.
			// with this data we prefill the settings and give warnings when tracking is doubled.
			if ( ! COMPLIANZ::$banner_loader->wizard_completed_once() ) {
				$this->parse_for_statistics_settings( $html );
			}

			if ( preg_match_all( '/ga\.js/', $html ) > 1
				|| preg_match_all( '/analytics\.js/', $html ) > 1
				|| preg_match_all( '/googletagmanager\.com\/gtm\.js/', $html ) > 1
				|| preg_match_all( '/piwik\.js/', $html ) > 1
				|| preg_match_all( '/matomo\.js/', $html ) > 1
				|| preg_match_all( '/getclicky\.com\/js/', $html ) > 1
				|| preg_match_all( '/mc\.yandex\.ru\/metrika\/watch\.js/', $html ) > 1
			) {
				update_option( 'cmplz_double_stats', true );
			} else {
				delete_option( 'cmplz_double_stats' );
			}

			$stored_stats = cmplz_scan_detected_stats();
			if ( ! $stored_stats ) {
				$stored_stats = array();
			}
			$stats = $this->parse_for_stats( $html );
			$stats = array_unique( array_merge( $stored_stats, $stats ), SORT_REGULAR );
			update_option( 'cmplz_detected_stats', $stats );
		}

		/**
		 * Check a string for statistics
		 *
		 * @param string $html       HTML to parse for statistics markers.
		 * @param bool   $single_key Return a single string instead of an array.
		 *
		 * @return array|string $thirdparty
		 * */
		public function parse_for_stats( $html, $single_key = false ) {
			$stats         = array();
			$stats_markers = COMPLIANZ::$config->stats_markers;
			foreach ( $stats_markers as $key => $markers ) {
				foreach ( $markers as $marker ) {
					if ( $single_key && strpos( $html, $marker ) !== false ) {
						return $key;
					}

					if ( strpos( $html, $marker ) !== false && ! in_array( $key, $stats ) ) {
						if ( $single_key ) {
							return $key;
						}
						$stats[] = $key;
					}
				}
			}
			if ( $single_key ) {
				return false;
			}

			return $stats;
		}

		/**
		 * Run once to retrieve the settings for most used stats tools
		 *
		 * @param string $html HTML to parse for statistics tool settings.
		 */
		private function parse_for_statistics_settings( $html ) {

			if ( strpos( $html, 'gtm.js' ) !== false || strpos( $html, 'gtm.start' ) !== false
			) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/(\'|")(GTM-[A-Z]{7})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option_no_hooks( 'gtm_code', sanitize_text_field( $matches[2] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option( 'compile_statistics', 'google-tag-manager' );
				}
			}

			if ( strpos( $html, 'analytics.js' ) !== false || strpos( $html, 'ga.js' ) !== false || strpos( $html, '_getTracker' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/(\'|")(UA-[0-9]{8}-[0-9]{1})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option( 'ua_code', sanitize_text_field( $matches[2] ) );
					cmplz_update_option( 'compile_statistics', 'google-analytics' );
				}

				// gtag.
				$pattern = '/(\'|")(G-[0-9a-zA-Z]{10})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option( 'ua_code', sanitize_text_field( $matches[2] ) );
					cmplz_update_option( 'compile_statistics', 'google-analytics' );
				}
				$pattern = '/\'anonymizeIp|anonymize_ip\'|:[ ]{0,1}true/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches ) {
					$value = cmplz_get_option( 'compile_statistics_more_info' );
					if ( ! is_array( $value ) ) {
						$value = array();
					}
					if ( ! in_array( 'ip-addresses-blocked', $value, true ) ) {
						$value[] = 'ip-addresses-blocked';
					}
					cmplz_update_option( 'compile_statistics_more_info', $value );
				}
			}

			if ( strpos( $html, 'piwik.js' ) !== false || strpos( $html, 'matomo.js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );
				$pattern = '/(var u=")((https|http):\/\/.*?)"/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option( 'matomo_url', sanitize_text_field( $matches[2] ) );
					update_option( 'cmplz_detected_stats_data', true );
				}

				$pattern = '/\[\'setSiteId\', \'([0-9]){1,3}\'\]\)/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option( 'matomo_site_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
				}

				cmplz_update_option( 'compile_statistics', 'matomo' );
			}

			if ( strpos( $html, 'static.getclicky.com/js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/clicky_site_ids\.push\(([0-9]{1,3})\)/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option( 'clicky_site_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option( 'compile_statistics', 'clicky' );
				}
			}

			if ( strpos( $html, 'mc.yandex.ru/metrika/watch.js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/w.yaCounter([0-9]{1,10}) = new/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option( 'yandex_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option( 'compile_statistics', 'yandex' );
				}
			}
		}

		/**
		 * Check a string for third party services
		 *
		 * @param string $html       HTML to parse for third-party service markers.
		 * @param bool   $single_key Return a single string instead of an array.
		 *
		 * @return array|string $thirdparty
		 * */
		public function parse_for_thirdparty_services( $html, $single_key = false ) {
			$thirdparty         = array();
			$thirdparty_markers = COMPLIANZ::$config->thirdparty_service_markers;
			foreach ( $thirdparty_markers as $key => $markers ) {
				foreach ( $markers as $marker ) {
					if ( $single_key && strpos( $html, $marker ) !== false ) {
						return $key;
					}

					if ( strpos( $html, $marker ) !== false && ! in_array( $key, $thirdparty ) ) {
						$thirdparty[] = $key;
					}
				}
			}
			if ( $single_key ) {
				return false;
			}

			return $thirdparty;
		}

		private function get_next_page_url() {
			// Cron has no logged-in user but background_remote_scan() depends on
			// this method — without the cron exception the daily background
			// remote scan can never fire.
			if ( ! cmplz_user_can_manage() && ! wp_doing_cron() ) {
				return '';
			}
			$token = wp_create_nonce( 'complianz_scan_token' );
			$pages = array_filter( $this->pages_to_process() );
			if ( count( $pages ) === 0 ) {
				return false;
			}
			$id_to_process = reset( $pages );

			// in case of remote, we want to wait until the process has completed before moving on to the next.
			if ( 'remote' !== $id_to_process ) {
				$this->set_page_as_processed( $id_to_process );
			} elseif ( COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
				$this->set_page_as_processed( $id_to_process );
			}

			switch ( $id_to_process ) {
				case 'remote':
					return 'remote';
				case 'home':
					$url = home_url();
					break;
				case 'loginpage':
					$url = wp_login_url();
					break;
				default:
					$url = get_permalink( $id_to_process );
			}
			$url = add_query_arg(
				array(
					'complianz_scan_token' => $token,
					'complianz_id'         => $id_to_process,
				),
				$url
			);
			if ( is_ssl() ) {
				$url = str_replace( 'http://', 'https://', $url );
			}

			return apply_filters( 'cmplz_next_page_url', $url );
		}

		/**
		 * Get the list of posttypes to process
		 *
		 * @return array
		 */
		public function get_scannable_post_types(): array {
			$post_types = array( 'post', 'page' );
			return apply_filters( 'cmplz_cookiescan_post_types', $post_types );
		}

		/**
		 * All public post types eligible for scanning — the selectable universe,
		 * before any free/premium or user-selection narrowing.
		 *
		 * Not to be confused with get_scannable_post_types(), which returns the
		 * currently active set.
		 * Media-only and Complianz-internal post types are excluded as they
		 * produce meaningless cookie scan results.
		 *
		 * @param bool $with_labels true: slug => plural label (field options); false: slug list.
		 * @return array
		 */
		public function get_public_scannable_post_types( bool $with_labels = false ): array {
			$all = get_post_types( array( 'public' => true ), 'objects' );

			/**
			 * Filters the post types excluded from the scannable universe.
			 *
			 * Excluded types disappear from the "Post types to scan" field options
			 * and from every scan surface (local scanner, WSC batch, scan column).
			 *
			 * @param string[] $excluded Post type slugs to exclude.
			 */
			$excluded = apply_filters(
				'cmplz_scan_excluded_post_types',
				array( 'attachment', 'elementor_font', 'cmplz-dataleak', 'cmplz-processing', 'cookie' )
			);

			foreach ( $excluded as $slug ) {
				unset( $all[ $slug ] );
			}

			if ( ! $with_labels ) {
				return array_keys( $all );
			}

			$options = array();
			foreach ( $all as $slug => $post_type_object ) {
				$options[ $slug ] = $post_type_object->labels->name ?? $slug;
			}
			return $options;
		}

		/**
		 * Return WooCommerce and EDD page IDs to exclude from the local scanner batch.
		 * These pages are handled as fixed pages in pro; in free they must not enter
		 * the generic page batch so the upsell notice remains accurate.
		 *
		 * @return int[]
		 */
		private function get_webshop_page_ids(): array {
			$ids = array();

			if ( class_exists( 'WooCommerce' ) ) {
				$ids = array_merge(
					$ids,
					array_filter(
						array_map(
							'intval',
							array(
								get_option( 'woocommerce_shop_page_id' ),
								get_option( 'woocommerce_cart_page_id' ),
								get_option( 'woocommerce_checkout_page_id' ),
								get_option( 'woocommerce_myaccount_page_id' ),
							)
						)
					)
				);
			}

			if ( class_exists( 'Easy_Digital_Downloads' ) && function_exists( 'edd_get_option' ) ) {
				$ids = array_merge(
					$ids,
					array_filter(
						array_map(
							'intval',
							array(
								edd_get_option( 'purchase_page' ),
								edd_get_option( 'success_page' ),
								edd_get_option( 'failure_page' ),
								edd_get_option( 'purchase_history_page' ),
							)
						)
					)
				);
			}

			return array_values( $ids );
		}

		/**
		 * Get fixed (non-post) pages to include in every scan run.
		 *
		 * 'remote' is only appended when WSC is authenticated; callers that
		 * previously hard-coded ['home','remote'] should use this method.
		 *
		 * @return array
		 */
		private function get_fixed_pages(): array {
			$pages = array( 'home' );
			if ( cmplz_wsc_auth::wsc_is_authenticated() ) {
				$pages[] = 'remote';
			}
			if ( cmplz_get_option( 'wp_admin_access_users' ) === 'yes' ) {
				$pages[] = 'loginpage';
			}
			return apply_filters( 'cmplz_scan_fixed_pages', $pages );
		}

		/**
		 *
		 * Get list of page id's that we want to process this set of scan requests, which weren't included in the scan before
		 *
		 * @return array $pages
		 * *@since 1.0
		 */
		public function get_pages_list_single_run() {
			// Cron exception: background_remote_scan() needs the list to find
			// the 'remote' sentinel; cron requests have no logged-in user.
			if ( ! cmplz_user_can_manage() && ! wp_doing_cron() ) {
				return array();
			}
			$posts = get_transient( 'cmplz_pages_list' );
			if ( ! $posts ) {
				$posts           = $this->get_fixed_pages();
				$post_types      = $this->get_scannable_post_types();
				$not_in          = $this->get_webshop_page_ids();
				$representatives = array();
				$batch_size      = apply_filters( 'cmplz_scan_batch_size', 5 );

				// One post per type first — fast representative sample.
				foreach ( $post_types as $post_type ) {
					$args            = array(
						'post__not_in'   => $not_in,
						'post_type'      => $post_type,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'meta_query'     => array(
							array(
								'key'     => '_cmplz_scanned_post',
								'compare' => 'NOT EXISTS',
							),
						),
					);
					$new_posts       = get_posts( $args );
					$representatives = array_merge( $representatives, $new_posts );
					$not_in          = array_merge( $not_in, $new_posts );
				}

				$posts = array_merge( $posts, $representatives );

				// Bulk batch per type, skipping the representative posts.
				foreach ( $post_types as $post_type ) {
					$args = apply_filters(
						'cmplz_scan_post_args',
						array(
							'post__not_in'   => $not_in,
							'post_type'      => $post_type,
							'posts_per_page' => $batch_size,
							'fields'         => 'ids',
							'meta_query'     => array(
								array(
									'key'     => '_cmplz_scanned_post',
									'compare' => 'NOT EXISTS',
								),
							),
						),
						$post_type
					);
					// Non-overridable structural constraints — re-enforced after filter.
					$args['fields']       = 'ids';
					$args['post_type']    = $post_type;
					$args['post__not_in'] = $not_in;
					$args['meta_query']   = array(
						array(
							'key'     => '_cmplz_scanned_post',
							'compare' => 'NOT EXISTS',
						),
					);
					$new_posts            = get_posts( $args );
					$posts                = array_merge( $posts, $new_posts );
				}

				if ( count( $posts ) === 0 && ! $this->automatic_cookiescan_disabled() ) {
					/*
					 * No posts found — all batches exhausted.  Reset meta so the
					 * next scan attempt picks them up again.
					 */
					$this->reset_scanned_post_batches();
					$this->reset_pages_list();
				} else {
					foreach ( $posts as $post_id ) {
						if ( is_int( $post_id ) ) {
							update_post_meta( $post_id, '_cmplz_scanned_post', true );
						}
					}
				}

				set_transient( 'cmplz_pages_list', $posts, MONTH_IN_SECONDS );
			}

			return array_filter( $posts );
		}

		/**
		 * Reset the list of pages
		 *
		 * @param bool $delay  Delay the restart of the scan cycle.
		 * @param bool $manual Manual reset always resets; automatic reset is skipped when the automatic scan is disabled.
		 *
		 * @return void
		 *
		 * @since 2.1.5
		 */
		public function reset_pages_list( $delay = false, $manual = false ) {

			if ( ! $manual && $this->automatic_cookiescan_disabled() ) {
				return;
			}

			if ( $manual ) {
				$this->reset_scanned_post_batches();
			}

			if ( $delay ) {
				$current_list    = get_transient( 'cmplz_pages_list' );
				$processed_pages = get_transient( 'cmplz_processed_pages_list' );
				set_transient( 'cmplz_pages_list', $current_list, HOUR_IN_SECONDS );
				set_transient( 'cmplz_processed_pages_list', $processed_pages, HOUR_IN_SECONDS );

			} else {
				delete_transient( 'cmplz_pages_list' );
				delete_transient( 'cmplz_processed_pages_list' );
			}
		}

		/**
		 * The scanned post meta is used to create batches of posts. A batch that is being processed is set to scanned.
		 * This is only reset when all posts have been processed, or if user has disabled automatic scanning, and the manual scan is fired.
		 * */
		public function reset_scanned_post_batches() {
			if ( ! function_exists( 'delete_post_meta_by_key' ) ) {
				require_once ABSPATH . WPINC . '/post.php';
			}
			delete_post_meta_by_key( '_cmplz_scanned_post' );
		}

		/**
		 * Check if the automatic scan is disabled
		 *
		 * @return bool
		 */
		public function automatic_cookiescan_disabled() {
			return cmplz_get_option( 'disable_automatic_cookiescan' ) == 1;
		}


		/**
		 * Get list of pages that were processed before
		 *
		 * @return array $pages
		 */
		public function get_processed_pages_list() {

			$pages = get_transient( 'cmplz_processed_pages_list' );
			if ( ! is_array( $pages ) ) {
				$pages = array();
			}

			return array_filter( $pages );
		}

		/**
		 * Check if the scan is complete
		 *
		 * @return bool
		 * @since 1.0
		 * */
		public function scan_complete() {
			$pages = array_filter( $this->pages_to_process() );
			return count( $pages ) === 0;
		}

		/**
		 *
		 * Get list of pages that still have to be processed
		 *
		 * @return array
		 * @since 1.0
		 */
		private function pages_to_process(): array {

			$pages_list           = $this->get_pages_list_single_run();
			$processed_pages_list = $this->get_processed_pages_list();
			return array_diff( $pages_list, $processed_pages_list );
		}

		/**
		 * Set a page as being processed
		 *
		 * @param int|string $id Post ID or fixed-page sentinel ('home', 'loginpage', 'remote').
		 *
		 * @return void
		 * @since 1.0
		 */
		public function set_page_as_processed( $id ): void {
			// Cron exception: when the background remote scan completes, the
			// 'remote' sentinel must be markable as processed from cron context.
			if ( ! cmplz_user_can_manage() && ! wp_doing_cron() ) {
				return;
			}

			if ( 'home' !== $id && 'loginpage' !== $id && 'remote' !== $id && ! is_numeric( $id ) ) {
				return;
			}

			// Normalize numeric IDs to int so strict in_array works regardless of
			// whether the caller passed a string (REST param) or an integer.
			if ( is_numeric( $id ) ) {
				$id = (int) $id;
			}

			$pages = $this->get_processed_pages_list();
			if ( ! in_array( $id, $pages, true ) ) {
				$pages[]    = $id;
				$expiration = $this->automatic_cookiescan_disabled() ? 10 * YEAR_IN_SECONDS : MONTH_IN_SECONDS;
				set_transient( 'cmplz_processed_pages_list', $pages, $expiration );
			}
		}

		/**
		 * Update the cookie policy date
		 */
		public function update_cookie_policy_date() {
			update_option( 'cmplz_publish_date', time() );

			// also reset the email notification, so it will get sent next year.
			update_option( 'cmplz_update_legal_documents_mail_sent', false );
		}

		/**
		 * Get progress of the current scan to output with ajax
		 *
		 * @param array           $data    Response data passed along the cmplz_do_action filter.
		 * @param string          $action  Requested action; this handler responds to 'scan'.
		 * @param WP_REST_Request $request Incoming REST request.
		 *
		 * @return array
		 */
		public function get_scan_progress( array $data, string $action, WP_REST_Request $request ): array {
			if ( ! cmplz_user_can_manage() ) {
				return array();
			}

			if ( 'get_scan_progress' !== $action ) {
				return $data;
			}

			$data = array(
				'progress'  => 0,
				'next_page' => false,
				'cookies'   => array(),
				'token'     => '',
			);

			if ( ! cmplz_wsc_auth::wsc_is_authenticated() ) {
				return $data;
			}

			$timezone_offset = get_option( 'gmt_offset' );
			$time            = time() + ( 60 * 60 * $timezone_offset );

			update_option( 'cmplz_last_cookie_scan', $time );

			$next_url = $this->get_next_page_url();

			if ( 'remote' === $next_url ) {
				do_action( 'cmplz_remote_cookie_scan' );
				// only proceed to next page if remote scan is complete.
				if ( COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
					$next_url = $this->get_next_page_url();
				} else {
					// Don't return 'remote' to React app - it will create iframe with src="remote".
					// Return a data URL that won't cause network requests or 404 errors.
					$next_url = 'data:text/html,<html><body>WSC scan in progress...</body></html>';

					// Mark 'remote' as processed so we can move to next page, but only when NOT in cron context - although it might not be necessary.
					if ( ! wp_doing_cron() ) {
						$this->set_page_as_processed( 'remote' );
					}
				}
			} elseif ( false !== strpos( $next_url, 'complianz_id' ) ) {
				$response = wp_remote_get( $next_url );
				if ( ! is_wp_error( $response ) ) {
					$html = $response['body'];
					$this->parse_html( $html );
				}
			}

			$this->clear_double_cookienames();

			$cookies  = COMPLIANZ::$banner_loader->get_cookies();
			$progress = $this->get_progress_count();
			$total    = count( $cookies );
			$current  = (int) ( $progress / 100 * $total );
			$cookies  = array_slice( $cookies, 0, $current );
			$cookies  = count( $cookies ) > 0 ? wp_list_pluck( $cookies, 'name' ) : array();

			$data['progress']  = $progress;
			$data['next_page'] = $next_url;
			$data['cookies']   = $cookies;
			$data['token']     = wp_create_nonce( 'complianz_scan_token' );

			return $data;
		}

		/**
		 * Rescan after a manual "rescan" command from the user
		 *
		 * @param array           $data    Response data passed along the cmplz_do_action filter.
		 * @param string          $action  Requested action; this handler responds to 'scan'.
		 * @param WP_REST_Request $request Incoming REST request.
		 * @return array
		 */
		public function reset_scan( $data, $action, $request ) {
			if ( ! cmplz_user_can_manage() ) {
				return array();
			}

			if ( 'scan' === $action ) {
				$scan_type = sanitize_title( $request->get_param( 'scan_action' ) );
				if ( 'reset' === $scan_type ) {
					global $wpdb;
					$table_names = array( $wpdb->prefix . 'cmplz_cookies' );
					foreach ( $table_names as $table_name ) {
						if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
							$wpdb->query( "TRUNCATE TABLE $table_name" );
						}
					}
					update_option( 'cmplz_detected_social_media', false );
					update_option( 'cmplz_detected_thirdparty_services', false );
					update_option( 'cmplz_detected_stats', false );
				}

				if ( 'reset' === $scan_type || 'restart' === $scan_type ) {
					COMPLIANZ::$wsc_scanner->wsc_scan_reset();
					do_action( 'cmplz_scan_reset' );
					$this->reset_pages_list( false, true );
					COMPLIANZ::$sync->resync();
				}

				$data = array();
			}
			return $data;
		}

		/**
		 * Get progress of the scan in percentage
		 *
		 * @return float
		 */
		public function get_progress_count() {

			$remote_scan_total    = 100;
			$remote_scan_progress = COMPLIANZ::$wsc_scanner->wsc_scan_progress();

			$local_done  = count( $this->get_processed_pages_list() );
			$local_total = count( $this->get_pages_list_single_run() );

			// convert local to a 100 scale
			// prevent division by zero.
			$local_total = 0 === $local_total ? $local_done : $local_total;
			$local_done  = 100 * ( $local_done / $local_total );

			$total = 200;
			$done  = $remote_scan_progress + $local_done;

			$progress = 100 * ( $done / $total );
			if ( $progress > 100 ) {
				$progress = 100;
			}

			return $progress;
		}

		// ── Post list column ──────────────────────────────────────────────────────

		/**
		 * Register the Cookie Scan column for all scannable post types.
		 *
		 * Runs on admin_init (free + premium). Pro extends the list of scannable
		 * post types via the cmplz_cookiescan_post_types filter before this fires.
		 */
		public function register_scan_post_columns(): void {
			foreach ( $this->get_scannable_post_types() as $post_type ) {
				add_filter(
					"manage_{$post_type}_posts_columns",
					static function ( array $columns ): array {
						$columns['cmplz_scan'] = __( 'Complianz Website Scan', 'complianz-gdpr' );
						return $columns;
					}
				);
				add_action(
					"manage_{$post_type}_posts_custom_column",
					array( $this, 'render_scan_post_column' ),
					10,
					2
				);
			}
		}

		/**
		 * Render the Cookie Scan column cell.
		 *
		 * Handles: column guard, publish check, status badge.
		 * Button rendering is delegated via two hooks so free code has zero pro references:
		 *   - cmplz_scan_column_wsc_state filter  → pro populates WSC done/inflight/cooldown state.
		 *   - cmplz_render_scan_column_button action → pro renders the full button cascade.
		 * Free-only installs: hooks unregistered → free upgrade link rendered directly.
		 *
		 * Status badges:
		 *   Scanned     — scan completed.
		 *   In progress — in scan queue.
		 *   Pending     — no scan activity.
		 *
		 * @param string $column  Column name.
		 * @param int    $post_id Post ID.
		 */
		public function render_scan_post_column( string $column, int $post_id ): void {
			if ( 'cmplz_scan' !== $column ) {
				return;
			}

			$is_published = get_post_status( $post_id ) === 'publish';
			$local_done   = (bool) get_post_meta( $post_id, '_cmplz_scanned_post', true );
			$pages_list   = get_transient( 'cmplz_pages_list' );
			$in_queue     = is_array( $pages_list ) && in_array( $post_id, $pages_list, true );

			$wsc_state = apply_filters(
				'cmplz_scan_column_wsc_state',
				array(
					'wsc_done'        => false,
					'wsc_inflight'    => false,
					'in_wsc_cooldown' => false,
					'wsc_scanned_at'  => 0,
					'in_batch_queue'  => false,
				),
				$post_id
			);

			$wsc_done        = $wsc_state['wsc_done'];
			$wsc_inflight    = $wsc_state['wsc_inflight'];
			$in_wsc_cooldown = $wsc_state['in_wsc_cooldown'];
			$in_batch_queue  = $wsc_state['in_batch_queue'];

			// ── Status label ──────────────────────────────────────────────────

			if ( $local_done || $wsc_done ) {
				$label = __( 'Scanned', 'complianz-gdpr' );
				$class = 'cmplz-scan-status--done';
			} elseif ( $in_queue || $wsc_inflight ) {
				$label = __( 'In progress', 'complianz-gdpr' );
				$class = 'cmplz-scan-status--queued';
			} elseif ( $in_batch_queue ) {
				$label = __( 'Queued', 'complianz-gdpr' );
				$class = 'cmplz-scan-status--queued';
			} else {
				$label = __( 'Pending', 'complianz-gdpr' );
				$class = 'cmplz-scan-status--pending';
			}

			echo '<div class="cmplz-scan-cell">';

			if ( $label ) {
				echo '<span class="cmplz-scan-status ' . esc_attr( $class ) . '">'
					. '<span></span>'
					. esc_html( $label )
					. '</span>';
			}

			// ── Button cascade ────────────────────────────────────────────────

			if ( ! $is_published ) {
				echo '</div>';
				return;
			}

			if ( defined( 'cmplz_free' ) ) {
				echo '<a href="' . esc_url( cmplz_get_referral_url( 'menu', 'manual-scan-column-upgrade', 'https://complianz.io/pricing/' ) ) . '" class="button button-small cmplz-has-tip" target="_blank" rel="noopener noreferrer"'
					. ' data-cmplz-tip="' . esc_attr__( 'Upgrade to Complianz Premium to enable per-post website scanning.', 'complianz-gdpr' ) . '">'
					. esc_html__( 'Upgrade', 'complianz-gdpr' )
					. '</a>';
				echo '</div>';
				return;
			}

			do_action( 'cmplz_render_scan_column_button', $post_id, $wsc_state );

			echo '</div>';
		}

		/**
		 * Single source of truth for all scan upsell entries.
		 * Ordered by priority: webshop > cpt > volume.
		 *
		 * Each entry contains all fields consumed by both the warning system
		 * (add_scan_upsell_warnings) and the React alert (get_scan_upsell_data).
		 *
		 * @return array<string, array>
		 */
		private function get_scan_upsell_catalog(): array {
			$defaults = array(
				'icon'        => 'warning',
				'icon_color'  => 'orange',
				'cta_label'   => __( 'Upgrade Now', 'complianz-gdpr' ),
				'cta_url'     => 'https://complianz.io/pricing-subpages/',
				'dismissible' => true,
			);

			return array(
				'webshop' => array_merge(
					$defaults,
					array(
						'condition'    => 'cmplz_site_has_webshop',
						'title'        => __( 'Limited scan coverage', 'complianz-gdpr' ),
						'subtitle'     => __( 'Webshop pages not included', 'complianz-gdpr' ),
						'body'         => __( 'Your webshop pages are not covered by the free Website Scan. Upgrade to cover all pages.', 'complianz-gdpr' ),
						'cta_field_id' => 'scan-site-has-webshop',
					)
				),
				'cpt'     => array_merge(
					$defaults,
					array(
						'condition'    => 'cmplz_site_has_custom_post_types',
						'title'        => __( 'Limited scan coverage', 'complianz-gdpr' ),
						'subtitle'     => __( 'Custom post types not included', 'complianz-gdpr' ),
						'body'         => __( 'You have custom post types that are not covered by the free Website Scan. Upgrade to cover all post types.', 'complianz-gdpr' ),
						'cta_field_id' => 'scan-site-has-cpt',
					)
				),
				'volume'  => array_merge(
					$defaults,
					array(
						'condition'      => 'cmplz_volume_upsell_applies',
						'title_callback' => function () {
							$count = (int) get_transient( 'cmplz_scan_post_count' );
							return $count > 0
								? sprintf( __( 'We found %d posts.', 'complianz-gdpr' ), $count )
								: __( 'Limited scan coverage', 'complianz-gdpr' );
						},
						'subtitle'       => __( "The free Website Scan can't fully cover your site.", 'complianz-gdpr' ),
						'body'           => __( 'Your free plan covers only 50 posts. Upgrade to scan your full site and automatically keep your cookie policy up to date with live cookiedatabase.org sync, avoid manual configuration and lower compliance risks.', 'complianz-gdpr' ),
						'cta_field_id'   => 'scan-site-has-volume',
					)
				),
			);
		}

		/**
		 * Return the highest-priority upsell data object for the current free site, or null.
		 * All fields are defined in the catalog so React renders without owning any copy.
		 *
		 * @return array{code:string,title:string,subtitle:string,body:string,icon:string,icon_color:string,cta_label:string,cta_url:string,cta_field_id:string}|null
		 */
		public function get_scan_upsell_data(): ?array {
			if ( ! defined( 'cmplz_free' ) || ! cmplz_free ) {
				return null;
			}
			foreach ( $this->get_scan_upsell_catalog() as $code => $entry ) {
				if ( ! function_exists( $entry['condition'] ) || ! call_user_func( $entry['condition'] ) ) {
					continue;
				}
				$title = isset( $entry['title_callback'] )
					? call_user_func( $entry['title_callback'] )
					: $entry['title'];
				return array(
					'code'         => $code,
					'title'        => $title,
					'subtitle'     => $entry['subtitle'],
					'body'         => $entry['body'],
					'icon'         => $entry['icon'],
					'icon_color'   => $entry['icon_color'],
					'cta_label'    => $entry['cta_label'],
					'cta_url'      => $entry['cta_url'],
					'cta_field_id' => $entry['cta_field_id'],
				);
			}
			return null;
		}

		/**
		 * Inject scan upsell entries into the global warning system via cmplz_warning_types filter.
		 * Only for free upgraded installs — fresh installs are excluded; catalog body and
		 * cta_url serve as the warning text and link.
		 *
		 * @param array $warnings Existing warning type definitions.
		 * @return array
		 */
		public function add_scan_upsell_warnings( array $warnings ): array {
			if ( ! defined( 'cmplz_free' ) || ! cmplz_free || cmplz_is_new_install() ) {
				return $warnings;
			}
			foreach ( $this->get_scan_upsell_catalog() as $code => $entry ) {
				$warnings[ 'scan-site-has-' . $code ] = array(
					'warning_condition'   => $entry['condition'],
					'urgent'              => $entry['body'],
					'plus_one'            => true,
					'dismissible'         => $entry['dismissible'],
					'include_in_progress' => false,
					'url'                 => $entry['cta_url'],
				);
			}
			return $warnings;
		}
	}

} //class closure
