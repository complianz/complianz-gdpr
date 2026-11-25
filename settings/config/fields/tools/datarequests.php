<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_tools_fields', 110 );
function cmplz_tools_fields( $fields ) {

	return array_merge( $fields, [
		[
			'id'       => 'datarequest-entries',
			'menu_id'  => 'data-requests',
			'group_id' => 'datarequest-entries',
			'type'     => 'datarequests',
		],
		[
			'id'          => 'export-personal-data',
			'menu_id'     => 'data-requests',
			'group_id'    => 'settings',
			'type'        => 'button',
			'url'         => admin_url( 'export-personal-data.php' ),
			'button_text' => __( "View export options", 'complianz-gdpr' ),
			'label'       => __( "Export personal data", 'complianz-gdpr' ),
		],
		[
			'id'          => 'erase-personal-data',
			'menu_id'     => 'data-requests',
			'group_id'    => 'settings',
			'type'        => 'button',
			'url'         => admin_url( 'erase-personal-data.php' ),
			'button_text' => __( "View erase options", 'complianz-gdpr' ),
			'label'       => __( "Erase personal data", 'complianz-gdpr' ),
		],
		[
			'id'       => 'export-datarequests',
			'menu_id'  => 'data-requests',
			'group_id' => 'settings',
			'type'     => 'export-datarequests',
			'label'    => __( "Export Data Requests", 'complianz-gdpr' ),
		],
		[
			'id'                 => 'notification_from_email',
			'menu_id'                 => 'data-requests',
			'group_id'                => 'settings',
			'type'               => 'email',
			'label'              => __( "Notification sender email address", 'complianz-gdpr' ),
			'default'            => get_option('admin_email'),
			'tooltip'               => __( "When emails are sent, you can choose the sender email address here. Please note that it should have this website's domain as sender domain, otherwise the server might block the email from being sent.", 'complianz-gdpr' ),
			'help'             => [
				'label' => 'default',
				'title' => __( "Responding to data requests", 'complianz-gdpr' ),
				'text'  => __( 'You have an open data requests ready for response? Get started here.', 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/help/wordpress/faq/responding-to-a-data-request/',
			],
		],
		[
			'id'                => 'notification_email_subject',
			'menu_id'           => 'data-requests',
			'group_id'          => 'settings',
			'type'              => 'text',
			'label'             => __( "Notification email subject", 'complianz-gdpr' ),
			'default'           => __( 'We have received your request', 'complianz-gdpr' ),
			'tooltip'           => __( "Subject used for Data Request email notifications.", 'complianz-gdpr' ),
			'server_conditions' => [
				'relation' => 'AND',
				[
					'cmplz_datarequests_or_dnsmpi_active()' => true,
				]
			],
		],
		[
			'id'                => 'notification_email_content',
			'menu_id'           => 'data-requests',
			'group_id'          => 'settings',
			'type'              => 'editor',
			'label'             => __( "Notification email content", 'complianz-gdpr' ),
			'default'           => __( 'Hi {name}', 'complianz-gdpr' ) . '<br><br>'
			                       . __( 'We have received your request on {blogname}. Depending on the specific request and legal obligations we might follow-up.', 'complianz-gdpr' )
			                       . '<br />' . '<p>' . _x( 'Kind regards,', 'email signature', 'complianz-gdpr' ) . '</p>'
			                       . '<br />' . '<p>' . '{blogname} ' . '</p>',
			'tooltip'           => __( "Email content used for Data Request email notifications.", 'complianz-gdpr' ),
			'server_conditions' => [
				'relation' => 'AND',
				[
					'cmplz_datarequests_or_dnsmpi_active()' => true,
				]
			],
		],
	] );


}
