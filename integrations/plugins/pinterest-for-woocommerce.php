<?php
/**
 * Pinterest for WooCommerce script integration for Complianz.
 *
 * This file customizes Complianz behavior for Pinterest for WooCommerce script, preventing the load of the tracking code
 * before the user consent.
 *
 * @package ComplianzIntegrations
 */

defined( 'ABSPATH' ) || die( "you do not have access to this page!" );

/**
 * Add Pinterest for WooCommerce script to known script tags.
 *
 * @param array $tags The current script tags.
 *
 * @return array
 */
add_filter( 'cmplz_known_script_tags', 'cmplz_pinterest_for_woocommerce_script' );
function cmplz_pinterest_for_woocommerce_script( $tags ) {
    $tags[] = array(
		'name'     => 'pinterest',
		'category' => 'marketing',
		'urls'     => array(
			'pinterest-for-woocommerce-save-button.min.js',
		),
	);
	return $tags;
}

/**
 * Add social media to the list of detected items, so it will get set as default, and will be added to the notice about it
 *
 * @param $social_media
 *
 * @return array
 */
function cmplz_pinterest_for_woocommerce_detected_social_media( $social_media ) {
	if ( ! in_array( 'pinterest', $social_media ) ) {
		$social_media[] = 'pinterest';
	}

	return $social_media;
}

add_filter( 'cmplz_detected_social_media', 'cmplz_pinterest_for_woocommerce_detected_social_media' );