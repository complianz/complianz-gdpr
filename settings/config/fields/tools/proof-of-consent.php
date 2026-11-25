<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_proof_of_consent_fields', 110 );
function cmplz_proof_of_consent_fields($fields){

	return array_merge($fields, [
		[
			'id'                      => 'create-proof_of_consent',
			'menu_id'                 => 'proof-of-consent',
			'group_id'                => 'create-proof-of-consent',
			'type'    => 'create-proof-of-consent',
			'default' => false,
//			'label'   => __( "Proof of Consent", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'!records_of_consent' => 'yes',
				]
			],
		],
		[
			'id'                      => 'proof_of_consent',
			'menu_id'                 => 'proof-of-consent',
			'group_id'                => 'proof-of-consent',
			'type'    => 'proof-of-consent',
			'default' => false,
//			'label'   => __( "Proof of Consent", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'!records_of_consent' => 'yes',
				]
			],
		],
		[
			'id'                      => 'create-records_of_consent',
			'menu_id'                 => 'records-of-consent',
			'group_id'                => 'create-records-of-consent',
			'premium' => [
				'disabled' => false,
			],
			'type'    => 'create-proof-of-consent',
			'default' => false,
//			'label'   => __( "Proof of Consent", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'records_of_consent' => 'yes',
				]
			],
		],
		[
			'id'                      => 'export-records_of_consent',
			'menu_id'                 => 'records-of-consent',
			'group_id'                => 'create-records-of-consent',
			'premium' => [
				'disabled' => false,
			],
			'type'    => 'export-records-of-consent',
			'default' => false,
			'label'   => __( "Export Records of Consent", 'complianz-gdpr' ),
			'help'             => [
				'label' => 'default',
				'title' => __( "What are records of consent?", 'complianz-gdpr' ),
				'text'  => __( 'Records of Consent are required in certain circumstances, you can read our article about dealing with records of consent and why it is needed.', 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/help/wordpress/configuration/records-of-consent/',
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'records_of_consent' => 'yes',
				]
			],
		],
		[
			'id'                      => 'records_of_consent_overview',
			'menu_id'                 => 'records-of-consent',
			'group_id'                => 'records-of-consent',
			'premium' => [
				'disabled' => false,
			],
			'type'    => 'records-of-consent',
			'default' => false,
			//'label'   => __( "Records of Consent", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'records_of_consent' => 'yes',
				]
			],
		],
	]);


}
