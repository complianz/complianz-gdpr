<?php
/**
 * Updated integration with the FlipperCode WP Google Maps Plugin
 */
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );
if ( ! defined( 'CMPLZ_GOOGLE_MAPS_INTEGRATION_ACTIVE' ) ) {
	define( 'CMPLZ_GOOGLE_MAPS_INTEGRATION_ACTIVE', true );
}

add_filter( 'cmplz_known_script_tags', 'cmplz_wp_google_map_plugin_script' );
function cmplz_wp_google_map_plugin_script( $tags ) {
	$tags[] = array(
		'name'               => 'google-maps',
		'category'           => 'marketing',
		'placeholder'        => 'google-maps',
		'urls'               => array(
			'maps.js',
			'maps.min.js',
			'maps.googleapis.com',
			'maps.google.com',
			'wpgmp_frontend.min.js',
		),
		'enable_placeholder' => 1,
		'placeholder_class'  => 'wpgmp_map_parent',
		'enable_dependency'  => '1',
		'dependency'         => array(
			// 'wait-for-this-script' => 'script-that-should-wait'.
			'maps.js' => '/maps/api/js',
		),
	);

	return $tags;
}

/**
 * Add services to the list of detected items, so it will get set as default, and will be added to the notice about it
 *
 * @param array $services
 *
 * @return array
 */
function cmplz_wp_google_map_plugin_detected_services( $services ) {
	if ( ! in_array( 'google-maps', $services, true ) ) {
		$services[] = 'google-maps';
	}

	return $services;
}
add_filter( 'cmplz_detected_services', 'cmplz_wp_google_map_plugin_detected_services' );

function cmplz_wp_google_map_plugin_placeholder() {
}

/**
 * Add a warning that integrations changed.
 *
 * @param array $warnings
 *
 * @return array
 */
function cmplz_wp_google_map_warnings_types( $warnings ) {
	$warnings['wp_google_map-plugin'] = array(
		'plus_one'          => true,
		'dismissable'       => true,
		'warning_condition' => '_true_',
		'open'              => __( 'The WP Maps Pro integration has changed with the help of the authors of WP Maps, please check your implementation.', 'complianz-gdpr' ),
		'url'               => 'https://complianz.io/wp-maps-pro-google-is-not-defined/',
	);

	return $warnings;
}
add_filter( 'cmplz_warning_types', 'cmplz_wp_google_map_warnings_types' );
