<?php
defined('ABSPATH') or die("you do not have access to this page!");

add_filter( 'cmplz_known_script_tags', 'cmplz_matomo_script' );
function cmplz_matomo_script( $tags ) {
	$tags[] = array(
		'name' => 'matomo',
		'category' => 'statistics',
		'urls' => array(
			'matomo.js',
			'piwik.js',
		),
	);

	return $tags;
}
