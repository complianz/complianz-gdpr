<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_placeholders_fields', 100 );
function cmplz_placeholders_fields($fields){
	return array_merge($fields, [
		[
			'id'                      => 'dont_use_placeholders',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-settings',
			'type'      => 'checkbox',
			'step'      => 'cookie-blocker',
			'label'     => __( "Disable placeholder insertion", 'complianz-gdpr' ),
			'default'   => false,
			'tooltip'      => __( "If you experience styling issues with videos or iFrames you can disable the placeholder insertion, which in some themes can conflict with theme styling.", 'complianz-gdpr' ),
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
				]
			],
		],
		[
			'id'                      => 'blocked_content_text',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-settings',
			'type'         => 'text',
			'translatable' => true,
			'label'        => __( "Blocked content text", 'complianz-gdpr' ),
			'default'      => cmplz_sprintf(__( 'Click to accept %s cookies and enable this content', 'complianz-gdpr' ), '{category}'),
			'tooltip'      => __( 'The blocked content text appears when for example a YouTube video is embedded.', 'complianz-gdpr' ),
			'help'             => [
				'label' => 'default',
				'title' => __( "Blocked content text", 'complianz-gdpr' ),
				'text'  => __( 'Do not change or translate the {category} string.', 'complianz-gdpr' ).'&nbsp;'.__( 'You may remove it if you want.', 'complianz-gdpr' ).'&nbsp;'.__( 'It will be replaced with the name of the category that is blocked.', 'complianz-gdpr' ),
			],
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
					'consent_per_service' => 'no',
					'dont_use_placeholders' => false,
				]
			],
		],
		[
			'id'                      => 'blocked_content_text_per_service',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-settings',
			'type'         => 'text',
			'translatable' => true,
			'label'        => __( "Blocked content text", 'complianz-gdpr' ),
			'default'      => cmplz_sprintf(__( "Click 'I agree' to enable %s", 'complianz-gdpr' ), '{service}'),
			'tooltip'      => __( 'The blocked content text appears when for example a YouTube video is embedded.', 'complianz-gdpr' ),
			'help'             => [
				'label' => 'default',
				'title' => __( "Blocked content text", 'complianz-gdpr' ),
				'text'      => __( 'Do not change or translate the {service} string.', 'complianz-gdpr' ).'&nbsp;'.__( 'You may remove it if you want.', 'complianz-gdpr' ).'&nbsp;'.__( 'It will be replaced with the name of the service that is blocked.', 'complianz-gdpr' ),
			],
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
					'consent_per_service' => 'yes',
					'dont_use_placeholders' => false,

				]
			],
		],
		[
			'id'                      => 'agree_text_per_service',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-settings',
			'type'         => 'text',
			'translatable' => true,
			'label'        => __( "Text on 'I agree' button", 'complianz-gdpr' ),
			'default'      => __( "I agree", 'complianz-gdpr' ),
			'tooltip'      => __( 'The blocked content text appears when for example a YouTube video is embedded.', 'complianz-gdpr' ),
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
					'consent_per_service' => 'yes',
					'dont_use_placeholders' => false,
				]
			],
		],
		[
			'id'                      => 'placeholder_style',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-appearance',
			'type'      => 'select',
			'premium' => [
				'comment' =>'',
				'disabled' => false,
				'help' => [
					'label' => 'default',
					'title' => __( "Custom placeholders", 'complianz-gdpr' ),
					'text'  => __( "Choose the style that best complements your website's design.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/help/wordpress/customization/customize-service-placeholders/',
				]
			],
			'options'   => array(
				'minimal' => __("Default",'complianz-gdpr'),
				'light' => __("Light",'complianz-gdpr'),
				'color' => __("Full Color",'complianz-gdpr'),
				'dark' => __("Dark Mode",'complianz-gdpr'),
				'custom' => __("Custom",'complianz-gdpr'),
			),
			'condition_action' => 'disable',
			'disabled' => array(
				'light',
				'color',
				'dark',
				'custom',
			),
			'label'     => __( "Placeholder style", 'complianz-gdpr' ),
			'default'   => 'minimal',
			'tooltip'      => __( "You can choose your favorite placeholder style here.", 'complianz-gdpr' ),
			'help' => [
				'label' => 'default',
				'title' => __( "Custom placeholders", 'complianz-gdpr' ),
				'text'  => __( "You can change your placeholders manually or use Premium to do it for you.", 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/help/wordpress/customization/customize-service-placeholders/',
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
					'dont_use_placeholders' => false,
				]
			],
		],
		[
			'id'                      => 'placeholder_preview',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-appearance',
			'type'      => 'placeholder_preview',
			'condition_action' => 'disable',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
				]
			],
		],
		[
			'id'                      => 'google-maps-format',
			'menu_id'                 => 'placeholders',
			'group_id'                => 'placeholders-appearance',
			'type'      => 'select',
			'options'   => array(
				'1280x920' => "1280 x 920",
				'1280x500' => "1280 x 500",
				'500x500' => "500 x 500",
				'custom' => __("Custom",'complianz-gdpr'),
			),
			'label'     => __( "Google Maps placeholder ratio", 'complianz-gdpr' ),
			'default'   => '1280x920',
			'tooltip'      => __( "Select the optimal placeholder ratio for your site.", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'safe_mode' => false,
					'thirdparty_services_on_site' => ['google-maps','openstreetmaps'],
				]
			],
			'help' => [
				'label' => 'default',
				'title' => __( "Custom ratio for Google Maps", 'complianz-gdpr' ),
				'text'  => __( "If you select custom, you need to add your custom image to your site.", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/help/wordpress/customization/customize-service-placeholders/',
			],
		],

	]
);
}
