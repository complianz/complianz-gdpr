<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_wizard_consent_fields', 100 );
function cmplz_wizard_consent_fields( $fields ) {

	$fields = array_merge( $fields,
		[
			[
				'id'       => 'cookie_scan',
				'type'     => 'cookie_scan',
				'menu_id'  => 'cookie-scan',
				'group_id' => 'cookie-scan',
				'help'     => [
					'label' => 'default',
					'title' => __( "Website Scan", 'complianz-gdpr' ),
					'text'  => __( "If you want to clear all cookies from the plugin, you can do so here. If you want to start with a clean slate, you might need to clear your browsercache, to make sure all cookies are removed from your browser as well.",
						"complianz-gdpr" ),
					'url'   => 'https://complianz.io/cookie-scan-results/',
				],
			],
			[
				'id'                      => 'compile_statistics',
				'type'                    => 'radio',
				'menu_id'                 => 'consent-statistics',
				'group_id' => 'consent-statistics',
				'required'                => true,
				'default'                 => '',
				'revoke_consent_onchange' => true,
				'label'                   => __( "Do you compile statistics of this website?", 'complianz-gdpr' ),
				'options'                 => [
					'google-tag-manager' => __( 'Yes, and Google Tag Manager fires this script', 'complianz-gdpr' ),
					'matomo-tag-manager' => __( 'Yes, and Matomo Tag Manager fires this script', 'complianz-gdpr' ),
					'google-analytics'   => __( 'Yes, with Google Analytics', 'complianz-gdpr' ),
					'matomo'             => __( 'Yes, with Matomo', 'complianz-gdpr' ),
					'clicky'             => __( 'Yes, with Clicky', 'complianz-gdpr' ),
					'yandex'             => __( 'Yes, with Yandex', 'complianz-gdpr' ),
					'clarity'            => __( 'Yes, with Clarity', 'complianz-gdpr' ),
					'yes'                => __( 'Yes, but not with any of the above services', 'complianz-gdpr' ),
					'no'                 => __( 'No', 'complianz-gdpr' ),
				],
			],
			[
				'id'                      => 'compile_statistics_more_info',
				'menu_id'                 => 'consent-statistics',
				'group_id' => 'consent-statistics',
				'type'                    => 'multicheckbox',
				'revoke_consent_onchange' => true,
				'default'                 => '',
				'label'                   => __( "Does the following apply to your website?", 'complianz-gdpr' ),
				'tooltip'                 => __( "When checking all three checkboxes, we will set statistics to anonymous. Based on your region, statistics might be set before consent.",
					'complianz-gdpr' ),
				'comment'                 => __( "By design, IP anonymization is always enabled for GA4 properties.", 'complianz-gdpr' ),
				'options'                 => [
					'accepted'             => __( 'I have accepted the Google data processing amendment', 'complianz-gdpr' ),
					'no-sharing'           => __( 'Google is not allowed to use this data for other Google services', 'complianz-gdpr' ),
					'ip-addresses-blocked' => __( 'IP addresses are anonymized or let Complianz do this for me.', 'complianz-gdpr' ),
				],
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics' => 'google-analytics',
					]
				],
				'help'                    => [
					'label' => 'default',
					'title' => __( "Anonymized IP", 'complianz-gdpr' ),
					'text'  => __( 'If you select the option that IP addresses are anonymized, and let Complianz handle the statistics, Complianz will ensure that ip addresses are anonymized by default, unless consent is given for statistics.',
						'complianz-gdpr' ),
					'url'   => 'https://complianz.io/how-to-configure-google-analytics-for-gdpr/',
				],
			],
			[
				'id'                      => 'compile_statistics_more_info_tag_manager',
				'menu_id'                 => 'consent-statistics',
				'group_id' => 'consent-statistics',
				'type'                    => 'multicheckbox',
				'revoke_consent_onchange' => true,
				'default'                 => '',
				'label'                   => __( "Does the following apply to your website?", 'complianz-gdpr' ),
				'options'                 => array(
					'accepted'             => __( 'I have accepted the Google data processing amendment', 'complianz-gdpr' ),
					'no-sharing'           => __( 'Google is not allowed to use this data for other Google services', 'complianz-gdpr' ),
					'ip-addresses-blocked' => __( 'Acquiring IP-addresses is blocked', 'complianz-gdpr' ),
				),
				'help'                    => [
					'label' => 'default',
					'title' => "Configuring Google Tag Manager",
					'text'  => __( 'You can configure Google Tag Manager for Complianz, and, if applicable, adjust configuration for Google Analytics for GDPR and other opt-in based privacy laws.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/definitive-guide-to-tag-manager-and-complianz/',
				],
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics' => 'google-tag-manager',
					]
				],
			],
			[
				'id'                      => 'matomo_anonymized',
				'menu_id'                 => 'consent-statistics',
				'group_id' => 'consent-statistics',
				'type'                    => 'select',
				'revoke_consent_onchange' => true,
				'default'                 => '',
				'label'                   => __( "Do you want to use cookieless tracking with Matomo?", 'complianz-gdpr' ),
				'options'                 => COMPLIANZ::$config->yes_no,
				'help'                    => [
					'label' => 'default',
					'title' => "Matomo",
					'text'  => __( 'Learn more about using cookieless tracking with Matomo.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/cookieless-tracking-matomo/',
				],
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics' => 'matomo',
					]
				],
			],
			[
				'id'                => 'consent_for_anonymous_stats',
				'menu_id'           => 'statistics-configuration',
				'order'             => 10,
				'type'              => 'radio',
				'tooltip'  => __( "In some countries, like Germany, Austria, Belgium or Spain, consent is required for statistics, even if the data is anonymized.", 'complianz-gdpr' ),
				'default'           => 'yes',
				'label'             => __( "Do you want to ask consent for statistics?", 'complianz-gdpr' ),
				'options'           => COMPLIANZ::$config->yes_no,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'cmplz_statistics_privacy_friendly()' => true,
					]
				],
				'react_conditions'  => [
					'relation' => 'AND',
					[
						'!compile_statistics' => 'no',
					]
				],
			],
			[
				'id'               => 'script_center_button',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'button',
				'post_get'         => 'get',
				'url'              => '#integrations/integrations-script-center',
				'default'          => 'yes',
				'label'            => __( "Controlling your statistics script", 'complianz-gdpr' ),
				'button_text'      => __( "Script Center", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'compile_statistics' => 'yes',
					]
				],
				'help'             => [
					'label' => 'default',
					'title' => __( "Script Center", "complianz-gdpr" ),
					'text'  => __( "Below you can choose to implement your statistics script with Complianz.", 'complianz-gdpr' ) . '&nbsp;' .
					           __( "We will add the needed snippets and control consent at the same time.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/integrating-plugins/',
				],
			],
			[
				'id'               => 'configuration_by_complianz',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'yes',
				'label'            => cmplz_sprintf(__( "Do you want Complianz to add %s to your website?", 'complianz-gdpr' ), '{cmplz_dynamic_content=compile_statistics}' ),
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'compile_statistics' => [ 'google-analytics', 'matomo', 'yandex', 'clicky', 'google-tag-manager', 'matomo-tag-manager' ],
					]
				],
				'tooltip'          => __( "It's recommended to let Complianz handle the statistics script. This way, the plugin can detect if it needs to be hooked into the cookie consent code or not. But if you have set it up yourself and don't want to change this, you can choose to do so.", 'complianz-gdpr' ),
			],
			[
				'id'               => 'consent-mode',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'         => false,
				],
				'label'            => __( "Do you want to enable Google Consent Mode V2?", 'complianz-gdpr' ),
				'comment'   => cmplz_sprintf(__("Please read this %sarticle%s to make sure Consent Mode is working as expected.", "complianz-gdpr"),'<a target="_blank" href="https://complianz.io/simplified-guide-to-google-consent-mode-v2/">', '</a>'),
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'compile_statistics' => [ 'google-analytics', 'google-tag-manager' ],
						'configuration_by_complianz' => 'yes',
					]
				],
				'help'             => [
					'label' => 'default',
					'title' => "About Google Consent Mode",
					'text'  => __( 'Enabling this feature means all Google tags will be handled by Google Consent Mode. You will need to read the integration manual below, and double-check to see if it works for your setup.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/consent-mode-for-7-0/',
				],
			],
			[
				'id'               => 'gtag-basic-consent-mode',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'=> false,
				],
				'label'            => __( "Do you want to block all Google Tags before consent?", 'complianz-gdpr' ),
				'help'             => [
					'label' => 'warning',
					'title' => __( "Do you want to block all Google Tags before consent?", "complianz-gdpr" ),
					'text'  => __( 'By default Consent Mode is enabled in Advanced Mode. This means tags are loaded before consent, but depending on user preferences selects the appropriate tracking mechanisms, e.g. cookieless tracking or cookies, automatically. If you answer Yes, Complianz will only apply Consent Mode after consent.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/consent-mode-for-7-0/',
				],
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'consent-mode' => 'yes',
						'compile_statistics' => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'               => 'cmplz-gtag-urlpassthrough',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'=> false,
				],
				'label'            => __( "Do you want to set a URL passthrough parameter", 'complianz-gdpr' ),
				'tooltip'          => __( "This can improve conversion accuracy, but can contain personal data like a client ID.", 'complianz-gdpr' ),
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'consent-mode' => 'yes',
						'compile_statistics' => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'               => 'cmplz-gtag-ads_data_redaction',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'=> false,
				],
				'label'            => __( "Deny cookies when advertising is rejected?", 'complianz-gdpr' ),
				'tooltip'          => __( "When enabled, cookies will no longer be set when ad_storage is denied and identifiers in network requests will be redacted.", 'complianz-gdpr' ),
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'consent-mode' => 'yes',
						'compile_statistics' => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'               => 'cmplz-tm-template',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'=> false,
				],
				'label'            => __( "Will you be using our Tag Manager template?", 'complianz-gdpr' ),
				'options'          => array(
					'yes' => __( 'Yes', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'consent-mode' => 'yes',
						'compile_statistics' => 'google-tag-manager',
						'configuration_by_complianz' => 'yes',
					]
				],
				'help'             => [
					'label' => 'warning',
					'title' => __( "Configuring Consent Mode & Tag Manager", "complianz-gdpr" ),
					'text'  => __( 'You can choose the official Consent Mode template by Complianz from the template gallery, or use local initialization.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/consent-mode-for-7-0/',
				],
			],
			[
				'id'                      => 'ua_code',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'default'                 => '',
				'placeholder'             => 'G_TRACKING_ID',
				'required'                => false,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Google Tag - Statistics", 'complianz-gdpr' ),
				'help' => [
					'label'    => 'default',
					'title'    => __( "Tracking ID", 'complianz-gdpr' ),
					'text'     => __( 'If you add the ID for your Statistics tool here, Complianz will configure your site for statistics tracking.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/consent-mode-for-7-0/',
				],
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
				'tooltip'                 => __( "For the Google Analytics tracking ID, log in and click Admin and copy the tracking ID.", 'complianz-gdpr' ),
			],
			[
				'id'                      => 'additional_gtags_stats',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'placeholder'                => 'GT_TRACKING_ID',
				'required'                => false,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Additional Google Tags - Statistics", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'consent-mode' => 'yes',
						'compile_statistics'         => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
				'tooltip'                 => __( "You can add additional tags, comma separated.", 'complianz-gdpr' ),
			],
			[
				'id'                      => 'gtm_code',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'default'                 => '',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Please enter your GTM container ID", 'complianz-gdpr' ),
				'help' => [
					'label'    => 'default',
					'title'    => __( "Configuration by Complianz", 'complianz-gdpr' ),
					'text'     => __( 'If you add the ID for your statistics tool here, Complianz will configure your site for statistics tracking.', 'complianz-gdpr' )
				],
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'google-tag-manager',
						'configuration_by_complianz' => 'yes',
					]
				],
				'tooltip'                 => __( "For the Google Tag Manager code, log in and you will immediately see your container codes. The one next to your website name is the code you will need to fill in here, the container ID.", 'complianz-gdpr' ),
			],
			[
				'id'               => 'gtm_code_head',
				'menu_id'          => 'statistics-configuration',
				'type'             => 'radio',
				'default'          => 'no',
				'premium'          => [
					'url'     => 'https://complianz.io/pricing/',
					'disabled'=> false,
				],
				'label'            => __( "Do you want to force the script in the header?", 'complianz-gdpr' ),
				'options'          => array(
					'yes' => __( 'Yes - (Experimental)', 'complianz-gdpr' ),
					'no'  => __( 'No', 'complianz-gdpr' ),
				),
				'comment'          => __("It's possible that forcing this script in the header breaks configurations and integrations with other plugins.", "complianz-gdpr"),
				'disabled'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'compile_statistics' => [ 'google-analytics', 'google-tag-manager' ],
						'configuration_by_complianz' => 'yes'
					]
				],
			],
			[
				'id'                      => 'aw_code',
				'menu_id'                 => 'statistics-configuration',
				'source'                  => 'wizard',
				'type'                    => 'text',
				'default'                 => '',
				'placeholder'             => 'AW_CONVERSION_ID',
				'required'                => false,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Google Tag - Marketing or Advertising", 'complianz-gdpr' ),
				'tooltip'                 => __( "This will be fired on marketing consent.", 'complianz-gdpr' ),
				'Comment'                 => __( "Optional.", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'                      => 'additional_gtags_marketing',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'placeholder'                 => 'DC_FLOODLIGHT_ID',
				'required'                => false,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Additional Google Tags - Marketing or Advertising", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'google-analytics',
						'configuration_by_complianz' => 'yes',
					]
				],
				'tooltip'                 => __( "You can add additional tags, comma separated.", 'complianz-gdpr' ),
			],
			/**
			 * Matomo Classic
			 */
			[
				'id'                      => 'matomo_url',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'url',
				'placeholder'             => 'https://domain.com/stats',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Enter the URL of Matomo", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'matomo',
						'configuration_by_complianz' => 'yes',
					]
				],
				'help'                    => [
					'label' => 'default',
					'title' => __( "Configuration by Complianz", 'complianz-gdpr' ),
					'text'  => __( "The URL depends on your configuration of Matomo.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/configuring-matomo-for-wordpress-with-complianz/',
				],
			],
			[
				'id'                      => 'matomo_site_id',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'number',
				'label'                   => __( "Enter your Matomo site ID", 'complianz-gdpr' ),
				'default'                 => '',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'matomo',
						'configuration_by_complianz' => 'yes',
					]
				],
				'help' => [
					'label'    => 'default',
					'title'    => __( "Configuration by Complianz", 'complianz-gdpr' ),
					'text'     => __( 'If you add the ID for your statistics tool here, Complianz will configure your site for statistics tracking.', 'complianz-gdpr' )
				],
			],
			/**
			 * Matomo Tag Manager
			 */

			[
				'id'                      => 'matomo_tag_url',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'url',
				'placeholder'             => 'https://domain.com/stats/js',
				'required'                => true,
				'revoke_consent_on§§change' => true,
				'label'                   => __( "Enter the URL of Matomo", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'matomo-tag-manager',
						'configuration_by_complianz' => 'yes',
					]
				],
				'help'                    => [
					'label' => 'default',
					'title' => __( "Configuration", "complianz-gdpr" ),
					'text'  => __( "The URL depends on your configuration of Matomo.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/configuring-matomo-for-wordpress-with-complianz/',
				],
			],
			[
				'id'                      => 'matomo_container_id',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'default'                 => '',
				'placeholder'             => 'Aaa1bBBB',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Enter your Matomo container ID", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'matomo-tag-manager',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'                      => 'clicky_site_id',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'number',
				'default'                 => '',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Enter your Clicky site ID", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'clicky',
						'configuration_by_complianz' => 'yes',
					]
				],
				'help'                    => [
					'label' => 'default',
					'title' => __( "Configuration", "complianz-gdpr" ),
					'text'  => __( "Because Clicky always sets a so-called unique identifier cookie, consent for statistics is always required.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/configuring-clicky-for-gdpr/',
				],
			],
			[
				'id'                      => 'yandex_id',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'number',
				'default'                 => '',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Enter your Yandex ID", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'yandex',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'                      => 'clarity_consent_mode',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'radio',
				'default'                 => 'yes',
				'options'                 => COMPLIANZ::$config->yes_no,
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Do you want to use Clarity Consent Mode V2?", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'clarity',
						'consent_for_anonymous_stats' => 'yes'
					]
				],
			],
			[
				'id'                      => 'clarity_id',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'text',
				'default'                 => '',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Enter your Clarity project ID", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'clarity',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
			[
				'id'                      => 'yandex_ecommerce',
				'menu_id'                 => 'statistics-configuration',
				'type'                    => 'radio',
				'default'                 => 'no',
				'options'                 => COMPLIANZ::$config->yes_no,
				'required'                => true,
				'revoke_consent_onchange' => true,
				'label'                   => __( "Do you want to enable the Yandex ecommerce datalayer?", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'compile_statistics'         => 'yandex',
						'configuration_by_complianz' => 'yes',
					]
				],
			],
		]
	);


	return $fields;
}
