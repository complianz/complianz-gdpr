<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_wizard_document_fields', 100 );
function cmplz_wizard_document_fields( $fields ) {
	$fields = array_merge( $fields,
		[
			[
				'id'       => 'create-documents',
				'type'     => 'create-documents',
				'menu_id'  => 'create-documents',
				'group_id' => 'create-documents',
				'label'    => __( "Create documents", 'complianz-gdpr' ),
			],
			[
				'id'       => 'region_redirect',
				'menu_id'  => 'document-menu',
				'type'     => 'radio',
				'options'  => [
					'yes' => __("Yes, redirect based on GEO IP", 'complianz-gdpr'),
					'no' => __("No, choose a menu per document", 'complianz-gdpr'),
				],
				'premium' => [
					'url' => 'https://complianz.io/pricing/',
					'default' => 'yes',
					'disabled' => false,
				],
				'disabled' => true,
				'default' => 'no',
				'comment' =>  __("GEO IP based redirect is available in Premium", "complianz-gdpr"),
				'label'    => __("Use a region redirect on the relevant documents", 'complianz-gdpr'),
				'react_conditions' => [
					'relation' => 'OR',
					[
						'use_country' => true,
					]
				],
			],
			[
				'id'       => 'add_pages_to_menu',
				'menu_id'  => 'document-menu',
				'type' => 'documents_menu',
				'label'    => '',
				'react_conditions' => [
					'relation' => 'OR',
					[
						'region_redirect' => 'no',
					]
				],
			],
			[
				'id'       => 'add_pages_to_menu_region_redirected',
				'menu_id'  => 'document-menu',
				'type' => 'documents_menu_region_redirect',
				'label'    => '',
				'react_conditions' => [
					'relation' => 'OR',
					[
						'region_redirect' => 'yes',
					]
				],
			],

		]
	);


	return $fields;
}
