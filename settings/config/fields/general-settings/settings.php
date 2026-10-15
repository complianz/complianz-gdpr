<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_settings_fields', 100 );
/**
 * Register general settings fields.
 *
 * @param array $fields Existing field definitions.
 *
 * @return array Field definitions with the general settings appended.
 */
function cmplz_settings_fields( $fields ) {

	$fields = array_merge(
		$fields,
		array(
			array(
				'id'                      => 'use_country',
				'menu_id'                 => 'settings-general',
				'group_id'                => 'settings-general',
				'premium'                 => array(
					'description' => __( 'You can use GEO IP to enable the warning only for countries with a cookie law, or which you target', 'complianz-gdpr' ),
				),
				'type'                    => 'checkbox',
				'label'                   => __( 'Enable GEO IP', 'complianz-gdpr' ),
				'revoke_consent_onchange' => true,
				'tooltip'                 => __( 'If enabled, the cookie warning will not show for countries without a cookie law, and will adjust consent management depending on supported privacy laws', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'use_cdb_api',
				'menu_id'  => 'settings-cd',
				'group_id' => 'settings-cd',
				'type'     => 'radio',
				'required' => false,
				'default'  => 'yes',
				'options'  => COMPLIANZ::$config->yes_no,
				'label'    => __( 'Do you consent to the use of the cookiedatabase.org API?', 'complianz-gdpr' ),
				'tooltip'  => __( 'Without the API, you will have to manually describe all found cookies, their purpose, function, service and service types. ', 'complianz-gdpr' ),
				'help'     => array(
					'label' => 'default',
					'title' => 'Cookiedatabase.org',
					'text'  => __( 'Complianz provides your Cookie Policy with comprehensive cookie descriptions, supplied by cookiedatabase.org. We connect to this open-source database using an external API, which sends the results of the cookiescan (a list of found cookies, used plugins and your domain) to cookiedatabase.org, for the sole purpose of providing you with accurate descriptions and keeping them up-to-date on a regular basis.', 'complianz-gdpr' ),

					'url'   => 'https://cookiedatabase.org/privacy-statement/',
				),
			),
			array(
				'id'               => 'use_cdb_links',
				'menu_id'          => 'settings-cd',
				'group_id'         => 'settings-cd',
				'type'             => 'radio',
				'required'         => false,
				'default'          => 'yes',
				'options'          => COMPLIANZ::$config->yes_no,
				'react_conditions' => array(
					'relation' => 'AND',
					array(
						'use_cdb_api' => 'yes',
					),
				),
				'label'            => __( 'Do you want to hyperlink cookie names so visitors can find more information on Cookiedatabase.org?', 'complianz-gdpr' ),
				'tooltip'          => __( "These links will be added with HTML attributes so it won't hurt SEO.", 'complianz-gdpr' ),
			),
			array(
				'id'       => 'send_notifications_email',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',
				'type'     => 'checkbox',
				'required' => false,
				'default'  => false,
				'options'  => COMPLIANZ::$config->yes_no,
				'label'    => __( 'Notifications by email', 'complianz-gdpr' ),
				'tooltip'  => __( 'Get notified of important updates, changes, and settings.', 'complianz-gdpr' ),
			),
			array(
				'id'               => 'notifications_email_address',
				'menu_id'          => 'settings-general',
				'group_id'         => 'settings-general',
				'type'             => 'email',
				'required'         => false,
				'default'          => get_bloginfo( 'admin_email' ),
				'label'            => __( 'Email address', 'complianz-gdpr' ),
				'react_conditions' => array(
					'relation' => 'AND',
					array(
						'send_notifications_email' => true,
					),
				),
			),
			array(
				'id'       => 'disable_notifications',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',
				'type'     => 'checkbox',
				'label'    => __( 'Disable notifications', 'complianz-gdpr' ),
				'tooltip'  => __( 'Disable all plus ones and warnings on your dashboard.', 'complianz-gdpr' ),
				'disabled' => false,
			),
			array(
				'id'          => 'restart_tour',
				'menu_id'     => 'settings-general',
				'group_id'    => 'settings-general',
				'url'         => add_query_arg(
					array(
						'page' => 'complianz',
						'tour' => 1,
					),
					admin_url( 'admin.php' )
				) . '#dashboard',
				'type'        => 'button',
				'label'       => __( 'Restart plugin tour', 'complianz-gdpr' ),
				'button_text' => __( 'Start', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'cookie_expiry',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',
				'type'     => 'number',
				'default'  => 365,
				'label'    => __( 'Consent banner expiration in days', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'disable_automatic_cookiescan',
				'menu_id'  => 'settings-cd',
				'group_id' => 'settings-cd',
				'type'     => 'checkbox',
				'default'  => false,
				'label'    => __( 'Disable the monthly site scan, subsequent sync with cookiedatabase.org and compliance reporting.', 'complianz-gdpr' ),
				'tooltip'  => __( 'Stay up-to-date with our monthly newsletter, including the onboarding of certain features and information about plugin changes.', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'enable_cookieblocker_ajax',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',
				'type'     => 'checkbox',
				'label'    => __( 'Enable cookie blocker for ajax loaded content', 'complianz-gdpr' ),
				'disabled' => false,
				'default'  => false,
				'tooltip'  => __( 'When content is loaded with ajax, for example with a load more button or lightbox, this option could help blocking the service correctly', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'enable_scan_column',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',
				'type'     => 'checkbox',
				'default'  => true,
				'label'    => __( 'Show the Website Scan column in the Posts and Pages overview', 'complianz-gdpr' ),
				'tooltip'  => __( 'When disabled, the "Complianz Website Scan" column is removed from the Posts and Pages list for all users. In Complianz Premium, this also applies to all custom post types.', 'complianz-gdpr' ),
			),
			array(
				'id'       => 'set_cookies_on_root',
				'menu_id'  => 'settings-general',
				'group_id' => 'settings-general',

				'type'     => 'checkbox',
				'default'  => false,
				'label'    => __( 'Set cookiebanner cookies on the root domain', 'complianz-gdpr' ),
				'help'     => array(
					'label' => 'default',
					'title' => __( 'Cookies on Root Domain', 'complianz-gdpr' ),
					'text'  => __( 'This is useful if you have a multisite, or several sites as subdomains on a main site', 'complianz-gdpr' ),
				),
			),
			array(
				'id'               => 'cookie_domain',
				'menu_id'          => 'settings-general',
				'group_id'         => 'settings-general',
				'type'             => 'text',
				'default'          => false,
				'label'            => __( 'Domain to set the cookies on', 'complianz-gdpr' ),
				'help'             => array(
					'label' => 'default',
					'title' => __( 'Cookie Domain', 'complianz-gdpr' ),
					'text'  => __( 'This should be your main, root domain.', 'complianz-gdpr' ),
				),
				'react_conditions' => array(
					'relation' => 'AND',
					array(
						'set_cookies_on_root' => true,
					),
				),
			),
		)
	);

	// Experimental: opt-in to WordPress 7.0+ Interactivity API router navigation for the cookie banner.
	// Pre-release suffixes (e.g. "7.0-RC1") are stripped so an RC counts as 7.0.
	$wp_version_clean = preg_replace( '/[^0-9.].*$/', '', get_bloginfo( 'version' ) );
	if ( version_compare( $wp_version_clean, '7.0', '>=' ) ) {
		$fields[] = array(
			'id'       => 'enable_iapi_navigation',
			'menu_id'  => 'settings-general',
			'group_id' => 'settings-general',
			'type'     => 'checkbox',
			'default'  => false,
			'label'    => __( 'Use Interactivity API navigation (experimental)', 'complianz-gdpr' ),
			'tooltip'  => __( 'Experimental: lets the cookie banner hook into the WordPress 7.0+ Interactivity API router for client-side navigation. When disabled, Complianz uses a fallback that works on all WordPress versions.', 'complianz-gdpr' ),
			'help'     => array(
				'label' => 'default',
				'title' => __( 'Interactivity API navigation', 'complianz-gdpr' ),
				'text'  => __( 'From WordPress 7.0, themes and plugins can use the Interactivity API for instant client-side page navigation. Enable this to let the cookie banner use the official Interactivity API router. When disabled, Complianz uses a safe compatibility fallback that works on all WordPress versions.', 'complianz-gdpr' ),
			),
		);
	}

	return $fields;
}
