<?php defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Whitelisting podcast player inline script.
 * Compatibility fix for Complianz GDPR/CCPA
 *
 * https://wordpress.org/support/plugin/podcast-player/
 * author: @vedathemes
 */

add_filter ( 'cmplz_service_category',
	function( $category, $total_match, $found ) {
		if ( $found && false !== strpos( $total_match, 'pppublic-js-extra' ) ) {
			$category = 'functional';
		}
		return $category;
	}, 10 , 3
);
