<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
if ( cmplz_admin_logged_in() ) {
	require_once( 'admin/integrations.php' );
}
require_once( trailingslashit(CMPLZ_PATH) . 'integrations/forms.php' );

global $cmplz_integrations_list;
$cmplz_integrations_list = apply_filters( 'cmplz_integrations', array(
	'advanced-nocaptcha-recaptcha' => array(
			'constant_or_function' => 'ANR_PLUGIN_VERSION',
			'label'                => 'Advanced noCaptcha & invisible Captcha',
			'firstparty_marketing' => false,
	),

	'advanced-ads' => array(
			'constant_or_function' => 'ADVADS_VERSION',
			'label'                => 'Advanced Ads',
			'firstparty_marketing' => false,
	),

//	'hcaptcha' => array(
//			'constant_or_function' => 'HCAPTCHA_VERSION',
//			'label'                => 'hCaptcha for WordPress',
//			'firstparty_marketing' => false,
//	),


	'wp-consent-api' => array(
			'constant_or_function' => 'WP_CONSENT_API_VERSION',
			'label'                => 'WP Consent API',
			'firstparty_marketing' => false,
	),

	'omnisend' => array(
			'constant_or_function' => 'OMNISEND_SETTINGS_PAGE',
			'label'                => 'Email Marketing for WooCommerce by Omnisend',
			'firstparty_marketing' => false,
	),

	'bb-powerpack' => array(
			'constant_or_function' => 'BB_PowerPack',
			'label'                => 'Beaver Builder Power Pack',
			'firstparty_marketing' => false,
	),
	'content-views-plugin' => array(
			'constant_or_function' => 'PT_CV_VERSION',
			'label'                => 'Content Views – Post Grid & Filter for WordPress',
			'firstparty_marketing' => false,
	),
	'divi' => array(
			'constant_or_function' => 'Divi',
			'label'                => 'Divi',
			'firstparty_marketing' => false,
	),
	'divi-plugin' => array(
			'constant_or_function' => 'ET_BUILDER_PLUGIN_DIR',
			'label'                => 'Divi Plugin',
			'firstparty_marketing' => false,
	),
	'agile-store-locator' => array(
			'constant_or_function' => 'ASL_PLUGIN_PATH',
			'label'                => 'Agile Store Locator',
			'firstparty_marketing' => false,
	),
	'lead-forensics' => array(
			'constant_or_function' => 'LFRTrackingCode',
			'label'                => 'Lead Forensics',
			'firstparty_marketing' => false,
	),

	'mailchimp-woocommerce' => array(
			'constant_or_function' => 'MAILCHIMP_WOOCOMMERCE_NEWSLETTER_VERSION',
			'label'                => 'Mailchimp for Woocommerce',
			'firstparty_marketing' => false,
	),

	'burst-statistics' => array(
			'constant_or_function' => 'burst_version',
			'label'                => 'Burst Statistics',
			'firstparty_marketing' => false,
	),
	'beaver-builder' => array(
		'constant_or_function' => 'FL_BUILDER_VERSION',
		'label'                => 'Beaver Builder',
		'firstparty_marketing' => false,
	),

	'theeventscalendar' => array(
			'constant_or_function' => 'TRIBE_EVENTS_FILE',
			'label'                => 'The Events Calendar',
			'firstparty_marketing' => false,
	),

	'themify-builder' => array(
			'constant_or_function' => 'themify_builder_theme_check',
			'label'                => 'Themify Builder',
			'firstparty_marketing' => false,
	),

	'meks-easy-maps' => array(
			'constant_or_function' => 'MKS_MAP_VER',
			'label'                => 'Meks Easy Maps',
			'firstparty_marketing' => false,
	),

	'wp-video-lightbox' => array(
			'constant_or_function' => 'WP_VIDEO_LIGHTBOX_VERSION',
			'label'                => 'WP Video Lightbox',
			'firstparty_marketing' => false,
	),

	'woocommerce-variation-swatches' => array(
			'constant_or_function' => 'woo_variation_swatches',
			'label'                => 'Variation Swatches for WooCommerce',
			'firstparty_marketing' => false,
	),

	'ultimate-addons-elementor' => array(
			'constant_or_function' => 'UAEL_FILE',
			'label'                => 'Ultimate Addons for Elementor - Google Maps',
			'firstparty_marketing' => false,
	),

	'invisible-recaptcha' => array(
			'constant_or_function' => 'InvisibleReCaptcha',
			'label'                => 'Google Invisible reCaptcha voor WordPress',
			'firstparty_marketing' => false,
	),

  'easy-fancybox' => array(
	 	  'constant_or_function' => 'EASY_FANCYBOX_VERSION',
		  'label'                => 'Easy FancyBox',
		  'firstparty_marketing' => false,
  ),

	'novo-map' => array(
			'constant_or_function' => 'NOVO_MAP_VERSION',
			'label'                => 'Novo-Map',
			'firstparty_marketing' => false,
	),

	'wpadverts' => array(
		'constant_or_function' => 'ADVERTS_PATH',
		'label'                => 'WP Adverts',
		'firstparty_marketing' => false,
	),

	'citadela-directory' => array(
			'constant_or_function' => 'CITADELA_DIRECTORY_LITE_PLUGIN',
			'label'                => 'Citadela Directory',
			'firstparty_marketing' => false,
	),

	'elementor' => array(
			'constant_or_function' => 'ELEMENTOR_VERSION',
			'label'                => 'Elementor',
			'firstparty_marketing' => false,
	),

	'elementor-pro/elementor-pro' => array(
			'constant_or_function' => 'ELEMENTOR_PRO_VERSION',
			'label'                => 'Elementor Pro',
			'firstparty_marketing' => false,
	),

	'nudgify'          => array(
		'constant_or_function' => 'NUDGIFY_PLUGIN_VERSION',
		'label'                => 'Nudgify',
		'firstparty_marketing' => false,
	),

	'generatepress-maps'          => array(
		'constant_or_function' => 'GeneratePress',
		'label'                => 'GeneratePress Maps',
		'firstparty_marketing' => false,
	),

	'avada-maps'          => array(
		'constant_or_function' => 'FUSION_BUILDER_VERSION',
		'label'                => 'Avada Fusion Builder',
		'firstparty_marketing' => false,
	),

	'volocation'          => array(
		'constant_or_function' => 'VOSL_VERSION',
		'label'                => 'VO Locator',
		'firstparty_marketing' => false,
	),

	'trustpulse'          => array(
		'constant_or_function' => 'TRUSTPULSE_PLUGIN_VERSION',
		'label'                => 'TrustPulse',
		'firstparty_marketing' => false,
	),

	'addtoany'          => array(
		'constant_or_function' => 'A2A_SHARE_SAVE_init',
		'label'                => 'Add To Any',
		'firstparty_marketing' => false,
	),

	'amp'               => array(
		'constant_or_function' => 'AMP__VERSION',
		'label'                => 'AMP (official AMP plugin for WordPress)',
		'firstparty_marketing' => false,
	),

	'podcast-player'         => array(
		'constant_or_function' => 'PODCAST_PLAYER_VERSION',
		'label'                => 'Podcast Player',
		'firstparty_marketing' => false,
	),

	'google-maps-easy'               => array(
		'constant_or_function' => 'toeGetClassNameGmp',
		'label'                => 'Google Maps Easy',
		'firstparty_marketing' => false,
	),

	'flexible-map'               => array(
			'constant_or_function' => 'FLXMAP_PLUGIN_VERSION',
			'label'                => 'Flexible Map',
			'firstparty_marketing' => false,
	),

	'activecampaign'               => array(
		'constant_or_function' => 'ACTIVECAMPAIGN_URL',
		'label'                => 'Active Campaign',
		'firstparty_marketing' => true,
	),

	'google-site-kit'               => array(
		'constant_or_function' => 'GOOGLESITEKIT_VERSION',
		'label'                => 'Google Site Kit',
	),

	'clarity'               => array(
		'constant_or_function' => 'clarity_register_settings',
		'label'                => 'Microsoft Clarity',
	),

	'beehive'           => array(
		'constant_or_function' => 'BEEHIVE_PRO',
		'label'                => 'Beehive',
		'firstparty_marketing' => false,
	),
	'easy-liveblogs' => array(
		'constant_or_function' => 'ELB_NAME',
		'label'                => 'Easy Liveblogs',
		'firstparty_marketing' => false,
	),

	'simple-business-directory' => array(
		'constant_or_function' => 'QCSBD_DIR',
		'label'                => 'Simple Business Directory',
		'firstparty_marketing' => false,
	),

	'acf'           => array(
		'constant_or_function' => 'ACF_VERSION',
		'label'                => 'Advanced Custom Fields',
		'firstparty_marketing' => false,
	),

	'pixelyoursite'     => array(
		'constant_or_function' => 'PYS_FREE_VERSION',
		'label'                => 'PixelYourSite',
		'firstparty_marketing' => false,
	),

	'pixelyoursite-pro'     => array(
			'constant_or_function' => 'PYS_VERSION',
			'label'                => 'PixelYourSite Pro',
			'firstparty_marketing' => false,
	),

	'pixelyoursite-pinterest'     => array(
			'constant_or_function' => 'PYS_PINTEREST_VERSION',
			'label'                => 'PixelYourSite Pinterest',
			'firstparty_marketing' => false,
	),

	'pixelyoursite-bing'     => array(
			'constant_or_function' => 'PYS_BING_VERSION',
			'label'                => 'PixelYourSite Bing',
			'firstparty_marketing' => false,
	),

	'weglot-translate'          => array(
		'constant_or_function' => 'WEGLOT_VERSION',
		'label'                => 'Weglot Translate',
		'firstparty_marketing' => false,
	),

	'colibriwp'          => array(
		'constant_or_function' => 'COLIBRI_PAGE_BUILDER_VERSION',
		'label'                => 'ColibriWP Page Builder',
		'firstparty_marketing' => false,
	),

	'user-registration' => array(
		'constant_or_function' => 'UR',
		'label'                => 'User Registration',
		'firstparty_marketing' => false,
	),

	'user-registration-pro' => array(
		'constant_or_function' => 'User_Registration_Pro_Shortcodes',
		'label'                => 'User Registration Pro',
		'firstparty_marketing' => false,
	),

	'contact-form-7'    => array(
		'constant_or_function' => 'WPCF7_VERSION',
		'label'                => 'Contact Form 7',
		'firstparty_marketing' => false,
	),

	'disable-and-remove-google-fonts'    => array(
		'constant_or_function' => 'drgf_dequeueu_fonts',
		'label'                => 'Disable and remove Google Fonts',
		'firstparty_marketing' => false,
	),
	'embed-google-fonts'    => array(
		'constant_or_function' => 'Embed_Google_Fonts_Proxy',
		'label'                => 'Embed Google Fonts',
		'firstparty_marketing' => false,
	),

	'omgf'    => array(
		'constant_or_function' => 'OMGF_PLUGIN_DIR',
		'label'                => 'OMGF',
		'firstparty_marketing' => false,
	),
	'local-google-fonts'    => array(
		'constant_or_function' => 'LGF_PLUGIN_FILE',
		'label'                => 'Local Google Fonts',
		'firstparty_marketing' => false,
	),
	'olympus-google-fonts'    => array(
		'constant_or_function' => 'ogf_initiate',
		'label'                => 'Fonts Plugin | Google Fonts Typography',
		'firstparty_marketing' => false,
	),
	'use-any-font'    => array(
		'constant_or_function' => 'UAF_FILE_PATH',
		'label'                => 'Use Any Font',
		'firstparty_marketing' => false,
	),

	'facebook-for-wordpress' => array(
		'constant_or_function' => 'FacebookPixelPlugin\\FacebookForWordpress',
		'label'                => 'Official Facebook Pixel',
		'firstparty_marketing' => false,
	),

	'facebook-for-woocommerce' => array(
		'constant_or_function' => 'facebook_for_woocommerce',
		'label'                => 'Facebook for WooCommerce',
		'firstparty_marketing' => false,
	),

	'google-tagmanager-for-wordpress' => array(
		'constant_or_function' => 'GTM4WP_VERSION',
		'label'                => 'Google Tag Manager for WordPress',
		'firstparty_marketing' => false,
	),

	'jetpack' => array(
		'constant_or_function' => 'JETPACK__VERSION',
		'label'                => 'JetPack',
		'firstparty_marketing' => false,
	),

	'g1-gmaps' => array(
		'constant_or_function' => 'G1_GMaps',
		'label'                => 'G1 GMAPS',
		'firstparty_marketing' => false,
	),

	'monsterinsights' => array(
		'constant_or_function' => 'MonsterInsights',
		'label'                => 'MonsterInsights',
		'firstparty_marketing' => false,
	),

	'map-multi-marker' => array(
		'constant_or_function' => 'MapMultiMarker',
		'label'                => 'Map Multi Marker',
		'firstparty_marketing' => false,
	),

	'caos-host-analytics-local' => array(
		'constant_or_function' => 'CAOS_STATIC_VERSION',
		'label'                => 'CAOS host analytics locally',

		'firstparty_marketing' => false,
	),
	'wp-google-maps'            => array(
		'constant_or_function' => 'WPGMZA_VERSION',
		'label'                => 'WP Go Maps',
		'firstparty_marketing' => false,
	),
	'wp-google-map-plugin'            => array(
		'constant_or_function' => 'WPGMP_VERSION',
		'label'                => 'WP Google Map Plugin',
		'firstparty_marketing' => false,
	),
	'woocommerce-google-analytics-pro' => array(
		'constant_or_function' => 'WC_Google_Analytics_Pro_Loader',
		'label'                => 'Woocommerce Google Analytics Pro',
		'firstparty_marketing' => false,
	),
	'woocommerce-google-analytics-integration' => array(
		'constant_or_function' => 'WC_Google_Analytics_Integration',
		'label'                => 'Woocommerce Google Analytics Integration',
		'firstparty_marketing' => false,
	),
		'woocommerce' => array(
				'constant_or_function' => 'WC_PLUGIN_FILE',
				'label'                => 'WooCommerce',
				'firstparty_marketing' => false,
		),

	'geo-my-wp' => array(
		'constant_or_function' => 'GMW_VERSION',
		'label'                => 'Geo My WP',
		'firstparty_marketing' => false,
	),

	'google-analytics-dashboard-for-wp' => array(
		'constant_or_function' => 'EXACTMETRICS_VERSION',
		'label'                => 'ExactMetrics',
		'firstparty_marketing' => false,
	),

	'wp-google-maps-widget' => array(
		'constant_or_function' => 'GMW_PLUGIN_DIR',
		'label'                => 'Maps Widget for Google Maps',
		'firstparty_marketing' => false,
	),

	'wp-donottrack' => array(
		'constant_or_function' => 'wp_donottrack_config',
		'label'                => 'WP Do Not Track',
		'firstparty_marketing' => false,
	),

	'pixel-caffeine'   => array(
		'constant_or_function' => 'AEPC_PIXEL_VERSION',
		'label'                => 'Pixel Caffeine',
		'firstparty_marketing' => false,
	),

	'rate-my-post'   => array(
		'constant_or_function' => 'RATE_MY_POST_VERSION',
		'label'                => 'Rate My Post',
		'firstparty_marketing' => false,
	),

	'super-socializer' => array(
		'constant_or_function' => 'THE_CHAMP_SS_VERSION',
		'label'                => 'Super Socializer',
		'firstparty_marketing' => false,
	),

	'tidio-live-chat'  => array(
		'constant_or_function' => 'TIDIOCHAT_VERSION',
		'label'                => 'Tidio Live Chat',
		'firstparty_marketing' => false,
	),

	'sumo'             => array(
		'constant_or_function' => 'SUMOME__PLUGIN_DIR',
		'label'                => 'Sumo – Boost Conversion and Sales',
		'firstparty_marketing' => false,
	),

	'wpforms'          => array(
		'constant_or_function' => 'wpforms',
		'label'                => 'WP Forms',
		'firstparty_marketing' => false,
	),

	'wp-rocket' => array(
		'constant_or_function' => 'WP_ROCKET_VERSION',
		'label'                => 'WP Rocket',
		'firstparty_marketing' => false,
	),

	'uncode' => array(
		'constant_or_function' => 'UncodeCore_Plugin',
		'label'                => 'Uncode Google Maps',
		'firstparty_marketing' => false,
	),

	'forminator' => array(
		'constant_or_function' => 'FORMINATOR_VERSION',
		'label'                => 'Forminator',
		'early_load'           => 'forminator-addon-registration.php',
		'callback_condition'   => array(
			'regions' => array( 'eu', 'uk', 'za'),
		),
		'firstparty_marketing' => false,

	),

	'happyforms' => array(
		'constant_or_function' => 'HAPPYFORMS_VERSION',
		'label'                => 'Happy Forms',
		'firstparty_marketing' => false,
	),

	'lazy-loader' => array(
		'constant_or_function' => 'FlorianBrinkmann\LazyLoadResponsiveImages\Plugin',
		'label'                => 'Lazy Loader',
		'firstparty_marketing' => false,
	),

	'osm' => array(
		'constant_or_function' => 'OSM_PLUGIN_URL',
		'label'                => 'OSM - OpenStreetMap',
		'firstparty_marketing' => false,
	),

	'primavera' => array(
		'constant_or_function' => 'Primavera',
		'label'                => 'Primavera Theme',
		'firstparty_marketing' => false,
	),

	'so-widgets-bundle' => array(
		'constant_or_function' => 'SOW_BUNDLE_VERSION',
		'label'                => 'SiteOrigin Widgets Bundle',
		'firstparty_marketing' => false,
	),

	'superfly-menu' => array(
		'constant_or_function' => 'SFM_VERSION_KEY',
		'label'                => 'Superfly Menu',
		'firstparty_marketing' => false,
	),

	'wp-store-locator' => array(
		'constant_or_function' => 'WPSL_VERSION_NUM',
		'label'                => 'WP Store Locator',
		'firstparty_marketing' => false,
	),
	'thrive' => array(
		'constant_or_function' => 'Thrive_Product_Manager',
		'label'                => 'Thrive',
		'firstparty_marketing' => false,
	),
	'gravity-forms' => array(
		'constant_or_function' => 'GF_MIN_WP_VERSION',
		'label'                => 'Gravity Forms',
		'firstparty_marketing' => false,
	),
	'qtranslate'               => array(
		'constant_or_function' => 'QTX_VERSION',
		'label'                => 'qTranslate-XT',
		'firstparty_marketing' => false,
	),
	'welaunch-store-locator'   => array(
		'constant_or_function' => 'run_WordPress_Store_Locator',
		'label'                => 'WeLaunch Store Locator',
		'firstparty_marketing' => false,
	),
	'presto-player'               => array(
			'constant_or_function' => 'PRESTO_PLAYER_PLUGIN_FILE',
			'label'                => 'Presto Player',
			'firstparty_marketing' => false,
	),
	'yotu-wp'               => array(
			'constant_or_function' => 'YOTUWP_VERSION',
			'label'                => 'Video Gallery – YouTube Playlist, Channel Gallery by YotuWP',
			'firstparty_marketing' => false,
	),
	'buttonizer'               => array(
			'constant_or_function' => 'BUTTONIZER_VERSION',
			'label'                => 'Buttonizer',
			'firstparty_marketing' => false,
	),
	'greenshift' => array(
		'constant_or_function' => 'GREENSHIFT_DIR_URL',
		'label'                => 'Greenshift',
		'firstparty_marketing' => false,
	),
) );


/**
 * WordPress, include always
 */
require_once( 'wordpress/wordpress.php' );

foreach ( $cmplz_integrations_list as $plugin => $details ) {
	if ( ! isset( $details['early_load'] ) ) {
		continue;
	}

	if ( ! file_exists( WP_PLUGIN_DIR . "/" . $plugin . "/" . $plugin . ".php" ) ) {
		continue;
	}

	$early_load = $details['early_load'];
	$file       = apply_filters( 'cmplz_early_load_path', CMPLZ_PATH . "integrations/plugins/$early_load", $details );

	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}

/**
 * Check if a plugin from the integrations list is active
 *
 * @param string $plugin
 *
 * @return bool
 */
function cmplz_integration_plugin_is_enabled( string $plugin ): bool {
	//because we need a default, we don't use the get_value from complianz. The fields array is not loaded yet, so there are no defaults
	$fields = get_option( 'complianz_options_integrations', [] );
	$enabled = ! isset( $fields[ $plugin ] ) || $fields[ $plugin ];
	return cmplz_integration_plugin_is_active( $plugin ) && $enabled;
}

/**
 * Check if a plugin from the integrations list is activated
 *
 * @param string $plugin
 *
 * @return bool
 */
function cmplz_integration_plugin_is_active( string $plugin ): bool {
	global $cmplz_integrations_list;
	if ( !isset($cmplz_integrations_list[ $plugin ]) ) {
		return false;
	}
	$details = $cmplz_integrations_list[ $plugin ];
	$theme = wp_get_theme();
	$active = defined( $details['constant_or_function'] )
	       || function_exists( $details['constant_or_function'] )
	       || class_exists( $details['constant_or_function'] )
	       || ( $theme && ( $theme->name === $details['constant_or_function'] ) )
	       || ( $theme->parent() !== false && trim( $theme->parent()->Name ) === trim( $details['constant_or_function'] ) );
	return $active;
}

/**
 * code loaded without privileges to allow integrations between plugins and services, when enabled.
 */

function cmplz_integrations() {
	global $cmplz_integrations_list;
	$stored_integrations_count = get_option('cmplz_active_integrations', 0 );
	$actual_integrations_count = 0;
	foreach ( $cmplz_integrations_list as $plugin => $details ) {
		if ( cmplz_integration_plugin_is_enabled( $plugin ) ) {
			$actual_integrations_count++;
			$file = apply_filters( 'cmplz_integration_path', CMPLZ_PATH . "integrations/plugins/$plugin.php", $plugin );
			if ( file_exists( $file ) ) {
				require_once( $file );
			}
		}
	}

	//cannot be an absolute match.
	if ( $stored_integrations_count != $actual_integrations_count) {
		update_option('cmplz_active_integrations',  $actual_integrations_count);
		update_option('cmplz_integrations_changed', true );
	}

	/**
	 * Services
	 */

	$services = COMPLIANZ::$config->thirdparty_service_markers;
	$services = array_keys( $services );
	foreach ( $services as $service ) {
		if ( cmplz_uses_thirdparty( $service ) ) {
			if ( file_exists( CMPLZ_PATH . "integrations/services/$service.php" ) ) {
				require_once( "services/$service.php" );
			}
		}
	}

	$services = COMPLIANZ::$config->social_media_markers;
	$services = array_keys( $services );
	foreach ( $services as $service ) {
		if ( cmplz_uses_thirdparty( $service ) ) {
			if ( file_exists( CMPLZ_PATH . "integrations/services/$service.php" ) ) {
				require_once( "services/$service.php" );
			}
		}
	}

	/**
	 * statistics
	 */

	$statistics = cmplz_get_option( 'compile_statistics' );
	if ( $statistics === 'google-analytics' ) {
		require_once( 'statistics/google-analytics.php' );
	}
	if ( $statistics === 'matomo' && cmplz_get_option('configuration_by_complianz') !=='yes' ) {
		require_once( 'statistics/matomo.php' );
	}
}

add_action( 'plugins_loaded', 'cmplz_integrations', 10 );


/**
 * Check if a third party is used on this site
 *
 * @param string $name
 *
 * @return bool uses_thirdparty
 */

function cmplz_uses_thirdparty( $name ) {
	if ( cmplz_get_option( 'uses_thirdparty_services' ) === 'yes' ) {
		$thirdparty_types = cmplz_get_option( 'thirdparty_services_on_site' );

		if ( is_array($thirdparty_types) && in_array( $name, $thirdparty_types) ) {
			return true;
		}
	}

	if ( cmplz_get_option( 'uses_social_media' ) === 'yes' ) {
		$social_media_types = cmplz_get_option( 'socialmedia_on_site' );
		if ( is_array($social_media_types) && in_array( $name, $social_media_types) ) {
			return true;
		}
	}

	return false;
}

/**
 * Callback to check if google maps is used
 * @return bool
 */
function cmplz_uses_google_maps(){
	return cmplz_uses_thirdparty('google-maps');
}

function cmplz_google_maps_integration_enabled(){
	return defined('CMPLZ_GOOGLE_MAPS_INTEGRATION_ACTIVE');
}

function cmplz_uses_woocmmerce(){
	return cmplz_uses_thirdparty('google-maps');
}

/**
 * placeholders that are disabled will be removed by hook here.
 *
 * This only applies to the non iframe placeholders. Other placeholders are blocked using the cmplz_placeholder_disabled function
 */

//add_action( "plugins_loaded", 'cmplz_unset_placeholder_hooks' );
//function cmplz_unset_placeholder_hooks() {
//	$disabled_placeholders = get_option( 'cmplz_disabled_placeholders', array() );
//	foreach ( $disabled_placeholders as $service ) {
//		$has_placeholder = ( function_exists( "cmplz_{$service}_placeholder" ) );
//		if ( $has_placeholder ) {
//			remove_filter( 'cmplz_placeholder_markers', "cmplz_{$service}_placeholder" );
//		}
//	}
//}

/**
 * check if the placeholder for a service is disabled
 *
 * @param string $service
 *
 * @return bool $disabled
 */

function cmplz_placeholder_disabled( $service ) {
	$disabled_placeholders = get_option( 'cmplz_disabled_placeholders',
		array() );

	if ( in_array( $service, $disabled_placeholders ) ) {
		return true;
	}
	//check also other variation
	$service = str_replace( '-', "_", $service );
	if ( in_array( $service, $disabled_placeholders ) ) {
		return true;
	}

	return false;

}

/**
 * check if we should use placeholders
 *
 * @param string|bool $src
 *
 * @return bool
 */

function cmplz_use_placeholder( $src = false ) {
	if ( cmplz_get_option( 'dont_use_placeholders' ) ) {
		return false;
	}

	if ( ! $src ) {
		return true;
	}


	//no placeholder on facebook like button
	if ( strpos( $src, 'like.php' ) !== false ) {
		return false;
	}

	//get service from $src
	$service = cmplz_get_service_by_src( $src );
	if ( cmplz_placeholder_disabled( $service ) ) {
		return false;
	}

	return true;
}

/**
 * Get a service by string, looking at the src of a frame or script
 *
 * @param string $src
 *
 * @return bool|string
 */

function cmplz_get_service_by_src( $src ) {
	$type = false;
	$services = COMPLIANZ::$config->thirdparty_service_markers;
	$social = COMPLIANZ::$config->social_media_markers;
	$stats = COMPLIANZ::$config->stats_markers;
	$all = $services+$social+$stats;
	foreach ( $all as $service_id => $markers ) {
		$service = cmplz_strpos_arr($src, $markers);
		if ( $service !== FALSE ) {
			$type = $service_id;
			break;
		}
	}

	if ( ! $type ) {
		$type = COMPLIANZ::$banner_loader->parse_for_social_media( $src, true );
		if ( ! $type ) {
			$type = COMPLIANZ::$banner_loader->parse_for_thirdparty_services( $src, true );
		}
	}
	return $type ?: 'general';
}

/**
 * Maybe update css if integrations have been changed
 */

function cmplz_maybe_update_css(){

	if ( !cmplz_admin_logged_in() ) {
		return;
	}

	$integrations_changed = get_option('cmplz_integrations_changed', false );
	if ( $integrations_changed ) {
		cmplz_update_all_banners();
	}
	update_option('cmplz_integrations_changed', false );
}
add_action('admin_init', 'cmplz_maybe_update_css');

/**
 * Check if this plugin's integration is enabled
 *
 * @param string $plugin_name
 *
 * @return bool
 */
function cmplz_is_integration_enabled( string $plugin_name ): bool {
	global $cmplz_integrations_list;
	if ( ! array_key_exists( $plugin_name, $cmplz_integrations_list ) ) {
		return false;
	}
	$fields = get_option( 'complianz_options_integrations' );
	//default enabled, which means it's enabled when not set.
	return ! ( isset( $fields[ $plugin_name ] ) && $fields[ $plugin_name ] != 1 );
}
