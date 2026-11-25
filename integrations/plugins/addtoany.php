<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// add_filter( 'cmplz_known_script_tags', 'cmplz_addtoany_script' );
// function cmplz_addtoany_script( $tags ) {
// 	if ( !cmplz_consent_api_active() ) {
// 		$tags[] = array(
// 			'name'               => 'addtoany',
// 			'category'           => 'marketing',
// 			'urls'               => array(
// 				'static.addtoany.com/menu/page.js',
// 			),
// 			'enable_placeholder' => '0',
// 		);
// 	}
// 	return $tags;
// }

/**
 * Add a warning that integrations changed.
 *
 * @param array $warnings
 *
 * @return array
 */
//function cmplz_wp_consent_api_warnings_add_to_any($warnings)
//{
//	if ( !cmplz_consent_api_active() ){
//		$warnings['wp_consent-api'] = array(
//			'plus_one'          => false,
//			'dismissable'       => true,
//			'warning_condition' => '_true_',
//			'open'              => __( 'You have installed the Add To Any plugin that uses the WP Consent API.', 'complianz-gdpr' ),
//			'url'               => 'https://complianz.io/help/wordpress/faq/proposal-to-add-a-consent-api-to-wordpress/',
//		);
//	}
//	return $warnings;
//}
//add_filter('cmplz_warning_types', 'cmplz_wp_consent_api_warnings_add_to_any');
//

/**
 * Add social media to the list of detected items, so it will get set as default, and will be added to the notice about it
 *
 * @param array $social_media
 *
 * @return array
 */
function cmplz_addtoany_detected_social_media( $social_media ) {

	if ( ! in_array( 'facebook', $social_media ) ) {
		$social_media[] = 'facebook';
	}
	if ( ! in_array( 'twitter', $social_media ) ) {
		$social_media[] = 'twitter';
	}
	if ( ! in_array( 'pinterest', $social_media ) ) {
		$social_media[] = 'pinterest';
	}

	return $social_media;
}
add_filter( 'cmplz_detected_social_media', 'cmplz_addtoany_detected_social_media' );

/**
 * Add services to the list of detected items, so it will get set as default, and will be added to the notice about it
 *
 * @param array $services
 *
 * @return array
 */

function cmplz_addtoany_detected_services( $services ) {
	if ( ! in_array( 'addtoany', $services ) ) {
		$services[] = 'addtoany';
	}

	return $services;
}
add_filter( 'cmplz_detected_services', 'cmplz_addtoany_detected_services' );
