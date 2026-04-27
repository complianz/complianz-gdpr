<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_proof_of_consent" ) ) {
	class cmplz_proof_of_consent {
		private static $_this;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}
			self::$_this = $this;
			add_filter( 'cmplz_do_action', array( $this, 'get_proof_of_consent_data' ), 10, 3 );

		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Get start or end date timestamp
		 * @param int    $year
		 * @param int    $month
		 * @param string $type
		 *
		 * @return int
		 */
		public function get_time_stamp_for_date( $year, $month, $type = 'start_date' ){
			if ( $year != 0 && $month != 0 ) {
				$day = '1';
				$month = (int) $month;
				$year = (int) $year;
				$t = '00:00:00';

				if ( $type === 'end_date' ) {
					$month = $month + 1;
				}
				return DateTime::createFromFormat("Y-m-d H:i:s", "$year-$month-$day $t")->getTimestamp();
			}
			return 0;
		}


		/**
		 * Get a list of processors
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return []
		 */

		public function get_proof_of_consent_data($data, $action, $request){
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}
			if ( $action==='get_proof_of_consent_documents' ){
				$regions = cmplz_get_regions(false, 'full');
				//convert key value array to array of objects with id and label
				$regions = array_map(function($id, $label){
					return (object) array('value'=>$id, 'label'=>$label);
				}, array_keys($regions), $regions);
				$regions[]=['value'=>'', 'label'=>__('Select a region', 'complianz-gdpr')];
				$documents = $this->get_cookie_snapshot_list();

				//convert unix timestamp to date
				//convert the array key to an 'id'
				foreach ($documents as $key => $document) {
					$document['time'] = date_i18n( get_option( 'date_format' ), $document['time'] );
					$document['id'] = $key;
					$documents[$key]=$document;
				}

				//strip key from the array
				$documents = empty($documents) ? [] : array_values($documents);
				$data = [
					'documents' => $documents,
					'regions' => $regions,
					'download_url' => cmplz_upload_url('snapshots'),
				];
			} else if ($action==='delete_proof_of_consent_documents') {
				$documents = $request->get_param('documents');
				foreach ($documents as $document) {
					$this->delete_snapshot($document['file']);
				}

			} else if ($action==='generate_proof_of_consent'){
				$this->generate_cookie_policy_snapshot(true);
				$data = [
					'success' => true,
				];
			}
			return $data;
		}

		/**
		 * Get list of cookie statement snapshots
		 * @param array $args
		 *
		 * @return array
		 */

		public function get_cookie_snapshot_list( $args = array() ): array {
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}


			$defaults   = array(
				'number' => 30,
				'region' => false,
				'offset' => 0,
				'order'  => 'DESC',
				'start_date'    => 0,
				'end_date'      => 9999999999999,
			);
			$args       = wp_parse_args( $args, $defaults );
			$path       =  cmplz_upload_dir('snapshots');
			$url        = cmplz_upload_url('snapshots');
			$filelist   = array();
			$extensions = array( "pdf" );
			$index = 0;
			if ( file_exists( $path ) && $handle = opendir( $path ) ) {
				while ( false !== ( $file = readdir( $handle ) ) ) {
					if ( $file !== "." && $file !== ".." ) {
						$file = $path . $file;
						$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
						if ( is_file( $file ) && in_array( $ext, $extensions ) ) {

							if ( $args['region'] && strpos(basename($file), $args['region'].'-proof-of-consent') === false ) {
								continue;
							}
							//get the region from the file name, e.g. rogier-lankhorst-test-amp-bla-eu-proof-of-consent-1-april-2023.pdf
							//with a regex, where the regex matches the two character region before '-proof-of-consent'
							$matches = array();
							preg_match('/([a-z]{2})-proof-of-consent/', basename($file), $matches);
							$region = isset($matches[1]) ? $matches[1] : '';

							if ( empty( $args['search'] ) || strpos( $file, $args['search'] ) !== false) {
								$index++;
								$parsed_index = sprintf('%02d', $index);
								if ($args['start_date'] < filemtime( $file ) && filemtime( $file ) < $args['end_date'] ) {
									$filelist[filemtime($file).$parsed_index]["path"] = $file;
									$filelist[filemtime($file).$parsed_index]["url"]  = trailingslashit($url).basename($file);
									$filelist[filemtime($file).$parsed_index]["file"] = basename($file);
									$filelist[filemtime($file).$parsed_index]["time"] = filemtime($file);
									$filelist[filemtime($file).$parsed_index]["region"] = $region;
									$filelist[filemtime($file).$parsed_index]["consent"] = cmplz_consenttype_nicename(cmplz_get_consenttype_for_region($region));
								}
							}
						}
					}
				}
				closedir( $handle );
			}

			if ( $args['order'] === 'DESC' ) {
				krsort( $filelist );
			} else {
				ksort( $filelist );
			}
			if ( empty( $filelist ) ) {
				return [];
			}

			$page       = (int) $args['offset'];
			$total      = count( $filelist ); //total items in array
			$limit      = (int) $args['number'];

			$totalPages = $limit===-1 ? 1 : ceil( $total / $limit ); //calculate total pages
			$page       = max( $page, 1 ); //get 1 page when $_GET['page'] <= 0
			$page       = min( $page, $totalPages ); //get last page when $_GET['page'] > $totalPages
			$offset     = ( $page - 1 ) * $limit;
			if ( $offset < 0 ) {
				$offset = 0;
			}
			if ($limit!=-1) {
				$filelist = array_slice( $filelist, $offset, $limit, true );
			}

			if ( empty( $filelist ) ) {
				return [];
			}

			return $filelist;
		}

		/**
		 * @param string $filename
		 */

		public function delete_snapshot( string $filename ): void {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			$path = cmplz_upload_dir('snapshots/');

			//don't allow \ and / in the filename, to prevent path traversal, replace them with ''
			$filename = str_replace( array( '/', '\\' ), '', $filename );

			unlink( $path . sanitize_file_name( $filename ) );
		}


		/**
		 * Generate the cookie policy snapshot
		 * @param bool $force
		 */

		public function generate_cookie_policy_snapshot( $force = false ) {
			if ( defined('CMPLZ_SKIP_SNAPSHOT_GENERATION') && CMPLZ_SKIP_SNAPSHOT_GENERATION ) {
				return;
			}

			if ( ! $force
			     && ! get_option( 'cmplz_generate_new_cookiepolicy_snapshot' )
			) {
				return;
			}

			$regions = cmplz_get_regions();
			foreach ( $regions as $region ) {
				$banner_id = cmplz_get_default_banner_id();
				$banner    = cmplz_get_cookiebanner( $banner_id );
				$settings  = $banner->get_front_end_settings();
				$settings  += $banner->get_html_settings();
				$settings['privacy_link_us '] = COMPLIANZ::$document->get_page_url( 'privacy-statement', 'us' );
				$settings_html = '';
				$skip          = array(
					'categories',
					'use_custom_cookie_css',
					'logo',
					'custom_css_amp',
					'static',
					'set_cookies',
					'position',
					'version',
					'banner_version',
					'a_b_testing',
					'a_b_testing_buttons',
					'title',
					'privacy_link',
					'nonce',
					'url',
					'current_policy_id',
					'type',
					'layout',
					'use_custom_css',
					'custom_css',
					'banner_width',
                    'colorpalette_background',
                    'colorpalette_text',
                    'colorpalette_toggles',
                    'colorpalette_border_radius',
                    'border_width',
                    'store_consent',
                    'cookie_domain',
                    'set_cookies_on_root',
                    'placeholdertext',
                    'css_file',
                    'page_links',
                    'tm_categories',
                    'cookie_path',
                    'colorpalette_button_accept',
                    'colorpalette_button_deny',
                    'colorpalette_button_settings',
                    'buttons_border_radius',
				);

				$settings['categories'] =  isset($settings['categories']) ? implode(', ', $settings['categories']) : '';
				unset( $settings["readmore_url"] );
				$settings = apply_filters( 'cmplz_cookie_policy_snapshot_settings' ,$settings );
				foreach ( $settings as $key => $value ) {

					if ( in_array( $key, $skip ) ) {
						continue;
					}
					if (is_array($value)) $value = implode(',', $value);
					$settings_html .= '<li>' . $key . ' => ' . esc_html( $value ) . '</li>';
				}

				$settings_html = '<div><h1>' . __( 'Cookie consent settings', 'complianz-gdpr' ) . '</h1><ul>' . ( $settings_html ) . '</ul></div>';
				$intro         = '<h1>' . __( "Proof of Consent", "complianz-gdpr" ) . '</h1>
                     <p>' . cmplz_sprintf( __( "This document was generated to show efforts made to comply with privacy legislation.
                            This document will contain the Cookie Policy and the cookie consent settings to proof consent
                            for the time and region specified below. For more information about this document, please go
                            to %shttps://complianz.io/consent%s.",
						"complianz-gdpr" ),
						'<a target="_blank" href="https://complianz.io/consent">',
						"</a>" ) . '</p>';
				COMPLIANZ::$document->generate_pdf( 'cookie-statement', $region, false, true, $intro, $settings_html );
				do_action('cmplz_after_proof_of_consent_generation', get_option( 'cmplz_generate_new_cookiepolicy_snapshot') );
			}

			update_option( 'cmplz_generate_new_cookiepolicy_snapshot', false, false );
		}
	}
} //class closure
