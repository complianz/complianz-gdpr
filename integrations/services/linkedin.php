<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_linkedin_script' );
function cmplz_linkedin_script( $tags ) {
	$tags[] = array(
		'name' => 'linkedin',
		'placeholder' => 'linkedin',
		'category' => 'marketing',
		'urls' => array(
			'platform.linkedin.com/in.js',
			'linkedin.com/embed/feed/update',
			'linkedin.com',
            'snap.licdn.com',
		),
		'enable_placeholder' => '1',
		'placeholder_class' => 'share-update-card',
	);
	return $tags;
}
