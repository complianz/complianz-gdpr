<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

defined( 'ABSPATH' ) || die();

/**
 * Menu items.
 *
 * @return array
 */
function cmplz_menu() {
	if ( ! cmplz_user_can_manage() ) {
		return array();
	}
	$menu_items = array(
		array(
			'id'         => 'dashboard',
			'title'      => __( 'Dashboard', 'complianz-gdpr' ),
			'menu_items' => array(),
		),
		array(
			'id'         => 'wizard',
			'title'      => __( 'Wizard', 'complianz-gdpr' ),
			'menu_items' => array(
				array(
					'id'         => 'general',
					'group_id'   => 'general',
					'title'      => __( 'General', 'complianz-gdpr' ),
					'menu_items' => array(
						array(
							'id'    => 'visitors',
							'title' => __( 'Visitors', 'complianz-gdpr' ),
							'intro' => __( 'The Complianz wizard will guide you through the necessary steps to configure your website for privacy legislation around the world. We designed the wizard to be comprehensible, without making concessions in legal compliance.', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'documents',
							'title' => __( 'Documents', 'complianz-gdpr' ),
							'intro' => __( 'Here you can select which legal documents you want to generate with Complianz. You can also use existing legal documents.', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'website-information',
							'title' => __( 'Website information', 'complianz-gdpr' ),
							'intro' => __( 'We need some information to be able to generate your documents and configure your consent banner.', 'complianz-gdpr' ),
						),
						array(
							'id'       => 'impressum',
							'title'    => __( 'Imprint', 'complianz-gdpr' ),
							'intro'    => __( 'We need some information to be able to generate your Imprint. Not all fields are required.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/impressum-required-information',
						),
						array(
							'id'       => 'disclaimer',
							'title'    => __( 'Disclaimer', 'complianz-gdpr' ),
							'intro'    => __( 'As you have selected the Disclaimer to be generated, please fill out the questions below.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/definition/what-is-a-disclaimer/',
						),
						array(
							'id'       => 'financial',
							'title'    => __( 'Financial incentives', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/',
							'region'   => array( 'us' ),
						),
						array(
							'id'       => 'children',
							'title'    => __( 'Children\'s Privacy Policy', 'complianz-gdpr' ),
							'intro'    => __( 'In one ore more regions your selected, you need to specify if you target children.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/',
							'region'   => array( 'us', 'uk', 'ca', 'au', 'za', 'br' ),
						),
						array(
							'id'       => 'children-purposes',
							'title'    => __( 'Children: Purposes', 'complianz-gdpr' ),
							'intro'    => __( 'In one ore more regions your selected, you need to specify if you target children.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/',
							'region'   => array( 'us', 'au' ),
						),
						array(
							'id'     => 'dpo',
							'title'  => __( 'Data Protection Officer', 'complianz-gdpr' ),
							'intro'  => '',
							'region' => array( 'eu', 'uk' ),
						),
						array(
							'id'    => 'purpose',
							'title' => __( 'Purpose', 'complianz-gdpr' ),
							'intro' => '',
						),
						array(
							'id'    => 'details-per-purpose',
							'title' => __( 'Details per Purpose', 'complianz-gdpr' ),
							'intro' => '',
						),
						array(
							'id'     => 'sharing-of-data',
							'title'  => __( 'Sharing of Data', 'complianz-gdpr' ),
							'region' => array( 'eu', 'us', 'uk', 'au', 'za', 'br' ),

						),
						array(
							'id'    => 'security-consent',
							'title' => __( 'Security & Consent', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'         => 'consent',
					'group_id'   => 'consent',
					'title'      => __( 'Consent', 'complianz-gdpr' ),
					'menu_items' => array(
						array(
							'id'                    => 'cookie-scan',
							'title'                 => __( 'Website Scan', 'complianz-gdpr' ),
							'intro'                 => __( 'Complianz will scan several pages of your website for first-party cookies and known third-party scripts. The scan will be recurring monthly to keep you up-to-date!', 'complianz-gdpr' ).' '. cmplz_sprintf( __( 'For more information, %sread our 5 tips%s about the site scan.', 'complianz-gdpr'), '<a href="https://complianz.io/cookie-scan-results/" target="_blank">','</a>'),
							'helpLink'              => 'https://complianz.io/cookie-scan-results/',
							'save_buttons_required' => false,
						),
						array(
							'id'       => 'consent-statistics',
							'title'    => __( 'Statistics', 'complianz-gdpr' ),
							'intro'    => __( 'Below you can choose to implement your statistics tooling with Complianz. We will add the needed snippets and control consent at the same time', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/statistics-implementation',
						),
						array(
							'id'       => 'statistics-configuration',
							'title'    => __( 'Statistics configuration', 'complianz-gdpr' ),
							'intro'    => __( 'If you choose Complianz to handle your statistics implementation, please delete the current implementation.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/statistics-implementation#configuration',
						),
						array(
							'id'    => 'services',
							'title' => __( 'Services', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'plugins',
							'title' => __( 'Plugins', 'complianz-gdpr' ),
							'intro' => __( 'We have detected the below plugins.', 'complianz-gdpr' ) . ' ' . __( 'We have enabled the integrations and possible placeholders.', 'complianz-gdpr' ) . ' ' . __( 'To change these settings, please visit the script center.', 'complianz-gdpr' ),
						),
						// we need TCF at least one menu item separated from the option to enabled it (services) otherwise the fields data
						// might not be loaded when we get here.
						array(
							'id'       => 'tcf',
							'title'    => __( 'Advertising', 'complianz-gdpr' ),
							'intro'    => __(
								'The below questions will help you configure a vendor list of your choosing. Only vendors that adhere to the purposes and special features you configure will be able to serve ads.',
								'complianz-gdpr'
							),
							'helpLink' => 'https://complianz.io/tcf/',
						),
						array(
							'id'                    => 'cookie-descriptions',
							'title'                 => 'Cookiedatabase.org',
							'intro'                 => __( 'Complianz provides your Cookie Policy with comprehensive cookie descriptions, supplied by cookiedatabase.org.', 'complianz-gdpr' ) . ' '
								. __( 'We connect to this open-source database using an external API, which sends the results of the cookiescan (a list of found cookies, used plugins and your domain) to cookiedatabase.org, for the sole purpose of providing you with accurate descriptions and keeping them up-to-date on a regular basis.', 'complianz-gdpr' ),
							'helpLink'              => 'https://complianz.io/our-cookiedatabase-a-new-initiative/',
							'save_buttons_required' => false,
						),
					),
				),
				array(
					'id'         => 'manage-documents',
					'group_id'   => 'manage-documents',
					'title'      => __( 'Documents', 'complianz-gdpr' ),
					'menu_items' => array(
						array(
							'id'       => 'create-documents',
							'title'    => __( 'Documents', 'complianz-gdpr' ),
							'intro'    => __( 'Generate your documents, then you can add them to your menu directly or do it manually after the wizard is finished.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/how-to-create-a-menu-in-wordpress/',
						),
						array(
							'id'       => 'document-menu',
							'title'    => __( 'Link to menu', 'complianz-gdpr' ),
							'intro'    => __( 'It\'s possible to use region redirect when GEO IP is enabled, and you have multiple policies and statements.', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/how-to-redirect-your-policies-based-on-region/',
						),
					),
				),
				array(
					'id'       => 'finish',
					'group_id' => 'finish',
					'title'    => __( 'Finish', 'complianz-gdpr' ),

				),
			),
		),
		array(
			'id'         => 'banner',
			'title'      => __( 'Consent Banner', 'complianz-gdpr' ),
			'menu_items' => array(
				array(
					'id'    => 'banner-general',
					'title' => __( 'General', 'complianz-gdpr' ),
					'intro' => __( 'These are the main options to customize your consent banner. To go even further you can use our documentation on complianz.io for CSS Lessons, or even start from scratch and create your own with just HTML and CSS.', 'complianz-gdpr' ),
				),
				array(
					'id'    => 'appearance',
					'title' => __( 'Appearance', 'complianz-gdpr' ),
				),
				array(
					'id'     => 'colors',
					'title'  => __( 'Colors', 'complianz-gdpr' ),
					'groups' => array(
						array(
							'id'    => 'colors-general',
							'title' => __( 'General', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'colors-toggles',
							'title' => __( 'Toggles', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'colors-buttons',
							'title' => __( 'Buttons', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'       => 'banner-texts',
					'title'    => __( 'Texts', 'complianz-gdpr' ),
					'intro'    => __( 'Here you can edit the texts on your banner.', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/social-media-on-a-cookiebanner/',
				),
				array(
					'id'       => 'custom-css',
					'title'    => __( 'Custom CSS', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/search/CSS+Lesson',
				),
			),
		),
		array(
			'id'         => 'integrations',
			'title'      => __( 'Integrations', 'complianz-gdpr' ),
			'menu_items' => array(
				array(
					'id'       => 'integrations-services',
					'title'    => __( 'Services', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/integrating-plugins/',
				),
				array(
					'id'       => 'integrations-plugins',
					'title'    => __( 'Plugins', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/integrating-plugins/',
				),
				array(
					'id'       => 'integrations-script-center',
					'title'    => __( 'Script Center', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/integrating-plugins/',
				),
			),
		),
		array(
			'id'         => 'settings',
			'title'      => __( 'Settings', 'complianz-gdpr' ),
			'menu_items' => array(
				array(
					'id'       => 'settings-general',
					'group_id' => 'settings-general',
					'title'    => __( 'General', 'complianz-gdpr' ),
					'groups'   => array(
						array(
							'id'    => 'settings-general',
							'title' => __( 'General', 'complianz-gdpr' ),
							'intro' => __( 'Missing any settings? We have moved settings to Tools, available in the menu.', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'       => 'settings-cd',
					'group_id' => 'settings-cd',
					'title'    => 'APIs',
					'groups'   => array(
						array(
							'id'    => 'settings-cd',
							'title' => 'Cookiedatabase.org',
						),
					),
				),
			),
		),
		array(
			'id'         => 'tools',
			'title'      => __( 'Tools', 'complianz-gdpr' ),
			'menu_items' => array(
				array(
					'id'     => 'support',
					'title'  => __( 'Support', 'complianz-gdpr' ),
					'groups' => array(
						array(
							'id'           => 'premiumsupport',
							'title'        => __( 'Support', 'complianz-gdpr' ),
							'intro'        => '<strong>' . __( 'About this support form', 'complianz-gdpr' ) . ' </strong><br>' .
								__( 'This form sends your message via email using your WordPress site\'s mail system. Many WordPress sites (especially local or development environments) don\'t have email properly configured, which means your message might not reach us. You can install an SMTP plugin to enable email functionality.', 'complianz-gdpr' ) . '<br><br>' .
								'<strong>' . __( 'For the most reliable support', 'complianz-gdpr' ) . '</strong>, ' .
								cmplz_sprintf(
									/* translators: 1: URL, 2: aria-label text */
									__( 'we recommend using our <a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">online support form</a> instead.', 'complianz-gdpr' ),
									'https://complianz.io/support',
									esc_attr__( 'Online support form (opens in new tab)', 'complianz-gdpr' )
								),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'Get premium support with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'debugging',
							'title' => __( 'Debugging', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'       => 'data-requests',
					'title'    => __( 'Data Requests', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/data-requests-forms/',
					'groups'   => array(
						array(
							'id'       => 'datarequest-entries',
							'title'    => __( 'Data Requests', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/responding-to-a-data-request/',
						),
						array(
							'id'       => 'settings',
							'title'    => __( 'Settings', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/data-requests-forms/',
						),
					),
				),
				array(
					'id'     => 'placeholders',
					'title'  => __( 'Placeholders', 'complianz-gdpr' ),
					'groups' => array(
						array(
							'id'       => 'placeholders-appearance',
							'title'    => __( 'Placeholder Style', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/changing-the-default-social-placeholders/',
						),
						array(
							'id'    => 'placeholders-settings',
							'title' => __( 'Settings', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'     => 'tools-documents',
					'title'  => __( 'Documents', 'complianz-gdpr' ),
					'groups' => array(
						array(
							'id'    => 'tools-documents-general',
							'title' => __( 'General', 'complianz-gdpr' ),
						),
						array(
							'id'       => 'tools-documents-css',
							'title'    => __( 'Document CSS', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/search/document+css',
						),
					),
				),
				array(
					'id'       => 'multisite',
					'title'    => __( 'Multisite options', 'complianz-gdpr' ),
					'helpLink' => 'https://complianz.io/cross-domain-cookie-consent/',
				),
				array(
					'id'                    => 'processing-agreements',
					'title'                 => __( 'Processing Agreements', 'complianz-gdpr' ),
					'helpLink'              => 'https://complianz.io/',
					'save_buttons_required' => false,
					'groups'                => array(
						array(
							'id'           => 'create-processing-agreements',
							'title'        => __( 'Create Processing Agreements', 'complianz-gdpr' ),
							'helpLink'     => 'https://complianz.io/do-i-need-a-processing-agreement-with-complianz/',
							'intro'        => __( 'Here you can create and upload processing agreements. These are necessary when you allow other third parties to process your data.', 'complianz-gdpr' ),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'Create Processing Agreements with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
						array(
							'id'           => 'processing-agreements',
							'title'        => __( 'Processing Agreements', 'complianz-gdpr' ),
							'helpLink'     => 'https://complianz.io/definition/what-is-a-processing-agreement/',
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'View and manage Processing Agreements with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'                    => 'data-breach-reports',
					'title'                 => __( 'Data Breach Reports', 'complianz-gdpr' ),
					'helpLink'              => 'https://complianz.io/',
					'save_buttons_required' => false,
					'groups'                => array(
						array(
							'id'           => 'create-data-breach-reports',
							'title'        => __( 'Create Data Breach Reports', 'complianz-gdpr' ),
							'helpLink'     => 'https://complianz.io/definition/what-is-a-data-breach/',
							'intro'        => __( 'Do you think your data might have been compromised? Did you experience a security incident or are not sure who had access to personal data for a period of time? Create a data breach report below to see what you need to do.', 'complianz-gdpr' ),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'Create Data Breach Reports with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
						array(
							'id'           => 'data-breach-reports',
							'title'        => __( 'Data Breach Reports', 'complianz-gdpr' ),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'View and manage Data Breach Reports with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'                    => 'proof-of-consent',
					'title'                 => __( 'Proof of Consent', 'complianz-gdpr' ),
					'helpLink'              => 'https://complianz.io/definition/what-is-proof-of-consent/',
					'save_buttons_required' => false,
					'groups'                => array(
						array(
							'id'       => 'create-proof-of-consent',
							'title'    => __( 'General', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/definition/what-is-proof-of-consent/',
						),
						array(
							'id'       => 'proof-of-consent',
							'title'    => __( 'Proof of Consent', 'complianz-gdpr' ),
							'helpLink' => 'https://complianz.io/',
						),
					),
				),
				array(
					'id'                    => 'records-of-consent',
					'title'                 => __( 'Records of Consent', 'complianz-gdpr' ),
					'helpLink'              => 'https://complianz.io/records-of-consent/',
					'save_buttons_required' => false,
					'groups'                => array(
						array(
							'id'           => 'create-records-of-consent',
							'title'        => __( 'General', 'complianz-gdpr' ),
							'helpLink'     => 'https://complianz.io/records-of-consent/',
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'View and manage Records of Consent with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
						array(
							'id'           => 'records-of-consent',
							'title'        => __( 'Records of Consent', 'complianz-gdpr' ),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'View and manage Records of Consent with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'     => 'ab-testing',
					'title'  => __( 'Statistics', 'complianz-gdpr' ),
					'groups' => array(
						array(
							'id'                    => 'statistics-settings',
							'title'                 => __( 'General', 'complianz-gdpr' ),
							'save_buttons_required' => true,
							'premium'               => true,
							'upgrade'               => 'https://complianz.io/pricing/',
						),
						array(
							'id'           => 'statistics-view',
							'title'        => __( 'Statistics', 'complianz-gdpr' ),
							'premium'      => true,
							'upgrade'      => 'https://complianz.io/pricing/',
							'premium_text' => __( 'View and manage Records of Consent with %sComplianz GDPR Premium%s', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'                    => 'security',
					'title'                 => __( 'Security', 'complianz-gdpr' ),
					'save_buttons_required' => false,
					'groups'                => array(
						array(
							'id'    => 'security-install',
							'title' => __( 'Improve Security', 'complianz-gdpr' ),
						),
						array(
							'id'    => 'security-privacy',
							'title' => __( 'Privacy Statement', 'complianz-gdpr' ),
							'intro' => __( 'Below text is meant for your Privacy Statement, and is created by using Really Simple Security. In Complianz Premium the text will be automatically added to the Privacy Statement.', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'       => 'tools-data',
					'group_id' => 'tools-data',
					'title'    => __( 'Data', 'complianz-gdpr' ),
					'groups'   => array(
						array(
							'id'    => 'settings-data',
							'title' => __( 'Data', 'complianz-gdpr' ),
						),
					),
				),
				array(
					'id'       => 'tools-multisite',
					'group_id' => 'tools-multisite',
					'title'    => __( 'Multisite', 'complianz-gdpr' ),
					'groups'   => array(
						array(
							'id'    => 'tools-multisite',
							'title' => __( 'Data', 'complianz-gdpr' ),
						),
					),
				),
			),
		),
	);

	$items = apply_filters( 'cmplz_menu', $menu_items );

	return cmplz_add_referral_to_premium_items( $items );
}


/**
 * Recursively add referral parameters to premium menu items and campaign to the helpLink.
 *
 * @param array $items Menu items array.
 * @return array Modified menu items with referral parameters.
 */
function cmplz_add_referral_to_premium_items( $items ) {
	foreach ( $items as &$item ) {
		// Process current item if it's premium.
		if ( isset( $item['premium'] ) && true === $item['premium'] && isset( $item['upgrade'] ) && isset( $item['id'] ) ) {
			$item['upgrade'] = cmplz_get_referral_url( 'menu', $item['id'], $item['upgrade'] );
		}

		// Process helpLink if it points to complianz.io.
		if ( isset( $item['helpLink'] ) && isset( $item['id'] ) && strpos( $item['helpLink'], 'complianz.io' ) !== false ) {
			$item['helpLink'] = cmplz_get_referral_url( 'articles', $item['id'], $item['helpLink'] );
		}

		// Recursively process menu_items.
		if ( isset( $item['menu_items'] ) && is_array( $item['menu_items'] ) ) {
			$item['menu_items'] = cmplz_add_referral_to_premium_items( $item['menu_items'] );
		}

		// Recursively process groups.
		if ( isset( $item['groups'] ) && is_array( $item['groups'] ) ) {
			$item['groups'] = cmplz_add_referral_to_premium_items( $item['groups'] );
		}
	}

	return $items;
}

/**
 * Generate a referral URL with UTM parameters based on the plugin source and type.
 *
 * This function constructs a URL with UTM parameters for tracking purposes.
 * It uses the provided type and field ID to customize the UTM medium and content.
 * If the source is 'cmplz_free' and the type is not 'articles', it adds referral
 * parameters based on defined partner constants.
 *
 * $type can be:
 * - articles | ['help']['url'] in `cmplz_fields` filter and 'helpLink' in `cmplz_menu` filter and ['url'] in `cmplz_field_notices` filter
 * - menu | 'upgrade' url in `cmplz_menu` filter
 * - fields | ['premium']['url'] in `cmplz_fields`
 * - warnings | 'url' in `cmplz_warning_types` filter
 *
 * @param string $type     The type of link (e.g., 'articles', 'menu', 'fields', 'warnings').
 * @param string $field_id The ID of the field/menu item.
 * @param string $url      The base URL to which UTM parameters will be added.
 * @return string          The generated referral URL with UTM parameters.
 */
function cmplz_get_referral_url( string $type = 'articles', string $field_id = '', string $url = '' ) {

	$fallback_url = 'https://complianz.io/pricing/';

	$url    = cmplz_get_url( $url, $fallback_url );
	$source = cmplz_get_source();

	$type     = sanitize_title( $type );
	$field_id = sanitize_title( $field_id );

	$args = array(
		'utm_source'   => $source,
		'utm_medium'   => empty( $type ) ? 'plugin' : 'plugin_' . $type,
		'utm_content'  => empty( $field_id ) ? false : $field_id,
		'utm_campaign' => 'articles',
	);

	$url = add_query_arg( $args, $url );

	if ( 'cmplz_free' !== $source || 'articles' === $type ) {
		return $url;
	}

	$referral_id          = cmplz_get_ref();
	$args['utm_campaign'] = $referral_id ? 'partners' : 'upgrade';
	$args['utm_id']       = $referral_id ? $referral_id : false;
	$args['ref']          = $referral_id ? $referral_id : false;

	$url = add_query_arg( $args, $url );

	return $url;
}

/**
 * Sanitize and normalize a URL, using a fallback when empty, and ensure a trailing slash.
 *
 * This function:
 * - Sanitizes the provided URL using WordPress' sanitize_url().
 * - If the sanitized URL is empty, uses the provided fallback URL.
 * - Ensures the returned URL ends with a trailing slash using trailingslashit().
 *
 * @param string $url          The URL to sanitize and normalize.
 * @param string $fallback_url Fallback URL to use when the provided $url is empty.
 * @return string              Sanitized and normalized URL guaranteed to end with a trailing slash.
 */
function cmplz_get_url( string $url, string $fallback_url ) {

	$url = sanitize_url( $url );
	$url = empty( $url ) ? $fallback_url : $url;

	// Add the trailing slash at the end if it's not there.
	if ( substr( $url, -1 ) !== '/' ) {
		$url = trailingslashit( $url );
	}

	return $url;
}

/**
 * Determine the plugin source identifier used for UTM/referral parameters.
 *
 * The function returns one of the following string identifiers:
 * - 'cmplz_free'               Default when no premium constants are set or truthy.
 * - 'cmplz_premium'            When the `cmplz_premium` constant is defined and truthy.
 * - 'cmplz_premium_multisite'  When the `cmplz_premium_multisite` constant is defined and truthy.
 *
 * Note: The `cmplz_premium_multisite` value takes precedence over `cmplz_premium`
 * because it is checked after it and will overwrite the previously set value.
 *
 * @return string Source identifier for UTM/analytics/referral usage.
 */
function cmplz_get_source() {
	$source = 'cmplz_free';

	if ( defined( 'cmplz_premium' ) && cmplz_premium ) {
		$source = 'cmplz_premium';
	}

	if ( defined( 'cmplz_premium_multisite' ) && cmplz_premium_multisite ) {
		$source = 'cmplz_premium_multisite';
	}

	return $source;
}

/**
 * Get the referral ID based on defined partner constants.
 *
 * This function checks for specific partner constants and returns a corresponding
 * referral ID. If no relevant constants are defined, it returns false.
 *
 * @return int|false Referral ID if a partner constant is defined, otherwise false.
 */
function cmplz_get_ref() {
	if ( defined( 'cmplz_premium' ) && defined( 'cmplz_premium_multisite' ) ) {
		return false;
	}

	$id = 0;

	if ( defined( 'EXTENDIFY_PARTNER_ID' ) && ! empty( EXTENDIFY_PARTNER_ID ) ) {
		$id = 1; // hard reference to Extendify platform.
	}

	if ( empty( $id ) ) {
		return false;
	}

	$references = array(
		1 => 1166, // Extendify.
	);

	return $references[ $id ] ? $references[ $id ] : false;
}

/**
 * Add referral parameters to premium field upgrade links
 *
 * @param array $fields Fields array.
 * @return array Modified fields with referral parameters.
 */
function cmplz_add_referral_to_fields_link( $fields ) {
	foreach ( $fields as &$field ) {
		// Process field if it has premium upgrade URL.
		if ( isset( $field['premium'] ) && is_array( $field['premium'] ) && isset( $field['premium']['url'] ) && isset( $field['id'] ) ) {
			$field['premium']['url'] = cmplz_get_referral_url( 'fields', $field['id'], $field['premium']['url'] );
		}

		if ( isset( $field['help'] ) && is_array( $field['help'] ) && isset( $field['help']['url'] ) && isset( $field['id'] ) && strpos( $field['help']['url'], 'complianz.io' ) !== false ) {
			$field['help']['url'] = cmplz_get_referral_url( 'articles', $field['id'], $field['help']['url'] );
		}
	}

	return $fields;
}

// Add referral parameters to premium items with lowest priority (runs last).
add_filter( 'cmplz_fields', 'cmplz_add_referral_to_fields_link', PHP_INT_MAX );
