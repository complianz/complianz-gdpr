<?php defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
if ( ! class_exists( "CMPLZ_SERVICE" ) ) {
	/**
	 * All properties are public, because otherwise the empty check on a property fails, and requires an intermediate variable assignment.
	 * https://stackoverflow.com/questions/16918973/php-emptystring-return-true-but-string-is-not-empty
	 */
	class CMPLZ_SERVICE {
		public $ID = false;
		public $name;
		public $serviceType;
		public $category;
		public $sharesData;
		public $thirdParty;
		public $secondParty; //service that share data, but have cookies on the sites domain
		public $sync;
		public $synced;
		public $privacyStatementURL;
		public $isTranslationFrom;
		public $lastUpdatedDate;
		public $language;
		public $languages;
		public $complete;
		public $slug;

		function __construct( $ID = false, $language = 'en' ) {
			$this->language = cmplz_sanitize_language( $language );

			if ( $ID ) {
				if ( is_numeric( $ID ) ) {
					$this->ID = intval( $ID );
				} else {
					$this->name = $this->sanitize_service( $ID );
				}
			}

			if ( $this->name !== false || $this->ID !== false ) {
				//initialize the cookie with this id.
				$this->get();
			}
		}


		public function __get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			}
		}

		public function __set( $property, $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}

			return $this;
		}

		/**
		 * retrieve list of cookies with this service
		 *
		 * @return array() $cookies
		 */

		public function get_cookies() {
			if ( ! $this->ID ) {
				return array();
			}
			global $wpdb;
			$cookies = wp_cache_get('cmplz_service_cookies_'.$this->ID, 'complianz');
			if ( !$cookies ) {
				$cookies = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where serviceID = %s ", $this->ID ) );
				wp_cache_set('cmplz_service_cookies_'.$this->ID, $cookies, 'complianz', HOUR_IN_SECONDS);
			}

			return $cookies;
		}

		/**
		 * Retrieve the service data from the table
		 *
		 * @param $parent //if it should be the parent itme
		 */

		private function get( $parent = false ) {
			global $wpdb;

			if ( ! $this->name && ! $this->ID ) {
				return;
			}
			$sql = '';
			if ( $parent ) {
				$sql = " AND isTranslationFrom = FALSE";
			}

			if ( $this->ID ) {
				$service = wp_cache_get('cmplz_service_'.$this->ID, 'complianz');
				if ( !$service ) {
					$service = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_services where ID = %s ", $this->ID ) );
					wp_cache_set('cmplz_service_'.$this->ID, $service, 'complianz', HOUR_IN_SECONDS);
				}
			} else {
				$service = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_services where name = %s and language = %s " . $sql, $this->name,
					$this->language ) );
			}

			if ( $service ) {
				$this->ID                  = $service->ID;
				$this->name                = $service->name;
				$this->serviceType         = html_entity_decode($service->serviceType, ENT_QUOTES);
				$this->sharesData
				                           = $service->thirdParty; //legacy, sharesData was first called thirdparty
				$this->secondParty         = $service->secondParty;
				$this->thirdParty          = $this->sharesData
				                             && ! $service->secondParty;
				$this->sync                = $service->sync;
				$this->privacyStatementURL = $service->privacyStatementURL;
				$this->language            = $service->language;
				$this->category            = $service->category;
				$this->isTranslationFrom   = $service->isTranslationFrom;
				$this->lastUpdatedDate     = $service->lastUpdatedDate;
				$this->synced              = $this->sync && $service->lastUpdatedDate > 0 ? true : false;
				$this->slug                = $service->slug;

				$this->complete = ! ( empty( $this->name )
				                      || ( empty( $this->privacyStatementURL )
				                           && $this->sharesData )
				                      || empty( $this->serviceType )
				                      || empty( $this->name ) );
			}

			/**
			 * Fallback to parent service values for custom services
			 * If translation has empty fields, inherit from English parent
			 */
			if ( $this->isTranslationFrom ) {
				$needs_fallback = empty($this->serviceType) || empty($this->privacyStatementURL);
				if ( $needs_fallback ) {
					// Load parent directly from database to avoid recursion.
					global $wpdb;
					$parent_data = $wpdb->get_row( $wpdb->prepare( 
						"SELECT * FROM {$wpdb->prefix}cmplz_services WHERE ID = %s", 
						$this->isTranslationFrom 
					) );
					if ( $parent_data ) {
						if ( empty($this->serviceType) ) $this->serviceType = html_entity_decode($parent_data->serviceType, ENT_QUOTES);
						if ( empty($this->privacyStatementURL) ) $this->privacyStatementURL = $parent_data->privacyStatementURL;
						if ( empty($this->sharesData) ) $this->sharesData = $parent_data->thirdParty;
						if ( empty($this->secondParty) ) $this->secondParty = $parent_data->secondParty;
					}
				}
			}

		}

		/**
		 * Saves the data for a given service, ore creates a new one if no ID was passed.
		 * @param bool $updateAllLanguages
		 * @param bool   $forceWizardUpdate
		 */
		public function save( $updateAllLanguages = false, $forceWizardUpdate = true) {
			if ( !cmplz_user_can_manage() ) {
				return;
			}

			if ( empty( $this->name ) ) {
				return;
			}

			if ($forceWizardUpdate) {
				$this->add_to_wizard( $this->name );
			}

			if ( $this->language === 'en' ) {
				cmplz_register_translation( $this->serviceType, 'service_type' );
			}

			$update_array = array(
				'name'                => sanitize_text_field( $this->name ),
				'thirdParty'          => (bool) $this->sharesData,
				//legacy, sharesData was first called third party.
				'sharesData'          => (bool) $this->sharesData,
				//fluid upgrade
				'secondParty'         => (bool) $this->secondParty,
				'sync'                => (bool) $this->sync,
				'serviceType'         => sanitize_text_field( $this->serviceType ),
				'privacyStatementURL' => sanitize_text_field( $this->privacyStatementURL ),
				'language'            => cmplz_sanitize_language( $this->language ),
				'category'            => sanitize_text_field( $this->category ),
				'isTranslationFrom'   => sanitize_text_field( $this->isTranslationFrom ),
				'lastUpdatedDate'     => (int) $this->lastUpdatedDate,
				'slug'                => empty($this->slug) ? '' : sanitize_title( $this->slug ),

			);

			global $wpdb;
			//if we have an ID, we update the existing value
			if ( $this->ID ) {
				$wpdb->update( $wpdb->prefix . 'cmplz_services',
					$update_array,
					array( 'ID' => $this->ID )
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'cmplz_services',
					$update_array
				);
				$this->ID = $wpdb->insert_id;
			}

			if ( $updateAllLanguages ) {
				// Create missing translation rows if they don't exist
				$languages = COMPLIANZ::$banner_loader->get_supported_languages();
				foreach ( $languages as $language ) {
					if ( $language === $this->language ) {
						continue; // Skip parent language
					}
					// Check if translation exists
					$translation = new CMPLZ_SERVICE( $this->name, $language );
						// Create translation row
						$translation->name              = $this->name;
						$translation->language          = $language;
						$translation->isTranslationFrom = $this->ID;
						$translation->sync              = $this->sync;
						$translation->serviceType       = $this->serviceType;
						$translation->privacyStatementURL = $this->privacyStatementURL;
						$translation->sharesData        = $this->sharesData;
						$translation->secondParty       = $this->secondParty;
						$translation->category          = $this->category;
						$translation->save(false, false);
				}
				//keep all translations in sync
				$translationIDS = $this->get_translations();
				foreach ( $translationIDS as $translationID ) {
					if ( $this->ID == $translationID ) {
						continue;
					}
					$translation               = new CMPLZ_SERVICE( $translationID );
					$translation->name         = $this->name;
					$translation->sync         = $this->sync;
					$translation->serviceType  = $this->serviceType;
					$translation->privacyStatementURL = $this->privacyStatementURL;
					$translation->sharesData   = $this->sharesData;
					$translation->secondParty  = $this->secondParty;
					$translation->lastUpdatedDate = $this->lastUpdatedDate;
					$translation->save(false, false);
				}
			}
			cmplz_delete_transient('cmplz_cookie_shredder_list');
			wp_cache_delete('cmplz_service_cookies_'.$this->ID, 'complianz');
			wp_cache_delete('cmplz_service_'.$this->ID, 'complianz');
		}


		/**
		 * Delete this service, and all translations linked to it.
		 */

		public function delete() {
			if ( !cmplz_user_can_manage() ) {
				return;
			}
			if ( ! $this->ID ) {
				return;
			}
			//get all related cookies, and delete them.

			$cookies = $this->get_cookies();
			foreach ( $cookies as $service_cookie ) {
				$cookie = new CMPLZ_COOKIE( $service_cookie->ID );
				$cookie->delete(true);
			}

			$this->drop_from_wizard( $this->name );
			$translations = $this->get_translations();
			global $wpdb;
			foreach ( $translations as $ID ) {
				$wpdb->delete(
					$wpdb->prefix . 'cmplz_services',
					array( 'ID' => $ID )
				);
			}
		}

		/**
		 * Keep services in sync with the services in the list of the wizard.
		 *
		 * @param $service
		 */

		private function drop_from_wizard( $service ) {
			if ( !cmplz_user_can_manage() ) {
				return;
			}
			$slug            = $this->get_service_slug( $service );
			$services = cmplz_get_option( 'thirdparty_services_on_site' );
			if (!is_array($services)) $services = array();
			if ( in_array($slug, $services, true ) ) {
				$index = array_search($slug, $services, true);
				unset($services[$index]);
				cmplz_update_option_no_hooks('thirdparty_services_on_site', $services);
			}

			$social = cmplz_get_option( 'socialmedia_on_site' );
			if (!is_array($social)) $social = array();
			if ( in_array($slug, $social, true ) ) {
				$index = array_search($slug, $social, true);
				unset($social[$index]);
				cmplz_update_option_no_hooks('socialmedia_on_site', $social);
			}

		}

		/**
		 * Keep services in sync with the services in the list of the wizard.
		 *
		 * @param $service
		 */

		private function add_to_wizard( $service ) {
			if ( !cmplz_user_can_manage() ) {
				return;
			}

			$slug                = $this->get_service_slug( $service );
			$registered_services = COMPLIANZ::$config->thirdparty_services;
			$services = cmplz_get_option('thirdparty_services_on_site');
			if ( !is_array($services) ) $services = array();
			if ( isset( $registered_services[ $slug ] ) &&  !in_array($slug, $services, true ) ) {
				$services[] = $slug;
				cmplz_update_option_no_hooks( 'thirdparty_services_on_site', $services );
			}

			$registered_social = COMPLIANZ::$config->thirdparty_socialmedia;
			$social = cmplz_get_option('socialmedia_on_site');
			if ( !is_array($social) ) $social = array();
			if ( isset( $registered_social[ $slug ] ) &&  !in_array($slug, $social, true ) ) {
				$social[] = $slug;
				cmplz_update_option_no_hooks( 'socialmedia_on_site', $social );
			}
		}

		/**
		 * Get slug from service
		 *
		 * @param $name
		 *
		 * @return bool|false|int|string
		 */

		private function get_service_slug( $name ) {
			$services = COMPLIANZ::$config->thirdparty_services;
			if ( ( $slug = array_search( $name, $services ) ) !== false ) {
				return $slug;
			}
			$social = COMPLIANZ::$config->thirdparty_socialmedia;
			if ( ( $slug = array_search( $name, $social ) ) !== false ) {
				return $slug;
			}

			return false;
		}

		public function get_translations() {
			global $wpdb;
			//check if this cookie is a parent
			if ( ! $this->isTranslationFrom ) {
				//is parent. Get all cookies where translationfrom = this id
				$parent_id = $this->ID;
			} else {
				//not parent.
				$parent_id = $this->isTranslationFrom;
			}

			$sql
				          = $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_services where isTranslationFrom = %s",
				$parent_id );
			$results      = $wpdb->get_results( $sql );
			$translations = wp_list_pluck( $results, 'ID' );

			//add the parent id
			$translations[] = $parent_id;

			return $translations;
		}

		/**
		 * Add service to the database
		 *
		 * @param        $name
		 * @param array  $languages
		 * @param string $return_language
		 * @param string $categoryc
		 * @param bool   $sync_on
		 *
		 * @return bool|int
		 */

		public function add(
			$name, $languages = array( 'en' ), $return_language = 'en',
			$category = '', $sync_on = true
		) {
			if ( !cmplz_user_can_manage() ) {
				return false;
			}
			$return_id = false;
			//insert for each language
			$this->languages = cmplz_sanitize_languages( $languages );
			$this->name      = $name;
			//check if there is a parent cookie for this name
			$this->get( true );

			//if no ID is found, insert in the database
			if ( ! $this->ID ) {
				$this->sync     = $sync_on;
				$this->category = $category;
				$this->save(false, false );
			}

			$parent_ID = $this->ID;

			if ( $return_language === 'en' ) {
				$return_id = $this->ID;
			}

			//make sure each language is available
			foreach ( $this->languages as $language ) {
				if ( $language === 'en' ) {
					continue;
				}

				$translated_service = new CMPLZ_SERVICE( $name, $language );
				if ( ! $translated_service->ID ) {
					$translated_service->sync = $sync_on;
				}
				$translated_service->category          = $category;
				$translated_service->isTranslationFrom = $parent_ID;
				// Copy field values from parent to translation
				$translated_service->serviceType         = $this->serviceType;
				$translated_service->privacyStatementURL = $this->privacyStatementURL;
				$translated_service->sharesData          = $this->sharesData;
				$translated_service->secondParty         = $this->secondParty;
				$translated_service->save(false, false);

				if ( $return_language && $language == $return_language ) {
					$return_id = $translated_service->ID;
				}

			}

			return $return_id;

		}

		/**
		 * Validate a service string
		 *
		 * @param $service
		 *
		 * @return string|bool
		 */

		private function sanitize_service( $service ) {

			return sanitize_text_field( $service );
		}


	}
}


