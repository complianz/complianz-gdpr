<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_facebookforwoocommerce_script' );
function cmplz_facebookforwoocommerce_script( $tags ) {
	$tags[] = array(
		'name' => 'facebook',
		'category' => 'marketing',
		'placeholder' => 'facebook',
		'urls' => array(
			'connect.facebook.net',
			'fbq',
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
function cmplz_facebookforwoocommerce_detected_social_media( $social_media ) {
	if ( ! in_array( 'facebook', $social_media ) ) {
		$social_media[] = 'facebook';
	}

	return $social_media;
}

add_filter( 'cmplz_detected_social_media',
	'cmplz_facebookforwoocommerce_detected_social_media' );

add_filter( 'cmplz_image_tags', 'cmplz_facebookforwoocommerce_imagetags' );

/**
 * Block the pixel image of the <noscript> fallback. A visitor without JavaScript cannot see the banner or give
 * consent, but the image would still be requested.
 *
 * @param array $tags Image URLs to block.
 * @return array
 */
function cmplz_facebookforwoocommerce_imagetags( $tags ) {
	$tags[] = 'facebook.com/tr?';

	return $tags;
}
