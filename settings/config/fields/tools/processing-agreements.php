<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_processing_agreement_fields', 100 );
function cmplz_processing_agreement_fields($fields){

	return array_merge($fields, [
		[
			'id'                      => 'create-processing-agreements',
			'menu_id'                 => 'processing-agreements',
			'group_id'                => 'create-processing-agreements',
			'type'     => 'create-processing-agreements',
			'default'  => false,
			'help'             => [
				'label' => 'default',
				'title' => __( "About Processing Agreements", 'complianz-gdpr' ),
				'text'  =>__("To learn what Processing Agreements are and what you need them for, please read the below article", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/definition/what-is-a-processing-agreement/',
			],
		],
		[
			'id'                      => 'processing_agreements',
			'menu_id'                 => 'processing-agreements',
			'group_id'                => 'processing-agreements',
			'premium'          => [
				'url'     => 'https://complianz.io/pricing/',
			],
			'type'     => 'processing-agreements',
			'default'  => false,
		],
		[
			'id'                      => 'create-data-breach-reports',
			'menu_id'                 => 'data-breach-reports',
			'group_id'                => 'create-data-breach-reports',
			'premium'          => [
				'url'     => 'https://complianz.io/pricing/',
			],
			'type'     => 'create-data-breach-reports',
			'default'  => false,
			'help'             => [
				'label' => 'default',
				'title' => __( "About Data Breach Reports", 'complianz-gdpr' ),
				'text'  =>__("To learn what Data Breach Reports are and what you need them for, please read the below article", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/definition/what-is-a-data-breach/',
			],
		],
		[
			'id'                      => 'data_breach_reports',
			'menu_id'                 => 'data-breach-reports',
			'group_id'                => 'data-breach-reports',
			'premium'          => [
				'url'     => 'https://complianz.io/pricing/',
			],
			'type'     => 'data-breach-reports',
			'default'  => false,
		],

	]);


}
