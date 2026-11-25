<?php

add_filter( 'cmplz_fields', 'cmplz_integrations_services_fields', 100 );
function cmplz_integrations_services_fields($fields){

	return array_merge($fields, [
		[
			'id'                      => 'integrations-services',
			'menu_id'                 => 'integrations-services',
			'type'                    => 'integrations-services',
			'help'             => [
				'label' => 'default',
				'title' => __( "Developers and custom implementations", 'complianz-gdpr' ),
				'text'  => __( "Complianz is built to be easily configured, with automatic background processes, integrations and a wizard for specific questions. But this is WordPress, a million different configurations sometimes ask for custom implementations. We have collected many custom implementations from our contributors, and have written an article for anyone who wants to make their own integration.", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/help/wordpress/configuration/developers-guide-for-third-party-integrations/',
			],
		],
		[
			'id'                      => 'integrations-plugins',
			'menu_id'                 => 'integrations-plugins',
			'type'                    => 'integrations-plugins',
		],
		[
			'id'                      => 'integrations-script-center',
			'menu_id'                 => 'integrations-script-center',
			'type'                    => 'integrations-script-center',
		],

	]);


}
