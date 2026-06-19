<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_wizard_fields', 10 );

function cmplz_wizard_fields($fields){

	$fields += [
		[
			'id'               => 'regions',
			'premium' => [
				'label' => __( "Which region(s) do you target with your website?","complianz-gdpr"),
				'url' => 'https://complianz.io/pricing/',
				 'comment'  => ' ',//an empty string doesn't override the premium string
			],
			'menu_id'          => 'visitors',
			'type'             => 'radio',
			'label'            => __( "Which privacy law or guideline do you want to use as the default for your worldwide visitors?", 'complianz-gdpr' ),
			'options'           => COMPLIANZ::$config->supported_regions,
			'help'             => [
				'label' => 'default',
				'title' => __( "Which region(s) do I target?", 'complianz-gdpr' ),
				'text'  => __( "You don’t need to configure your website for ‘accidental’ visitors. Only choose the regions your website is intended for.", 'complianz-gdpr'),
				'url'   => 'https://complianz.io/what-regions-do-i-target/',
			],
			'default'          => false,
			'revoke_consent_onchange' => true,
			'required' => true,
			'comment' => __("If you want to dynamically apply privacy laws based on the visitor's location, consider upgrading to the premium version, which allows you to apply a privacy law specific for that region.", 'complianz-gdpr' ),
		],
		[
			'id'               => 'other_region_behaviour',
			'menu_id'          => 'visitors',
			'type'             => 'select',
			'default'  => 'none',
			'label'    => __( "Which banner do you want to display in other regions?", 'complianz-gdpr' ),
			'options'  => ['none'=> __("None", 'complianz-gdpr')] + COMPLIANZ::$config->supported_regions,
			'revoke_consent_onchange' => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'use_country' => true,
				]
			]
		],
		[
			'id'               => 'eu_consent_regions',
			'menu_id'          => 'visitors',
			'type'             => 'radio',
			'default'  => 'no',
			'label'     => __( "Do you target visitors from Germany, Austria, Belgium and/or Spain?", 'complianz-gdpr' ),
			'options'   => COMPLIANZ::$config->yes_no,
			'revoke_consent_onchange' => true,
			'required'  => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'regions' => ['eu'],
				]
			]
		],
		[
			'id'               => 'uk_consent_regions',
			'menu_id'          => 'visitors',
			'type'             => 'radio',
			'default'  => 'no',
			'label'     => __( "Do you target visitors from Jersey or Guernsey?", 'complianz-gdpr' ),
			'options'   => COMPLIANZ::$config->yes_no,
			'revoke_consent_onchange' => true,
			'required'  => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'regions' => ['uk'],
				]
			]
		],
		[
			'id'               => 'ca_targets_quebec',
			'menu_id'          => 'visitors',
			'type'             => 'radio',
			'default'  => '',
			'label'     => __( "Do you target visitors from Quebec?", 'complianz-gdpr' ),
			'options'   => COMPLIANZ::$config->yes_no,
			'revoke_consent_onchange' => true,
			'tooltip'          => __( "This will apply an opt-in mechanism for all visitors from Canada, as required by Quebec bill 64.", 'complianz-gdpr' ),
			'required'  => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'regions' => ['ca'],
				]
			]
		],
		[
			'id'               => 'us_states',
			'menu_id'          => 'visitors',
			'type'             => 'multicheckbox',
			'options'          => COMPLIANZ::$config->supported_states,
			// 'help'             => [
			// 	'label' => 'default',
			// 	'title' => __( "Do Not Sell My Personal Information", 'complianz-gdpr' ),
			// 	'text'  => __( "If you choose California you will be able to generate a DNSMPI page.", 'complianz-gdpr' ),
			// 	'url'   => 'https://complianz.io/privacy-in-the-united-states/',
			// ],
			'label'            => __( "Do you specifically target visitors from these states?", 'complianz-gdpr' ),
			'tooltip'          => __( "There are some laws that only apply to one or more states and are described separately if needed.", 'complianz-gdpr' ),
			'required'         => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'regions' => ['us'],
				]
			],
		],
		[
			'id'               => 'wp_admin_access_users',
			'menu_id'          => 'visitors',
			'type'     => 'radio',
			'default'  => 'no',
			'label'    => __( "Does your site have visitors with log-in access to a restricted area of the website?", 'complianz-gdpr' ),
			'tooltip'     => __( "If so, the scan will be extended to the wp-admin part of your site. ", 'complianz-gdpr' ),
			'required' => false,
			'options'  => COMPLIANZ::$config->yes_no,
		],
		[
			'id'       => 'cookie-statement',
			'menu_id'  => 'documents',
			'default'  => 'generated',
			'type'     => 'document',
			'options'  => [
				[ 'label' => __('Generated with Complianz','complianz-gdpr'), 'value'=> 'generated' ],
				[ 'label' => __('Existing page','complianz-gdpr'), 'value'=> 'custom' ],
				[ 'label' => 'URL', 'value'=> 'url' ],
			],
			'label'    => __( "Cookie Policy", 'complianz-gdpr' ),
			'required' => true,
			'tooltip'  => __( 'A Cookie Policy is required to inform your visitors about the way cookies and similar techniques are used on your website.', "complianz-gdpr" ),
		],
		[
			'id'       => 'privacy-statement',
			'premium' => [
				'url' => 'https://complianz.io/pricing/',
				'default' => 'generated',
				'disabled' => false,
			],
			'menu_id'  => 'documents',
			'disabled' => ['generated'],
			'type'     => 'document',
			'default'  => 'custom',
			'label'    => __( "Privacy Statement", 'complianz-gdpr' ),
			'options'  => [
				[ 'label' => __('Generated with Complianz','complianz-gdpr'), 'value'=> 'generated' ],
				[ 'label' => __('Existing page','complianz-gdpr'), 'value'=> 'custom' ],
				[ 'label' => 'URL', 'value'=> 'url' ],
				[ 'label' => __('None','complianz-gdpr'), 'value'=> 'none' ],
			],
			'required' => true,
			'tooltip' => __("A Privacy Statement is required to inform your visitors about the way you deal with the privacy of website visitors. A link to this document is placed on your consent banner.", 'complianz-gdpr'),
		],

		[
			'id'       => 'impressum',
			'menu_id'  => 'documents',
			'premium' => [
				'url' => 'https://complianz.io/pricing/',
			],
			'disabled' => ['generated'],
			'default'  => 'none',
			'type'     => 'document',
			'label'    => __( "Imprint", 'complianz-gdpr' ),
			'options'  => [
				[ 'label' => __('Generated with Complianz','complianz-gdpr'), 'value'=> 'generated' ],
				[ 'label' => __('Existing page','complianz-gdpr'), 'value'=> 'custom' ],
				[ 'label' => 'URL', 'value'=> 'url' ],
				[ 'label' => __('None','complianz-gdpr'), 'value'=> 'none' ],
			],
			'required' => true,
			'tooltip'  =>  __("An Imprint provides general contact information about the organization behind this website and might be required in your region.", 'complianz-gdpr'),
		],
		[
			'id'       => 'disclaimer',
			'menu_id'  => 'documents',
			'premium' => [
				'url' => 'https://complianz.io/pricing/',
			],
			'disabled' => ['generated'],
			'default'  => 'none',
			'type'     => 'document',
			'options'  => [
				[ 'label' => __('Generated','complianz-gdpr'), 'value'=> 'generated' ],
				[ 'label' => __('Existing page','complianz-gdpr'), 'value'=> 'custom' ],
				[ 'label' => 'URL', 'value'=> 'url' ],
				[ 'label' => __('None','complianz-gdpr'), 'value'=> 'none' ],
			],
			'label'    => __( "Disclaimer", 'complianz-gdpr' ),
			'required' => true,
			'tooltip'  => __("A Disclaimer is commonly used to exclude or limit liability or to make statements about the content of the website. Having a Disclaimer is not legally required.", 'complianz-gdpr'),
	],
		[
			'id'       => 'organisation_name',
			'menu_id'  => 'website-information',
			'type'     => 'text',
			'default'  => '',
			'placeholder'  => __( "Name or company name", 'complianz-gdpr' ),
			'label'    => __( "Who is the owner of the website?", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'OR',
				[
					'impressum' => 'generated',
					'cookie-statement' => 'generated',
					'privacy-statement' => 'generated',
					'disclaimer' => 'generated',
				]
			],
			'required' => true,
		],
		[
			'id'                 => 'address_company',
			'menu_id'            => 'website-information',
			'placeholder'        => __( 'Address, City and Zipcode', 'complianz-gdpr' ),
			'type'               => 'textarea',
			'default'            => '',
			'label'              => __( "What is your address?", 'complianz-gdpr' ),
			'required'           => true,
			'react_conditions' => [
				'relation' => 'OR',
				[
					'impressum' => 'generated',
					'cookie-statement' => 'generated',
					'privacy-statement' => 'generated',
					'disclaimer' => 'generated',
				]
			],
		],
		[
			'id'       => 'country_company',
			'menu_id'  => 'website-information',
			'type'     => 'select',
			'options'  => COMPLIANZ::$config->countries,
			'default'  => 'NL',
			'label'    => __( "What is your country?", 'complianz-gdpr' ),
			'required' => true,
			'tooltip'     => __( "This setting is automatically selected based on your WordPress language setting.", 'complianz-gdpr' ),
		],
		[
			'id'       => 'email_company',
			'menu_id'  => 'website-information',
			'type'     => 'email',
			'default'  => '',
			'tooltip'     => __( "The email address will be obfuscated on the front-end to prevent spidering.", 'complianz-gdpr' ),
			'label'    => __( "What is the email address your visitors can use to contact you about privacy issues?", 'complianz-gdpr' ),
			'required' => true,
			'react_conditions' => [
				'relation' => 'OR',
				[
					'impressum' => 'generated',
					'cookie-statement' => 'generated',
					'privacy-statement' => 'generated',
					'disclaimer' => 'generated',
				]
			],
		],
		[
			'id'       => 'telephone_company',
			'menu_id'  => 'website-information',
			'type'           => 'phone',
			'default'        => '',
			'document_label' => __( 'Phone number:', 'complianz-gdpr' ) . ' ',
			'label'          => __( "What is the telephone number your visitors can use to contact you about privacy issues?", 'complianz-gdpr' ),
			'required'       => false,
			'react_conditions' => [
				'relation' => 'OR',
				[
					'impressum' => 'generated',
					'cookie-statement' => 'generated',
					'privacy-statement' => 'generated',
					'disclaimer' => 'generated',
				]
			],
		],
		[
			'id' => 'ca_name_accountable_person',
			'menu_id'  => 'website-information',
			'type' => 'text',
			'required' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['ca', 'au', 'za'],
				]
			],
			'label' => __("Person who is accountable for the organization’s policies and practices and to whom complaints or inquiries can be forwarded.", 'complianz-gdpr'),
		],
		[
			'id' => 'ca_address_accountable_person',
			'menu_id'  => 'website-information',
			'type' => 'textarea',
			'required' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['ca', 'au', 'za'],
				]
			],
			'label' => __("What is the address where complaints or inquiries can be forwarded?", 'complianz-gdpr'),
		],
		[
			'id'                 => 'purpose_personaldata',
			'menu_id'            => 'purpose',
			'type'               => 'multicheckbox',
			'disabled' 		     => false,
			#In the free version, the purpose is not necessary for EU. In the premium it is necessary if a privacy statement is needed.
			'premium'            => [
				'react_conditions' => [
					'relation' => 'OR',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
					]
				]
			],
			'default'            => '',
			'label'              => __( "Indicate for what purpose personal data is processed via your website:", 'complianz-gdpr' ),
			'required'           => true,
			'options'            => COMPLIANZ::$config->purposes,
			'react_conditions' => [
				'relation' => 'OR',
				[
					'regions' => ['us'],
				]
			]
		],
		[
			'id'   => 'records_of_consent',
			'menu_id'  => 'security-consent',
			'premium' => [
				'url' => 'https://complianz.io/pricing/',
				'description' => __( "Extend Proof of Consent with Records of Consent", 'complianz-gdpr' ),
			],
			'label'    => __( "Do you want to enable Records of Consent?", 'complianz-gdpr' ),
			'type'     => 'radio',
			'options'  => COMPLIANZ::$config->yes_no,
			'default'  => 'no',
			'disabled' => true,
			'help' => [
				'label' => 'default',
				'title' => __( "Records of Consent", 'complianz-gdpr' ),
				'text'  => __( "Enabling this option will extend our Proof of Consent method with user consent registration.", 'complianz-gdpr' ).' '.
					__( "This option is recommended in combination with TCF and will store consent data in your database.", 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/records-of-consent',
			],
		],

		[
			'id'   => 'datarequest',
			'menu_id'  => 'security-consent',
			'premium' => [
				'url' => 'https://complianz.io/pricing/',
				'description' => __( "Do you want to enable Data Request Forms?", 'complianz-gdpr' ),
			],
			'label'    => __( "Do you want to enable Data Request Forms?", 'complianz-gdpr' ),
			'type'     => 'radio',
			'options'  => COMPLIANZ::$config->yes_no,
			'default'  => 'no',
			'disabled' => true,
			'help' => [
				'label' => 'default',
				'title' => __( "What are data request forms?", 'complianz-gdpr' ),
				'text'  => __( "This will enable Data Requests Forms for your Privacy Statements.", 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/data-requests-forms/',
			],
		],
		[
			'id'   => 'respect_dnt',
			'menu_id'  => 'security-consent',
			'type'    => 'radio',
			'options' => COMPLIANZ::$config->yes_no,
			'default' => 'no',
			'label'   => __( "Respect Do Not Track and Global Privacy Control?", 'complianz-gdpr' ),
			'tooltip' => __( 'If you enable this option, Complianz will not show the consent banner to users that enabled a ‘Do Not Track’ or \'Global Privacy Control\' setting in their browsers and their default consent status is set to ‘denied’.', 'complianz-gdpr' ),
		],
		[
			'id'   => 'sensitive_information_processed',
			'menu_id'  => 'security-consent',
			'type'               => 'radio',
			'required'           => false,
			'default'            => '',
			'options'            => COMPLIANZ::$config->yes_no,
			'label'              => __( "Does your website contain or process sensitive (personal) information?", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['ca', 'au'],
				]
			],
			'tooltip'            => __( 'Sensitive personal information is considered data that is very likely to have a greater impact on Privacy. For example medical, religious or legal information.', 'complianz-gdpr' ),
		],
	];

	return $fields;
}
