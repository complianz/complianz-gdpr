<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_default_value', 'cmplz_set_default', 10, 3 );
function cmplz_set_default( $value, $fieldname, $field ) {
	if ( $fieldname === 'compile_statistics' ) {
		$stats = cmplz_scan_detected_stats();
		if ( $stats ) {
			return reset( $stats );
		}
	}

	if ( $fieldname === 'purpose_personaldata' ) {
		if ( cmplz_has_region('us')
		     && COMPLIANZ::$banner_loader->site_shares_data()
		) {
			//possibly not an array yet, when it's empty
			if ( ! is_array( $value ) ) {
				$value = array();
			}
			$value[] = 'selling-data-thirdparty';

			return $value;
		}
	}

	if ( $fieldname === 'sensitive_information_processed' ) {
		if ( cmplz_uses_sensitive_data() ) {
			return 'yes';
		}
	}

	if ( $fieldname === 'purpose_personaldata' ) {
		$contact_forms = cmplz_site_uses_contact_forms();
		if ( $contact_forms ) {
			//possibly not an array yet, when it's empty
			if ( ! is_array( $value ) ) {
				$value = array();
			}
			$value[] = 'contact';

			return $value;
		}
	}

	if ( $fieldname === 'country_company' ) {
		$country_code = substr( get_locale(), 3, 2 );
		if ( isset( COMPLIANZ::$config->countries[ $country_code ] ) ) {
			$value = $country_code;
		}

	}

	if ( $fieldname === 'uses_social_media' ) {
		$social_media = cmplz_scan_detected_social_media();
		if ( $social_media ) {
			return 'yes';
		}
	}

	if ( $fieldname === 'socialmedia_on_site' ) {
		$social_media = cmplz_scan_detected_social_media();
		if ( $social_media ) {
			$current_social_media = array();
			foreach ( $social_media as $key ) {
				$current_social_media[ $key ] = 1;
			}

			return $current_social_media;
		}
	}

	if ( $fieldname === 'uses_thirdparty_services' ) {
		$blocked_scripts = COMPLIANZ::$cookie_blocker->blocked_scripts();
		$custom_thirdparty_scripts = is_array($blocked_scripts) && count( $blocked_scripts ) > 0;
		if ( cmplz_scan_detected_thirdparty_services() || $custom_thirdparty_scripts ) {
			return 'yes';
		}
	}
	if ( $fieldname === 'thirdparty_services_on_site' ) {
		$thirdparty = cmplz_scan_detected_thirdparty_services();
		if ( $thirdparty ) {
			$current_thirdparty = array();
			foreach ( $thirdparty as $key ) {
				$current_thirdparty[ $key ] = 1;
			}

			return $current_thirdparty;
		}
	}

	if ( $fieldname === 'data_disclosed_us' || $fieldname === 'data_sold_us' ) {
		if ( COMPLIANZ::$banner_loader->site_shares_data() ) {
			//possibly not an array yet.
			if ( ! is_array( $value ) ) {
				$value = array();
			}
			$value['internet'] = 1;

			return $value;
		}
	}

	if ( $fieldname === 'uses_firstparty_marketing_cookies' ) {
		if ( cmplz_detected_firstparty_marketing() ) {
			return 'yes';
		}
	}

	/** Add-ons **/
	if ($fieldname === 'financial-incentives-terms-url') {
		if ( defined( 'cmplz_tc_version' )) {
			$page_id = COMPLIANZ_TC::$document->get_shortcode_page_id();
			if ($page_id) {
				return get_permalink($page_id);
			}
		}
	}

	if ( $fieldname === 'dpo_or_gdpr' ) {
		if (cmplz_has_region('eu') &&  ! cmplz_company_located_in_region( 'eu' ) ) {
			return 'gdpr_rep';
		}

		if (cmplz_has_region('uk') && !cmplz_company_located_in_region('uk')) {
			return 'uk_gdpr_rep';
		}
	}

	if ( $fieldname === 'is_webshop' ){
		if (class_exists( 'WooCommerce' ) || class_exists( 'Easy_Digital_Downloads' ) ) {
			$value = 'yes';
		}
	}

	return $value;
}
