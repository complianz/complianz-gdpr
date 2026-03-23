<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
require_once plugin_dir_path(__FILE__) . 'functions-legacy.php';

global $cmplz_banner;
if ( !function_exists('cmplz_get_cookiebanner')) {
	function cmplz_get_cookiebanner($ID = false, $set_defaults = true, $load_wysiwyg_options = false){
		global $cmplz_banner;

		//try cached data if defaults are used
		if ( $ID && $set_defaults && !$load_wysiwyg_options ) {
			$banner = $cmplz_banner[ $ID ] ?? false;
			if (!$banner){
				$banner = new CMPLZ_COOKIEBANNER($ID, $set_defaults, $load_wysiwyg_options);
				$cmplz_banner[ $ID ] = $banner;
			}
		} else {
			$banner = new CMPLZ_COOKIEBANNER($ID, $set_defaults, $load_wysiwyg_options);
		}
		return $banner;
	}
}
if ( ! function_exists( 'cmplz_get_option' ) ) {
	/**
	 * Get a Complianz option by name
	 *
	 * @param string $name
	 * @param bool   $default
	 *
	 * @return mixed
	 */
	function cmplz_get_option( string $id, bool $load_default=true ) {
		$id = cmplz_sanitize_title_preserve_uppercase($id);
		//to ensure the fields function only runs once, we store it here, and check if it's filled in the next request.
		$fields = false;
		$options = get_option( 'cmplz_options', [] );
		$value = $options[ $id ] ?? false;
		if ( $value===false && $load_default && class_exists('COMPLIANZ') ) {
			$fields = COMPLIANZ::$config->fields ?? [];
			$keys   = array_keys( array_column( $fields, 'id' ), $id );
			$key    = reset( $keys );
			if (isset($fields[$key]) ) {
				$default = $fields[ $key ]['default'] ?? false;
				$value  = apply_filters( 'cmplz_default_value', $default, $id, $fields[$key] );
			}
		}

		/*
		 * Translate output
		 *
		 * */
		if ( function_exists('pll__') || function_exists('icl_translate') || defined("WPML_PLUGIN_BASENAME" ) ) {
			//check if Complianz::$config has property fields
			if (class_exists('COMPLIANZ')) {
				$config_fields = COMPLIANZ::$config->fields ?? [];
			} else {
				$config_fields = [];
			}
			$fields = $fields ?: $config_fields;
			$keys   = array_keys( array_column( $fields, 'id' ), $id );
			$key    = reset( $keys );
			if ( $key !== false ) {
				$type         = $fields[ $key ]['type'] ?? false;
				$translatable = $fields[ $key ]['translatable'] ?? false;
				if ( $translatable ) {
					if ( is_array( $value ) && ( $type === 'thirdparties' || $type === 'processors' ) ) {
						foreach ( $value as $item_key => $item ) {
							//contains the values of an item
							foreach ( $item as $key => $key_value ) {
								if ( function_exists( 'pll__' ) ) {
									$value[ $item_key ][ $key ] = pll__( $item_key . '_' . $id . "_" . $key );
								}
								if ( function_exists( 'icl_translate' ) ) {
									$value[ $item_key ][ $key ] = icl_translate( 'complianz', $item_key . '_' . $id . "_" . $key, $key_value );
								}

								$value[ $item_key ][ $key ] = apply_filters( 'wpml_translate_single_string', $key_value, 'complianz', $item_key . '_' . $id . "_" . $key );
							}
						}
					} else {
						if ( function_exists( 'pll__' ) ) {
							$value = pll__( $value );
						}
						if ( function_exists( 'icl_translate' ) ) {
							$value = icl_translate( 'complianz', $id, $value );
						}
						$value = apply_filters( 'wpml_translate_single_string', $value, 'complianz', $id );
					}
				}
			}
		}
		return apply_filters("cmplz_option_$id", $value, $id);
	}
}

if (!function_exists('cmplz_get_field')) {
	function cmplz_get_field($id){
		$fields = COMPLIANZ::$config->fields;
		foreach ($fields as $field) {
			if (isset($field['id']) && $field['id'] === $id) {
				return $field;
			}
		}
		return false;
	}
}
if (!function_exists('cmplz_get_field_index')) {
	function cmplz_get_field_index($id, $fields){
		foreach ($fields as $index => $field) {
			if (isset($field['id']) && $field['id'] === $id) {
				return $index;
			}
		}
		return false;
	}
}

if ( !function_exists('cmplz_remove_field') ) {
	/**
	 * @param array        $fields
	 * @param $ids
	 *
	 * @return array
	 */
	function cmplz_remove_field( array $fields, $ids): array {
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		$field_ids = array_column($fields, 'id');
		foreach ($ids as $id){
			$drop_index = array_search( $id, $field_ids, true );
			if ($drop_index!==false) {
				unset($fields[$drop_index]);
			}
		}

		return $fields;
	}
}



if ( !function_exists('cmplz_sanitize_title_preserve_uppercase')) {
	/**
	 * @param string $title
	 *
	 * @return string
	 */
	function cmplz_sanitize_title_preserve_uppercase($title) {
		if (empty($title)) {
			return '';
		}
		$title = preg_replace( '/&.+?;/', '', $title );
		$title = str_replace( '.', '-', $title );
		$title = preg_replace( '/[^%a-zA-Z0-9 _-]/', '', $title );
		$title = preg_replace( '/\s+/', '-', $title );
		$title = preg_replace( '|-+|', '-', $title );
		return str_replace(' ', '-', sanitize_text_field(remove_accents($title)));
	}
}

if ( ! function_exists( 'cmplz_uses_google_analytics' ) ) {

	/**
	 * Check if site uses google analytics
	 * @return bool
	 */

	function cmplz_uses_google_analytics() {
		return COMPLIANZ::$banner_loader->uses_google_analytics();
	}
}

if ( ! function_exists( 'cmplz_consent_mode' ) ) {

	/**
	 * Check if site uses google analytics
	 * @return bool
	 */

	function cmplz_consent_mode() {
		return cmplz_get_option( 'consent-mode' ) === 'yes';
	}
}

if ( ! function_exists('cmplz_upload_dir')) {
	/**
	 * Get the upload dir
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	function cmplz_upload_dir( string $path=''): string {
		$uploads    = wp_upload_dir();
		$upload_dir = trailingslashit( apply_filters( 'cmplz_upload_dir', $uploads['basedir'] ) ).'complianz/'.$path;
		if ( !is_dir( $upload_dir)  ) {
			cmplz_create_missing_directories_recursively($upload_dir);
		}

		return trailingslashit( $upload_dir );
	}
}

/**
 * Create directories recursively
 *
 * @param string $path
 */

if ( !function_exists('cmplz_create_missing_directories_recursively') ) {
	function cmplz_create_missing_directories_recursively( string $path ) {
		if ( ! cmplz_user_can_manage() ) {
			return;
		}

		$parts = explode( '/', $path );
		$dir   = '';
		foreach ( $parts as $part ) {
			$dir .= $part . '/';
			if (cmplz_has_open_basedir_restriction($dir)) {
				continue;
			}
			if ( ! is_dir( $dir ) && strlen( $dir ) > 0 && is_writable( dirname( $dir, 1 ) ) ) {
				if ( ! mkdir( $dir ) && ! is_dir( $dir ) ) {
					throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $dir ) );
				}
			}
		}
	}
}


if (!function_exists('cmplz_has_open_basedir_restriction')) {
	function cmplz_has_open_basedir_restriction($path) {
		// Default error handler is required
		set_error_handler(null);
		// Clean last error info.
		error_clear_last();
		// Testing...
		@file_exists($path);
		// Restore previous error handler
		restore_error_handler();
		// Return `true` if error has occurred
		return ($error = error_get_last()) && $error['message'] !== '__clean_error_info';
	}
}

if ( ! function_exists('cmplz_upload_url')) {
	/**
	 * Get the upload url
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	function cmplz_upload_url( string $path=''): string {
		$uploads    = wp_upload_dir();
		$upload_url = $uploads['baseurl'];
		$upload_url = trailingslashit( apply_filters('cmplz_upload_url', $upload_url) );
		return trailingslashit($upload_url.'complianz/'.$path);
	}
}

if ( ! function_exists( 'cmplz_uses_social_media' ) ) {

	/**
	 * Check if site uses social media
	 * @return bool
	 */

	function cmplz_uses_social_media() {
		$socialmedia_list = cmplz_get_option( 'socialmedia_on_site' );
		return is_array( $socialmedia_list ) && count( array_filter( $socialmedia_list ) ) > 0;
	}
}

if ( !function_exists('cmplz_upgraded_to_current_version')){

	/**
	 * Check if the user has upgraded to the current version, or if this is a fresh install with this version.
	 */

	function cmplz_upgraded_to_current_version() {
		$first_version = get_option( 'cmplz_first_version' );
		//if there's no first version yet, we assume it's not upgraded
		if ( !$first_version ) {
			return false;
		}
		//if the first version is below current, we just upgraded.
		if ( version_compare($first_version,CMPLZ_VERSION ,'<') ){
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'cmplz_get_template' ) ) {
	/**
	 * Get a template based on filename, overridable in theme dir
	 * @param string $filename
	 * @param array $args
	 * @param string $path
	 * @return string
	 */

	function cmplz_get_template( $filename , $args = array(), $path = false ) {
		$path = $path ? trailingslashit($path) : trailingslashit( CMPLZ_PATH ) . 'templates/';
		$file = apply_filters('cmplz_template_file', $path . $filename, $filename);
		$theme_file = trailingslashit( get_stylesheet_directory() )
		              . trailingslashit( basename( CMPLZ_PATH ) )
		              . 'templates/' . $filename;
		if ( !file_exists( $file ) ) {
		    return false;
        }

		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}
		if ( strpos( $file, '.php' ) !== false ) {
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			$contents = file_get_contents( $file );
		}

		if ( !empty($args) && is_array($args) ) {
			foreach($args as $fieldname => $value ) {
				$contents = str_replace( '{'.$fieldname.'}', $value, $contents );
			}
		}

		return $contents;
	}
}

if ( ! function_exists( 'cmplz_uses_google_tagmanager_or_analytics' ) ) {
	function cmplz_uses_google_tagmanager_or_analytics(){
		return COMPLIANZ::$banner_loader->uses_google_analytics() || COMPLIANZ::$banner_loader->uses_google_tagmanager();
	}
}

if ( ! function_exists( 'cmplz_tagmanager_conditional_helptext' ) ) {

	function cmplz_tagmanager_conditional_helptext() {

		if ( !COMPLIANZ::$banner_loader->consent_required_for_anonymous_stats() ) {
			$text = __( "Based on your Analytics configuration you should fire Analytics on event cmplz_functional.", 'complianz-gdpr' );
		} else {
			$text = __( "Based on your Analytics configuration you should fire Analytics on event cmplz_statistics.", 'complianz-gdpr' );
		}

		return $text;
	}
}

if ( ! function_exists( 'cmplz_statistics_privacy_friendly' ) ) {

	/**
	 * Checks if statistics are configured privacy friendly
	 *
	 * @return bool
	 */
	function cmplz_statistics_privacy_friendly()
	{
		return COMPLIANZ::$banner_loader->statistics_privacy_friendly();
	}
}

if ( ! function_exists( 'cmplz_manual_stats_config_possible' ) ) {

	/**
	 * Checks if the statistics are configured so no consent is need for statistics
	 *
	 * @return bool
	 */

	function cmplz_manual_stats_config_possible() {
		$stats = cmplz_get_option( 'compile_statistics' );
		if ( $stats === 'matomo' && cmplz_no_ip_addresses() ) {
			return true;
		}

		//Google Tag Manager should also be possible to embed yourself if you haven't integrated it anonymously
		if ( $stats === 'google-tag-manager' ) {
			return true;
		}

		if ( $stats === 'google-analytics' ) {
			if ( !COMPLIANZ::$banner_loader->consent_required_for_anonymous_stats()
			) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_get_stats_tool_nice' ) ) {
	function cmplz_get_stats_tool_nice() {
		//if the user just changed the setting, we use the posted data. The data is not saved yet, so would yield the previous setting
		if ( isset($_POST['cmplz_compile_statistics'])) {
			$stats = sanitize_text_field($_POST['cmplz_compile_statistics']);
		} else {
			$stats = cmplz_get_option( 'compile_statistics' );
		}
		switch ( $stats ){
			case 'google-analytics':
				return "Google Analytics";
			case 'matomo':
				return "Matomo";
			case 'clicky':
				return "Clicky";
			case 'yandex':
				return "Yandex";
			case 'google-tag-manager':
				return "Google Tag Manager";
			case 'matomo-tag-manager':
				return "Matomo Tag Manager";
			case 'clarity':
				return "Clarity";
			default:
				return __("Not found","complianz-gdpr");
		}
	}
}

if ( !function_exists('cmplz_detected_cookie_plugin')) {
	function cmplz_detected_cookie_plugin( $return_name = false ){
		$plugin = false;
		if (defined('CLI_LATEST_VERSION_NUMBER')){
			$plugin = "GDPR Cookie Consent";
		} elseif(defined('MOOVE_GDPR_VERSION')) {
			$plugin = "GDPR Cookie Compliance";
		}elseif(defined('CTCC_PLUGIN_URL')) {
			$plugin = "GDPR Cookie Consent Banner";
		}elseif(defined('RCB_FILE')) {
			$plugin = "Real Cookie Banner";
		}elseif(class_exists('Cookiebot_WP')) {
			$plugin = "Cookiebot";
		}elseif(function_exists('bst_plugin_install')) {
			$plugin = "BST DSGVO Cookie";
		}elseif(function_exists('jlplg_lovecoding_set_cookie')) {
			$plugin = "Simple Cookie Notice";
		}elseif(class_exists('SCCBPP_WpCookie_Save')) {
			$plugin = "Seers Cookie Consent Banner Privacy Policy";
		}elseif(function_exists('daextlwcnf_customize_action_links')) {
			$plugin = "Lightweight Cookie Notice Free";
		}elseif(defined('GDPRCN_VERSION')) {
			$plugin = "GDPR Cookie Notice";
		}elseif(function_exists('wp_gdpr_cookie_notice_check_requirements')) {
			$plugin = "WP GDPR Cookie Notice";
		}elseif(defined('SURBMA_GPGA_PLUGIN_VERSION_NUMBER')) {
			$plugin = "Surbma | GDPR Proof Cookie Consent & Notice Bar";
		}elseif(class_exists('dsdvo_wp_backend')) {
			$plugin = "DSGVO All in one for WP";
		}elseif(class_exists('Cookie_Notice')) {
			$plugin = "Cookie Notice & Compliance";
		}elseif(defined('CNCB_VERSION')) {
			$plugin = "Cookie Notice and Consent Banner";
		}elseif(function_exists('add_cookie_notice')) {
			$plugin = "Cookie Notice Lite";
		}elseif(function_exists('fhw_dsgvo_cookie_insert')) {
			$plugin = "GDPR tools: cookie notice + privacy";
		}elseif(defined('GDPR_COOKIE_CONSENT_PLUGIN_URL')) {
			$plugin = "GDPR Cookie Consent";
		}elseif(defined('WP_GDPR_C_SLUG')) {
			$plugin = "WP GDPR Compliance";
		}elseif(defined('TERMLY_VERSION')) {
			$plugin = "Termly | GDPR/CCPA Cookie Consent Banner";
		}

		if ( $plugin !== false && !$return_name ) {
			return true;
		} else {
			return $plugin;
		}
	}
}

if ( ! function_exists( 'cmplz_revoke_link' ) ) {
	/**
	 * Output a revoke button
	 * @param bool $text
	 *
	 * @return string
	 */
	function cmplz_revoke_link( $text = false ) {
		$text = sanitize_text_field($text) ? : __( 'Revoke', 'complianz-gdpr' );
		$text = apply_filters( 'cmplz_revoke_button_text', $text );
		$css
		      = "<style>.cmplz-status-accepted,.cmplz-status-denied {display: none;}</style>
				<script>
				document.addEventListener('cmplz_before_cookiebanner', function(){
                    if (cmplz_has_consent('marketing')) {
				        document.querySelector('.cmplz-status-accepted').style.display = 'block';
				        document.querySelector('.cmplz-status-denied').style.display = 'none';
				    } else {
						document.querySelector('.cmplz-status-accepted').style.display = 'none';
				        document.querySelector('.cmplz-status-denied').style.display = 'block';
						document.querySelector('.cmplz-revoke-custom').setAttribute('disabled', true);

				    }
                    document.addEventListener('click', e => {
						if ( e.target.closest('.cmplz-revoke-custom') ) {
							document.querySelector('.cmplz-revoke-custom').setAttribute('disabled', true);
                            cmplz_set_banner_status('dismissed');
						}
					});
                    document.addEventListener('click', e => {
						if ( e.target.closest('.cmplz-accept') ) {
                            document.querySelector('.cmplz-status-accepted').style.display = 'block';
				        	document.querySelector('.cmplz-status-denied').style.display = 'none';
							document.querySelector('.cmplz-revoke-custom').removeAttribute('disabled');
						}
					});
				});
			</script>";
		$html = $css . '<button class="cmplz-deny cmplz-revoke-custom">' . esc_html($text)
		        . '</button>&nbsp;<span class="cmplz-status-accepted">'
		        . cmplz_sprintf( __( 'Current status: %s', 'complianz-gdpr' ),
				__( "Accepted", 'complianz-gdpr' ) )
		        . '</span><span class="cmplz-status-denied">'
		        . cmplz_sprintf( __( 'Current status: %s', 'complianz-gdpr' ),
				__( "Denied", 'complianz-gdpr' ) ) . '</span>';

		return apply_filters( 'cmplz_revoke_link', $html );
	}
}

if ( ! function_exists( 'cmplz_sells_personal_data' ) ) {
	function cmplz_sells_personal_data() {
		$purposes = cmplz_get_option( 'purpose_personaldata');
		if ( isset( $purposes['selling-data-thirdparty'] )
		     && $purposes['selling-data-thirdparty']
		) {
			return true;
		}

		return false;
	}
}
if ( ! function_exists( 'cmplz_sold_data_12months' ) ) {
	function cmplz_sold_data_12months() {
		return COMPLIANZ::$company->sold_data_12months();
	}
}
if ( ! function_exists( 'cmplz_disclosed_data_12months' ) ) {
	function cmplz_disclosed_data_12months() {
		return COMPLIANZ::$company->disclosed_data_12months();
	}
}

if ( ! function_exists( 'cmplz_site_needs_cookie_warning' ) ) {
	/**
	 * Check if site needs a cookie warning
	 *
	 * @return bool
	 */
	function cmplz_site_needs_cookie_warning() {
		return COMPLIANZ::$banner_loader->site_needs_cookie_warning();
	}
}
if ( ! function_exists( 'cmplz_eu_site_needs_cookie_warning' ) ) {
	/**
	 * Check if EU targeted site needs a cookie warning
	 *
	 * @return bool
	 */
	function cmplz_eu_site_needs_cookie_warning() {
		return COMPLIANZ::$banner_loader->site_needs_cookie_warning( 'eu' );
	}
}

if ( ! function_exists( 'cmplz_za_site_needs_cookie_warning' ) ) {
	/**
	 * Check if ZA targeted site needs a cookie warning
	 *
	 * @return bool
	 */
	function cmplz_za_site_needs_cookie_warning() {
		return COMPLIANZ::$banner_loader->site_needs_cookie_warning( 'za' );
	}
}

if ( ! function_exists( 'cmplz_uk_site_needs_cookie_warning' ) ) {
	/**
	 * Check if EU targeted site needs a cookie warning
	 *
	 * @return bool
	 */
	function cmplz_uk_site_needs_cookie_warning() {
		return COMPLIANZ::$banner_loader->site_needs_cookie_warning( 'uk' );
	}
}

if ( ! function_exists( 'cmplz_site_uses_cookie_warning_cats' ) ) {

	/**
	 * Check if optin site needs cookie warning with categories
	 * @return bool
	 */
	function cmplz_site_uses_cookie_warning_cats() {
		$cookiebanner = cmplz_get_cookiebanner( apply_filters( 'cmplz_user_banner_id',  cmplz_get_default_banner_id() ) );
		if ( $cookiebanner->use_categories !== 'no'
		) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_company_located_in_region' ) ) {

	/**
	 * Check if this company is located in a certain region
	 *
	 * @param $region
	 *
	 * @return bool
	 */
	function cmplz_company_located_in_region( $region ) {
		$country_code = cmplz_get_option( 'country_company' );

		return ( cmplz_get_region_for_country( $country_code ) === $region );
	}
}

if ( ! function_exists( 'cmplz_has_region' ) ) {
	/**
	 * Check if this website targets a specific region.
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	function cmplz_has_region( $code ) {
		$regions = cmplz_get_regions(true);
		if ( in_array( $code, $regions ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_has_state' ) ) {
	/**
	 * Check if this website targets a specific state
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	function cmplz_has_state( $code ) {
		$regions = cmplz_get_regions(true);
		if ( !isset( $regions[ 'us' ] ) ) {
			return false;
		}

		$states = cmplz_get_option('us_states');
		if ( isset( $states[ $code ] ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'cmplz_get_region_from_legacy_type' ) ) {
	function cmplz_get_region_from_legacy_type( $type ) {
		$region = false;
		if ( strpos( $type, 'disclaimer' ) !== false || strpos( $type, 'all' ) !== false ) {
			$region = 'all';
		}
		//get last three chars of string. if not contains -, it's eu or disclaimer.
		if ( substr( $type, - 3, 1 ) === '-' ) {
			$region = substr( $type, - 2 );
		}
		if ( ! $region ) {
			$region = 'eu';
		}

		return $region;
	}
}

if (!function_exists('cmplz_format_as_javascript_array')) {
	function cmplz_format_as_javascript_array($array) {
		$out = [];
		foreach ($array as $key => $label){
			$out[] = [
					'id' => $key,
					'label' => $label,
			];
		}
		return $out;
	}
}

if ( ! function_exists( 'cmplz_get_regions' ) ) {
	function cmplz_get_regions( $ad_all_category = false, $label_type = false ) {
		$regions = cmplz_get_option( 'regions' );
		if ( ! is_array( $regions ) ) {
			$regions = !empty( $regions ) ? array( $regions ) :  [];
		}

		if ( $label_type && ! empty( $regions ) ) {
			$output = array();
			foreach ( $regions as $region ) {
				if ($label_type==='full') {
					$label = isset( COMPLIANZ::$config->regions[ $region ] ) ? COMPLIANZ::$config->regions[ $region ]['label_full'] : '';
				} else {
					$label = isset( COMPLIANZ::$config->regions[ $region ] ) ? COMPLIANZ::$config->regions[ $region ]['label'] : '';
				}
				$output[ $region ] = $label;
			}
		} else {
			$output = $regions;
		}

		if ( $ad_all_category ) {
			if ($label_type) {
				$output['all'] = __( 'General', 'complianz-gdpr' );
			} else {
				$output[] = 'all';
			}
		}
		return array_filter($output);
	}
}

if ( ! function_exists( 'cmplz_multiple_regions' ) ) {

	function cmplz_multiple_regions() {
		//if geo ip is not enabled, return false anyway
		if ( ! cmplz_geoip_enabled() ) {
			return false;
		}

		$regions = cmplz_get_regions();

		return count( $regions ) > 1;
	}
}

if ( ! function_exists( 'cmplz_get_region_for_country' ) ) {

	function cmplz_get_region_for_country( $country_code ) {
		$region = false;
		$regions = COMPLIANZ::$config->regions;
		foreach ( $regions as $region_code => $region_data ) {
			if ( in_array( $country_code, $region_data['countries'] ) ) {
				$region = $region_code;
				break;
			}
		}
		return apply_filters( "cmplz_region_for_country", $region, $country_code );
	}
}

if ( ! function_exists( 'cmplz_get_consenttype_for_country' ) ) {
	function cmplz_get_consenttype_for_country( $country_code ) {
		$regions       = COMPLIANZ::$config->regions;
		$used_regions = cmplz_get_regions();
		//do not unset a not used region if it's a manual override.
		if ( !isset($_GET['cmplz_user_region']) ) {
			foreach ( $regions as $region => $region_data ) {
				if ( empty($used_regions) || !in_array( $region, $used_regions )) {
					unset($regions[$region]);
				}
			}
		}

		$actual_region = apply_filters('cmplz_user_region', cmplz_get_region_for_country( $country_code ));
		if ( isset( $regions[ $actual_region ]) && isset( $regions[ $actual_region ]['type'] ) ) {
			$consenttype = apply_filters( 'cmplz_consenttype', $regions[ $actual_region ]['type'], $actual_region );
			return $consenttype;
		}
		return false;
	}
}

if ( ! function_exists( 'cmplz_targeting_multiple_regions' ) ) {
	function cmplz_targeting_multiple_regions(){
		if ( defined("POLYLANG_VERSION" ) ) return true;
		if ( defined("WPML_PLUGIN_BASENAME" ) ) return true;

		return false;
	}
}

/**
 * Check if the scan detected social media on the site.
 *
 *
 * */
if ( ! function_exists( 'cmplz_scan_detected_social_media' ) ) {

	function cmplz_scan_detected_social_media() {
		$social_media = get_option( 'cmplz_detected_social_media', array() );
		if ( ! is_array( $social_media ) ) {
			$social_media = array( $social_media );
		}
		$social_media = array_filter( $social_media );

		$social_media = apply_filters( 'cmplz_detected_social_media',
			$social_media );

		//nothing scanned yet, or nothing found
		if ( ! $social_media || ( count( $social_media ) == 0 ) ) {
			$social_media = false;
		}

		return $social_media;
	}
}

if ( ! function_exists( 'cmplz_scan_detected_thirdparty_services' ) ) {

	function cmplz_scan_detected_thirdparty_services() {
		$thirdparty = get_option( 'cmplz_detected_thirdparty_services', array() );
		if ( ! is_array( $thirdparty ) ) {
			$thirdparty = array( $thirdparty );
		}
		$thirdparty = array_filter( $thirdparty );
		$thirdparty = apply_filters( 'cmplz_detected_services', $thirdparty );

		//nothing scanned yet, or nothing found
		if ( ! $thirdparty || ( count( $thirdparty ) == 0 ) ) {
			$thirdparty = false;
		}

		return $thirdparty;
	}
}


if ( ! function_exists( 'cmplz_scan_detected_stats' ) ) {

	function cmplz_scan_detected_stats() {
		$stats = get_option( 'cmplz_detected_stats', array() );
		if ( ! is_array( $stats ) ) {
			$stats = array( $stats );
		}
		$stats = array_filter( $stats );
		//nothing scanned yet, or nothing found
		if ( ! $stats || ( count( $stats ) == 0 ) ) {
			$stats = false;
		}

		$stats = apply_filters( 'cmplz_detected_stats', $stats );

		return $stats;
	}
}

if ( ! function_exists( 'cmplz_page_is_of_type' ) ) {
	/**
	 * Save a complianz option
	 * @param string $type
	 * @return bool
	 */
	function cmplz_page_is_of_type( $type ) {
		$regions = cmplz_get_regions();
		global $post;
		if ( !$post ) return false;

		$post_id = $post->ID;
		foreach ( $regions as $region ) {
			$policy_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region );
			if ( $policy_id === $post_id ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'cmplz_uses_statistics' ) ) {
	function cmplz_uses_statistics() {
		$stats = cmplz_get_option( 'compile_statistics' );
		if ( $stats !== 'no' ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_show_install_burst_warning' ) ) {
	function cmplz_show_install_burst_warning() {
		if ( cmplz_get_option('consent_for_anonymous_stats') === 'yes' && !defined( 'burst_version' ) ) {
			return true;
		}
		return false;
	}
}


if ( ! function_exists( 'cmplz_uses_only_functional_cookies' ) ) {
	function cmplz_uses_only_functional_cookies() {
		return COMPLIANZ::$banner_loader->uses_only_functional_cookies();
	}
}

if ( !function_exists('cmplz_scan_in_progress')) {
	function cmplz_scan_in_progress(){
		return isset( $_GET['complianz_scan_token'] ) && wp_verify_nonce( $_GET['complianz_scan_token'], 'complianz_scan_token');
	}
}

if ( ! function_exists( 'cmplz_ecommerce_legal' ) ) {
	function cmplz_ecommerce_legal() {

		//check Woo and EDD constants
		$ecommerce_enabled = defined('WC_PLUGIN_FILE') || defined('EDD_VERSION');

		return $ecommerce_enabled;
	}
}

if ( ! function_exists( 'cmplz_site_shares_data' ) ) {
	/**
	 * Function to check if site shares data. Used in canada cookie policy
	 * @return bool
	 */
	function cmplz_site_shares_data() {
		return COMPLIANZ::$banner_loader->site_shares_data();
	}
}

if ( ! function_exists( 'cmplz_strip_spaces' ) ) {

	function cmplz_strip_spaces( $string ) {
		return preg_replace( '/\s*/m', '', $string );

	}
}

if ( ! function_exists( 'cmplz_localize_date' ) ) {

	function cmplz_localize_date( $unix_time ) {
		$formatted_date    = date( get_option( 'date_format' ), $unix_time );
		$month             = date( 'F', $unix_time ); //june
		$month_localized   = __( $month ); //juni
		$date              = str_replace( $month, $month_localized, $formatted_date );
		$weekday           = date( 'l', $unix_time ); //wednesday
		$weekday_localized = __( $weekday ); //woensdag
		$date              = str_replace( $weekday, $weekday_localized, $date );
		return $date;
	}
}

if (!function_exists('cmplz_strpos_arr')) {
	/**
	 * check if there is a partial match between a key of the array and the haystack
	 * We cannot use array_search, as this would not allow partial matches.
	 *
	 * @param string $haystack
	 * @param array  $needle
	 *
	 * @return bool|string
	 */

	function cmplz_strpos_arr( $haystack, $needle ) {
		if ( empty( $haystack ) ) {
			return false;
		}
		if ( ! is_array( $needle ) ) {
			$needle = array( $needle );
		}
		foreach ( $needle as $key => $value ) {
			if ( strlen($value) === 0 ) continue;
			if ( ( strpos( $haystack, $value ) ) !== false ) {
				return ( is_numeric( $key ) ) ? $value : $key;
			}
		}

		return false;
	}
}

/**
 * callback for privacy document Check if there is a text entered in the custom privacy statement text
 *
 * */
if ( ! function_exists( 'cmplz_has_custom_privacy_policy' ) ) {
	function cmplz_has_custom_privacy_policy() {
		$policy = cmplz_get_option( 'custom_privacy_policy_text' );
		if ( empty( $policy ) ) {
			return false;
		}

		return true;
	}
}

/**
 * callback for privacy statement document, check if google is allowed to share data with other services
 *
 * */
if ( ! function_exists( 'cmplz_statistics_no_sharing_allowed' ) ) {
	function cmplz_statistics_no_sharing_allowed() {

		$statistics       = cmplz_get_option( 'compile_statistics', false,
			'wizard' );
		$tagmanager       = ( $statistics === 'google-tag-manager' ) ? true
			: false;
		$google_analytics = ( $statistics === 'google-analytics' ) ? true
			: false;

		if ( $google_analytics || $tagmanager ) {
			$thirdparty = $google_analytics
				? cmplz_get_option( 'compile_statistics_more_info' )
				: cmplz_get_option( 'compile_statistics_more_info_tag_manager');
			if ( !is_array($thirdparty) ) {
				$thirdparty = array();
			}
			return in_array( 'no-sharing', $thirdparty, true );
		}

		//only applies to google
		return false;
	}
}

/**
 * callback for privacy statement document. Check if ip addresses are stored.
 *
 * */
if ( ! function_exists( 'cmplz_no_ip_addresses' ) ) {
	function cmplz_no_ip_addresses() {
		$statistics = cmplz_get_option( 'compile_statistics');

		//not anonymous stats.
		if ( $statistics === 'yes' ) {
			return false;
		}

		$tagmanager       = ( $statistics === 'google-tag-manager' ) ? true : false;
		$matomo           = ( $statistics === 'matomo' ) ? true : false;
		$google_analytics = ( $statistics === 'google-analytics' ) ? true : false;

		if ( $google_analytics || $tagmanager ) {
			$thirdparty   = $google_analytics
				? cmplz_get_option( 'compile_statistics_more_info')
				: cmplz_get_option( 'compile_statistics_more_info_tag_manager');
			if ( !is_array($thirdparty) ) {
				$thirdparty = array();
			}
			return in_array( 'ip-addresses-blocked', $thirdparty, true );
		}

		if ( $matomo ) {
			return false;
		}

		return false;
	}
}

if (!function_exists('cmplz_get_console_errors')){
	/**
	 * Get console errors as detected by complianz
	 * @return string
	 */
	function cmplz_get_console_errors(){
		$errors = get_option('cmplz_detected_console_errors');
		$location = isset($errors[2]) && strlen($errors[2])>0 ? $errors[2] : __('the page source', 'complianz-gdpr');
		$line_no = isset($errors[1]) ? $errors[1] : 0;
		if ( $errors && isset($errors[0]) && $line_no>1 ) {
			return cmplz_sprintf(__('%s on line %s of %s', 'complianz-gdpr'), $errors[0], $errors[1], $location);
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_cookie_warning_required_stats_eu' ) ) {
	function cmplz_cookie_warning_required_stats_eu() {
		return COMPLIANZ::$banner_loader->cookie_warning_required_stats('eu');
	}
}

if ( ! function_exists( 'cmplz_cookie_warning_required_stats_uk' ) ) {
	function cmplz_cookie_warning_required_stats_uk() {
		return COMPLIANZ::$banner_loader->cookie_warning_required_stats('uk');
	}
}

if ( ! function_exists( 'cmplz_cookie_warning_required_stats_za' ) ) {
	function cmplz_cookie_warning_required_stats_za() {
		return COMPLIANZ::$banner_loader->cookie_warning_required_stats('za');
	}
}




if ( ! function_exists( 'cmplz_accepted_processing_agreement' ) ) {
	function cmplz_accepted_processing_agreement() {
		$statistics       = cmplz_get_option( 'compile_statistics' );
		$tagmanager       = $statistics === 'google-tag-manager';
		$google_analytics = $statistics === 'google-analytics';

		if ( $google_analytics || $tagmanager ) {
			$thirdparty = $google_analytics
				? cmplz_get_option( 'compile_statistics_more_info' )
				: cmplz_get_option( 'compile_statistics_more_info_tag_manager' );
			if ( !is_array($thirdparty) ) {
				$thirdparty = array();
			}

			return in_array( 'accepted', $thirdparty, true );
		}

		//only applies to google
		return false;
	}
}

if ( ! function_exists( 'cmplz_init_cookie_blocker' ) ) {

	/**
	 * Check if the Cookie Blocker should run
	 * @param bool $admin_test
	 * @return bool
	 */

	function cmplz_can_run_cookie_blocker( $admin_test = false ){
		if ( ! COMPLIANZ::$banner_loader->site_needs_cookie_warning() ) {
			return false;
		}

		if ( cmplz_get_option('enable_cookie_blocker') !== 'yes' ) {
			return false;
		}

		//we can pass a variable $admin_test=true if we want to test cookieblocker availability from admin
		if ( !$admin_test ) {
			//only allow cookieblocker on admin when it's an ajax request
			if ( ! wp_doing_ajax() && is_admin() ) {
				return false;
			}
		}

		if ( is_feed() ) {
			return false;
		}

		//don't fire on the back-end
		if ( is_preview() || cmplz_is_pagebuilder_preview() || isset($_GET["cmplz_safe_mode"]) ) {
			return false;
		}

		if ( defined( 'CMPLZ_DO_NOT_BLOCK' ) && CMPLZ_DO_NOT_BLOCK ) {
			return false;
		}

		if ( cmplz_get_option( 'safe_mode' ) ) {
			return false;
		}

		/* Do not block when visitors are from outside EU or US, if geoip is enabled */
		//check cache, as otherwise all users would get the same output, while this is user specific
		//@todo better check for any caching plugin, as this check does not work with wp rocket for example.
		//if (!defined('wp_cache') && class_exists('cmplz_geoip') && COMPLIANZ::$geoip->geoip_enabled() && (COMPLIANZ::$geoip->region() !== 'eu') && (COMPLIANZ::$geoip->region() !== 'us')) return;

		//do not block cookies during the scan
		if ( cmplz_scan_in_progress() ) {
			return false;
		}

		/* Do not fix block when call is coming from wp_api or from xmlrpc or feed */
		if ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) {
			return false;
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'cmplz_init_cookie_blocker' ) ) {
	function cmplz_init_cookie_blocker() {

		if ( !cmplz_can_run_cookie_blocker() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			add_action( "admin_init", array( COMPLIANZ::$cookie_blocker, "start_buffer" ) );
		} else {
			if (cmplz_is_amp()) {
				add_action( "wp", array( COMPLIANZ::$cookie_blocker, "start_buffer" ) , 20);
			} else {
				add_action( "template_redirect", array( COMPLIANZ::$cookie_blocker, "start_buffer" ) );
			}
		}
		add_action( "shutdown",
			array( COMPLIANZ::$cookie_blocker, "end_buffer" ), 999 );

	}
}

/**
 * check if a pdf document is being generated
 *
 * @return bool
 */

if ( !function_exists('cmplz_is_loading_pdf')) {
	function cmplz_is_loading_pdf() {
		return cmplz_user_can_manage() && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'cmplz_pdf_nonce' );
	}
}

/**
 *
 * Check if we are currently in preview mode from one of the known page builders
 *
 * @return bool
 * @since 2.0.7
 *
 */
if ( ! function_exists( 'cmplz_is_pagebuilder_preview' ) ) {
	function cmplz_is_pagebuilder_preview() {
		$preview = false;
		global $wp_customize;
		if ( isset( $wp_customize )
			 || isset( $_GET['fb-edit'] ) //avada
			 || isset( $_GET['builder_id'] ) //avada
		     || isset( $_GET['et_pb_preview'] ) //divi
		     || isset( $_GET['et_fb'] ) //divi
		     || isset( $_GET['elementor-preview'] )
		     || isset( $_GET['elementor_library'] )
		     || isset( $_GET['elementor-app'] )
		     || isset( $_GET['vc_action'] )
		     || isset( $_GET['vc_editable'] )
		     || isset( $_GET['vcv-action'] )
		     || isset( $_GET['zion_builder_active'])
		     || isset( $_GET['zionbuilder-preview'])
		     || isset( $_GET['tb-preview']) //themify
		     || isset( $_GET['tb-id']) //themify
		     || isset( $_GET['fl_builder'] )
		     || isset( $_GET['tve'] )
			 || isset( $_GET['bricks'] ) //bricks builder
		     || isset( $_GET['ct_builder'] ) //oxygen
			 || isset( $_GET['tatsu'] ) //tatsu
			 || isset( $_GET['tatsu-header'] ) //tatsu
			 || isset( $_GET['tatsu-footer'] ) //tatsu
	         || ( isset( $_GET['action'] ) && $_GET['action'] === 'ms_get_preview' ) //meta slider
			 || strpos( $_SERVER['REQUEST_URI'], 'cornerstone/edit') !== false
		) {
			$preview = true;
		}

		//exclude widgets, and don't exclude banner api
		$request_url = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';
		if ( strpos($request_url, 'wp-json/complianz/')===false && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if (isset($_GET['context']) &&  $_GET['context']==='edit') {
			return true;
		}
		return apply_filters( 'cmplz_is_preview', $preview );
	}
}

if (!function_exists('cmplz_datarequests_active')) {
	/**
	 * Check if the site requires DNSMPI logic
	 *
	 * @return bool
	 */
	function cmplz_datarequests_active() {
		return cmplz_get_option( 'datarequest' ) === 'yes';
	}
}

if (!function_exists('cmplz_datarequests_or_dnsmpi_active')) {
	/**
	 * Check if the site requires data requests OR dnsmpi logic
	 *
	 * @return bool
	 */
	function cmplz_datarequests_or_dnsmpi_active() {
		return cmplz_datarequests_active() || cmplz_has_region('us');
	}
}

if (!function_exists('cmplz_file_exists_on_url')) {
	function cmplz_file_exists_on_url($url){
		$upload_dir = cmplz_upload_dir();
		$upload_url = cmplz_upload_url();
		$path        = str_replace( $upload_url, $upload_dir, $url );
		return file_exists($path);
	}
}

if ( ! function_exists( 'cmplz_geoip_enabled' ) ) {
	function cmplz_geoip_enabled() {
		return apply_filters( 'cmplz_geoip_enabled', false );
	}
}

if ( ! function_exists( 'cmplz_tcf_active' ) ) {
	function cmplz_tcf_active() {
		if ( !defined('cmplz_premium') ) {
			return false;
		}

		if ( cmplz_get_option('uses_ad_cookies') !=='yes' ) {
			return false;
		}

		$tcf_active = cmplz_get_option('uses_ad_cookies_personalized') === 'tcf' || cmplz_get_option('uses_ad_cookies_personalized') === 'yes';
		return apply_filters( 'cmplz_is_tcf_active', $tcf_active );
	}
}


if ( !function_exists('cmplz_get_transient') ) {

	/**
	 * We user our own transient, as the wp transient is not always persistent
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	function cmplz_get_transient( string $name ){
		if ( isset($_GET['cmplz_nocache']) ) {
			return false;
		}

		$value = false;
		$now = time();
		$transients = get_option('cmplz_transients', array());

		if ( isset($transients[$name]) ) {
			$data = $transients[$name];
			$expires = isset($data['expires']) ? $data['expires'] : 0;
			$value = isset($data['value']) ? $data['value'] : false;
			if ( $expires < $now ) {
				unset($transients[$name]);

				update_option('cmplz_transients', $transients);
				$value = false;
			}
		}
		return $value;
	}
}

if (!function_exists('cmplz_delete_transient')) {
	/**
	 * We user our own transient, as the wp transient is not always persistent
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	function cmplz_delete_transient( string $name ): void {
		$transients = get_option( 'cmplz_transients', array() );
		if ( !is_array( $transients ) ) {
			$transients = array();
		}

		if (isset($transients[$name])) {
			unset($transients[$name]);
		}

		update_option( 'cmplz_transients', $transients );
	}
}
if (!function_exists('cmplz_set_transient')) {
	/**
	 * We user our own transient, as the wp transient is not always persistent
	 * Specifically made for license transients, as it stores on network level if multisite.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param int    $expiration
	 *
	 * @return void
	 */
	function cmplz_set_transient( string $name, $value, $expiration ): void {

		$transients = get_option( 'cmplz_transients', array() );
		if ( ! is_array( $transients ) ) {
			$transients = array();
		}

		$transients[ $name ] = array(
				'value'   => $value,
				'expires' => time() + (int) $expiration,
		);
		update_option( 'cmplz_transients', $transients );
	}
}

if (!function_exists('cmplz_upgrade_to_premium')) {
	/**
	 * Standardization upgrade process
	 *
	 * @param string $url
	 * @param bool   $add_space
	 *
	 * @return string
	 */
	function cmplz_upgrade_to_premium( $url, $add_space = true ) {
		$html =  '<a class="cmplz-upgrade-to-premium" target="_blank" href="' . $url . '">'.__( "Upgrade", 'complianz-gdpr' ). '</a>';
		if ( $add_space ) {
			$html = '&nbsp;' . $html;
		}
		return $html;
	}
}

/**
 * For all legal documents for the US, privacy statement, dataleaks or processing agreements, the language should always be en_US
 *
 * */
add_filter( 'locale', 'cmplz_set_plugin_language', 19, 1 );
if ( ! function_exists( 'cmplz_set_plugin_language' ) ) {
	function cmplz_set_plugin_language( $locale ) {
		//@todo the region can't be detected here, because the term is not defined yet.
		if ( isset( $_GET['clang'] ) && $_GET['clang'] === 'en' ) {
			$locale = 'en_US';
		}

		return $locale;
	}
}

/**
 * To make sure the US documents are loaded entirely in English on the front-end,
 * We check if the locale is a not en- locale, and if so, redirect with a query arg.
 * This allows us to recognize the page on the next page load is needing a force US language.
 * */

add_action( 'wp', 'cmplz_add_query_arg' );
if ( ! function_exists( 'cmplz_add_query_arg' ) ) {
	function cmplz_add_query_arg() {
		$cmplz_lang = isset( $_GET['clang'] ) ? $_GET['clang'] : false;
		if ( ! $cmplz_lang && ! cmplz_is_pagebuilder_preview() ) {
			global $wp;
			$type = false;

			$post   = get_queried_object();
			$locale = get_locale();

			//if the locale is english, don't add any query args.
			if ( strpos( $locale, 'en' ) !== false ) {
				return;
			}

			if ( $post && property_exists( $post, 'post_content' ) ) {
				$pattern = '/cmplz-document.*type=".*?".*region="(.*?)"/i';
				$pattern_gutenberg = '/<!-- wp:complianz\/document {.*?"selectedDocument":"[^\"](.*?)\".*?} \/-->/i';
				if ( preg_match_all( $pattern, $post->post_content, $matches,
						PREG_PATTERN_ORDER )
				) {
					if ( isset( $matches[1][0] ) ) {
						$type = $matches[1][0];
					}
				} elseif ( preg_match_all( $pattern_gutenberg,
						$post->post_content, $matches, PREG_PATTERN_ORDER )
				) {
					if ( isset( $matches[1][0] ) ) {
						$type = $matches[1][0];
					}
				}

				if ( strpos( $type, 'us' ) !== false
					 || strpos( $type, 'uk' ) !== false
					 || strpos( $type, 'au' ) !== false
				) {
					//remove lang property, add our own.
					wp_redirect( home_url( add_query_arg( 'clang', 'en',
							remove_query_arg( 'lang', $wp->request ) ) ) );
					exit;
				}
			}

		}
	}
}

if ( !function_exists('cmplz_has_recommended_phpversion')) {
	function cmplz_has_recommended_phpversion(){
		if (version_compare(PHP_VERSION, '7.2','>=')) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'cmplz_array_filter_multidimensional' ) ) {
	function cmplz_array_filter_multidimensional( $array, $filter_key, $filter_value ): array {
		return array_filter( $array,
			static function ( $var ) use ( $filter_value, $filter_key ) {
				return isset( $var[ $filter_key ] ) && $var[ $filter_key ] === $filter_value;
			} );
	}
}

if ( ! function_exists( 'cmplz_is_amp' ) ) {
	/**
	 * Check if we're on AMP, and AMP integration is active
	 * Function should be run not before the 'wp' hook!
	 *
	 * @return bool
	 */
	function cmplz_is_amp() {

		$amp_on = false;

		if ( !$amp_on && function_exists( 'ampforwp_is_amp_endpoint' ) ) {
			$amp_on = ampforwp_is_amp_endpoint();
		}

		if ( !$amp_on && function_exists( 'is_amp_endpoint' ) ) {
			$amp_on = is_amp_endpoint();
		}

		if ( $amp_on ) {
			$amp_on = cmplz_amp_integration_active();
		}

		return $amp_on;
	}
}

if ( ! function_exists( 'cmplz_is_amp_endpoint' ) ) {
	/**
	 * Check if the site is loading as AMP
	 * Function should be run not before the 'wp' hook!
	 *
	 * @return bool
	 */
	function cmplz_is_amp_endpoint() {

		$amp_on = false;

		if ( !$amp_on && function_exists( 'ampforwp_is_amp_endpoint' ) ) {
			$amp_on = ampforwp_is_amp_endpoint();
		}

		if ( !$amp_on && function_exists( 'is_amp_endpoint' ) ) {
			$amp_on = is_amp_endpoint();
		}

		return $amp_on;
	}
}

if ( ! function_exists( 'cmplz_amp_integration_active' ) ) {
	/**
	 * Check if AMP integration is active
	 *
	 * @return bool
	 */
	function cmplz_amp_integration_active() {
		return cmplz_is_integration_enabled( 'amp' );
	}
}


if ( ! function_exists( 'cmplz_allowed_html' ) ) {
	function cmplz_allowed_html() {

		$allowed_tags = array(
			'a'          => array(
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
				'id' => [],
			),
			'button'     => array(
				'id'  => [],
				'class'  => [],
				'href'   => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
				'aria-expanded' => [],
				'aria-controls' => [],
			),
			'b'          => [],
			'br'         => [],
			'blockquote' => array(
				'cite' => [],
			),
			'div' => array(
				'class' => [],
				'id'    => [],
			),
			'h1'         => [],
			'h2'         => array(),
			'h3'         => [],
			'h4'         => [],
			'h5'         => [],
			'h6'         => [],
			'i'          => [],
			'input'      => array(
				'type'        => [],
				'class'       => [],
				'name'        => [],
				'id'          => [],
				'required'    => [],
				'value'       => [],
				'placeholder' => [],
				'data-category' => [],
				'data-service' => [],
				'style' => array(
					'color' => [],
				),			),
			'img'        => array(
				'alt'    => [],
				'class'  => [],
				'height' => [],
				'src'    => [],
				'width'  => [],
			),
			'label'      => array(
				'for' => [],
				'class' => [],
				'style' => array(
					'visibility' => [],
				),
			),
			'li'         => array(
				'class' => [],
				'id'    => [],
			),
			'ol'         => array(
				'class' => [],
				'id'    => [],
			),
			'p'          => array(
				'class' => [],
				'id'    => [],
			),
			'span'       => array(
				'class' => [],
				'title' => [],
				'style' => array(
					'color' => [],
					'display' => [],
				),
				'id'    => [],
			),
			'strong'     => [],
			'table'      => array(
				'class' => [],
				'id'    => [],
			),
			'tr'         => [],
			'details' => array(
				'class' => [],
				'id'    => [],
			),
			'summary' => array(
				'class' => [],
				'id'    => [],
			),
			'svg'         => array(
				'width' => [],
				'height' => [],
				'viewBox' => [],
			),
			'polyline'    => array(
				'points' => [],
			),
			'path'    => array(
				'd' => [],

			),
			'style'      => [],
			'ul'         => array(
				'class' => [],
				'id'    => [],
			),
			'form'         => array(
					'id'    => [],
			),
		);

		return apply_filters( "cmplz_allowed_html", $allowed_tags );
	}
}

if ( ! function_exists( 'cmplz_placeholder' ) ) {
	/**
	 * Get placeholder for any type of blocked content
	 *
	 * @param bool|string $type
	 * @param string      $src
	 *
	 * @return string url
	 *
	 * @since 2.1.0
	 */
	function cmplz_placeholder( $type = false, $src = '' ) {
		if ( ! $type ) {
			$type = cmplz_get_service_by_src( $src );
		}
		$new_src = cmplz_default_placeholder( $type );
		$new_src = apply_filters( "cmplz_placeholder_$type", $new_src, $src );
		$new_src = apply_filters( 'cmplz_placeholder', $new_src, $type, $src );
		return $new_src;
	}
}

if ( ! function_exists( 'cmplz_count_socialmedia' ) ) {
	/**
	 * count the number of social media used on the site
	 *
	 * @return int
	 */
	function cmplz_count_socialmedia() {
		$sm = cmplz_get_option( 'socialmedia_on_site' );
		if ( ! $sm ) {
			return 0;
		}
		if ( ! is_array( $sm ) ) {
			return 1;
		}

		return count( array_filter( $sm ) );
	}
}


if ( ! function_exists( 'cmplz_download_to_site' ) ) {
	/**
	 * Download a placeholder from youtube or video to this website
	 *
	 * @param string      $src
	 * @param bool|string $id
	 * @param bool        $use_filename //some filenames are too long to use.
	 *
	 * @return string url
	 *
	 *
	 * @since 2.1.5
	 */
	function cmplz_download_to_site( $src, $id = false, $use_filename = true ) {
		if ( strpos( $src, "https://" ) === false
			 && strpos( $src, "http://" ) === false
		) {
			$src = str_replace( '//', 'https://', $src );
		}
		if ( ! $id ) {
			$id = time();
		}
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$upload_dir = cmplz_upload_dir('placeholders');

		//set the path
		$filename = $use_filename ? "-" . basename( $src ) : '.jpg';
		$file     = $upload_dir . $id . $filename;

		//set the url
		$new_src = cmplz_upload_url( "placeholders") .  $id.$filename;
		//download file
		$tmpfile = download_url( $src, $timeout = 25 );

		//check for errors
		if ( is_wp_error( $tmpfile ) ) {
			$new_src = cmplz_default_placeholder();
		} else {
			//remove current file
			if ( file_exists( $file ) ) {
				unlink( $file );
			}

			//in case the server prevents deletion, we check it again.
			if ( ! file_exists( $file ) ) {
				copy( $tmpfile, $file );
			}
		}

		if ( is_string( $tmpfile ) && file_exists( $tmpfile ) ) {
			unlink( $tmpfile );
		} // must unlink afterwards

		if ( file_exists( $file ) ) {
			try {
				$new_src = cmplz_create_webp( $file, $new_src );
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( $e->getMessage() );
				}
			}
		}

		if ( ! file_exists( $file ) ) {
			return cmplz_default_placeholder();
		}

		return $new_src;
	}
}

//if (!function_exists('cmplz_create_webp')){
//	function cmplz_create_webp($file, $new_src) {
//		//check webp availability
//		if (
//			!function_exists('imagecreatefromjpeg') ||
//			!function_exists('imagecreatefrompng') ||
//			!function_exists('imagewebp') ||
//			!function_exists('imagedestroy') ||
//			!function_exists('imagepalettetotruecolor') ||
//			!function_exists('imagealphablending') ||
//			!function_exists('imagesavealpha')
//		) {
//			return $new_src;
//		}
//
//		if ( stripos( $file, '.jpeg' ) !== false || stripos( $file, '.jpg' ) !== false ) {
//			$webp_file    = str_replace( array( ".jpeg", '.jpg' ), ".webp", $file );
//			$webp_new_src = str_replace( array( ".jpeg", '.jpg' ), ".webp", $new_src );
//			$image        = imagecreatefromjpeg( $file );
//			imagewebp( $image, $webp_file, 80 );
//			imagedestroy( $image );
//
//			return file_exists( $webp_file ) ? $webp_new_src : $new_src;
//		} elseif ( stripos( $file, '.png' ) !== false ) {
//			$webp_file    = str_replace( '.png', ".webp", $file );
//			$webp_new_src = str_replace( '.png', ".webp", $new_src );
//			$image        = imagecreatefrompng( $file );
//			imagepalettetotruecolor( $image );
//			imagealphablending( $image, true );
//			imagesavealpha( $image, true );
//			imagewebp( $image, $webp_file, 80 );
//			imagedestroy( $image );
//
//			return file_exists( $webp_file ) ? $webp_new_src : $new_src;
//		} else {
//			return $new_src;
//		}
//
//	}
//}

if (!function_exists('cmplz_create_webp')){

	function cmplz_create_webp($file, $new_src) {
		switch ( $file ) {
			case strpos( $file, '.jpeg' )!==false:
			case strpos( $file, '.jpg' )!==false:
				$ext = array(".jpeg", '.jpg');
				break;
			case strpos( $file, 'png' )!==false:
				$ext = '.png';
				break;
			default:
				return $new_src;
		}
		//@todo: new filename is returned by save, so can be used for output instead of brute force extension replace.
		$webp_file = str_replace( $ext, ".webp", $file );
		$webp_new_src = str_replace( $ext, ".webp", $new_src );
		$image = wp_get_image_editor($file);
		$result = $image->save($webp_file, 'image/webp');
		if ( is_wp_error( $result ) ) {
			return $new_src;
		}

		return file_exists($webp_file) ? $webp_new_src : $new_src;
	}
}

if ( ! function_exists( 'cmplz_used_cookies' ) ) {
	function cmplz_used_cookies() {
		$services_template = cmplz_get_template( 'cookiepolicy/services.php' );
		$cookies_row    = cmplz_get_template( 'cookiepolicy/cookies_row.php' );
		$purpose_row    = cmplz_get_template( 'cookiepolicy/purpose_row.php' );
		$language       = substr( get_locale(), 0, 2 );
		$args = array(
			'language'     => $language,
			'showOnPolicy' => true,
			'hideEmpty'    => true,
			'ignored'      => false
		);

		if ( cmplz_get_option( 'wp_admin_access_users' ) === 'yes' ) {
			$args['isMembersOnly'] = 'all';
		}

		$cookies = COMPLIANZ::$banner_loader->get_cookies_by_service( $args );
		$use_cdb_links = cmplz_get_option( 'use_cdb_links' ) === 'yes';
		$consent_per_service = cmplz_get_option( 'consent_per_service' ) === 'yes';
		$cookie_list = COMPLIANZ::$cookie_blocker->cookie_list;

		$google_fonts = new CMPLZ_SERVICE('Google Fonts');
		$servicesHTML = '';
		foreach ( $cookies as $serviceID => $serviceData ) {
			$service    = new CMPLZ_SERVICE( $serviceID, substr( get_locale(), 0, 2 ) );
			//if google fonts is self hosted, don't include in the cookie policy
			if ( cmplz_get_option('self_host_google_fonts') === 'yes'
				 && defined('CMPLZ_SELF_HOSTED_PLUGIN_ACTIVE')
			     && ($serviceID == $google_fonts->ID || $service->isTranslationFrom == $google_fonts->ID) ) {
				continue;
			}
			if ( isset($cookie_list['marketing'][COMPLIANZ::$cookie_blocker->sanitize_service_name($service->name)]) ){
				$topCategory = 'marketing';
			} else if ( isset($cookie_list['statistics'][COMPLIANZ::$cookie_blocker->sanitize_service_name($service->name)]) ) {
				$topCategory = 'statistics';
			} else if ( isset($cookie_list['preferences'][COMPLIANZ::$cookie_blocker->sanitize_service_name($service->name)]) ) {
				$topCategory = 'preferences';
			} else {
				$topCategory = 'functional';
			}

			$serviceCheckboxClass = $consent_per_service ? '' : 'cmplz-hidden';
			$has_empty_cookies = false;
			$allPurposes = array();
            $cookieHTML = "";

			foreach ( $serviceData as $purpose => $service_cookies ) {
				$cookies_per_purpose_HTML = "";
				foreach ( $service_cookies as $cookie ) {
					$has_empty_cookies = $has_empty_cookies || strlen( $cookie->retention ) == 0;
					$link_open         = $link_close = '';

					if ( $use_cdb_links && strlen( $cookie->slug ) !== 0 ) {
						$service_slug = ( empty($service->slug) ) ? 'unknown-service' : $service->slug;
						$link_open
						              = '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/cookie/'
						                . $service_slug . '/' . trailingslashit($cookie->slug)
						                . '">';
						$link_close   = '</a>';
					}
					$cookie_function = apply_filters('cmplz_cookie_function', ucfirst( $cookie->cookieFunction ), $cookie );

                    $cookies_per_purpose_HTML .= str_replace( array(
						'{name}',
						'{retention}',
						'{cookieFunction}',
						'{link_open}',
						'{link_close}'
					), array(
						$cookie->name,
						$cookie->retention,
						$cookie_function,
						$link_open,
						$link_close
					), $cookies_row );
				}
				$cookieHTML .= str_replace( array( '{purpose}', '{cookies_per_purpose}' ), array( $purpose, $cookies_per_purpose_HTML ), $purpose_row );
				$allPurposes[] = $purpose;
			}

			$service_name = $service->name;
			if ( !$service->ID || empty( $service_name ) ){
				$service_name = __( 'Miscellaneous', 'complianz-gdpr' );
				$serviceCheckboxClass = 'cmplz-hidden';
			}

			$sharing = '';
			if ( $service_name === 'Complianz' ) {
				$link = '<a target="_blank" rel="noopener noreferrer" href="https://complianz.io/legal/privacy-statement/">';
				$sharing = __( 'This data is not shared with third parties.', 'complianz-gdpr' )
						.'&nbsp;'
						. cmplz_sprintf( __( 'For more information, please read the %s%s Privacy Statement%s.', 'complianz-gdpr' ), $link, $service_name, '</a>' );
			} else if ( $service->sharesData  ) {
				$attributes = "noopener noreferrer nofollow";
				if ( $service->privacyStatementURL !== '' ) {
					$link    = '<a target="_blank" rel="'.$attributes.'" href="' . $service->privacyStatementURL . '">';
					$sharing = cmplz_sprintf( __( 'For more information, please read the %s%s Privacy Statement%s.', 'complianz-gdpr' ), $link, $service_name, '</a>' );
				}
			} elseif ( !empty( $service->name ) ) { //don't state sharing info on misc services
				$sharing = __( 'This data is not shared with third parties.', 'complianz-gdpr' );
			} else {
				$sharing = __( 'Sharing of data is pending investigation', 'complianz-gdpr' );
			}
			$purposeDescription = ( ( !empty( $service_name ) ) && ( !empty( $service->serviceType ) ) )
				? cmplz_sprintf( _x( "We use %s for %s.", 'Legal document cookie policy', 'complianz-gdpr' ), $service_name, $service->serviceType ) : '';

			if ( $use_cdb_links
			     && !empty( $service->slug )
			     && $service->slug !== 'unknown-service'
			) {
				// Create descriptive link text that explains what the link is for
				$link_text = cmplz_sprintf( __( 'Read more about %s', 'complianz-gdpr' ), $service_name );
				$aria_label = cmplz_sprintf( __( 'Read more about %s service on Cookie Database', 'complianz-gdpr' ), $service_name );
				
				$link_open = '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/service/' . $service->slug . '/" aria-label="' . esc_attr( $aria_label ) . '">';
				$purposeDescription .= ' ' . $link_open . $link_text . '</a>';
			}
			if ( count($allPurposes)>1 ){
				$p_key = array_search(__( 'Purpose pending investigation', 'complianz-gdpr' ), $allPurposes);
				if ($p_key!==false) unset($allPurposes[$p_key]);
			}

			$allPurposes = implode (", ", $allPurposes);
			$service_slug = str_replace(' ', '-', strtolower($service_name));

			$servicesHTML .= str_replace( array(
				'{service}',
				'{service_slug}',
				'{sharing}',
				'{purposeDescription}',
				'{cookies}',
				'{allPurposes}',
				'{serviceCheckboxClass}',
				'{topCategory}'
			), array(
				$service_name,
				$service_slug,
				$sharing,
				$purposeDescription,
				$cookieHTML,
				$allPurposes,
				$serviceCheckboxClass,
				$topCategory
			), $services_template );
		}

		$servicesHTML = '<div id="cmplz-cookies-overview">'.$servicesHTML.'</div>';

		return str_replace( '{plugin_url}',CMPLZ_URL, $servicesHTML);
	}
}

if (!function_exists('cmplz_has_consent')) {
	/**
	 * @param string $category
	 *
	 * @return bool
	 */
	function cmplz_has_consent( $category ) {
		$consent_type     = apply_filters( 'cmplz_user_consenttype', COMPLIANZ::$company->get_default_consenttype() );
		$prefix           = COMPLIANZ::$banner_loader->get_cookie_prefix();
		$cookie_name      = "{$prefix}{$category}";
		if ( ! $consent_type ) {
			// If consent_type is not set, there's no consent management, we should
			// return true to activate all cookies.
			$has_consent = true;
		} elseif ( strpos( $consent_type, 'optout' ) !== false && (!isset( $_COOKIE[ $cookie_name ] )) ) {
			// If it's opt out and no cookie is set or it's false, we should also return true.
			$has_consent = true;
		} elseif ( isset( $_COOKIE[ $cookie_name ] ) && 'allow' === $_COOKIE[ $cookie_name ] ) {
			// All other situations, return only true if value is allow.
			$has_consent = true;
		} else {
			$has_consent = false;
		}

		return apply_filters( 'cmplz_has_consent', $has_consent, $category );
	}
}

if (!function_exists('cmplz_has_service_consent')) {
	/**
	 * Check if a service has consent
	 *
	 * @param string $service
	 *
	 * @return bool
	 *
	 */
	function cmplz_has_service_consent( $service ) {
		$consent_type     = apply_filters( 'cmplz_user_consenttype', COMPLIANZ::$company->get_default_consenttype() );
		$prefix           = COMPLIANZ::$banner_loader->get_cookie_prefix();
		$cookie_name      = "{$prefix}consented_services";
		$consented_services = isset($_COOKIE[ $cookie_name ]) ? json_decode(stripslashes($_COOKIE[ $cookie_name ])) : false;
		if ( ! $consent_type ) {
			// If consent_type is not set, there's no consent management, we should
			// return true to activate all cookies.
			$has_consent = true;
		} elseif ( strpos( $consent_type, 'optout' ) !== false ) {
			// If it's opt out there's no consent per service, we should also return true.
			$has_consent = true;
		} elseif ( $consented_services && property_exists( $consented_services, $service ) && 1 == $consented_services->{$service} ) {
			// All other situations, return only true if value is allow.
			$has_consent = true;
		} else {
			$has_consent = false;
		}

		return apply_filters( 'cmplz_has_service_consent', $has_consent, $service );
	}
}

/**
 * Check if this field is translatable
 *
 * @param $fieldname
 *
 * @return bool
 */

if ( ! function_exists( 'cmplz_translate' ) ) {
	function cmplz_translate( $value, $fieldname ) {
		if ( function_exists( 'pll__' ) ) {
			$value = pll__( $value );
		}

		if ( function_exists( 'icl_translate' ) ) {
			$value = icl_translate( 'complianz', $fieldname, $value );
		}

		$value = apply_filters( 'wpml_translate_single_string', $value, 'complianz', $fieldname );

		return $value;

	}
}

if ( !function_exists('cmplz_get_server') ) {
	/**
	 * Get server type
	 *
	 * @return string
	 */

	function cmplz_get_server() {
		$server_raw = strtolower( sanitize_text_field($_SERVER['SERVER_SOFTWARE']) );
		//figure out what server they're using
		if ( strpos( $server_raw, 'apache' ) !== false ) {
			return 'Apache';
		} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
			return 'NGINX';
		} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
			return 'LiteSpeed';
		} else { //unsupported server
			return 'Not recognized';
		}
	}
}

if (!function_exists('cmplz_sanitize_category')) {
	function cmplz_sanitize_category($category){
		$cats = ['functional','preferences', 'statistics', 'marketing'];
		if ( !in_array( $category, $cats, true ) ) {
			$category = 'marketing';
		}
		return $category;
	}
}

if (!function_exists('cmplz_sanitize_consenttype')) {
	function cmplz_sanitize_consenttype($consenttype){
		$types = ['optin','optout', 'other', 'optinstats'];//optinstats might be used by wp consent api
		if ( !in_array( $consenttype, $types, true ) ) {
			$consenttype = 'other';
		}
		return $consenttype;
	}
}

/**
 * Show a reference to cookiedatabase if user has accepted the API
 *
 * @return bool
 */

if ( ! function_exists( 'cmplz_cdb_reference_in_policy' ) ) {
	function cmplz_cdb_reference_in_policy() {
        $use_reference = COMPLIANZ::$banner_loader->use_cdb_api();
		return apply_filters( 'cmplz_use_cdb_reference', $use_reference );
	}
}

/**
 * Register a translation
 *
 * @param $fieldname
 *
 * @return bool
 */

if ( ! function_exists( 'cmplz_register_translation' ) ) {

	function cmplz_register_translation( $string, $fieldname ) {
		if ( ! is_string( $string ) || !is_string($fieldname) ) {
			return;
		}
		//polylang
		if ( function_exists( "pll_register_string" ) ) {
			pll_register_string( $fieldname, $string, 'complianz' );
		}

		//wpml
		if ( function_exists( 'icl_register_string' ) ) {
			icl_register_string( 'complianz', $fieldname, $string );
		}

		do_action( 'wpml_register_single_string', 'complianz', $fieldname, $string );

	}
}

if ( ! function_exists( 'cmplz_default_placeholder' ) ) {
	/**
	 * Return the default placeholder image
	 *
	 * @return string placeholder
	 * @since 2.1.5
	 */
	function cmplz_default_placeholder( $type = 'default' ) {
		//treat open streetmaps same as google maps.
		if ( $type === 'openstreetmaps' ) {
			$type = 'google-maps';
		}

		$style = cmplz_get_option('placeholder_style');
		$ratio = $type === 'google-maps' ? cmplz_get_option( 'google-maps-format' ) : '';
		$path = CMPLZ_PATH . "assets/images/placeholders";
		//check if this type exists as placeholder
		if ( file_exists( "$path/$type-$style-$ratio.jpg" ) ) {
			$img = "$type-$style-$ratio.jpg";
		} else if ( file_exists( "$path/$type-$style.jpg" ) ) {
			$img = "$type-$style.jpg";
		} else {
			$img = "default-$style.jpg";
		}

		$img_url = CMPLZ_URL . 'assets/images/placeholders/' . $img;

		//check for image in themedir/complianz-gpdr-premium
		$theme_img = trailingslashit( get_stylesheet_directory() ) . trailingslashit( basename( CMPLZ_PATH ) ) . $img;
		if ( file_exists( $theme_img ) ) {
			$img_url = trailingslashit( get_stylesheet_directory_uri() ) . trailingslashit( basename( CMPLZ_PATH ) ) . $img;
		}

		return apply_filters( 'cmplz_default_placeholder', $img_url, $type, $style );
	}
}

if ( ! function_exists( 'cmplz_get_document_url' ) ) {
	/**
	 * Get url to legal document
	 *
	 * @param string $region
	 *
	 * @return string URL
	 */

	function cmplz_get_document_url( $type, $region = 'eu' ) {
		return COMPLIANZ::$document->get_page_url( $type, $region );
	}
}

if ( ! function_exists( 'cmplz_remote_file_exists' ) ) {
	/**
	 * Check if a remote file exists
	 *
	 * @param string $url
	 *
	 * @return bool
	 */

	function cmplz_remote_file_exists( string $url ): bool {
		if ( empty($url) ) {
			return false;
		}

		try {
			$headers = @get_headers($url);
			if ($headers === false) {
				// URL is not accessible or some error occurred
				return false;
			}

			// Check if the HTTP status code starts with "200" (indicating success)
			return strpos($headers[0], '200') !== false;

		} catch (Exception $e) {

		}

		return false;
	}

}

if ( ! function_exists( 'cmplz_detected_firstparty_marketing' )) {

	/**
	 * Check if we detect first party marketing scripts
	 * @return bool
	 */

	function cmplz_detected_firstparty_marketing(){
		global $cmplz_integrations_list;
		$active_plugins = array();
		foreach ( $cmplz_integrations_list as $plugin => $details ) {
			if ( cmplz_integration_plugin_is_enabled( $plugin ) ) {
				$active_plugins[$plugin] = $details;
			}
		}
		$firstparty_plugins = array_filter(array_column($active_plugins, 'firstparty_marketing'));

		return count($firstparty_plugins)>0;
	}
}

if ( ! function_exists( 'cmplz_uses_gutenberg' ) ) {
	function cmplz_uses_gutenberg() {

		if ( function_exists( 'has_block' )
		     && !class_exists( 'Classic_Editor', false )
		) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_get_used_consenttypes' ) ) {
	/**
	 * Get list of consenttypes in use on this site, based on the selected regions
	 * @param bool $add_labels
	 * @return array consenttypes
	 */
	function cmplz_get_used_consenttypes( $add_labels = false ) {
		//get all regions in use on this site
		$regions       = cmplz_get_regions();
		//if manuanl override detected, add that region's consenttype here.
		$consent_types = array();
		//for each region, get the consenttype
		foreach ( $regions as $region ) {
			if ( ! isset( COMPLIANZ::$config->regions[ $region ]['type'] ) ) {
				continue;
			}
			$consent_types[] = apply_filters( 'cmplz_consenttype', COMPLIANZ::$config->regions[ $region ]['type'], $region );
		}

		//there's no way we can simply find the consenttype for the manually added region, due to fallback complexity. So we add all of them in that case.
		if ( isset( $_GET['cmplz_user_region']) ) {
			$consent_types[] = 'optin';
			$consent_types[] = 'optout';
		}

		//remove duplicates
		$consent_types = array_unique( $consent_types );
		if ( $add_labels ) {
			$consent_types_labelled = array();
			foreach ( $consent_types as $consent_type ) {
				$consent_types_labelled[$consent_type] = cmplz_consenttype_nicename($consent_type);
			}
			$consent_types = $consent_types_labelled;
		}

		return $consent_types;
	}
}

if ( ! function_exists( 'cmplz_short_date_format') ) {
	/**
	 * Make sure the date formate is always the short version. If "F" (February) is used, replace with "M" (Feb)
	 * @return string
	 */
	function cmplz_short_date_format(){
		return str_replace( array('F', 'Y'), array('M', 'y'), get_option( 'date_format' ) );
	}
}

if ( ! function_exists( 'cmplz_uses_preferences_cookies' ) ) {

    /**
     * Check if the site uses preferences cookies
     *
     * @return bool
     */
    function cmplz_uses_preferences_cookies()
    {
        return cmplz_consent_mode() || cmplz_consent_api_active() || cmplz_get_option( 'consent_per_service' ) === 'yes';
    }
}

if ( ! function_exists( 'cmplz_uses_statistic_cookies' ) ) {

	/**
	 * Check if the site uses statistic cookies
	 *
	 * @return bool
	 */
	function cmplz_uses_statistic_cookies()
	{
		return cmplz_get_option( 'compile_statistics' ) !== 'no' || cmplz_uses_thirdparty('vimeo');
	}
}

if ( ! function_exists( 'cmplz_uses_marketing_cookies' ) ) {

	/**
	 * Check if the site uses marketing cookies
	 *
	 * @return bool
	 */
	function cmplz_uses_marketing_cookies() {

		$uses_marketing_cookies
				= cmplz_get_option( 'uses_ad_cookies' ) === 'yes'
				  || cmplz_get_option( 'uses_firstparty_marketing_cookies' ) === 'yes'
				  || cmplz_get_option( 'uses_thirdparty_services' ) === 'yes'
				  || cmplz_get_option( 'uses_social_media' ) === 'yes';

		return apply_filters( 'cmplz_uses_marketing_cookies', $uses_marketing_cookies );
	}
}



if ( ! function_exists( 'cmplz_impressum_required' ) ) {

    /**
     * Check if the site requires an impressum
     *
     * @return bool
     */
    function cmplz_impressum_required() {
        return cmplz_get_option( 'eu_consent_regions' ) === 'yes' && cmplz_get_option( 'impressum' ) !== 'none' ;
    }
}

if ( ! function_exists( 'cmplz_uses_optin' ) ) {

	/**
	 * Check if the site uses one of the optin types
	 *
	 * @return bool
	 */
	function cmplz_uses_optin() {
		$regions = cmplz_get_regions();
		//ensure a default in case of no regions, to prevent weird cookie banner wysiwyg issues.
		if (count($regions)===0) {
			return true;
		}
		return ( in_array( 'optin', cmplz_get_used_consenttypes() )
		         || in_array( 'optinstats', cmplz_get_used_consenttypes() ) );
	}
}


if ( ! function_exists( 'cmplz_has_only_functional_category' ) ) {
	/**
	 * Check if the site only has functional cookies available
	 * This means no marketing, statistics, or preferences cookies are used
	 *
	 * @return bool
	 */
	function cmplz_has_only_functional_category() {
		// Check if any other categories besides functional are used
		$uses_marketing = cmplz_uses_marketing_cookies();
		$uses_statistics = cmplz_uses_statistic_cookies();
		$uses_preferences = cmplz_uses_preferences_cookies();
		
		// If none of the other categories are used, only functional is available
		return !$uses_marketing && !$uses_statistics && !$uses_preferences;
	}
}

if ( ! function_exists( 'cmplz_uses_optout' ) ) {
	function cmplz_uses_optout() {
		return ( in_array( 'optout', cmplz_get_used_consenttypes() ) );
	}
}

if ( ! function_exists( 'cmplz_ab_testing_enabled' ) ) {
	function cmplz_ab_testing_enabled() {
		return apply_filters( 'cmplz_ab_testing_enabled', false );
	}
}


if ( ! function_exists( 'cmplz_consenttype_nicename' ) ) {
	/**
	 * Get nice name for consenttype
	 *
	 * @param string $consenttype
	 *
	 * @return string nicename
	 */
	function cmplz_consenttype_nicename( $consenttype ) {
		switch ( $consenttype ) {
			case 'optin':
				return __( 'Opt-in', 'complianz-gdpr' );
			case 'optout':
				return __( 'Opt-out', 'complianz-gdpr' );
			default :
				return __( 'All consent types', 'complianz-gdpr' );
		}
	}
}

if ( ! function_exists( 'cmplz_uses_sensitive_data' ) ) {
	/**
	 * Check if site uses sensitive data
	 *
	 * @return bool uses_sensitive_data
	 */
	function cmplz_uses_sensitive_data() {
		$special_data = array(
			'bank-account',
			'financial-information',
			'medical',
			'health-insurcance'
		);
		foreach ( COMPLIANZ::$config->purposes as $key => $label ) {

			if ( ! empty( COMPLIANZ::$config->details_per_purpose_us ) ) {
				foreach ( $special_data as $special_data_key ) {
					$value = cmplz_get_option( $key . '_data_purpose_us' );
					if ( isset( $value[ $special_data_key ] )
					     && $value[ $special_data_key ]
					) {
						return true;
					}
				}
			}

		}

		return false;
	}
}


if ( ! function_exists( 'cmplz_get_consenttype_for_region' ) ) {
	/**
	 * Get the consenttype which is used in this region
	 *
	 * @param string $region
	 *
	 * @return string consenttype
	 */
	function cmplz_get_consenttype_for_region( $region ) {
		//fallback
		if ( ! isset( COMPLIANZ::$config->regions[ $region ]['type'] ) ) {
			$consenttype = 'optin';
		} else {
			$consenttype = COMPLIANZ::$config->regions[ $region ]['type'];
		}
		return apply_filters( 'cmplz_consenttype', $consenttype, $region );
	}
}

if ( ! function_exists( 'cmplz_uses_consenttype' ) ) {
	/**
	 * Check if a specific consenttype is used
	 *
	 * @param string $check_consenttype
	 * @param string $region
	 *
	 * @return bool $uses_consenttype
	 */
	function cmplz_uses_consenttype( $check_consenttype, $region = false ) {
		if ( $region ) {
			//get consenttype for region
			$consenttype = cmplz_get_consenttype_for_region( $region );
			if ( $consenttype === $check_consenttype ) {
				return true;
			}
		} else {
			//check if any region has a consenttype $check_consenttype
			$regions = cmplz_get_regions();
			foreach ( $regions as $k_region ) {
				$consenttype = cmplz_get_consenttype_for_region( $k_region );
				if ( $consenttype === $check_consenttype ) {
					return true;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_get_default_banner_id' ) ) {

	/**
	 * Get the default banner ID
	 *
	 * @return int default_ID
	 */
	function cmplz_get_default_banner_id() {
		$banner_id = cmplz_get_transient('cmplz_default_banner_id');
		if ( !$banner_id ){
			if ( !get_option('cmplz_cbdb_version') ) {
				//table not created yet.
				return 0;
			}
			global $wpdb;
			$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners as cb where cb.default = true" );

			//if nothing, try the first entry
			if ( empty( $cookiebanners ) ) {
				$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners" );
			}

			if ( ! empty( $cookiebanners ) ) {
				$banner_id = $cookiebanners[0]->ID;
			}
			cmplz_set_transient('cmplz_default_banner_id', $banner_id, HOUR_IN_SECONDS);
		}
		return $banner_id;
	}
}

if ( ! function_exists( 'cmplz_user_can_manage' ) ) {
	function cmplz_user_can_manage() {
		if ( current_user_can( apply_filters('cmplz_capability','manage_privacy') )
		) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_get_cookiebanners' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param array $args
	 *
	 * @return array
	 */

	function cmplz_get_cookiebanners( $args = array() ) {
		$args = wp_parse_args( $args, array( 'status' => 'active' ) );
		$sql  = '';
		global $wpdb;

		if ( isset($args['ID']) ) {
			$sql = 'AND cdb.ID = ' . (int) $args['ID'];
		}

		if ( isset( $args['default'] ) && $args['default'] === true ) {
			$sql = 'AND cdb.default = true LIMIT 1';
		}
		if ( isset( $args['default'] ) && $args['default'] === false ) {
			$sql = 'AND cdb.default = false';
		}
		if ( isset( $args['limit'] ) && $args['limit'] !== false ) {
			$sql = ' LIMIT ' . (int) $args['limit'];
		}

		$sql_string = empty($sql) ? 'default' : sanitize_title($sql);
		$cookiebanners = wp_cache_get('cmplz_cookiebanners_'.$sql_string, 'complianz');
		if ( !$cookiebanners ){
			$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners as cdb where 1=1 $sql" );
			wp_cache_set('cmplz_cookiebanners_'.$sql_string, $cookiebanners, 'complianz', HOUR_IN_SECONDS);
		}

		return $cookiebanners;
	}
}

/**
 * Force re-save all cookie banners to regenerate CSS and clear caches
 *
 * Used during upgrades to ensure banners render correctly with template changes.
 *
 * @return void
 */
if ( ! function_exists( 'cmplz_resave_all_banners' ) ) {
	function cmplz_resave_all_banners() {
		$banners = cmplz_get_cookiebanners();
		if ( $banners ) {
			foreach ( $banners as $banner_item ) {
				$banner = new CMPLZ_COOKIEBANNER( $banner_item->ID );
				$banner->save();
			}
		}
	}
}

if ( ! function_exists( 'cmplz_sanitize_language' ) ) {

	/**
	 * Validate a language string
	 *
	 * @param $language
	 *
	 * @return bool|string
	 */

	function cmplz_sanitize_language( $language ) {
		$pattern = '/^[a-zA-Z]{2}$/';
		if ( ! is_string( $language ) ) {
			return false;
		}
		$language = substr( $language, 0, 2 );

		if ( (bool) preg_match( $pattern, $language ) ) {
			$language = strtolower( $language );

			return $language;
		}

		return false;
	}
}


if ( ! function_exists( 'cmplz_sanitize_languages' ) ) {

	/**
	 * Validate a languages array
	 *
	 * @param array $language
	 *
	 * @return array
	 */

	function cmplz_sanitize_languages( $languages ) {
		$output = array();
		foreach ( $languages as $language ) {
			$output[] = cmplz_sanitize_language( $language );
		}

		return $output;
	}
}

if ( ! function_exists( 'cmplz_remove_free_translation_files' ) ) {

	/**
	 *   Get a list of files from a directory, with the extensions as passed.
	 */

	function cmplz_remove_free_translation_files() {
		//can't use CMPLZ_PATH here, it may not have been defined yet on activation
		$path = plugin_dir_path(__FILE__);
		$path = dirname( $path, 2 ) . "/languages/plugins/";
		$extensions = array( "po", "mo", "php" );
		if ( $handle = opendir( $path ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file != "." && $file != ".." ) {
					$file = $path . '/' . $file;
					$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

					if ( is_file( $file ) && in_array( $ext, $extensions )
					     && strpos( $file, 'complianz-gdpr' ) !== false
					     && strpos( $file, 'backup' ) === false
					) {
						unlink($file);
					}
				}
			}
			closedir( $handle );
		}
	}
}

if ( ! function_exists( 'cmplz_has_free_translation_files' ) ) {

	/**
	 * Get a list of files from a directory, with the extensions as passed.
	 *
	 * @return bool
	 */

	function cmplz_has_free_translation_files() {
		//can't use CMPLZ_PATH here, it may not have been defined yet on activation
		$path = plugin_dir_path(__FILE__);
		$path = dirname( $path, 2 ) . "/languages/plugins/";

		if ( ! file_exists( $path ) ) {
			return false;
		}

		$has_free_files = false;
		$extensions     = array( "po", "mo" );
		if ( $handle = opendir( $path ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file != "." && $file != ".." ) {
					$file = $path . '/' . $file;
					$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

					if ( is_file( $file ) && in_array( $ext, $extensions )
					     && strpos( $file, 'complianz-gdpr' ) !== false
					) {
						$has_free_files = true;
						break;
					}
				}
			}
			closedir( $handle );
		}

		return $has_free_files;
	}
}

if (!function_exists('array_key_first')) {
    function array_key_first(array $array) {
		reset($array);
		return key($array);
    }
}

if ( ! function_exists( 'cmplz_sprintf' ) ) {
	/**
	 * Wrapper function for sprintf to prevent fatal errors when the %s variables in source and target do not match
	 * @param string $format
	 * @param mixed $values
	 * @return string
	 */
	function cmplz_sprintf(){
		$args = func_get_args();
		$count = substr_count($args[0], '%s');
		$args_count = count($args) - 1;
		if ( $args_count === $count ){
			return call_user_func_array('sprintf', $args);
		} else {
			$output = $args[0];
			if ( cmplz_admin_logged_in() ){
				$output .=  '&nbsp;<a target="_blank" href="https://complianz.io/translation-error-sprintf-printf-too-few-arguments">(Translation error)</a>';
			}
			return $output;
		}
	}
}

if ( !function_exists('cmplz_dnt_enabled') ) {
	/**
	 * Premium should respect Do Not Track settings in browsers, if the user has enabled this setting.
	 *
	 *
	 * */
	function cmplz_dnt_enabled()
	{
		//only if the user has explicitly enabled this
		if ( cmplz_get_option('respect_dnt') !== 'no' ) {
			return ( ( isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1 ) || isset($_SERVER['HTTP_SEC_GPC']) );
		}
		return false;
	}
}

if ( ! function_exists( 'cmplz_printf' ) ) {
	/**
	 * Wrapper function for printf to prevent fatal errors when the %s variables in source and target do not match
	 * @param string $format
	 * @param mixed $values
	 * @echo string
	 */
	function cmplz_printf(){
		$args = func_get_args();
		$count = substr_count($args[0], '%s');
		$args_count = count($args) - 1;
		if ( $args_count === $count ){
			echo call_user_func_array('sprintf', $args);
		} else {
			$output = $args[0];
			if ( cmplz_admin_logged_in() ){
				$output .=  '&nbsp;<a target="_blank" href="https://complianz.io/translation-error-sprintf-printf-too-few-arguments">(Translation error)</a>';
			}
			echo $output;
		}
	}
}

if ( ! function_exists('cmplz_quebec_notice')) {
	function cmplz_quebec_notice() {

		$text = cmplz_sprintf( __( "In September 2023 the Quebec bill 64 will be enforced in Canada. In order to keep your site compliant, %sopt-in must be implemented for Canada%s. Please Navigate to the %sWizard%s and enable opt-in for Canada.", "complianz-gdpr" ), '<strong>', '</strong>' , '<a href="' . admin_url( 'admin.php?page=cmplz-wizard&step=1' ) . '">', '</a>' ) . "<br><br>";
		$text .= __( "Please be aware that this will activate opt-in for Canada, altering the banner and blocking non-functional scripts and cookies prior to consent. Please check the front-end of your site after activating opt-in.", "complianz-gdpr" );

		return $text;
	}
}

if ( ! function_exists('cmplz_requires_quebec_notice') ) {
	function cmplz_requires_quebec_notice() {

		if ( array_key_exists('ca', cmplz_get_regions() )
		     && cmplz_get_option('sensitive_information_processed') !== 'yes'
			 && cmplz_upgraded_to_current_version() ) {
			return true;
		}

		return false;

	}
}


if (!function_exists('cmplz_wsc_is_enabled')) {
	/**
	 * Helper function to check if the wsc is enabled,
	 * then show/hide input fields on wsc settings page.
	 *
	 * @return bool
	 */
	function cmplz_wsc_is_enabled(): bool
	{
		$signup_status = get_option('cmplz_wsc_signup_status') === 'enabled'; // true if === "enabled"

		return $signup_status;
	}
}


if ( ! function_exists('cmplz_targets_quebec') ) {
	function cmplz_targets_quebec() {
		if ( cmplz_get_option('ca_targets_quebec') === 'yes') {
			return true;
		}

		return false;
	}
}
