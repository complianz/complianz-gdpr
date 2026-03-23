<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );
/**
 * Conditional notices for fields
 *
 * @return array
 */
function cmplz_field_notices(): array {
	if ( ! cmplz_user_can_manage() ) {
		return array();
	}

	$notices = array();
	if ( get_option( 'cmplz_detected_stats_type' ) || get_option( 'cmplz_detected_stats_data' ) ) {
		$notices[] = array(
			'field_id' => 'compile_statistics',
			'label'    => 'default',
			'title'    => __( 'Prefilled field', 'complianz-gdpr' ),
			'text'     => __( 'Some answers have been been pre-filled by our system detection. Please check before continuing', 'complianz-gdpr' ) .
							' ' . __( 'Please make sure you remove your current implementation to prevent double statistics tracking.', 'complianz-gdpr' ),
		);
	}
	$stats = cmplz_scan_detected_stats();
	if ( $stats ) {
		$type      = reset( $stats );
		$type      = COMPLIANZ::$config->stats[ $type ];
		$notices[] = array(
			'field_id' => 'compile_statistics',
			'label'    => 'default',
			'title'    => __( 'Detected statistics', 'complianz-gdpr' ),
			// translators: %s placeholder for the statistics service currently enabled on the site.
			'text'     => cmplz_sprintf( __( 'The site scan detected %s on your site, which means the answer to this question should be %s.', 'complianz-gdpr' ), $type, $type ),
		);
	}

	$social_media = cmplz_scan_detected_social_media();
	if ( $social_media ) {
		foreach ( $social_media as $key => $social_medium ) {
			$social_media[ $key ] = COMPLIANZ::$config->thirdparty_socialmedia[ $social_medium ];
		}
		$social_media = implode( ', ', $social_media );
		$notices[]    = array(
			'field_id' => 'uses_social_media',
			'label'    => 'default',
			'title'    => __( 'Detected social media', 'complianz-gdpr' ),
			// translators: %s placeholder for the social media services currently detected on the site.
			'text'     => cmplz_sprintf( __( 'The scan found social media buttons or widgets for %s on your site, which means the answer should be yes', 'complianz-gdpr' ), $social_media ),
		);
	}

	$contact_forms = cmplz_site_uses_contact_forms();
	if ( $contact_forms ) {
		$notices[] = array(
			'field_id' => 'purpose_personaldata',
			'label'    => 'default',
			'title'    => __( 'Detected forms', 'complianz-gdpr' ),
			'text'     => __( 'The scan found forms on your site, which means answer should probably include "contact".', 'complianz-gdpr' ),
		);
	}

	$thirdparties = cmplz_scan_detected_thirdparty_services();
	if ( $thirdparties ) {
		foreach ( $thirdparties as $key => $thirdparty ) {
			$thirdparties[ $key ] = COMPLIANZ::$config->thirdparty_services[ $thirdparty ];
		}
		$thirdparties = implode( ', ', $thirdparties );
		$notices[]    = array(
			'field_id' => 'uses_thirdparty_services',
			'label'    => 'default',
			'title'    => __( 'Detected third-party services', 'complianz-gdpr' ),
			// translators: %1$ placeholder for the third part services currently enabled on the site.
			'text'     => cmplz_sprintf( __( 'The scan found third-party services on your website: %s, this means the answer should be yes.', 'complianz-gdpr' ), $thirdparties ),
		);
	}

	if ( cmplz_has_region( 'us' ) && COMPLIANZ::$banner_loader->site_shares_data()
	) {
		$notices[] = array(
			'field_id' => 'purpose_personaldata',
			'label'    => 'default',
			'title'    => __( 'Selling personal data', 'complianz-gdpr' ),
			'text'     => __( 'The site scan detected cookies from services that share data with Third Parties. According to US privacy laws, your website is considered to sell personal data if it collects and shares any personal data in return for money or services. This includes a service like Google Analytics.', 'complianz-gdpr' ),
		);
	}

	if ( function_exists( 'et_setup_theme' ) ) {
		$notices[] = array(
			'field_id' => 'thirdparty_services_on_site',
			'label'    => 'warning',
			'title'    => __( 'Divi detected', 'complianz-gdpr' ),
			'text'     => __( 'Your site uses Divi. If you use reCAPTCHA on your site, you may need to disable the reCAPTCHA integration in Complianz. ', 'complianz-gdpr' ),
			'url'      => 'https://complianz.io/blocking-recaptcha-on-divi/',
		);
	}

	if ( COMPLIANZ::$banner_loader->site_shares_data() ) {
		$notices[] = array(
			'field_id' => 'data_disclosed_us',
			'label'    => 'default',
			'title'    => __( 'Third-party cookies', 'complianz-gdpr' ),
			'text'     => __(
				"The site scan detected cookies from services which share data with Third Parties. If these cookies were also used in the past 12 months, you should at least select the option 'Internet activity...' under General > Details per purpose.",
				'complianz-gdpr'
			),
		);
	}
	if ( COMPLIANZ::$banner_loader->uses_google_tagmanager() ) {
		$notices[] = array(
			'field_id' => 'category_all',
			'label'    => 'warning',
			'title'    => 'Google Tag Manager',
			'text'     => __( "You're using Google Tag Manager. This means you need to configure Tag Manager to use the below categories.", 'complianz-gdpr' ),
			'url'      => 'https://complianz.io/definitive-guide-to-tag-manager-and-complianz/',
		);
	}

	if ( COMPLIANZ::$banner_loader->cookie_warning_required_stats( 'eu' ) ) {
		$notices[] = array(
			'field_id' => 'use_categories',
			'label'    => 'warning',
			'title'    => __( 'Using categories is mandatory', 'complianz-gdpr' ),
			'text'     => __( 'Categories are mandatory for your statistics configuration.', 'complianz-gdpr' ),
			'url'      => 'https://complianz.io/categories-are-mandatory-for-your-statistics-configuration',
		);
	}
	/**
	 * For the cookie page and the US banner we need a link to the privacy statement.
	 * In free, and in premium when the privacy statement is not enabled, we choose the WP privacy page. If it is not set, the user needs to create one.
	 * */
	if ( cmplz_has_region( 'us' ) || cmplz_has_region( 'ca' ) || cmplz_has_region( 'au' ) ) {
		$notices[] = array(
			'field_id' => 'privacy-statement',
			'label'    => 'default',
			'title'    => __( 'Privacy Statement', 'complianz-gdpr' ),
			'text'     => __( 'It is recommended to select a Privacy Statement.', 'complianz-gdpr' ) . ' ' . __( 'The link to the Privacy Statement is used in the consent banner and in your Cookie Policy.', 'complianz-gdpr' ),
		);
	} else {
		$notices[] = array(
			'field_id' => 'privacy-statement',
			'label'    => 'default',
			'title'    => __( 'Privacy Statement', 'complianz-gdpr' ),
			'text'     => __( 'It is recommended to select a Privacy Statement.', 'complianz-gdpr' ) . ' ' . __( 'The link to the Privacy Statement is used in your Cookie Policy.', 'complianz-gdpr' ),
		);
	}

	/**
	 * If a plugin places marketing cookie as first party, we can't block it automatically, unless the wp consent api is used.
	 * User should be warned, and category marketing is necessary
	 * */
	if ( cmplz_detected_firstparty_marketing() ) {
		$notices[] = array(
			'field_id' => 'uses_firstparty_marketing_cookies',
			'label'    => 'default',
			'title'    => __( 'First-party marketing cookies', 'complianz-gdpr' ),
			'text'     => __( 'You use plugins which place first-party marketing cookies. Complianz cannot only block such cookies if the plugin conforms to the WP Consent API, or you have enabled Consent Per Service', 'complianz-gdpr' ),
			'url'      => 'https://complianz.io/first-party-marketing-cookies',
		);
	}

	if ( cmplz_uses_sensitive_data() ) {
		$notices[] = array(
			'field_id' => 'sensitive_information_processed',
			'label'    => 'default',
			'title'    => __( 'Sensitive & personal data', 'complianz-gdpr' ),
			'text'     => __( "You have selected options that indicate your site processes sensitive, personal data. You should select 'Yes'", 'complianz-gdpr' ),
		);
	}

	if ( cmplz_get_option( 'cookie-statement' ) !== 'generated' ) {
		$notices[] = array(
			'field_id' => 'uses_ad_cookies_personalized',
			'label'    => 'warning',
			'title'    => __( 'TCF not possible with custom Cookie Policy', 'complianz-gdpr' ),
			'text'     => __( 'You have chosen a custom Cookie Policy. The TCF option is disabled as it can only be used in combination with the Cookie Policy generated by Complianz.', 'complianz-gdpr' ),
		);
	}

	if ( cmplz_tcf_active() ) {
		$notices[] = array(
			'field_id' => 'cookie-statement',
			'label'    => 'default',
			'title'    => __( 'TCF enabled', 'complianz-gdpr' ),
			'text'     => __( 'You have enabled TCF. This option can only be used in combination with the Cookie Policy generated by Complianz.', 'complianz-gdpr' ),
		);
	}

	if ( cmplz_tcf_active() ) {
		$notices[] = array(
			'field_id'    => 'uses_thirdparty_services',
			'label'       => 'warning',
			'title'       => __( 'TCF enabled: Review customization guidelines', 'complianz-gdpr' ),
			'text'        => __(
				"You have enabled TCF. Please check the do's and don'ts regarding customizations:
		                     <a href='https://complianz.io/customizing-the-tcf-banner/?utm_source=tipstricks&utm_medium=plugin&utm_campaign=articles&utm_id=66&utm_content=tcf' target='_blank' aria-label='Read more about TCF customization guidelines'>Read more about TCF customization guidelines</a>",
				'complianz-gdpr'
			),
			'dismissible' => true,
		);
	}

	// Single functional category notice.
	if ( cmplz_has_only_functional_category() ) {
		$notices[] = array(
			'field_id'    => 'last-step-feedback',
			'label'       => 'info',
			'title'       => __( 'Single Functional Category Detected', 'complianz-gdpr' ),
			'text'        => '<strong>' . __( 'Attention: please review your cookie configuration', 'complianz-gdpr' ) . '</strong><br><br>' .
							__( "Currently, the only active cookie category is Functional. In some regions, when only one category is available, the cookie banner may not be 
	displayed. We recommend double-checking your settings and, if needed, following this guide to force the banner display.", 'complianz-gdpr' ),
			'url'         => 'https://complianz.io/display-a-cookie-banner-even-when-not-required',
			'dismissible' => true,
		);
	}

	if ( 'clarity' === cmplz_get_option( 'compile_statistics' ) ) {
		$notices[] = array(
			'field_id' => 'clarity_consent_mode',
			'label'    => 'warning',
			'title'    => __( 'Clarity Consent Mode V2', 'complianz-gdpr' ),
			'text'     => __( 'Enforced from October 31st 2025; see how it works.', 'complianz-gdpr' ),
			'url'      => 'https://complianz.io/microsoft-consent-mode/',
		);
	}

	$notices = apply_filters( 'cmplz_field_notices', $notices );
	return cmplz_add_referral_to_fields_notices( $notices );
}


/**
 * Add referral parameters to premium field upgrade links
 *
 * @param array $fields Fields array.
 * @return array Modified fields with referral parameters
 */
function cmplz_add_referral_to_fields_notices( $fields ) {
	foreach ( $fields as &$field ) {
		if ( isset( $field['url'] ) && isset( $field['field_id'] ) && strpos( $field['url'], 'complianz.io' ) !== false ) {
			$field['url'] = cmplz_get_referral_url( 'articles', $field['field_id'], $field['url'] );
		}
	}

	return $fields;
}
