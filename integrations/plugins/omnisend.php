<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Kept simple, no intervention with wizard to allow other analytics tooling
 */

add_filter( 'cmplz_known_script_tags', 'cmplz_omnisend_script' );
function cmplz_omnisend_script( $tags ) {
	$tags[] = array(
		'name' => 'omnisend',
		'category' => 'statistics',
		'urls' => array(
			'omnisend',
			'omnisnippet1.com'
		),
	);
	return $tags;
}
