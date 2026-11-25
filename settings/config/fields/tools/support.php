<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_support_fields', 100 );
function cmplz_support_fields($fields){
	return array_merge($fields, [
		[
			'id'                      => 'safe_mode',
			'menu_id'                 => 'support',
			'group_id'                => 'debugging',
			'source_id' => 'enable_cookie_blocker',
			'source_mapping' => [
				//source value => target value
				'no' => 1,
				'yes' => 0,
			],
			'type'    => 'checkbox',
			'label'   => __( "Enable safe mode", 'complianz-gdpr' ),
			'help' => [
				'label' => 'default',
				'title' => __( "Safe Mode", 'complianz-gdpr' ),
				'text'  => __( "When safe mode is enabled, all integrations will be disabled temporarily, please read the instructions to debug the issue or ask support if needed.", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/help/wordpress/configuration/debugging-issues/',
			],
		],
		[
			'id'                      => 'debug_data',
			'menu_id'                 => 'support',
			'group_id'                => 'debugging',
			'type'    => 'debug-data',
			'label'   => __( "Possible relevant errors", 'complianz-gdpr' ),
			'default' => false,
		],
		[
			'id'                      => 'system_status',
			'menu_id'                 => 'support',
			'group_id'                => 'debugging',
			'type'    => 'button',
			'label'   => __( "System Status", 'complianz-gdpr' ),
			'button_text'    => __( "Download", 'complianz-gdpr' ),
			'default' => false,
			'url' => trailingslashit( CMPLZ_URL ) . 'system-status.php'

		],
		[
			'id'                      => 'support_form',
			'menu_id'                 => 'support',
			'group_id'                => 'premiumsupport',
			'type'    => 'support',
			'label'   => __( "Support form", 'complianz-gdpr' ),
			'default' => false,
		],
	]);


}
