<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_jetpack_script' );
function cmplz_jetpack_script( $tags ) {
	$tags[] = array(
		'name' => 'jetpack-statistics',
		'category' => 'statistics',
		'urls' => array(
			'pixel.wp.com',
			'stats.wp.com',
		),
	);

	$tags[] = array(
		'name' => 'jetpack-twitter',
		'category' => 'marketing',
		'placeholder' => 'twitter',
		'urls' => array(
			'/twitter-timeline.min.js',
			'/twitter-timeline.js',
		),
		'enable_placeholder' => 1,
		'placeholder_class' => 'widget_twitter_timeline',
	);
	return $tags;
}

/**
 * Make sure it's set as not anonymous when tracking enabled
 * @param bool $stats_category_required
 */
function cmplz_jetpack_set_statistics_required( $stats_category_required ){
	if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'stats' ) ) {
		$stats_category_required = true;
	}
	return $stats_category_required;
}
add_filter('cmplz_cookie_warning_required_stats', 'cmplz_jetpack_set_statistics_required');


