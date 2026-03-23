<?php defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );
if ( ! class_exists( 'CMPLZ_COOKIE' ) ) {
	/**
	 * All properties are public, because otherwise the empty check on a property fails, and requires an intermediate variable assignment.
	 * https://stackoverflow.com/questions/16918973/php-emptystring-return-true-but-string-is-not-empty
	 */
	class CMPLZ_COOKIE {
		public $ID     = false;
		public $object = false;
		public $name;

		/**
		 * Sync should the cookie stay in sync or not
		 *
		 * @var bool
		 */
		public $sync = true;

		/**
		 * Retention period
		 *
		 * @var string
		 */
		public $retention;
		public $type;
		public $service;
		public $serviceID;
		public $collectedPersonalData;
		public $cookieFunction;
		public $purpose;
		public $isTranslationFrom;
		public $lastUpdatedDate;
		public $lastAddDate;
		public $firstAddDate;
		public $synced;
		public $complete;
		public $slug = '';
		public $old;
		public $domain;
		public $isOwnDomainCookie = false;

		/**
		 * in CDB, we can mark a cookie as not relevant to users.
		 *
		 * @var int
		 */
		private $ignored;
		/**
		 * we do not actually delete it , otherwise it would be found on next run again
		 *
		 * @var
		 */
		public $deleted;
		/**
		 * give user the possibility to hide a cookie
		 *
		 * @var bool
		 */
		public $showOnPolicy = true;
		public $isMembersOnly;
		private $languages;
		public $language;

		function __construct( $name = false, $language = 'en', $service_name = false ) {
			if ( is_object( $name ) ) {
				$this->name = $name->name;
				$this->ID   = $name->ID;
				// after the sync, we are still missing the purpose in the objects. We load the cookie from the database to get the purpose.
				if ( ! empty( $name->purpose ) ) {
					$this->object = $name;
				}
			} elseif ( is_numeric( $name ) ) {
				$this->ID = (int) $name;
			} else {
				$this->name = $this->sanitize_cookie( $name );
			}

			$this->language = cmplz_sanitize_language( $language );
			if ( $service_name ) {
				$this->service = $service_name;
			}

			if ( $this->name !== false ) {
				// initialize the cookie with this id.
				$this->get();
			}
		}

		/**
		 * Add a new cookie for each passed language.
		 *
		 * @param             $name
		 * @param array       $languages
		 * @param string|bool $return_language
		 * @param bool        $service_name
		 * @param bool        $sync_on
		 *
		 * @return bool|int cookie_id
		 */
		public function add(
			$name,
			$languages = array( 'en' ),
			$return_language = false,
			$service_name = false,
			bool $sync_on = true
		) {
			// don't add cookies with the site url in the name
			if ( strpos( $name, site_url() ) !== false ) {
				return false;
			}

			if ( ! cmplz_user_can_manage() ) {
				return 0;
			}

			$this->name = $this->sanitize_cookie( $name );

			// the parent cookie gets "en" as default language
			$this->language  = 'en';
			$return_id       = 0;
			$this->languages = cmplz_sanitize_languages( $languages );

			// check if there is a parent cookie for this name
			$this->get( true );
			// if no ID is found, insert  in the database
			if ( ! $this->ID ) {
				$this->service      = $service_name;
				$this->sync         = $sync_on;
				$this->showOnPolicy = true;
			}

			// we save, to update previous, but also to make sure last add date is saved.
			$this->lastAddDate = time();
			$this->save();

			// we now should have an ID, which will be the parent item
			$parent_ID = $this->ID;

			if ( $return_language === 'en' ) {
				$return_id = $this->ID;
			}

			// make sure each language is available
			foreach ( $this->languages as $language ) {
				if ( $language === 'en' ) {
					continue;
				}
				$translated_cookie = new CMPLZ_COOKIE( $name, $language, $service_name );
				if ( ! $translated_cookie->ID ) {
					$translated_cookie->sync         = $sync_on;
					$translated_cookie->showOnPolicy = true;
				}
				$translated_cookie->domain            = $this->domain;
				$translated_cookie->isTranslationFrom = $parent_ID;
				$translated_cookie->service           = $service_name;
				$translated_cookie->lastAddDate       = time();
				// Copy field values from parent to translation
				$translated_cookie->retention             = $this->retention;
				$translated_cookie->cookieFunction        = $this->cookieFunction;
				$translated_cookie->purpose               = $this->purpose;
				$translated_cookie->type                  = $this->type;
				$translated_cookie->collectedPersonalData = $this->collectedPersonalData;
				$translated_cookie->save();
				if ( $return_language && $language === $return_language ) {
					$return_id = $translated_cookie->ID;
				}
			}

			return $return_id;
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
		 * Delete this cookie, and all translations linked to it.
		 */
		public function delete( $permanently = false ) {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			if ( ! $this->ID ) {
				return;
			}

			$translations = $this->get_translations();
			global $wpdb;
			foreach ( $translations as $ID ) {
				if ( $permanently ) {
					$wpdb->delete( $wpdb->prefix . 'cmplz_cookies', array( 'ID' => $ID ) );
				} else {
					$wpdb->update(
						$wpdb->prefix . 'cmplz_cookies',
						array( 'deleted' => true ),
						array( 'ID' => $ID )
					);
				}
			}
		}

		/**
		 * Restore a deleted cookie
		 */
		public function restore() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			if ( ! $this->ID ) {
				return;
			}

			$translations = $this->get_translations();
			global $wpdb;
			foreach ( $translations as $ID ) {
				$wpdb->update(
					$wpdb->prefix . 'cmplz_cookies',
					array( 'deleted' => false ),
					array( 'ID' => $ID )
				);
			}
		}


		public function get_translations() {
			global $wpdb;
			// check if this cookie is a parent
			if ( ! $this->isTranslationFrom ) {
				// is parent. Get all cookies where translationfrom = this id
				$parent_id = $this->ID;
			} else {
				// not parent.
				$parent_id = $this->isTranslationFrom;
			}

			$sql          = $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where isTranslationFrom = %s", $parent_id );
			$results      = $wpdb->get_results( $sql );
			$translations = wp_list_pluck( $results, 'ID' );

			// add the parent id
			$translations[] = $parent_id;

			return $translations;
		}

		/**
		 * Retrieve the cookie data from the table
		 *
		 * @param bool $parent get only the parent cookie, not a translation
		 */
		private function get( bool $parent = false ) {
			global $wpdb;

			if ( ! $this->name && ! $this->ID ) {
				return;
			}
			$sql = '';
			if ( $parent ) {
				$sql = ' AND isTranslationFrom = FALSE';
			}

			// if the service is set, we check within the service as well.
			if ( $this->service ) {
				$service = new CMPLZ_SERVICE( $this->service, $this->language );
				if ( $service->ID ) {
					$sql .= $wpdb->prepare( ' AND serviceID = %s', $service->ID );
				}
			}

			if ( $this->object ) {
				$cookie = $this->object;
			} elseif ( $this->ID ) {
				$cookie = wp_cache_get( 'cmplz_cookie_' . $this->ID, 'complianz' );
				if ( ! $cookie ) {
					$cookie = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where ID = %s ", $this->ID ) );
					wp_cache_set( 'cmplz_cookie_' . $this->ID, $cookie, 'complianz', HOUR_IN_SECONDS );
				}
			} else {
				$cookie = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where name = %s and language = %s $sql", $this->name, $this->language ) );
				// if not found with service, try without service.
				if ( ! $cookie ) {
					$cookie = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where name = %s and language = %s", $this->name, $this->language ) );
				}
			}

			// if there's still no match, try to do a fuzzy match
			if ( ! $cookie ) {
				$cookies   = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where language = %s $sql", $this->language ) );
				$cookies   = wp_list_pluck( $cookies, 'name', 'ID' );
				$cookie_id = $this->get_fuzzy_match( $cookies, $this->name );

				// if no cookie_id found yet, try without service
				if ( ! $cookie_id ) {
					$cookies   = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where language = %s", $this->language ) );
					$cookies   = wp_list_pluck( $cookies, 'name', 'ID' );
					$cookie_id = $this->get_fuzzy_match( $cookies, $this->name );
				}

				if ( $cookie_id ) {
					$cookie = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookies where ID = %s", $cookie_id ) );
				}
			}

			if ( $cookie ) {
				$this->ID                    = $cookie->ID;
				$this->name                  = substr( $cookie->name, 0, 200 ); // maximize cookie name length
				$this->serviceID             = $cookie->serviceID;
				$this->sync                  = (bool) $cookie->sync;
				$this->language              = $cookie->language;
				$this->ignored               = (bool) $cookie->ignored;
				$this->deleted               = (bool) $cookie->deleted;
				$this->retention             = $cookie->retention;
				$this->type                  = $cookie->type;
				$this->isOwnDomainCookie     = (bool) $cookie->isOwnDomainCookie;
				$this->domain                = $cookie->domain;
				$this->cookieFunction        = $cookie->cookieFunction;
				$this->purpose               = html_entity_decode( $cookie->purpose, ENT_QUOTES );
				$this->isMembersOnly         = $cookie->isMembersOnly && cmplz_get_option( 'wp_admin_access_users' ) === 'yes';
				$this->collectedPersonalData = $cookie->collectedPersonalData;
				$this->isTranslationFrom     = $cookie->isTranslationFrom;
				$this->showOnPolicy          = (bool) $cookie->showOnPolicy;
				$this->lastUpdatedDate       = $cookie->lastUpdatedDate;
				$this->lastAddDate           = $cookie->lastAddDate;
				$this->firstAddDate          = $cookie->firstAddDate;
				$this->slug                  = $cookie->slug;
				$this->synced                = $cookie->lastUpdatedDate > 0;
				$this->old                   = $cookie->lastAddDate < strtotime( '-3 months' ) && $cookie->lastAddDate > 0;
			}

			// legacy, upgrade data
			if ( empty( $this->domain ) ) {
				if ( $this->isOwnDomainCookie ) {
					$this->domain = 'self';
				} else {
					$this->domain = 'thirdparty';
				}
			}

			/**
			 * Fallback to parent cookie values for custom cookies
			 * If translation has empty fields, inherit from English parent
			 */
			if ( $this->isTranslationFrom ) {
				$needs_fallback = empty( $this->retention ) || empty( $this->cookieFunction ) || empty( $this->purpose );
				if ( $needs_fallback ) {
					// Load parent directly from database to avoid recursion.
					global $wpdb;
					$parent_data = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}cmplz_cookies WHERE ID = %s",
							$this->isTranslationFrom
						)
					);
					if ( $parent_data ) {
						if ( empty( $this->retention ) ) {
							$this->retention = $parent_data->retention;
						}
						if ( empty( $this->cookieFunction ) ) {
							$this->cookieFunction = $parent_data->cookieFunction;
						}
						if ( empty( $this->purpose ) ) {
							$this->purpose = html_entity_decode( $parent_data->purpose, ENT_QUOTES );
						}
						if ( empty( $this->type ) ) {
							$this->type = $parent_data->type;
						}
						if ( empty( $this->collectedPersonalData ) ) {
							$this->collectedPersonalData = $parent_data->collectedPersonalData;
						}
					}
				}
			}
			/**
			 * Don't translate purpose with Polylang, as polylang does not use the fieldname to translate. This causes mixed up strings when context differs.
			 * To prevent newly added cookies from getting translated, only translate when not in admin or cron, leaving front-end, where cookies aren't saved.
			 */
			if ( $this->language !== 'en' && ! is_admin() && ! wp_doing_cron() ) {
				if ( ! defined( 'POLYLANG_VERSION' ) || ! $this->sync ) {
					if ( ! empty( $this->purpose ) ) {
						$this->purpose = cmplz_translate( $this->purpose, 'cookie_purpose' );
					}
				}
				if ( ! empty( $this->retention ) ) {
					$this->retention = cmplz_translate( $this->retention, 'cookie_retention' );
				}
				if ( ! empty( $this->cookieFunction ) ) {
					$this->cookieFunction = cmplz_translate( $this->cookieFunction, 'cookie_function' );
				}
				if ( ! empty( $this->collectedPersonalData ) ) {
					$this->collectedPersonalData = cmplz_translate( $this->collectedPersonalData, 'cookie_collected_personal_data' );
				}
			}

			/**
			 * complianz cookie retention can be retrieved form this site
			 */

			if ( ! empty( $this->name ) ) {
				if ( strpos( $this->name, 'cmplz' ) !== false || strpos( $this->name, 'complianz' ) !== false ) {
					$this->retention = cmplz_sprintf( __( '%s days', 'complianz-gdpr' ), cmplz_get_option( 'cookie_expiry' ) );
				}
			}

			// get serviceid from service name
			if ( $this->serviceID ) {
				$service       = new CMPLZ_SERVICE( $this->serviceID, $this->language );
				$this->service = $service->name;
			}

			$this->complete = ( ! empty( $this->name )
								&& ! empty( $this->purpose )
								&& ! empty( $this->retention )
								&& ! empty( $this->service )
			);
		}

		/**
		 * - opslaan service ID met ID uit CDB
		 * - Als SERVICE ID er nog niet is, toevoegen in tabel
		 * - Synce services met CDB
		 */


		/**
		 * Saves the data for a given Cookie, or creates a new one if no ID was passed.
		 *
		 * @param bool $updateAllLanguages
		 */
		public function save( $updateAllLanguages = false ) {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			// let's skip cookies with this site url in the name
			if ( strpos( $this->name, site_url() ) !== false ) {
				return;
			}

			// don't save empty items.
			if ( empty( $this->name ) ) {
				return;
			}
			// get serviceid from service name
			if ( ! empty( $this->service ) ) {
				$service = new CMPLZ_SERVICE( $this->service, $this->language );
				if ( ! $service->ID ) {
					$languages       = $this->get_used_languages();
					$this->serviceID = $service->add( $this->service, $languages, $this->language );
				} else {
					$this->serviceID = $service->ID;
				}
			}

			/**
			 * complianz cookie retention can be retrieved from this site
			 */

			if ( strpos( $this->name, 'cmplz' ) !== false || strpos( $this->name, 'complianz' ) !== false ) {
				$this->retention = cmplz_sprintf( __( '%s days', 'complianz-gdpr' ), cmplz_get_option( 'cookie_expiry' ) );
			}

			/**
			 * Don't translate with Polylang, as polylang does not use the fieldname to translate. This causes mixed up strings when context differs.
			 */

			if ( $this->language === 'en' ) {
				if ( ! defined( 'POLYLANG_VERSION' ) || ! $this->sync ) {
					cmplz_register_translation( $this->purpose, 'cookie_purpose' );
				}
				cmplz_register_translation( $this->retention, 'cookie_retention' );
				cmplz_register_translation( $this->cookieFunction, 'cookie_function' );
				cmplz_register_translation( $this->collectedPersonalData, 'cookie_collected_personal_data' );
			}

			// update legacy data
			if ( empty( $this->domain ) ) {
				if ( $this->isOwnDomainCookie ) {
					$this->domain = 'self';
				} else {
					$this->domain = 'thirdparty';
				}
			}
			$update_array = array(
				'name'                  => sanitize_text_field( $this->name ),
				'retention'             => sanitize_text_field( $this->retention ),
				'type'                  => sanitize_text_field( $this->type ),
				'isOwnDomainCookie'     => (bool) $this->isOwnDomainCookie,
				'serviceID'             => (int) $this->serviceID,
				'domain'                => sanitize_text_field( $this->domain ),
				'cookieFunction'        => sanitize_text_field( $this->cookieFunction ),
				'purpose'               => sanitize_text_field( $this->purpose ),
				'isMembersOnly'         => (bool) $this->isMembersOnly,
				'collectedPersonalData' => sanitize_text_field( $this->collectedPersonalData ),
				'sync'                  => $this->sync,
				'ignored'               => (bool) $this->ignored,
				'deleted'               => (bool) $this->deleted,
				'language'              => cmplz_sanitize_language( $this->language ),
				'isTranslationFrom'     => (int) $this->isTranslationFrom,
				'showOnPolicy'          => $this->showOnPolicy,
				'lastUpdatedDate'       => (int) $this->lastUpdatedDate,
				'lastAddDate'           => (int) $this->lastAddDate,
				'slug'                  => empty( $this->slug ) ? '' : sanitize_title( $this->slug ),
			);
			if ( empty( $this->firstAddDate ) ) {
				$update_array['firstAddDate'] = time();
			}

			global $wpdb;
			// if we have an ID, we update the existing value
			if ( $this->ID ) {
				$wpdb->update( $wpdb->prefix . 'cmplz_cookies', $update_array, array( 'ID' => $this->ID ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'cmplz_cookies', $update_array );
				$this->ID = $wpdb->insert_id;
			}

			if ( $updateAllLanguages ) {
				// keep all translations in sync
				$translationIDS = $this->get_translations();
				foreach ( $translationIDS as $translationID ) {
					if ( $this->ID == $translationID ) {
						continue;
					}
					$translation                = new CMPLZ_COOKIE( $translationID );
					$translation->name          = $this->name;
					$translation->serviceID     = $this->serviceID;
					$translation->sync          = $this->sync;
					$translation->isMembersOnly = $this->isMembersOnly;
					$translation->slug          = $this->slug;
					$translation->showOnPolicy  = $this->showOnPolicy;
					$translation->deleted       = $this->deleted;
					$translation->ignored       = $this->ignored;
					$translation->domain        = $this->domain;
					$translation->save();
				}
			}
			cmplz_delete_transient( 'cmplz_cookie_shredder_list' );
			wp_cache_delete( 'cmplz_cookie_' . $this->ID, 'complianz' );
		}


		private function get_used_languages() {
			global $wpdb;

			$sql       = "SELECT language FROM {$wpdb->prefix}cmplz_cookies group by language";
			$languages = $wpdb->get_results( $sql );
			$languages = wp_list_pluck( $languages, 'language' );

			return $languages;
		}

		/**
		 * Validate a cookie string
		 *
		 * @param $cookie
		 *
		 * @return string|bool
		 */
		private function sanitize_cookie( $cookie ) {
			if ( ! $this->is_valid_cookie( $cookie ) ) {
				return false;
			}

			$cookie = sanitize_text_field( $cookie );

			// 100 characters max
			$cookie = substr( $cookie, 0, 100 );

			// remove whitespace
			$cookie = trim( $cookie );

			// strip double and single quotes
			$cookie = str_replace( '"', '', $cookie );
			return str_replace( "'", '', $cookie );
		}

		/**
		 * Check if a cookie is of a valid cookie structure
		 *
		 * @param $id
		 *
		 * @return bool
		 */
		private function is_valid_cookie( $id ) {
			if ( ! is_string( $id ) || empty( $id ) ) {
				return false;
			}

			$pattern = '/[a-zA-Z0-9\_\-\*]/i';

			return (bool) preg_match( $pattern, $id );
		}


		private function get_fuzzy_match( $cookies, $search ) {
			// to prevent match from wp_comment_123 on wp_*
			// we keep track of all matches, and only return the longest match, which is the closest match.
			$match            = false;
			$new_match        = false;
			$match_length     = 0;
			$new_match_length = 0;
			$partial          = '*';

			// clear up items without any match possibility
			foreach ( $cookies as $post_id => $cookie_name ) {
				if ( strpos( $cookie_name, $partial ) === false ) {
					unset( $cookies[ $post_id ] );
				}
			}

			foreach ( $cookies as $post_id => $compare_cookie_name ) {
				// check if the string "partial" is in the comparison cookie name
				// check if it has an underscore before or after the partial. If so, take it into account

				// get the substring before or after the partial
				$str1 = substr(
					$compare_cookie_name,
					0,
					strpos( $compare_cookie_name, $partial )
				);
				$str2 = substr(
					$compare_cookie_name,
					strpos( $compare_cookie_name, $partial )
					+ strlen( $partial )
				);
				// a partial match is enough on this type

				// $str2: match should end with this string
				if ( strlen( $str1 ) === 0 ) {
					$len     = strlen( $search ); // "*test" : 5
					$pos     = strpos( $search, $str2 ); // "*test" : 1
					$sub_len = strlen( $str2 ); // 4
					if ( $pos !== false && ( $len - $sub_len == $pos ) ) {
						$new_match_length = strlen( $str1 ) + strlen( $str2 );
						$new_match        = $post_id;
					}
					// match should start with this string
				} elseif ( strlen( $str2 ) === 0 ) {

					$pos = strpos( $search, $str1 );
					if ( $pos === 0 ) {
						$new_match_length = strlen( $str1 ) + strlen( $str2 );
						$new_match        = $post_id;
					}
				} else {
					$len2     = strlen( $search ); // "*test" : 5
					$pos2     = strpos( $search, $str2 ); // "*test" : 1
					$sub_len2 = strlen( $str2 ); // 4
					if ( strpos( $search, $str1 ) === 0
						&& strpos( $search, $str2 ) !== false
						&& ( $len2 - $sub_len2 == $pos2 )
					) {
						$new_match_length = strlen( $str1 ) + strlen( $str2 );
						$new_match        = $post_id;
					}
				}

				if ( $new_match_length > $match_length ) {
					$match_length = $new_match_length;
					$match        = $new_match;
				}
			}

			return $match;
		}
	}
}

/**
 * Install cookies table
 * */
add_action( 'cmplz_install_tables', 'cmplz_install_cookie_table' );
function cmplz_install_cookie_table() {
	// only load on front-end if it's a cron job
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}

	if ( ! wp_doing_cron() && ! cmplz_user_can_manage() ) {
		return;
	}
	if ( get_option( 'cmplz_cookietable_version' ) != CMPLZ_VERSION ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'cmplz_cookies';
		$sql             = "CREATE TABLE $table_name (
             `ID` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(250) NOT NULL,
            `slug` varchar(250) NOT NULL,
            `sync` int(11) NOT NULL,
            `ignored` int(11) NOT NULL,
            `retention` text NOT NULL,
            `type` text NOT NULL,
            `serviceID` int(11) NOT NULL,
            `cookieFunction` text NOT NULL,
            `collectedPersonalData` text NOT NULL,
            `purpose` text NOT NULL,
            `language` varchar(6) NOT NULL,
            `isTranslationFrom` int(11) NOT NULL,
            `isOwnDomainCookie` int(11) NOT NULL,
            `domain` text NOT NULL,
            `deleted` int(11) NOT NULL,
            `isMembersOnly` int(11) NOT NULL,
            `showOnPolicy` int(11) NOT NULL,
            `lastUpdatedDate` int(11) NOT NULL,
            `lastAddDate` int(11) NOT NULL,
            `firstAddDate` int(11) NOT NULL,
              PRIMARY KEY  (ID)
            ) $charset_collate;";
		dbDelta( $sql );

		/**
		 * Services
		 */
		$table_name = $wpdb->prefix . 'cmplz_services';
		$sql        = "CREATE TABLE $table_name (
                 `ID` int(11) NOT NULL AUTO_INCREMENT,
                 `name` varchar(250) NOT NULL,
                 `slug` varchar(250) NOT NULL,
                 `serviceType` varchar(250) NOT NULL,
                 `category` varchar(250) NOT NULL,
                 `thirdParty` int(11) NOT NULL,
                 `sharesData` int(11) NOT NULL,
                 `secondParty` int(11) NOT NULL,
                 `privacyStatementURL` varchar(250) NOT NULL,
                 `language` varchar(6) NOT NULL,
                `isTranslationFrom` int(11) NOT NULL,
                `sync` int(11) NOT NULL,
                `lastUpdatedDate` int(11) NOT NULL,
                  PRIMARY KEY  (ID)
                ) $charset_collate;";
		dbDelta( $sql );

		// don't set to preload false, as we need this one in the get_cookies function.
		update_option( 'cmplz_cookietable_version', CMPLZ_VERSION );

	}
}
