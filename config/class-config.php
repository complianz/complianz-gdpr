<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_config" ) ) {
	class cmplz_config {
		private static $_this;
		public $fields = array();
		public $formal_languages = array();
		public $generic_documents_list;
		public $supported_states;
		public $cookie_consent_converter;
		public $language_codes;
		public $supported_regions;
		public $thirdparty_services
			= array(
				'activecampaign'   => 'Active Campaign',
				"adobe-fonts"      => 'Adobe Fonts',
				'google-fonts'     => 'Google Fonts',
				'google-recaptcha' => 'Google reCAPTCHA',
				"google-maps"      => 'Google Maps',
				"openstreetmaps"   => 'OpenStreetMaps',
				"vimeo"            => 'Vimeo',
				"youtube"          => 'YouTube',
				"videopress"       => 'VideoPress',
				"dailymotion"      => 'Dailymotion',
				"soundcloud"       => 'SoundCloud',
				"twitch"           => 'Twitch',
				"paypal"           => 'PayPal',
				"spotify"          => 'Spotify',
				"hotjar"           => 'Hotjar',
				"addthis"          => 'AddThis',
				"addtoany"         => 'AddToAny',
				"sharethis"        => 'ShareThis',
				"livechat"         => 'LiveChat',
				"hubspot"          => 'HubSpot',
				"calendly"         => 'Calendly',
				"microsoftads"     => 'Microsoft Ads'
			);

		public $thirdparty_socialmedia
			= array(
				'facebook'  => 'Facebook',
				'twitter'   => 'Twitter',
				'linkedin'  => 'LinkedIn',
				'whatsapp'  => 'WhatsApp',
				'instagram' => 'Instagram',
				'tiktok' 	=> 'TikTok',
				'disqus'    => 'Disqus',
				'pinterest' => 'Pinterest',
			);



		/**
		 * The services for which a placeholder exists in the assets/images/placeholders folder.
		 * @var array
		 */
		public $placeholders;

		/**
		 * This is used in the scan function to tell the user he/she uses social media
		 * Also in the function to determine a media type for the placeholders
		 * Based on this the cookie warning is enabled.
		 *
		 * */

		public $social_media_markers
			= array(
				"linkedin"  => array(
					"platform.linkedin.com",
					'addthis_widget.js',
					'linkedin.com/embed/feed'
				),
				"twitter"   => array(
					'super-socializer',
					'sumoSiteId',
					'addthis_widget.js',
					"platform.twitter.com",
					'twitter-widgets.js'
				),
				"facebook"  => array(
					'fbq',
					'super-socializer',
					'sumoSiteId',
					'addthis_widget.js',
					"fb-root",
					"<!-- Facebook Pixel Code -->",
					'connect.facebook.net',
					'www.facebook.com/plugins',
					'pixel-caffeine',
					'facebook.com/plugins',
				),
				"pinterest" => array(
					'super-socializer',
					'assets.pinterest.com'
				),
				"disqus"    => array( 'disqus.com' ),
				"tiktok"    => array( 'tiktok.com' ),
				"instagram" => array(
					'instawidget.net/js/instawidget.js',
					'instagram.com',
				),
			);

		/**
		 * Scripts with this string in the content get listed in the third party list.
		 * Also used in cmplz_placeholder()
		 * */

		public $thirdparty_service_markers
			= array(
				"google-maps"      => array(
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
				"soundcloud"       => array( 'w.soundcloud.com/player' ),
				"openstreetmaps"   => array(
					'openstreetmap.org',
					'osm/js/osm'
				),
				"vimeo"            => array(
					'player.vimeo.com',
					'i.vimeocdn.com',
				),
				"google-recaptcha" => array(
					'google.com/recaptcha',
					'grecaptcha',
					'recaptcha.js',
					'recaptcha/api'
				),
				"youtube"          => array(
					'youtube.com',
					'youtube-nocookie.com',
					'youtu.be',
					'yotuwp',
				),
				"videopress"       => array(
					'videopress.com/embed',
					'videopress.com/videopress-iframe.js'
				),
				"dailymotion"      => array( 'dailymotion.com/embed/video/' ),
				"hotjar"           => array( 'static.hotjar.com' ),
				"spotify"          => array( 'open.spotify.com/embed' ),
				"google-fonts"     => array( 'fonts.googleapis.com' ),
				"paypal"           => array(
					'www.paypal.com/tagmanager/pptm.js',
					'www.paypalobjects.com/api/checkout.js'
				),
				"disqus"           => array( 'disqus.com' ),
				"addthis"          => array( 'addthis.com' ),
				"addtoany" 				 => array( 'static.addtoany.com/menu/page.js' ),
				"sharethis"        => array( 'sharethis.com' ),
				"microsoftads"     => array('bat.bing.com'),
				"livechat"         => array( 'cdn.livechatinc.com/tracking.js' ),
				"hubspot"         => array( 'js.hs-scripts.com/', 'hbspt.forms.create', 'js.hsforms.net','track.hubspot.com','js.hs-analytics.net'),
				"calendly"         => array( 'assets.calendly.com' ),
				"twitch"          => array( 'twitch.tv', 'player.twitch.tv'),
				"adobe-fonts"    => array( 'p.typekit.net', 'use.typekit.net'),
			);

		public $stats
			= array(
				'google-analytics'   => 'Google Analytics',
				'google-tag-manager' => 'Tag Manager',
				'matomo'             => 'Matomo',
				'clicky'             => 'Clicky',
				'yandex'             => 'Yandex',
				'clarity'            => 'Clarity',

			);
		public $stats_markers = array(
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
				'matomo' => array( 'piwik.js', 'matomo.js' ),
				'clicky' => array( 'static.getclicky.com/js', 'clicky_site_ids' ),
				'yandex' => array( 'mc.yandex.ru/metrika/watch.js' ),
				'clarity' => array( 'clarity.ms' ),
			);

		public $amp_tags
			= array(
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

		public $lawful_bases;

		public $sections;
		public $pages = array();
		public $warning_types;
		public $yes_no;
		public $countries;
		public $purposes;
		public $details_per_purpose_us;
		public $regions;
		public $eu_countries;
		public $collected_info_children;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
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
			 * Preload fields with a filter, to allow for overriding types
			 */
			// load countries, purpose, documents, common types...  on init because of the translated strings
			add_action( 'init', array($this, 'load_translated' ), 0 );
			add_action( 'init', array( $this, 'init' ), 5 );
			add_action( 'init', array( $this, 'load_pages' ), 10 );
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Loads configuration files and translations at the 'init' action to avoid early
		 * loading issues for the `complianz-gdpr` text domain, as required by WordPress 6.7.
		 *
		 * @return void
		 */
		public function load_translated():void {
			// load common types
			$this->load_common_types();
			// require all files with translated strings
			require_once( cmplz_path . '/config/countries.php' );
			require_once( cmplz_path . '/config/purpose.php' );
			require_once( cmplz_path . '/config/documents/documents.php' );
			require_once( cmplz_path . 'settings/config/config.php' );
			if ( file_exists(cmplz_path . 'pro/settings/config.php') ) {
				require_once( cmplz_path . 'pro/settings/config.php');
				require_once( cmplz_path . 'pro/config/dynamic-document-elements.php');
			}
			require_once( cmplz_path . '/cookiebanner/settings.php' );
		}

		/**
		 * Load common types used in the plugin.
		 *
		 *
		 * @return void
		 */
		private function load_common_types():void {
			$this->yes_no = array(
				'yes' => __( 'Yes', 'complianz-gdpr' ),
				'no'  => __( 'No', 'complianz-gdpr' ),
			);

			$this->lawful_bases = [
				'1' => __('I obtain permission from the person concerned', 'complianz-gdpr'),
				'2' => __('It is necessary for the execution of an agreement with the person concerned', 'complianz-gdpr'),
				'3' => __('I am obligated by law', 'complianz-gdpr'),
				'4' => __('It is necessary to fulfilll a task concerning public law', 'complianz-gdpr'),
				'5' => __('It is necessary for my own legitimate interest, and that interest outweighs the interest of the person concerned', 'complianz-gdpr'),
				'6' => __('It is necessary to protect the life or physical safety of a person', 'complianz-gdpr'),
			];

			$this->placeholders = array(
				'default' => __('Default','complianz-gdpr'),
				'calendly' => 'Calendly',
				'facebook' => 'Facebook',
				'google-maps' => 'Google Maps',
				'google-recaptcha' => 'Google Recaptcha',
				'instagram' => 'Instagram',
				'openstreetmaps' => 'Open Street Maps',
				'soundcloud' => 'SoundCloud',
				'spotify' => 'Spotify',
				'ted' => 'Ted',
				'twitter' => 'Twitter',
				'tiktok' => 'Tik Tok'
			);
		}

		/**
		 * Get full array of regions, but only active ones
		 * @return array
		 */
		public function active_regions(){
			return array_intersect_key( COMPLIANZ::$config->regions, cmplz_get_regions(false, 'short') );
		}

		/**
		 * For the Brazil privacy law there are some additional options. These should only be enabled when the only chosen region is Brazil.
		 */
		private function load_additional_br_options() {
			if ( !cmplz_has_region('br') || cmplz_multiple_regions() ) {
				return;
			}

			$this->lawful_bases['7'] = __('It is necessary to carry out studies by a research body, ensuring, whenever possible, the anonymization of personal data', 'complianz-gdpr');
			$this->lawful_bases['8'] = __('It is necessary for the regular exercise of rights in judicial, administrative or arbitration proceedings', 'complianz-gdpr');
			$this->lawful_bases['9'] = __('It is necessary for the protection of health, exclusively, in a procedure performed by health professionals, health services or health authority', 'complianz-gdpr');
			$this->lawful_bases['10'] = __('It is necessary for credit protection', 'complianz-gdpr');
		}

		private function load_documents() {
			$files = [];
			$regions = cmplz_get_regions();
			$privacy_statement = cmplz_get_option( 'privacy-statement' ) === 'generated';
			$files[] = '/pro/config/documents/documents.php';
			foreach ($regions as $region) {
				$files[] = "/config/documents/cookie-policy-$region.php";
				if ($privacy_statement) $files[] = "/pro/config/documents/$region/privacy-policy.php";
				$files[] = "/pro/config/documents/$region/privacy-policy-children.php";
			}

			if (cmplz_get_option( 'disclaimer' ) === 'generated') $files[] = '/pro/config/documents/disclaimer.php';
			if (cmplz_get_option( 'impressum' ) === 'generated') $files[] = '/pro/config/documents/impressum.php';

			foreach ($files as $file) {
				if ( file_exists( cmplz_path . $file ) ) {
					require_once( cmplz_path . $file );
				}
			}
		}

		public function init() {
			// load br additional options
			$this->load_additional_br_options();
			// load documents
			$this->load_documents();

			$this->stats_markers = apply_filters( 'cmplz_stats_markers', $this->stats_markers );
			//ensure that indexes are sequential with array_values
			$this->fields = array_values(apply_filters( 'cmplz_fields', [] ));
		}

		public function load_pages(){
			$this->pages = apply_filters( 'cmplz_pages_load_types', $this->pages );
			$regions = cmplz_get_regions();
			foreach ($regions as $region) {
				if ( !isset( $this->pages[ $region ] ) ) {
					continue;
				}
				foreach ( $this->pages[ $region ] as $type => $data ) {
					if ( !isset( $this->pages[ $region ][ $type ]['document_elements'] ) ) {
						continue;
					}
					$this->pages[ $region ][ $type ]['document_elements'] = apply_filters( 'cmplz_document_elements', $this->pages[ $region ][ $type ]['document_elements'], $region, $type, $this->fields );
				}
			}
		}

	} //class closure
}
