<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_tools_documents_fields', 100 );
function cmplz_tools_documents_fields( $fields ) {
	return array_merge( $fields, [
		[
			'id'                 => 'use_document_css',
			'menu_id'                 => 'tools-documents',
			'group_id'                => 'tools-documents-general',
			'type'    => 'checkbox',
			'label'   => __( "Use document CSS by Complianz", 'complianz-gdpr' ),
			'default' => true,
			'tooltip'    => __( "Disable to let your theme take over.", 'complianz-gdpr' ),
		],
		[
			'id'                 => 'use_custom_document_css',
			'menu_id'                 => 'tools-documents',
			'group_id'                => 'tools-documents-css',
			'type'    => 'checkbox',
			'label'   => __( "Enable custom document CSS", 'complianz-gdpr' ),
			'default' => false,
			'tooltip' => __( "Enable if you want to add custom CSS for the documents", 'complianz-gdpr' ),
		],
		[
			'id'       => 'custom_document_css',
			'menu_id'                 => 'tools-documents',
			'group_id'                => 'tools-documents-css',
			'type'      => 'css',
			'label'     => __( "Custom document CSS", 'complianz-gdpr' ),
			'default'   => '#cmplz-document h2 {} /* titles in complianz documents */'
			               . "\n" . '#cmplz-document h2.annex{} /* titles in annexes */'
			               . "\n" . '#cmplz-document .subtitle.annex{} /* subtitles in annexes */'
			               . "\n" . '#cmplz-document, #cmplz-document p, #cmplz-document span, #cmplz-document li {} /* text */'
			               . "\n" . '#cmplz-cookies-overview .cmplz-service-header {} /* service header in cookie policy */'
			               . "\n" . '#cmplz-cookies-overview .cmplz-service-desc {} /* service description */'
			               . "\n" . '#cmplz-document.impressum, #cmplz-document.cookie-statement'
										 . "\n" . '#cmplz-document.privacy-statement {} /* styles for impressum */',
			'help' => [
				'label' => 'default',
				'title' => __( "Custom CSS", 'complianz-gdpr' ),
				'text'  => __( "You can add additional custom CSS here. For tips and CSS lessons, check out our documentation.", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/search/css',
			],
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'use_custom_document_css' => true,
				]
			],
		],
	] );

}
