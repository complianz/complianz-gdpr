<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, Squiz.Commenting.FileComment.Missing
defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );

if ( ! class_exists( 'cmplz_config' ) ) {
	/**
	 * Class cmplz_config
	 */
	// phpcs:ignore PEAR.NamingConventions.ValidClassName.StartWithCapital, Squiz.Commenting.ClassComment.Missing, PEAR.NamingConventions.ValidClassName.Invalid
	class cmplz_config {

		/**
		 * Holds the single instance of the class.
		 *
		 * @var cmplz_config
		 */
		private static $_this; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Array of fields used in the configuration.
		 *
		 * @var array
		 */
		public $fields = array();

		/**
		 * Array of formal languages.
		 *
		 * @var array
		 */
		private $formal_languages = array();

		/**
		 * List of generic documents.
		 *
		 * @var array
		 */
		private $generic_documents_list;

		/**
		 * Supported states.
		 *
		 * @var array
		 */
		private $supported_states;

		/**
		 * Cookie consent converter.
		 *
		 * @var mixed
		 */
		private $cookie_consent_converter;

		/**
		 * Language codes.
		 *
		 * @var array
		 */
		private $language_codes;

		/**
		 * Supported regions.
		 *
		 * @var array
		 */
		private $supported_regions;

		/**
		 * Third-party services.
		 *
		 * @var array
		 */
		private $thirdparty_services = array(
			'activecampaign'   => 'Active Campaign',
			'adobe-fonts'      => 'Adobe Fonts',
			'google-fonts'     => 'Google Fonts',
			'google-recaptcha' => 'Google reCAPTCHA',
			'google-maps'      => 'Google Maps',
			'openstreetmaps'   => 'OpenStreetMaps',
			'vimeo'            => 'Vimeo',
			'youtube'          => 'YouTube',
			'videopress'       => 'VideoPress',
			'dailymotion'      => 'Dailymotion',
			'soundcloud'       => 'SoundCloud',
			'twitch'           => 'Twitch',
			'paypal'           => 'PayPal',
			'spotify'          => 'Spotify',
			'hotjar'           => 'Hotjar',
			'addthis'          => 'AddThis',
			'addtoany'         => 'AddToAny',
			'sharethis'        => 'ShareThis',
			'livechat'         => 'LiveChat',
			'hubspot'          => 'HubSpot',
			'calendly'         => 'Calendly',
			'microsoftads'     => 'Microsoft Ads',
		);

		/**
		 * Third-party social media services.
		 *
		 * @var array
		 */
		private $thirdparty_socialmedia = array(
			'facebook'  => 'Facebook',
			'twitter'   => 'Twitter',
			'linkedin'  => 'LinkedIn',
			'whatsapp'  => 'WhatsApp',
			'instagram' => 'Instagram',
			'tiktok'    => 'TikTok',
			'disqus'    => 'Disqus',
			'pinterest' => 'Pinterest',
		);

		/**
		 * Placeholders.
		 *
		 * The services for which a placeholder exists in the assets/images/placeholders folder.
		 *
		 * @var array
		 */
		private $placeholders;

		/**
		 * Social media markers.
		 *
		 * This is used in the scan function to tell the user he/she uses social media
		 * Also in the function to determine a media type for the placeholders
		 * Based on this the cookie warning is enabled.
		 *
		 * @var array
		 */
		private $social_media_markers = array(
			'linkedin'  => array(
				'platform.linkedin.com',
				'addthis_widget.js',
				'linkedin.com/embed/feed',
			),
			'twitter'   => array(
				'super-socializer',
				'sumoSiteId',
				'addthis_widget.js',
				'platform.twitter.com',
				'twitter-widgets.js',
			),
			'facebook'  => array(
				'fbq',
				'super-socializer',
				'sumoSiteId',
				'addthis_widget.js',
				'fb-root',
				'<!-- Facebook Pixel Code -->',
				'connect.facebook.net',
				'www.facebook.com/plugins',
				'pixel-caffeine',
				'facebook.com/plugins',
			),
			'pinterest' => array(
				'super-socializer',
				'assets.pinterest.com',
			),
			'disqus'    => array( 'disqus.com' ),
			'tiktok'    => array( 'tiktok.com' ),
			'instagram' => array(
				'instawidget.net/js/instawidget.js',
				'instagram.com',
			),
		);

		/**
		 * Third party services markers.
		 *
		 * Scripts with this string in the content get listed in the third party list.
		 * Also used in cmplz_placeholder().
		 *
		 * @var array
		 */
		private $thirdparty_service_markers = array(
			'google-maps'      => array(
				'apis.google.com/js/platform.js',
				'new google.maps.',
				'google.com/maps',
				'maps.google.com',
				'wp-google-maps',
				'new google.maps.InfoWindow',
				'new google.maps.Marker',
				'new google.maps.Map',
				'var mapOptions',
				'var map',
				'var Latlng',
			),
			'soundcloud'       => array( 'w.soundcloud.com/player' ),
			'openstreetmaps'   => array(
				'openstreetmap.org',
				'osm/js/osm',
			),
			'vimeo'            => array(
				'player.vimeo.com',
				'i.vimeocdn.com',
			),
			'google-recaptcha' => array(
				'google.com/recaptcha',
				'grecaptcha',
				'recaptcha.js',
				'recaptcha/api',
			),
			'youtube'          => array(
				'youtube.com',
				'youtube-nocookie.com',
				'youtu.be',
				'yotuwp',
			),
			'videopress'       => array(
				'videopress.com/embed',
				'videopress.com/videopress-iframe.js',
			),
			'dailymotion'      => array( 'dailymotion.com/embed/video/' ),
			'hotjar'           => array( 'static.hotjar.com' ),
			'spotify'          => array( 'open.spotify.com/embed' ),
			'google-fonts'     => array( 'fonts.googleapis.com' ),
			'paypal'           => array(
				'www.paypal.com/tagmanager/pptm.js',
				'www.paypalobjects.com/api/checkout.js',
			),
			'disqus'           => array( 'disqus.com' ),
			'addthis'          => array( 'addthis.com' ),
			'addtoany'         => array( 'static.addtoany.com/menu/page.js' ),
			'sharethis'        => array( 'sharethis.com' ),
			'microsoftads'     => array( 'bat.bing.com' ),
			'livechat'         => array( 'cdn.livechatinc.com/tracking.js' ),
			'hubspot'          => array( 'js.hs-scripts.com/', 'hbspt.forms.create', 'js.hsforms.net', 'track.hubspot.com', 'js.hs-analytics.net' ),
			'calendly'         => array( 'assets.calendly.com' ),
			'twitch'           => array( 'twitch.tv', 'player.twitch.tv' ),
			'adobe-fonts'      => array( 'p.typekit.net', 'use.typekit.net' ),
		);

		/**
		 * Stats.
		 *
		 * @var array
		 */
		private $stats = array(
			'google-analytics'   => 'Google Analytics',
			'google-tag-manager' => 'Tag Manager',
			'matomo'             => 'Matomo',
			'clicky'             => 'Clicky',
			'yandex'             => 'Yandex',
			'clarity'            => 'Clarity',
		);

		/**
		 * Stats markers.
		 *
		 * @var array
		 */
		private $stats_markers = array(
			'google-analytics'   => array(
				'google-analytics.com/ga.js',
				'www.google-analytics.com/analytics.js',
				'_getTracker',
				"gtag('js'",
			),
			'google-tag-manager' => array(
				'gtm.start',
				'gtm.js',
			),
			'matomo'             => array( 'piwik.js', 'matomo.js' ),
			'clicky'             => array( 'static.getclicky.com/js', 'clicky_site_ids' ),
			'yandex'             => array( 'mc.yandex.ru/metrika/watch.js' ),
			'clarity'            => array( 'clarity.ms' ),
		);

		/**
		 * Amp tags.
		 *
		 * @var array
		 */
		private $amp_tags = array(
			'amp-ad-exit',
			'amp-ad',
			'amp-analytics',
			'amp-auto-ads',
			'amp-call-tracking',
			'amp-experiment',
			'amp-pixel',
			'amp-sticky-ad',
			// Dynamic content.
			'amp-google-document-embed',
			'amp-gist',
			// Media.
			'amp-brightcove',
			'amp-dailymotion',
			'amp-hulu',
			'amp-soundcloud',
			'amp-vimeo',
			'amp-youtube',
			'amp-iframe',
			// Social.
			'amp-addthis',
			'amp-beopinion',
			'amp-facebook-comments',
			'amp-facebook-like',
			'amp-facebook-page',
			'amp-facebook',
			'amp-gfycat',
			'amp-instagram',
			'amp-pinterest',
			'amp-reddit',
			'amp-riddle-quiz',
			'amp-social-share',
			'amp-twitter',
			'amp-vine',
			'amp-vk',
		);

		/**
		 * Sections configurations.
		 *
		 * @var array
		 */
		private $sections;

		/**
		 * Pages configurations.
		 *
		 * @var array
		 */
		private $pages = array();

		/**
		 * Warning types configurations.
		 *
		 * @var array
		 */
		private $warning_types;

		/**
		 * Yes/No configurations.
		 *
		 * @var array
		 */
		private $yes_no;

		/**
		 * Countries configurations.
		 *
		 * @var array
		 */
		private $countries;

		/**
		 * Purposes configurations.
		 *
		 * @var array
		 */
		private $purposes;

		/**
		 * Details per purpose for US configurations.
		 *
		 * @var array
		 */
		private $details_per_purpose_us;

		/**
		 * Regions configurations.
		 *
		 * @var array
		 */
		private $regions;

		/**
		 * EU countries configurations.
		 *
		 * @var array
		 */
		private $eu_countries;

		/**
		 * Collected information for children configurations.
		 *
		 * @var array
		 */
		private $collected_info_children;

		/**
		 * Lawful bases configurations.
		 *
		 * @var array
		 */
		private $lawful_bases;

		const LOCALIZED_PROPS = array(
			'regions',
			'supported_regions',
			'supported_states',
			'countries',
			'language_codes',
			'generic_documents_list',
			'pages',
			'yes_no',
			'lawful_bases',
			'placeholders',
			'purposes',
			'details_per_purpose_us',
			'collected_info_children',
		);


		/**
		 * Constructor fot cmplz_config.
		 * Initializes the singleton instance of the class.
		 * If an instance already exists, it will terminate the script with an error message.
		 */
		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			self::$_this = $this;

			/**
			 * The legal version is only updated when document contents or the questions leading to it are changed
			 * 1: start version
			 * 2: introduction of US privacy questions
			 * 3: new questions
			 * 4: new questions
			 * 5: UK as separate region
			 * 6: CA as separate region
			 * 7: Impressum in germany
			 * */
			define( 'CMPLZ_LEGAL_VERSION', '8' );

			/**
			 * Properties
			 */
			$this->load_props();

			/**
			 * Settings
			 */
			$this->load_settings();

			/**
			 * Load hooks
			 */
			$this->load_hooks();
		}


		/**
		 * Magic method to get the value of a property.
		 *
		 * @param string $name The name of the property to get.
		 * @return mixed|null The value of the property, or null if the property does not exist.
		 */
		public function __get( string $name ) {
			if ( ! property_exists( $this, $name ) ) {
				return null;
			}
			if ( in_array( $name, self::LOCALIZED_PROPS, true ) ) {
				if ( method_exists( $this, 'get_i18n_' . $name ) ) {
					$method = 'get_i18n_' . $name;
					return $this->$method();
				} else {
					return $this->localize_array( $this->$name );
				}
			}

			return $this->$name;
		}


		/**
		 * Get full array of regions, but only active ones.
		 *
		 * @return array
		 */
		public function active_regions(): array {
			return array_intersect_key( COMPLIANZ::$config->regions, cmplz_get_regions( false, 'short' ) );
		}


		/**
		 * Localize an array of strings.
		 *
		 * @param array $properties The array of strings to localize.
		 * @return array The localized array.
		 */
		private function localize_array( array $properties ): array {
			return array_map(
				function ( $value ) {
					return __( $value, 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				},
				$properties
			);
		}


		/**
		 * Load various properties for the configuration.
		 *
		 * This method initializes various properties related to countries, utilities, purposes, and documents.
		 *
		 * @return void
		 */
		private function load_props(): void {

			/**
			 * Countries
			 */
			$this->load_eu_countries();
			$this->load_formal_languages();
			$this->load_regions();
			$this->load_supported_regions();
			$this->load_supported_states();
			$this->load_cookie_consent_converter();
			$this->load_countries();
			$this->load_language_codes();

			/**
			 * Utils
			 */
			$this->load_yes_no();
			$this->load_lawful_bases();
			$this->load_placeholders();

			/**
			 * Purposes
			 */
			$this->load_purposes();
			$this->load_details_per_purpose_us();
			$this->load_collected_info_children();

			/**
			 * Documents
			 */
			$this->load_generic_documents_list();
			$this->load_pages();
		}


		/**
		 * Load various settings for the configuration.
		 *
		 * This method includes the necessary configuration files for the settings.
		 *
		 * @return void
		 */
		private function load_settings(): void {
			require_once CMPLZ_PATH . 'settings/config/config.php';
			if ( file_exists( CMPLZ_PATH . 'pro/settings/config.php' ) ) {
				require_once CMPLZ_PATH . 'pro/settings/config.php';
				require_once CMPLZ_PATH . 'pro/config/dynamic-document-elements.php';
			}
			require_once CMPLZ_PATH . '/cookiebanner/settings.php';
		}


		/**
		 * Load hooks for the configuration.
		 *
		 * This method adds actions to initialize documents and other settings.
		 *
		 * @return void
		 */
		private function load_hooks(): void {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'load_documents' ) );
		}


		/**
		 * Initialize the config fields.
		 *
		 * This method applies filters to the stats markers and fields properties.
		 *
		 * @return void
		 */
		public function init(): void {
			$this->stats_markers = apply_filters( 'cmplz_stats_markers', $this->stats_markers );
			$this->fields        = array_values( apply_filters( 'cmplz_fields', array() ) );
		}


		/**
		 * Load documents.
		 *
		 * This method loads various document files based on the regions and options set in the configuration.
		 *
		 * @return void
		 */
		public function load_documents(): void {
			$files                    = array();
			$regions                  = cmplz_get_regions();
			$privacy_statement_exists = cmplz_get_option( 'privacy-statement' ) === 'generated';
			$files[]                  = '/pro/config/documents/documents.php';
			foreach ( $regions as $region ) {
				$files[] = "/config/documents/cookie-policy-$region.php";
				if ( $privacy_statement_exists ) {
					$files[] = "/pro/config/documents/$region/privacy-policy.php";
				}
				$files[] = "/pro/config/documents/$region/privacy-policy-children.php";
			}

			if ( cmplz_get_option( 'disclaimer' ) === 'generated' ) {
				$files[] = '/pro/config/documents/disclaimer.php';
			}
			if ( cmplz_get_option( 'impressum' ) === 'generated' ) {
				$files[] = '/pro/config/documents/impressum.php';
			}

			foreach ( $files as $file ) {
				if ( file_exists( CMPLZ_PATH . $file ) ) {
					require_once CMPLZ_PATH . $file;
				}
			}

			$this->pages = apply_filters( 'cmplz_pages_load_types', $this->pages );

			foreach ( $regions as $region ) {
				if ( ! isset( $this->pages[ $region ] ) ) {
					continue;
				}
				foreach ( $this->pages[ $region ] as $type => $data ) {
					if ( ! isset( $this->pages[ $region ][ $type ]['document_elements'] ) ) {
						continue;
					}
					$this->pages[ $region ][ $type ]['document_elements'] = apply_filters( 'cmplz_document_elements', $this->pages[ $region ][ $type ]['document_elements'], $region, $type, $this->fields );
				}
			}
		}


		/**
		 * Load the list of EU countries.
		 *
		 * This method initializes the `eu_countries` property with an array of country codes
		 * representing the countries in the European Union.
		 *
		 * @return void
		 */
		private function load_eu_countries(): void {
			$countries          = array(
				'BE',
				'BG',
				'CY',
				'DK',
				'DE',
				'EE',
				'FI',
				'FR',
				'GR',
				'HU',
				'IE',
				'IT',
				'IS',
				'HR',
				'LV',
				'LT',
				'LI',
				'LU',
				'MT',
				'NL',
				'NO',
				'AT',
				'PL',
				'PT',
				'RO',
				'SK',
				'SI',
				'ES',
				'CZ',
				'VL',
				'SE',
				'CH',
				'RE',
				'GP',
				'MQ',
				'GY',
				'YT',
				'MF',
			);
			$this->eu_countries = $countries;
		}


		/**
		 * Load the list of formal languages.
		 *
		 * This method initializes the `formal_languages` property with an array of language codes
		 * representing the formal languages supported.
		 *
		 * @return void
		 */
		private function load_formal_languages(): void {
			$formal_languages       = array(
				'de_DE',
				'nl_NL',
			);
			$this->formal_languages = $formal_languages;
		}


		/**
		 * Load the list of regions.
		 *
		 * This method initializes the `regions` property with an array of region configurations.
		 * Each region configuration includes details such as label, full label, countries, law, type, statistics consent, data leak type, and TCF support.
		 *
		 * @return void
		 */
		private function load_regions(): void {
			$regions       = array(
				'us' => array(
					'label'              => 'US',
					'label_full'         => 'United States',
					'countries'          => array( 'US' ),
					'law'                => 'CCPA',
					'type'               => 'optout',
					'statistics_consent' => 'no',
					'dataleak_type'      => '2',
					'tcf'                => true,
				),
				'ca' => array(
					'label'              => 'CA',
					'label_full'         => 'Canada',
					'countries'          => array( 'CA' ),
					'law'                => 'PIPEDA',
					'type'               => 'optout',
					'statistics_consent' => 'no',
					'dataleak_type'      => '3',
					'tcf'                => true,
				),
				'eu' => array(
					'label'              => 'EU',
					'label_full'         => 'European Union',
					'countries'          => $this->eu_countries,
					'law'                => 'GDPR',
					'type'               => 'optin',
					'statistics_consent' => 'when_not_anonymous',
					'dataleak_type'      => '1',
					'tcf'                => true,
				),
				'uk' => array(
					'label'              => 'UK',
					'label_full'         => 'United Kingdom',
					'countries'          => array( 'GB' ),
					'law'                => 'UK-GDPR',
					'type'               => 'optin',
					'statistics_consent' => 'always',
					'dataleak_type'      => '1',
					'tcf'                => true,
				),
				'au' => array(
					'label'              => 'AU',
					'label_full'         => 'Australia',
					'countries'          => array( 'AU' ),
					'law'                => 'APA',
					'type'               => 'optout',
					'statistics_consent' => 'no',
					'dataleak_type'      => '3',
					'tcf'                => true,
				),
				'za' => array(
					'label'              => 'ZA',
					'label_full'         => 'South Africa',
					'countries'          => array( 'ZA' ),
					'law'                => 'POPIA',
					'type'               => 'optin',
					'statistics_consent' => 'no',
					'dataleak_type'      => '3',
					'tcf'                => true,
				),
				'br' => array(
					'label'              => 'BR',
					'label_full'         => 'Brazil',
					'countries'          => array( 'BR' ),
					'law'                => 'LGPD',
					'type'               => 'optin',
					'statistics_consent' => 'no',
					'dataleak_type'      => '3',
					'tcf'                => true,
				),
			);
			$this->regions = $regions;
		}


		/**
		 * Get the translated regions array.
		 *
		 * This method maps over the regions array and translates the label, full label, and law of each region.
		 *
		 * @return array The array of regions with translated labels, full labels, and laws.
		 *
		 * @see self::__get() This method is dynamically accessed through __get().
		 */
		private function get_i18n_regions(): array {
			return array_map(
			/**
			 * Translate the label, full label, and law of a region.
			 *
			 * @param array $region The region configuration array.
			 * @return array The region configuration array with translated labels and law.
			 */
				function ( array $region ) {
					$region['label']      = __( $region['label'], 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					$region['label_full'] = __( $region['label_full'], 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					$region['law']        = __( $region['law'], 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					return $region;
				},
				$this->regions
			);
		}


		/**
		 * Load the list of supported regions.
		 *
		 * This method initializes the `supported_regions` property with an array of region codes
		 * and their corresponding descriptions.
		 *
		 * @return void
		 */
		private function load_supported_regions(): void {
			$regions                 = array(
				'eu' => 'European Union (GDPR)',
				'uk' => 'United Kingdom (UK-GDPR, PECR, Data Protection Act)',
				'us' => 'United States',
				'ca' => 'Canada (PIPEDA)',
				'au' => 'Australia (Privacy Act 1988)',
				'za' => 'South Africa (POPIA)',
				'br' => 'Brazil (LGPD)',
			);
			$this->supported_regions = $regions;
		}


		/**
		 * Load the list of supported states.
		 *
		 * This method initializes the `supported_states` property with an array of state codes
		 * and their corresponding descriptions.
		 *
		 * @return void
		 */
		private function load_supported_states(): void {
			$supported_states       = array(
				'cal'     => 'California (CPRA)',
				'col'     => 'Colorado (CPA)',
				'con'     => 'Connecticut (CTDPA)',
				'del'     => 'Delaware (PDPA)',
				'iow'     => 'Iowa (CDPA)',
				'mon'     => 'Montana (MCDPA)',
				'neb'     => 'Nebraska (DPA)',
				'nev'     => 'Nevada (NRS 603A)',
				'new_ham' => 'New Hampshire (DPA)',
				'new_jer' => 'New Jersey (DPL)',
				'ore'     => 'Oregon (OCPA)',
				'tex'     => 'Texas (TDPSA)',
				'uta'     => 'Utah (UCPA)',
				'vir'     => 'Virginia (CDPA)',
			);
			$this->supported_states = $supported_states;
		}


		/**
		 * Load the cookie consent converter.
		 *
		 * This method initializes the `cookie_consent_converter` property with an array
		 * mapping country codes to their corresponding regions.
		 *
		 * @return void
		 */
		private function load_cookie_consent_converter(): void {
			$this->cookie_consent_converter = array( 'GB' => 'UK' );
		}


		/**
		 * Load the list of countries.
		 *
		 * This method initializes the `countries` property with an array of country codes
		 * and their corresponding country names.
		 *
		 * @return void
		 */
		private function load_countries(): void {
			$countries       = array(
				'AF' => 'Afghanistan',
				'AX' => 'Aland Islands',
				'AL' => 'Albania',
				'DZ' => 'Algeria',
				'AS' => 'American Samoa',
				'AD' => 'Andorra',
				'AO' => 'Angola',
				'AI' => 'Anguilla',
				'AQ' => 'Antarctica',
				'AG' => 'Antigua And Barbuda',
				'AR' => 'Argentina',
				'AM' => 'Armenia',
				'AW' => 'Aruba',
				'AU' => 'Australia',
				'AT' => 'Austria',
				'AZ' => 'Azerbaijan',
				'BS' => 'Bahamas',
				'BH' => 'Bahrain',
				'BD' => 'Bangladesh',
				'BB' => 'Barbados',
				'BY' => 'Belarus',
				'BE' => 'Belgium',
				'BZ' => 'Belize',
				'BJ' => 'Benin',
				'BM' => 'Bermuda',
				'BT' => 'Bhutan',
				'BO' => 'Bolivia',
				'BA' => 'Bosnia And Herzegovina',
				'BW' => 'Botswana',
				'BV' => 'Bouvet Island',
				'BR' => 'Brazil',
				'IO' => 'British Indian Ocean Territory',
				'BN' => 'Brunei Darussalam',
				'BG' => 'Bulgaria',
				'BF' => 'Burkina Faso',
				'BI' => 'Burundi',
				'KH' => 'Cambodia',
				'CM' => 'Cameroon',
				'CA' => 'Canada',
				'CV' => 'Cape Verde',
				'KY' => 'Cayman Islands',
				'CF' => 'Central African Republic',
				'TD' => 'Chad',
				'CL' => 'Chile',
				'CN' => 'China',
				'CX' => 'Christmas Island',
				'CC' => 'Cocos (Keeling) Islands',
				'CO' => 'Colombia',
				'KM' => 'Comoros',
				'CG' => 'Congo',
				'CD' => 'Congo, Democratic Republic',
				'CK' => 'Cook Islands',
				'CR' => 'Costa Rica',
				'CI' => 'Cote D\'Ivoire',
				'HR' => 'Croatia',
				'CU' => 'Cuba',
				'CY' => 'Cyprus',
				'CZ' => 'Czech Republic',
				'DK' => 'Denmark',
				'DJ' => 'Djibouti',
				'DM' => 'Dominica',
				'DO' => 'Dominican Republic',
				'EC' => 'Ecuador',
				'EG' => 'Egypt',
				'SV' => 'El Salvador',
				'GQ' => 'Equatorial Guinea',
				'ER' => 'Eritrea',
				'EE' => 'Estonia',
				'ET' => 'Ethiopia',
				'FK' => 'Falkland Islands (Malvinas)',
				'FO' => 'Faroe Islands',
				'FJ' => 'Fiji',
				'FI' => 'Finland',
				'FR' => 'France',
				'GF' => 'French Guiana',
				'PF' => 'French Polynesia',
				'TF' => 'French Southern Territories',
				'GA' => 'Gabon',
				'GM' => 'Gambia',
				'GE' => 'Georgia',
				'DE' => 'Germany',
				'GH' => 'Ghana',
				'GI' => 'Gibraltar',
				'GR' => 'Greece',
				'GL' => 'Greenland',
				'GD' => 'Grenada',
				'GP' => 'Guadeloupe',
				'GU' => 'Guam',
				'GT' => 'Guatemala',
				'GG' => 'Guernsey',
				'GN' => 'Guinea',
				'GW' => 'Guinea-Bissau',
				'GY' => 'Guyana',
				'HT' => 'Haiti',
				'HM' => 'Heard Island & Mcdonald Islands',
				'VA' => 'Holy See (Vatican City State)',
				'HN' => 'Honduras',
				'HK' => 'Hong Kong',
				'HU' => 'Hungary',
				'IS' => 'Iceland',
				'IN' => 'India',
				'ID' => 'Indonesia',
				'IR' => 'Iran, Islamic Republic Of',
				'IQ' => 'Iraq',
				'IE' => 'Ireland',
				'IM' => 'Isle Of Man',
				'IL' => 'Israel',
				'IT' => 'Italy',
				'JM' => 'Jamaica',
				'JP' => 'Japan',
				'JE' => 'Jersey',
				'JO' => 'Jordan',
				'KZ' => 'Kazakhstan',
				'KE' => 'Kenya',
				'KI' => 'Kiribati',
				'KR' => 'Korea',
				'KW' => 'Kuwait',
				'KG' => 'Kyrgyzstan',
				'LA' => 'Lao People\'s Democratic Republic',
				'LV' => 'Latvia',
				'LB' => 'Lebanon',
				'LS' => 'Lesotho',
				'LR' => 'Liberia',
				'LY' => 'Libyan Arab Jamahiriya',
				'LI' => 'Liechtenstein',
				'LT' => 'Lithuania',
				'LU' => 'Luxembourg',
				'MO' => 'Macao',
				'MK' => 'North Macedonia',
				'MG' => 'Madagascar',
				'MW' => 'Malawi',
				'MY' => 'Malaysia',
				'MV' => 'Maldives',
				'ML' => 'Mali',
				'MT' => 'Malta',
				'MH' => 'Marshall Islands',
				'MQ' => 'Martinique',
				'MR' => 'Mauritania',
				'MU' => 'Mauritius',
				'YT' => 'Mayotte',
				'MX' => 'Mexico',
				'FM' => 'Micronesia, Federated States Of',
				'MD' => 'Moldova',
				'MC' => 'Monaco',
				'MN' => 'Mongolia',
				'ME' => 'Montenegro',
				'MS' => 'Montserrat',
				'MA' => 'Morocco',
				'MZ' => 'Mozambique',
				'MM' => 'Myanmar',
				'NA' => 'Namibia',
				'NR' => 'Nauru',
				'NP' => 'Nepal',
				'NL' => 'Netherlands',
				'AN' => 'Netherlands Antilles',
				'NC' => 'New Caledonia',
				'NZ' => 'New Zealand',
				'NI' => 'Nicaragua',
				'NE' => 'Niger',
				'NG' => 'Nigeria',
				'NU' => 'Niue',
				'NF' => 'Norfolk Island',
				'MP' => 'Northern Mariana Islands',
				'NO' => 'Norway',
				'OM' => 'Oman',
				'PK' => 'Pakistan',
				'PW' => 'Palau',
				'PS' => 'Palestinian Territory, Occupied',
				'PA' => 'Panama',
				'PG' => 'Papua New Guinea',
				'PY' => 'Paraguay',
				'PE' => 'Peru',
				'PH' => 'Philippines',
				'PN' => 'Pitcairn',
				'PL' => 'Poland',
				'PT' => 'Portugal',
				'PR' => 'Puerto Rico',
				'QA' => 'Qatar',
				'RE' => 'Reunion',
				'RO' => 'Romania',
				'RU' => 'Russian Federation',
				'RW' => 'Rwanda',
				'BL' => 'Saint Barthelemy',
				'SH' => 'Saint Helena',
				'KN' => 'Saint Kitts And Nevis',
				'LC' => 'Saint Lucia',
				'MF' => 'Saint Martin',
				'PM' => 'Saint Pierre And Miquelon',
				'VC' => 'Saint Vincent And Grenadines',
				'WS' => 'Samoa',
				'SM' => 'San Marino',
				'ST' => 'Sao Tome And Principe',
				'SA' => 'Saudi Arabia',
				'SN' => 'Senegal',
				'RS' => 'Serbia',
				'SC' => 'Seychelles',
				'SL' => 'Sierra Leone',
				'SG' => 'Singapore',
				'SK' => 'Slovakia',
				'SI' => 'Slovenia',
				'SB' => 'Solomon Islands',
				'SO' => 'Somalia',
				'ZA' => 'South Africa',
				'GS' => 'South Georgia And Sandwich Isl.',
				'ES' => 'Spain',
				'LK' => 'Sri Lanka',
				'SD' => 'Sudan',
				'SR' => 'Suriname',
				'SJ' => 'Svalbard And Jan Mayen',
				'SZ' => 'Swaziland',
				'SE' => 'Sweden',
				'CH' => 'Switzerland',
				'SY' => 'Syrian Arab Republic',
				'TW' => 'Taiwan',
				'TJ' => 'Tajikistan',
				'TZ' => 'Tanzania',
				'TH' => 'Thailand',
				'TL' => 'Timor-Leste',
				'TG' => 'Togo',
				'TK' => 'Tokelau',
				'TO' => 'Tonga',
				'TT' => 'Trinidad And Tobago',
				'TN' => 'Tunisia',
				'TR' => 'Turkey',
				'TM' => 'Turkmenistan',
				'TC' => 'Turks And Caicos Islands',
				'TV' => 'Tuvalu',
				'UG' => 'Uganda',
				'UA' => 'Ukraine',
				'AE' => 'United Arab Emirates',
				'GB' => 'United Kingdom',
				'US' => 'United States',
				'UM' => 'United States Outlying Islands',
				'UY' => 'Uruguay',
				'UZ' => 'Uzbekistan',
				'VU' => 'Vanuatu',
				'VE' => 'Venezuela',
				'VN' => 'Viet Nam',
				'VG' => 'Virgin Islands, British',
				'VI' => 'Virgin Islands, U.S.',
				'WF' => 'Wallis And Futuna',
				'EH' => 'Western Sahara',
				'YE' => 'Yemen',
				'ZM' => 'Zambia',
				'ZW' => 'Zimbabwe',
			);
			$this->countries = $countries;
		}


		/**
		 * Load the list of language codes.
		 *
		 * This method initializes the `language_codes` property with an array of language codes
		 * and their corresponding language names.
		 *
		 * @return void
		 */
		private function load_language_codes(): void {
			$languages_codes      = array(
				'en' => 'English',
				'da' => 'Danish',
				'de' => 'German',
				'el' => 'Greek',
				'es' => 'Spanish',
				'et' => 'Estonian',
				'fr' => 'French',
				'it' => 'Italian',
				'nl' => 'Dutch',
				'no' => 'Norwegian',
				'sv' => 'Swedish',
			);
			$this->language_codes = $languages_codes;
		}


		/**
		 * Load the Yes/No options.
		 *
		 * This method initializes the `yes_no` property with an array of Yes/No options.
		 *
		 * @return void
		 */
		private function load_yes_no(): void {
			$yes_no       = array(
				'yes' => 'Yes',
				'no'  => 'No',
			);
			$this->yes_no = $yes_no;
		}


		/**
		 * Load the lawful bases for data processing.
		 *
		 * This method initializes the `lawful_bases` property with an array of lawful bases
		 * for data processing. If the region is Brazil and multiple regions are not enabled,
		 * it includes additional Brazil-specific lawful bases.
		 *
		 * @return void
		 */
		private function load_lawful_bases(): void {
			$base_lawful_bases = array(
				'1' => 'I obtain permission from the person concerned',
				'2' => 'It is necessary for the execution of an agreement with the person concerned',
				'3' => 'I am obligated by law',
				'4' => 'It is necessary to fulfill a task concerning public law',
				'5' => 'It is necessary for my own legitimate interest, and that interest outweighs the interest of the person concerned',
				'6' => 'It is necessary to protect the life or physical safety of a person',
			);

			if ( cmplz_has_region( 'br' ) && ! cmplz_multiple_regions() ) {
				$brazil_specific_bases = array(
					'7'  => 'It is necessary to carry out studies by a research body, ensuring, whenever possible, the anonymization of personal data',
					'8'  => 'It is necessary for the regular exercise of rights in judicial, administrative or arbitration proceedings',
					'9'  => 'It is necessary for the protection of health, exclusively, in a procedure performed by health professionals, health services or health authority',
					'10' => 'It is necessary for credit protection',
				);

				$this->lawful_bases = array_merge( $base_lawful_bases, $brazil_specific_bases );
			} else {
				$this->lawful_bases = $base_lawful_bases;
			}
		}


		/**
		 * Load the list of placeholders.
		 *
		 * This method initializes the `placeholders` property with an array of placeholder names
		 * and their corresponding descriptions.
		 *
		 * @return void
		 */
		private function load_placeholders(): void {
			$placeholders       = array(
				'default'          => 'Default',
				'calendly'         => 'Calendly',
				'facebook'         => 'Facebook',
				'google-maps'      => 'Google Maps',
				'google-recaptcha' => 'Google Recaptcha',
				'instagram'        => 'Instagram',
				'openstreetmaps'   => 'Open Street Maps',
				'soundcloud'       => 'SoundCloud',
				'spotify'          => 'Spotify',
				'ted'              => 'Ted',
				'twitter'          => 'Twitter',
				'tiktok'           => 'Tik Tok',
			);
			$this->placeholders = $placeholders;
		}


		/**
		 * Load the purposes for data processing.
		 *
		 * This method initializes the `purposes` property with an array of purposes
		 * for which data may be processed, including descriptions of each purpose.
		 *
		 * @return void
		 */
		private function load_purposes(): void {
			$purposes       = array(
				'contact'                     => 'Contact - Through phone, mail, email and/or webforms',
				'payments'                    => 'Payments',
				'register-account'            => 'Registering an account',
				'newsletters'                 => 'Newsletters',
				'support-services'            => 'To support services or products that a customer wants to buy or has purchased',
				'legal-obligations'           => 'To be able to comply with legal obligations',
				'statistics'                  => 'Compiling and analyzing statistics for website improvement.',
				'offer-personalized-products' => 'To be able to offer personalized products and services',
				'selling-data-thirdparty'     => 'To sell or share data with a third party',
				'deliveries'                  => 'Deliveries',
			);
			$this->purposes = $purposes;
		}


		/**
		 * Load the US purposes for data processing.
		 *
		 * This method initializes the `purposes` property with an array of purposes
		 * for which data may be processed, including descriptions of each purpose.
		 *
		 * @return void
		 */
		private function load_details_per_purpose_us(): void {
			$details_per_purpose_us       = array(
				'first-lastname'          => 'A first and last name',
				'accountname-alias'       => 'Account name or alias',
				'address'                 => 'A home or other physical address, including street name and name of a city or town',
				'email'                   => 'An email address',
				'phone'                   => 'A telephone number',
				'ip'                      => 'IP Address',
				'internet'                => "Internet activity information, including, but not limited to, browsing history, search history, and information regarding a consumer's interaction with an Internet Web site, application, or advertisement",
				'geo'                     => 'Geolocation data',
				'marital-status'          => 'Marital status',
				'date-of-birth'           => 'Date of birth',
				'sex'                     => 'Sex',
				'photos'                  => 'Photos',
				'social-media'            => 'Social Media accounts',
				'social-security'         => 'A social security number',
				'signature'               => 'A signature',
				'physical-characteristic' => 'Physical characteristics or description',
				'passport'                => 'Passport number',
				'drivers-license'         => "Driver's license",
				'state-id'                => 'State identification card number',
				'insurance-policy'        => 'Insurance policy number',
				'education'               => 'Education information',
				'employment'              => 'Professional or employment-related information',
				'employment-history'      => 'Employment history',
				'financial-information'   => 'Financial information such as bank account number or credit card number',
				'medical'                 => 'Medical information',
				'health-insurcance'       => 'Health insurance information',
				'commercial'              => 'Commercial information, including records of personal property, products or services purchased, obtained, or considered',
				'biometric'               => 'Biometric information',
				'audio'                   => 'Audio, electronic, visual, thermal, olfactory, or similar information',
			);
			$this->details_per_purpose_us = $details_per_purpose_us;
		}


		/**
		 * Load the collected information for children.
		 *
		 * This method initializes the `collected_info_children` property with an array of information
		 * that may be collected from children, including descriptions of each type of information.
		 *
		 * @return void
		 */
		private function load_collected_info_children(): void {
			$collected_info_children       = array(
				'name'               => 'a first and last name',
				'address'            => 'a home or other physical address including street name and name of a city or town',
				'email-child'        => 'an email address from the child',
				'email-parent'       => 'an email address from the parent or guardian',
				'phone'              => 'a telephone number',
				'social-security-nr' => 'a Social Security number',
				'identifier-online'  => 'an identifier that permits the physical or online contacting of a child',
				'other'              => 'other information concerning the child or the parents, combined with an identifier as described above',
			);
			$this->collected_info_children = $collected_info_children;
		}


		/**
		 * Load the list of generic documents.
		 *
		 * This method initializes the `generic_documents_list` property with an array of generic documents,
		 * including their titles and whether they can redirect based on the region.
		 *
		 * @return void
		 */
		private function load_generic_documents_list(): void {
			$generic_documents_list       = array(
				'cookie-statement'           => array(
					'can_region_redirect' => true,
					'title'               => 'Cookie Policy',
				),
				'privacy-statement'          => array(
					'can_region_redirect' => true,
					'title'               => 'Privacy Statement',
				),
				'privacy-statement-children' => array(
					'can_region_redirect' => true,
					'title'               => "Children's statement",
				),
				'impressum'                  => array(
					'can_region_redirect' => false,
					'title'               => 'Impressum',
				),
				'disclaimer'                 => array(
					'can_region_redirect' => false,
					'title'               => 'Disclaimer',
				),
			);
			$this->generic_documents_list = $generic_documents_list;
		}


		/**
		 * Get the translated generic documents list.
		 *
		 * This method maps over the `generic_documents_list` array and translates the title of each document.
		 *
		 * @return array The array of generic documents with translated titles.
		 *
		 * @see self::__get() This method is dynamically accessed through __get().
		 */
		private function get_i18n_generic_documents_list(): array {
			/**
			 * Translate the title of a document.
			 *
			 * @param array $document The document array.
			 * @return array The document array with the translated title.
			 */
			return array_map(
				function ( array $document ) {
					$document['title'] = __( $document['title'], 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					return $document;
				},
				$this->generic_documents_list
			);
		}


		/**
		 * Load the pages configurations.
		 *
		 * This method initializes the `pages` property with an array of page configurations
		 * for different regions, including titles, public visibility, document elements, and conditions.
		 *
		 * @return void
		 */
		private function load_pages() {
			$pages = array(
				'eu'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (EU)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (EU)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'us'  => array(
					'cookie-statement'  => array(
						'title'             => 'Opt-out preferences',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (US)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),

				),
				'uk'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (UK)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (UK)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'ca'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (CA)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (CA)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'au'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (AU)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (AU)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'za'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (ZA)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (ZA)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'br'  => array(
					'cookie-statement'  => array(
						'title'             => 'Cookie Policy (BR)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'cookie-statement' => 'generated',
						),
					),
					'privacy-statement' => array(
						'title'             => 'Privacy Statement (BR)',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'privacy-statement' => 'generated',
						),
					),
				),
				'all' => array(
					'impressum' => array(
						'title'             => 'Imprint',
						'public'            => true,
						'document_elements' => '',
						'condition'         => array(
							'impressum' => 'generated',
						),
					),

				),
			);
			$this->pages = $pages;
		}


		/**
		 * Get the translated pages configurations.
		 *
		 * This method iterates over the `pages` property and translates the title of each page
		 * for different regions.
		 *
		 * @return array The array of pages with translated titles.
		 *
		 * @see self::__get() This method is dynamically accessed through __get().
		 */
		private function get_i18n_pages(): array {
			$translated_pages = array();
			foreach ( $this->pages as $region => $pages ) {
				foreach ( $pages as $page_key => $page_data ) {
					if ( isset( $page_data['title'] ) ) {
						$translated_pages[ $region ][ $page_key ]          = $page_data;
						$translated_pages[ $region ][ $page_key ]['title'] = __( $page_data['title'], 'complianz-gdpr' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					}
				}
			}
			return $translated_pages;
		}
	}
}
