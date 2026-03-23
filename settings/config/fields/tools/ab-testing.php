<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_abtesting_fields', 100 );
function cmplz_abtesting_fields($fields){
	return array_merge($fields, [
		/*
		** a_b_testing = consent statistics
		** a_b_testing_buttons = buttons to add banners
		** easier, not prettier
		*/
		[
			'id'                      => 'a_b_testing',
			'menu_id'                 => 'ab-testing',
			'group_id'                => 'statistics-settings',
			'premium'          => [
				'url'     => 'https://complianz.io/pricing/',
			],
			'type'     => 'checkbox',
			'label'    => __( "Enable consent statistics", 'complianz-gdpr' ),
			'comment'  => __( 'If enabled, the plugin will visualize stored records of consent.', 'complianz-gdpr' ),
			'disabled' => true,
			'default'  => false,
		],
		[
			'id'                      => 'a_b_testing_buttons',
			'menu_id'                 => 'ab-testing',
			'group_id'                => 'statistics-settings',
			'source'   => 'settings',
			'type'     => 'checkbox',
			'label'    => __( "Enable A/B testing", 'complianz-gdpr' ),
			'premium'          => [
				'url'     => 'https://complianz.io/pricing/',
				'disabled' => false,
			],
			'comment'  => __( 'If enabled, the plugin will track which consent banner has the best conversion rate.', 'complianz-gdpr' ),
			'disabled' => true,
			'default'  => false,
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'a_b_testing' => true,
				]
			],
		],
		[
			'id'                 => 'a_b_testing_duration',
			'menu_id'                 => 'ab-testing',
			'group_id'                => 'statistics-settings',
			'type'      => 'number',
			'label'     => __( "Duration in days of the A/B testing period", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'a_b_testing_buttons' => true,
				]
			],
			'default'   => 30,
		],
		[
			'id'                 => 'statistics-feedback',
			'menu_id'                 => 'ab-testing',
			'group_id'                => 'statistics-settings',
			'type'      => 'statistics-feedback',
			'label'     => '',
		],
		[
			'id'                 => 'statistics_overview',
			'menu_id'                 => 'ab-testing',
			'group_id'                => 'statistics-view',
			'type'      => 'statistics',
			'label'     => '',
			'disabled'  => true,
		],


	]);


}
