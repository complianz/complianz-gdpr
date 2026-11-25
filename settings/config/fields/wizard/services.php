<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_services_fields', 100 );
function cmplz_services_fields( $fields ) {

	$fields = array_merge(
		$fields,
		array(
			array(
				'id'      => 'consent_per_service',
				'menu_id' => 'services',
				'type'    => 'radio',
				'options' => COMPLIANZ::$config->yes_no,
				'default' => 'no',
				'label'   => __( "Do you want to use 'Consent per Service'?", 'complianz-gdpr' ),
				'tooltip' => __( "The default configuration is 'Consent per Category'. This is currently compliant with your selected regions.", 'complianz-gdpr' ),
				'comment' => __( "For a granular approach you can enable 'consent per service', a unique way to control cookies real-time.", 'complianz-gdpr' ),
				'help'    => array(
					'label' => 'default',
					'title' => __( 'Cookie Shredder', 'complianz-gdpr' ),
					'text'  => __( 'This feature includes real-time cookie removal with the CookieShredder.', 'complianz-gdpr' ) . ' ' . __(
						'This could break website functionality.',
						'complianz-gdpr'
					),
					'url'   => 'https://complianz.io/help/wordpress/configuration/introducing-the-cookieshredder/',
				),
			),
			array(
				'id'                      => 'uses_thirdparty_services',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'label'                   => __( 'Does your website use third-party services?', 'complianz-gdpr' ),
				'tooltip'                 => __( 'e.g. services like Google Fonts, Maps or reCAPTCHA usually place cookies.', 'complianz-gdpr' ),
			),
			array(
				'id'                      => 'thirdparty_services_on_site',
				'menu_id'                 => 'services',
				'type'                    => 'multicheckbox',
				'options'                 => COMPLIANZ::$config->thirdparty_services,
				'default'                 => '',
				'revoke_consent_onchange' => true,
				'label'                   => __( 'Select the types of third-party services you use on your site.', 'complianz-gdpr' ),
				'tooltip'                 => __(
					'Checking services here will add the associated cookies to your Cookie Policy, and block the service until consent is given (opt-in), or after consent is revoked (opt-out).',
					'complianz-gdpr'
				),
				'help'                    => array(
					'label' => 'default',
					'title' => __( 'Placeholders', 'complianz-gdpr' ),
					'text'  => __(
						'When possible a placeholder is activated. You can also disable or configure the placeholder to your liking. You can disable services and placeholders under Integrations.',
						'complianz-gdpr'
					),
					'url'   => 'https://complianz.io/help/wordpress/configuration/integrating-plugins/',
				),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_thirdparty_services' => 'yes',
					),
				),

			),
			array(
				'id'               => 'block_recaptcha_service',
				'menu_id'          => 'services',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'default'          => 'no',
				'react_conditions' => array(
					'relation' => 'AND',
					array(
						'thirdparty_services_on_site' => 'google-recaptcha',
					),
				),
				'label'            => __( 'Do you want to block reCAPTCHA before consent, and when consent is revoked?', 'complianz-gdpr' ),
				'help'             => array(
					'label' => 'warning',
					'title' => __( 'Blocking reCaptcha', 'complianz-gdpr' ),
					'text'  => __( 'If you choose to block reCAPTCHA, please make sure you add a placeholder to your forms.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/help/wordpress/configuration/blocking-recaptcha-manually/',
				),
			),
			array(
				'id'                => 'self_host_google_fonts',
				'menu_id'           => 'services',
				'type'              => 'radio',
				'default'           => 'no',
				'options'           => array(
					'self-host' => __( 'Yes (recommended)', 'complianz-gdpr' ),
					'block'     => __( 'No', 'complianz-gdpr' ),
				),
				'help'              => array(
					'label' => 'warning',
					'title' => __( 'Self-hosting Google Fonts', 'complianz-gdpr' ),
					'text'  => __( 'Your site uses Google Fonts. For best privacy compliance, we recommend to self host Google Fonts. To self host, follow the instructions in the below link.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/help/wordpress/configuration/self-hosting-google-fonts-for-wordpress/',
				),
				'comment'           => __( "If you choose 'No', Complianz will block all known Google Fonts sources.", 'complianz-gdpr' ) . ' ' . cmplz_sprintf( __( 'Please read this %1$sarticle%2$s why self-hosting Google Fonts is recommended.', 'complianz-gdpr' ), '<a target="_blank" href="https://complianz.io/help/wordpress/configuration/self-hosting-google-fonts-for-wordpress/">', '</a>' ),
				'react_conditions'  => array(
					'relation' => 'AND',
					array(
						'thirdparty_services_on_site' => 'google-fonts',
					),
				),
				'label'             => __( 'Will you self-host Google Fonts?', 'complianz-gdpr' ),
				'comment_status'    => 'warning',
				'server_conditions' => array(
					'relation' => 'AND',
					array(
						'cmplz_uses_optin()' => true,
					),
				),
			),
			array(
				'id'               => 'block_hubspot_service',
				'menu_id'          => 'services',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'default'          => 'no',
				'react_conditions' => array(
					'relation' => 'AND',
					array(
						'thirdparty_services_on_site' => 'hubspot',
					),
				),
				'label'            => __( 'Did you enable the consent module in your HubSpot account?', 'complianz-gdpr' ),
				'help'             => array(
					'label' => 'warning',
					'title' => __( 'Integrating Hubspot', 'complianz-gdpr' ),
					'text'  => __( 'Did you enable the consent module in your HubSpot account?', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/help/wordpress/integrations/hubspot-integration/',
				),

			),
			array(
				'id'                      => 'hotjar_privacyfriendly',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => '',
				'label'                   => __( 'Is Hotjar configured in a privacy-friendly way?', 'complianz-gdpr' ),
				'help'                    => array(
					'label' => 'warning',
					'title' => __( 'Integrating Hotjar', 'complianz-gdpr' ),
					'text'  => __( 'You can configure Hotjar privacy-friendly, if you do this, no consent is required for Hotjar.', 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/help/wordpress/configuration/configuring-hotjar-for-gdpr/',
				),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'thirdparty_services_on_site'  => 'hotjar',
						'!consent_for_anonymous_stats' => 'yes',
					),
				),
			),
			array(
				'id'                      => 'uses_social_media',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'label'                   => __( 'Does your website contain embedded social media content, like buttons, timelines, videos or pixels?', 'complianz-gdpr' ),
				'tooltip'                 => __( 'Content from social media is mostly embedded through iFrames. These often place third party (tracking) cookies, so must be blocked based on visitor consent. If your website only contains buttons or links to a social media profile on an external page you can answer No.', 'complianz-gdpr' ),
			),
			array(
				'revoke_consent_onchange' => true,
				'id'                      => 'socialmedia_on_site',
				'menu_id'                 => 'services',
				'type'                    => 'multicheckbox',
				'options'                 => COMPLIANZ::$config->thirdparty_socialmedia,
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_social_media' => 'yes',
					),
				),
				'default'                 => '',
				'label'                   => __( 'Select which social media are used on the website.', 'complianz-gdpr' ),
				'tooltip'                 => __( 'Checking services here will add the associated cookies to your Cookie Policy, and block the service until consent is given (opt-in), or after consent is revoked (opt-out)', 'complianz-gdpr' ),
			),
			array(
				'id'                      => 'uses_firstparty_marketing_cookies',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'label'                   => __( "You have stated that you don't use third-party services. Do you use plugins that might set marketing cookies?", 'complianz-gdpr' ),
				'tooltip'                 => __( "Complianz cannot automatically block first-party marketing cookies unless these plugins conform to the WP Consent API. Look for any possible integrations on our website if you're not sure. When you answer 'No' to this question, the marketing category will be removed.", 'complianz-gdpr' ),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_thirdparty_services' => 'no',
						'uses_social_media'        => 'no',

					),
				),
			),
			array(
				'id'                      => 'uses_ad_cookies',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'tooltip'                 => __( 'If you show advertising on your website, or place scripts for advertising purposes e.g. Google Shopping or remarketing, please answer with Yes.', 'complianz-gdpr' ),
				'label'                   => __( 'Does your website contain scripts for advertising purposes?', 'complianz-gdpr' ),
			),
			array(
				'id'                      => 'uses_ad_cookies_personalized',
				'menu_id'                 => 'services',
				'premium'                 => array(
					'disabled' => false,
					'url'      => 'https://complianz.io/help/wordpress/configuration/guide-to-implementing-the-tcf-framework-in-wordpress-with-complianz/',
				),
				'type'                    => 'radio',
				'required'                => true,
				'tooltip'                 => __( "If you're using AdSense, AdManager or AdMob, please choose Google CMP Certified Consent Management, for other advertising products that don't use Google you can only use TCF.", 'complianz-gdpr' ),
				'revoke_consent_onchange' => true,
				'options'                 => array(
					'no'  => __( "Don't use an additional framework.", 'complianz-gdpr' ),
					'yes' => __( 'Enable TCF, without support for Google Advertising Products.', 'complianz-gdpr' ),
					'tcf' => __( 'Enable TCF & Google CMP Certified Consent Management with support for Google Advertising Products', 'complianz-gdpr' ),
				),
				'default'                 => 'no',
				'label'                   => __( 'Choose the appropriate frameworks needed for your configuration.', 'complianz-gdpr' ),
				'comment'                 => __( "Google Advertising Products requires Google CMP Certified Consent Management. If you don't show ads, but use Google Advertising Products with Google Consent Mode, an additional framework is not required. Please be aware that this consent banner has additional guidelines and restricts customization.", 'complianz-gdpr' ),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_ad_cookies' => 'yes',
					),
				),

				'disabled'                => array(
					'tcf',
					'yes',
				),
			),
			array(
				'id'                      => 'enable_amazon_consent_signal',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'label'                   => __( 'Do you want to enable Amazon Consent Signal (ACS)?', 'complianz-gdpr' ),
				'tooltip'                 => __( 'Required if you use Amazon Advertising products (e.g. Amazon DSP) and are not using TCF. ACS sends consent status to Amazon for visitors in the UK and EEA.', 'complianz-gdpr' ),
				'help'                    => array(
					'label' => 'default',
					'title' => __( 'Amazon Consent Signal', 'complianz-gdpr' ),
					'text'  => __( "Amazon Consent Signal communicates user consent for ad storage and personal data processing to Amazon's advertising infrastructure.", 'complianz-gdpr' ),
					'url'   => 'https://advertising.amazon.com/help/GKJQ7E8SE9BRG73Q',
				),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_ad_cookies'              => 'yes',
						'uses_ad_cookies_personalized' => 'no',
					),
				),
			),

			// consent mode v2
			/*
			[
				'id'                      => 'uses_ad_cookies_consent_mode',
				'tooltip'                 => __( "If you're using AdSense, AdManager or AdMob, please choose Google CMP Certified Consent Mode, for other advertising products that don't use Google you can only use TCF.", 'complianz-gdpr' ),
				'menu_id'                 => 'services',
				'premium' => [
					'disabled' => false,
					'url'      => "https://complianz.io/consent-mode-v2-ready-for-2024/",
				],
				'type'                    => 'radio',
				'required'                => false,
				'tooltip'                 => __( "", 'complianz-gdpr' ),
				'revoke_consent_onchange' => false,
				'options'                 => [
					'yes' =>    __("Yes, I will designate all Google core plaform services to receive data. (Default)", "complianz-gdpr"),
					'manual' =>     __("Yes, but I only allow a subset of Google core platform services to receive data.", "complianz-gdpr"),
					'no' =>         __("No, I don't share any data with Google core platform services.", "complianz-gdpr"),

					],
				'default'                 => 'yes',
				'help'                    => [
					'label' => 'warning',
					'title' => __( "The Digital Markets Act", 'complianz-gdpr' ),
					'text'  => __( "The largest online platforms act as so-called gatekeepers in digital markets. The Digital Markets Act (DMA) aims to ensure that these platforms behave in a fair way online.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/consent-mode-v2-ready-for-2024/',
				],
				'label'                   => __( "Do you allow sharing data across Google core plaform services?", 'complianz-gdpr' ),
				'tooltip'                 => __( "When using Consent Mode and Advertising products by Google, you have the option to minimize sharing data between Google products as required by The Digital Markets Act. This should be designated in the UI of the Google product(s) you're using. Answer below what reflects your choice.", 'complianz-gdpr' ),
				'comment'                 => __( "Only change from the default answer if you actively configured you Google Advertising Products to reflect this choice, otherwise the default is recommended.", 'complianz-gdpr' ),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'uses_ad_cookies_personalized'  => 'no',
						'consent-mode'  => 'yes',
					]
				],

				'disabled' => [
					'tcf'
				],
			],

			// gekoppelde accounts, Google Ads?

			[
				'id'       => 'domain_cps',
				'menu_id'  => 'services',
				'type'     => 'text',
				'default'  => '',
				'comment'  => __( "Comma separate core platform services.", 'complianz-gdpr' ),
				'placeholder'  => __( "Link to the privacy policies for each Google product e.g. Google Shopping, YouTube etc", 'complianz-gdpr' ),
				'tooltip'  => __( "", 'complianz-gdpr' ),
				'label'    => __( "List the subset of core platform services.", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'uses_ad_cookies_consent_mode' => 'manual',
						'uses_ad_cookies_personalized' => 'no',
						'consent-mode'  => 'yes',
					]
				],
				'required' => false,
			],
			*/
			// consent mode v2 end

			array(
				'id'                      => 'uses_wordpress_comments',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'no',
				'label'                   => __( 'Does your website use WordPress comments?', 'complianz-gdpr' ),
				'server_conditions'       => array(
					'relation' => 'AND',
					array(
						'cmplz_uses_optin()'         => true,
						'cmplz_consent_api_active()' => false,
					),
				),
			),
			array(
				'id'                      => 'block_wordpress_comment_cookies',
				'menu_id'                 => 'services',
				'type'                    => 'radio',
				'required'                => true,
				'revoke_consent_onchange' => true,
				'options'                 => COMPLIANZ::$config->yes_no,
				'default'                 => 'yes',
				'label'                   => __( 'Do you want to disable the storage of personal data by the WP comments function and the checkbox?', 'complianz-gdpr' ),
				'help'                    => array(
					'label' => 'default',
					'title' => __( 'WordPress comments', 'complianz-gdpr' ),
					'text'  => __( "If you enable this, WordPress will not store personal data with comments and you won't need a consent checkbox for the comment form. The consent box will not be displayed.", 'complianz-gdpr' ),
				),
				'condition'               => array(
					'uses_wordpress_comments' => 'yes',
				),
				'react_conditions'        => array(
					'relation' => 'AND',
					array(
						'uses_wordpress_comments' => 'yes',
					),
				),
				'server_conditions'       => array(
					'relation' => 'AND',
					array(
						'cmplz_uses_optin()'         => true,
						'cmplz_consent_api_active()' => false,
					),
				),
			),

		)
	);

	return $fields;
}
