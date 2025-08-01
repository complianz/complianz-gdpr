<?php
defined( 'ABSPATH' ) or die();

if ( ! class_exists( "cmplz_scan" ) ) {
	class cmplz_scan {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
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
			add_filter( 'cmplz_every_five_minutes_hook', array( $this, 'background_remote_scan' ), 11, 3 );
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * If the remote scan is active, or has started, and we're not on a complianz page, run this on cron in the background
		 * @return void
		 */
		public function background_remote_scan(){

			if ( !wp_doing_cron() ) {
				return;
			}

			if ( isset($_GET['page'] ) && $_GET['page'] === 'complianz' ) {
				return;
			}

			$url = $this->get_next_page_url();
			if ( ! $url ) {
				return;
			}

			if ( $url === 'remote' && !COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
				//as the wsc cookie scan has a wait of 10 seconds on each request, we do this on cron
				do_action('cmplz_remote_cookie_scan');
			}
		}

		/**
		 * Check if there are any new cookies added
		 */

		public function track_cookie_changes() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			//only run if all pages are scanned.
			if ( ! $this->scan_complete() ) {
				return;
			}
			//check if anything was changed
			$new_cookies = COMPLIANZ::$banner_loader->get_cookies( array( 'new' => true ) );
			if ( count( $new_cookies ) > 0 ) {
				$this->set_cookies_changed();
			}
		}

		/**
		 * Set the cookies as having been changed
		 */

		public function set_cookies_changed() {
			update_option( 'cmplz_changed_cookies', 1 , false);

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
		 * @param int  $post_id
		 * @param bool $post_after
		 * @param bool $post_before
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
			//first, delete all cookies with a language not in the $languages array
			$wpdb->query( "DELETE from {$wpdb->prefix}cmplz_cookies where language NOT IN ('" . implode( "','", $languages ) . "')" );
			foreach ( $languages as $language ) {
				$settings = array(
					'language'      => $language,
					'isMembersOnly' => 'all',
				);
				$cookies  = COMPLIANZ::$banner_loader->get_cookies( $settings );
				foreach ( $cookies as $cookie ) {
					$same_name_cookies
						= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where name = %s and language = %s and serviceID = %s ",
						$cookie->name, $language, $cookie->serviceID ) );
					if ( count( $same_name_cookies ) > 1 ) {
						array_shift( $same_name_cookies );
						$IDS = wp_list_pluck( $same_name_cookies, 'ID' );
						$sql = implode( ' OR ID =', $IDS );
						$sql = "DELETE from {$wpdb->prefix}cmplz_cookies where ID=" . $sql;
						$wpdb->query( $sql );
					}
				}
				$settings = array(
					'language'      => $language,
				);
				$services  = COMPLIANZ::$banner_loader->get_services( $settings );
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
		 * @param string $hook
		 *
		 * */

		public function enqueue_admin_assets( $hook ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'complianz' ) {
				//script to check for ad blockers
				wp_enqueue_script( 'cmplz-ad-checker', CMPLZ_URL . "assets/js/ads.js", array(), CMPLZ_VERSION, true );
			}
		}

		/**
		 * Get all cookies, and post back to site with ajax.
		 * This script is only inserted when a valid token is passed, so will never run for other visitors than the site admin
		 *
		 * */

		public function test_cookies() {
			if ( $this->scan_complete() ) {
				return;
			}

			if (!isset($_GET['complianz_scan_token']) || !isset($_GET['complianz_id'])){
				return;
			}

			$token     = sanitize_title( $_GET['complianz_scan_token'] );
			$id        = sanitize_title( $_GET['complianz_id'] );
			$admin_url = esc_url_raw( rest_url('complianz/v1/') );
			$nonce 	= wp_create_nonce( 'wp_rest' );
			$javascript = cmplz_get_template( 'test-cookies.js' );
			$javascript = str_replace( array(
				'{admin_url}',
				'{token}',
				'{id}',
				'{nonce}'
			), array(
				esc_url_raw( $admin_url ),
				esc_attr( $token ),
				esc_attr( $id ),
				$nonce
			), $javascript );
			?>
			<script>
				<?php echo $javascript;?>
			</script>
			<?php
		}

		/**
		 * Insert an iframe to retrieve front-end cookies
		 *
		 *
		 * */

		public function run_cookie_scan(): void {
			if ( ! cmplz_admin_logged_in() ) {
				return;
			}

			if ( get_option('cmplz_activation_time') > strtotime('-30 minutes') ) {
				return;
			}

			if ( defined( 'CMPLZ_DO_NOT_SCAN' ) && CMPLZ_DO_NOT_SCAN ) {
				return;
			}

			if ( isset( $_GET['complianz_scan_token'] ) ) {
				return;
			}
			//if the last cookie scan date is more than a month ago, we re-scan.
			$last_scan_date = COMPLIANZ::$banner_loader->get_last_cookie_scan_date( true );
			$scan_interval = apply_filters( 'cmplz_scan_interval', 3 );
			$one_month_ago = strtotime( "-".$scan_interval." month" );
			if (
			     ( $one_month_ago > $last_scan_date )
				 && $this->scan_complete()
			     && !$this->automatic_cookiescan_disabled()
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

				//store the date
				$timezone_offset = get_option( 'gmt_offset' );
				$time            = time() + ( 60 * 60 * $timezone_offset );
				update_option( 'cmplz_last_cookie_scan', $time );

				$url = $this->get_next_page_url();
				if ( ! $url ) {
					return;
				}

				if ( $url === 'remote' ) {
					//as the wsc cookie scan has a wait of 10 seconds on each request, we do this on cron instead
					//do_action('cmplz_remote_cookie_scan');
				} else if ( strpos( $url, 'complianz_id' ) !== false ) {
					//get the html of this page.
					$response = wp_remote_get( $url );
					if ( ! is_wp_error( $response ) ) {
						$html = $response['body'];
						$this->parse_html($html);
					}
				}
				//load in iframe so the scripts run.
				echo '<iframe id="cmplz_cookie_scan_frame" class="hidden" src="' . $url . '"></iframe>';
			}
		}

		private function parse_html($html){
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

			//parse for google analytics and tagmanager, but only if the wizard wasn't completed before.
			//with this data we prefill the settings and give warnings when tracking is doubled
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
		 * @param string $html
		 * @param bool   $single_key //return a single string instead of array
		 *
		 * @return array|string $thirdparty
		 *
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
		 * @param $html
		 */

		private function parse_for_statistics_settings( $html ) {

			if ( strpos( $html, 'gtm.js' ) !== false || strpos( $html, 'gtm.start' ) !== false
			) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/(\'|")(GTM-[A-Z]{7})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option_no_hooks('gtm_code', sanitize_text_field( $matches[2] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option('compile_statistics', 'google-tag-manager' );
				}
			}

			if ( strpos( $html, 'analytics.js' ) !== false || strpos( $html, 'ga.js' ) !== false || strpos( $html, '_getTracker' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/(\'|")(UA-[0-9]{8}-[0-9]{1})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option('ua_code', sanitize_text_field( $matches[2] ) );
					cmplz_update_option('compile_statistics', 'google-analytics' );
				}

				//gtag
				$pattern = '/(\'|")(G-[0-9a-zA-Z]{10})(\'|")/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option('ua_code', sanitize_text_field( $matches[2] ) );
					cmplz_update_option('compile_statistics', 'google-analytics' );
				}
				$pattern = '/\'anonymizeIp|anonymize_ip\'|:[ ]{0,1}true/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches ) {
					$value = cmplz_get_option( 'compile_statistics_more_info' );
					if ( ! is_array( $value ) ) {
						$value = array();
					}
					if ( !in_array( 'ip-addresses-blocked', $value, true )) {
						$value[] = 'ip-addresses-blocked';
					}
					cmplz_update_option('compile_statistics_more_info', $value );
				}
			}

			if ( strpos( $html, 'piwik.js' ) !== false || strpos( $html, 'matomo.js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );
				$pattern = '/(var u=")((https|http):\/\/.*?)"/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[2] ) ) {
					cmplz_update_option('matomo_url', sanitize_text_field( $matches[2] ) );
					update_option( 'cmplz_detected_stats_data', true );
				}

				$pattern = '/\[\'setSiteId\', \'([0-9]){1,3}\'\]\)/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option('matomo_site_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
				}

				cmplz_update_option('compile_statistics', 'matomo' );
			}

			if ( strpos( $html, 'static.getclicky.com/js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/clicky_site_ids\.push\(([0-9]{1,3})\)/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option('clicky_site_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option('compile_statistics', 'clicky' );
				}
			}

			if ( strpos( $html, 'mc.yandex.ru/metrika/watch.js' ) !== false ) {
				update_option( 'cmplz_detected_stats_type', true );

				$pattern = '/w.yaCounter([0-9]{1,10}) = new/i';
				preg_match( $pattern, $html, $matches );
				if ( $matches && isset( $matches[1] ) ) {
					cmplz_update_option('yandex_id', intval( $matches[1] ) );
					update_option( 'cmplz_detected_stats_data', true );
					cmplz_update_option('compile_statistics', 'yandex' );
				}
			}

		}

		/**
		 * Check a string for third party services
		 *
		 * @param string $html
		 * @param bool   $single_key //return a single string instead of array
		 *
		 * @return array|string $thirdparty
		 *
		 * */

		public function parse_for_thirdparty_services( $html, $single_key = false ) {
			$thirdparty = array();
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
			if ( ! cmplz_user_can_manage() ) {
				return '';
			}
			$token = wp_create_nonce( 'complianz_scan_token' );
			$pages = array_filter($this->pages_to_process());
			if ( count( $pages ) === 0 ) {
				return false;
			}
			$id_to_process = reset( $pages );

			//in case of remote, we want to wait until the process has completed before moving on to the next.
			if ( $id_to_process !== 'remote' ) {
				$this->set_page_as_processed( $id_to_process );
			} else if ( COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
				$this->set_page_as_processed( $id_to_process );
			}

			switch ( $id_to_process ) {
				case 'remote':
					return 'remote';
				case 'home':
					$url = site_url();
					break;
				case 'loginpage':
					$url = wp_login_url();
					break;
				default:
					$url = get_permalink( $id_to_process );
			}
			$url = add_query_arg( array(
				"complianz_scan_token" => $token,
				'complianz_id'         => $id_to_process
			), $url );
			if ( is_ssl() ) {
				$url = str_replace( "http://", "https://", $url );
			}

			return apply_filters("cmplz_next_page_url", $url);
		}

		/**
		 * Get the list of posttypes to process
		 * @return array
		 */

		public function get_scannable_post_types(){
			$args       = array(
					'public' => true,
			);
			$post_types = get_post_types( $args );
			unset(
					$post_types['elementor_font'],
					$post_types['attachment'],
					$post_types['revision'],
					$post_types['nav_menu_item'],
					$post_types['custom_css'],
					$post_types['customize_changeset'],
					$post_types['cmplz-dataleak'],
					$post_types['cmplz-processing'],
					$post_types['user_request'],
					$post_types['cookie'],
					$post_types['product']
			);
			return apply_filters('cmplz_cookiescan_post_types',$post_types );
		}

		/**
		 *
		 * Get list of page id's that we want to process this set of scan requests, which weren't included in the scan before
		 *
		 * @return array $pages
		 * *@since 1.0
		 */

		public function get_pages_list_single_run() {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}
			$posts = get_transient( 'cmplz_pages_list' );
			if ( ! $posts ) {
				$posts = ['home', 'remote'];
				$post_types = $this->get_scannable_post_types();
				foreach ( $post_types as $post_type ) {
					$args      = array(
						'post_type'       => $post_type,
						'posts_per_page'  => 5,
						'fields'          => 'ids',
						'meta_query'      => array(
							array(
								'key'     => '_cmplz_scanned_post',
								'compare' => 'NOT EXISTS'
							),
						)
					);
					$new_posts = get_posts( $args );
					$posts     = array_merge( $posts, $new_posts );
				}
				if ( count( $posts ) === 0 && ! $this->automatic_cookiescan_disabled() ) {
					/*
                     * If we didn't find any posts, we reset the post meta that tracks if all posts have been scanned.
                     * This way we will find some posts on the next scan attempt
                     * */
					$this->reset_scanned_post_batches();

					//now we need to reset the scanned pages list too
					$this->reset_pages_list();
				} else {
					foreach ( $posts as $post_id ) {
						update_post_meta( $post_id, '_cmplz_scanned_post', true );
					}
				}

				if ( cmplz_get_option( 'wp_admin_access_users' ) === 'yes' ) {
					$posts[] = 'loginpage';
				}
				set_transient( 'cmplz_pages_list', $posts, MONTH_IN_SECONDS );
			}

			return array_filter($posts);
		}

		/**
		 * Reset the list of pages
		 *
		 * @param bool $delay
		 * @param bool $manual //if it's manual, we always reset. If automatic scan is disabled, we do not reset.
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

			return array_filter($pages);
		}

		/**
		 * Check if the scan is complete
		 *
		 * @param void
		 *
		 * @return bool
		 * @since 1.0
		 *
		 * */

		public function scan_complete() {
			$pages = array_filter($this->pages_to_process());
			return count( $pages ) === 0;
		}

		/**
		 *
		 * Get list of pages that still have to be processed
		 *
		 * @param void
		 *
		 * @return array $pagåes
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
		 * @param $id
		 *
		 * @return void
		 * @since 1.0
		 */

		public function set_page_as_processed( $id ): void {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			if ( $id !== 'home' && $id !== 'loginpage' && $id !== 'remote' && ! is_numeric( $id ) ) {
				return;
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

			//also reset the email notification, so it will get sent next year.
			update_option( 'cmplz_update_legal_documents_mail_sent', false );
		}

		/**
		 * Get progress of the current scan to output with ajax
		 *
		 * @param array           $data
		 * @param string          $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */

		public function get_scan_progress( array $data, string $action, WP_REST_Request $request): array {
			if (!cmplz_user_can_manage()) {
				return [];
			}

			if ( $action === 'get_scan_progress' ) {
				$timezone_offset = get_option( 'gmt_offset' );
				$time            = time() + ( 60 * 60 * $timezone_offset );
				update_option( 'cmplz_last_cookie_scan', $time );

				$next_url = $this->get_next_page_url();
				if ($next_url==='remote') {
					do_action('cmplz_remote_cookie_scan');
					//only proceed to next page if remote scan is complete
					if ( COMPLIANZ::$wsc_scanner->wsc_scan_completed() ) {
						$next_url = $this->get_next_page_url();
					}
				} else if ( strpos( $next_url, 'complianz_id' ) !== false ) {
					$response = wp_remote_get( $next_url );
					if ( ! is_wp_error( $response ) ) {
						$html = $response['body'];
						$this->parse_html($html);
					}
				}
				$this->clear_double_cookienames();
				$cookies  = COMPLIANZ::$banner_loader->get_cookies();
				$progress = $this->get_progress_count();
				$total = count($cookies);
				$current = (int) ( $progress / 100 * $total );
				$cookies = array_slice( $cookies, 0, $current);
				$cookies = count($cookies) > 0 ? wp_list_pluck( $cookies, 'name' ) : [];
				$data = [
						"progress"  => $progress,
						"next_page" => $next_url,
						'cookies' => $cookies,
						'token' => wp_create_nonce( 'complianz_scan_token' ),
				];
			}
			return $data;
		}

		/**
		 * Rescan after a manual "rescan" command from the user
		 */

		public function reset_scan($data, $action, $request) {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}

			if ( $action === 'scan' ) {
				$scan_type = sanitize_title($request->get_param('scan_action'));
				if ( $scan_type==='reset' ) {
					global $wpdb;
					$table_names = array( $wpdb->prefix . 'cmplz_cookies');
					foreach ( $table_names as $table_name ) {
						if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
							$wpdb->query( "TRUNCATE TABLE $table_name" );
						}
					}
					update_option( 'cmplz_detected_social_media', false );
					update_option( 'cmplz_detected_thirdparty_services', false );
					update_option( 'cmplz_detected_stats', false );
				}

				if ( $scan_type==='reset' || $scan_type==='restart' ) {
					COMPLIANZ::$wsc_scanner->wsc_scan_reset();
					$this->reset_pages_list( false, true );
					COMPLIANZ::$sync->resync();
				}

				$data = [];
			}
			return $data;
		}

		/**
		 * Get progress of the scan in percentage
		 *
		 * @return float
		 */

		public function get_progress_count() {

			$remote_scan_total = 100;
			$remote_scan_progress = COMPLIANZ::$wsc_scanner->wsc_scan_progress();

			$local_done  = count($this->get_processed_pages_list());
			$local_total = count($this->get_pages_list_single_run());

			//convert local to a 100 scale
			//prevent division by zero
			$local_total = $local_total === 0 ? $local_done : $local_total;
			$local_done = 100 * ( $local_done / $local_total);

			$total = 200;
			$done = $remote_scan_progress + $local_done;

			$progress = 100 * ( $done / $total);
			if ( $progress > 100 ) {
				$progress = 100;
			}

			return $progress;
		}

	}

} //class closure
