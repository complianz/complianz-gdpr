<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

/**
 * Complianz Dynamic Notifications
 *
 * This code is used to fetch and store the dynamic notifications from Complianz and inform the user of the latest news.
 *
 * @package Complianz
 * @since 7.5.3
 */

const COMPLIANZ_DYNAMIC_NOTIFICATIONS_ENDPOINT             = 'https://notifications.complianz.io/';
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_KEY        = 'cmplz_dn_notifications_';
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION = 24 * HOUR_IN_SECONDS;

// Hook into the admin_init action to update the notifications.
add_action( 'admin_init', 'cmplz_dn_update' );
// Hook into the cmplz_warning_types filter to include the notifications in the warnings array.
add_filter( 'cmplz_warning_types', 'cmplz_dn_include_notifications' );

/**
 * Update the notifications.
 *
 * @return void
 */
function cmplz_dn_update(): void {
	// Retrieve the user language, return just the language code (en, nl, de, etc).
	$language = cmplz_dn_get_language();
	// Generate the transient key.
	$transient_key = cmplz_dn_transient_key( $language );
	// Check for the presence of the transients of the fetched notifications.
	$notifications = get_transient( $transient_key, false );

	// If the transient is not set, fetch the notifications.
	if ( ! is_array( $notifications ) ) {
		$notifications = cmplz_dn_fetch_notifications();
	}
}

/**
 * Fetch the notifications from the endpoint.
 *
 * @return void
 */
function cmplz_dn_fetch_notifications(): void {
	$notifications = array();

	$endpoint       = cmplz_dn_endpoint();
	$plugin_version = COMPLIANZ::$wsc_scanner->get_cmplz_version();
	$language       = cmplz_dn_get_language();

	// Define the transient key.
	$transient_key = cmplz_dn_transient_key( $language );

	$payload = array(
		'version'  => $plugin_version,
		'language' => $language,
	);

	$response = wp_remote_post(
		$endpoint,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		// Avoid fetching notifications repeatedly if the response is invalid and set an empty array.
		cmplz_dn_set_empty_notifications( $transient_key );
		return;
	}

	$status = wp_remote_retrieve_response_code( $response );

	if ( 200 !== $status ) {
		// Avoid fetching notifications repeatedly if the response is invalid and set an empty array.
		cmplz_dn_set_empty_notifications( $transient_key );
		return;
	}

	$body = wp_remote_retrieve_body( $response );

	$raw_notifications = json_decode( $body, true );

	// Check if the response is valid.
	if ( ! is_array( $raw_notifications ) || ! isset( $raw_notifications['notifications'] ) || ! is_array( $raw_notifications['notifications'] ) ) {
		// Avoid fetching notifications repeatedly if the response is invalid and set an empty array.
		cmplz_dn_set_empty_notifications( $transient_key );
		return;
	}

	// Get the sub-array with the notifications.
	$notifications = $raw_notifications['notifications'];

	// Store the notifications for 24 hours.
	$saved = set_transient( $transient_key, $notifications, COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION );
}

/**
 * Set the transient to an empty array to avoid fetching notifications repeatedly.
 *
 * @param string $transient_key The transient key.
 * @return void
 */
function cmplz_dn_set_empty_notifications( string $transient_key ): void {
	set_transient( $transient_key, array(), COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION );
}

/**
 * Include the notifications in the warnings array.
 *
 * @param array $warnings The warnings array.
 * @return array The warnings array with the notifications included.
 */
function cmplz_dn_include_notifications( array $warnings ): array {
	$language      = cmplz_dn_get_language();
	$transient_key = cmplz_dn_transient_key( $language );
	$notifications = get_transient( $transient_key );

	if ( empty( $notifications ) ) {
		return $warnings;
	}

	// Map the notifications to the warnings array.
	$var = cmplz_dn_map_notifications( $notifications );

	// Add the notifications to the warnings array.
	$warnings = array_merge( $var, $warnings );

	return $warnings;
}

/**
 * Map the notifications to the warnings array.
 *
 * @param array $notifications The notifications array.
 * @return array The warnings array with the notifications included.
 */
function cmplz_dn_map_notifications( array $notifications ): array {
	$mapped_notifications = array();

	// Map the notifications to the warnings array.
	// First, skip the expired notifications.
	$notifications = array_filter(
		$notifications,
		function ( $notification ) {
			// If the expiration is not set, it means it's a permanent notification. So must be included.
			if ( ! isset( $notification['expiration'] ) ) {
				return true;
			}

			// If the expiration is set, check if it's in the future.
			$expiration = strtotime( $notification['expiration'] );

			return $expiration > time();
		}
	);

	foreach ( $notifications as $notification ) {
		$mapped_notifications[ $notification['id'] ] = $notification;
		// If the content is not set, nothing to show, skip the notification.
		if ( ! isset( $notification['content'] ) ) {
			continue;
		}

		// If it's an admin notice, we need to show it as open.
		if ( isset( $notification['admin_notice'] ) ) {
			$mapped_notifications[ $notification['id'] ]['open'] = $notification['content'];
		} else {
			$mapped_notifications[ $notification['id'] ][ $notification['status'] ] = $notification['content'];
		}

		// Remove the content from the array.
		unset( $mapped_notifications[ $notification['id'] ]['content'] );

		// If the notification is completed, we need to for the success conditions to be true.
		if ( 'completed' === $notification['status'] ) {
			$mapped_notifications[ $notification['id'] ]['success_conditions'] = array( '_true_' );
		}
	}

	return $mapped_notifications;
}

/**
 * Get the endpoint for the notifications.
 *
 * @return string The endpoint for the notifications.
 */
function cmplz_dn_endpoint(): string {
	return COMPLIANZ_DYNAMIC_NOTIFICATIONS_ENDPOINT;
}

/**
 * Get the transient key for the notifications.
 *
 * @param string $language The language code.
 * @return string The transient key for the notifications.
 */
function cmplz_dn_transient_key( string $language ): string {
	return COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_KEY . $language;
}

/**
 * Get the language for the notifications.
 *
 * @return string The language for the notifications.
 */
function cmplz_dn_get_language(): string {
	$language = get_user_locale();
	// Cut the language, stripping after the underscore.
	return substr( $language, 0, strpos( $language, '_' ) );
}

/** End of Dynamic Notifications */


function cmplz_load_warning_types() {
	return apply_filters('cmplz_warning_types' ,array(
		'phpversion' => array(
			'warning_condition' => 'NOT cmplz_has_recommended_phpversion',
			'urgent' => __( 'Your PHP version is lower than the recommended PHP version. Some features are not available. Support for this PHP version will be dropped soon.', 'complianz-gdpr' ),
			'url' => 'https://complianz.io/php-version/',
			'plus_one' => true,
			'include_in_progress' => true,
		),
		'migrate_js' => array(
			'warning_condition'  => 'get_value_enable_migrate_js==yes',
			'open' => __( 'Migrate.js, which allowed a smooth upgrade to 6.0, has been deprecated.', 'complianz-gdpr' ),
			'url' => 'https://complianz.io/migrate-js-deprecated/',
			'admin_notice' => true,
		),
		'enable_quebec_region' => array(
			'warning_condition'  => 'cmplz_requires_quebec_notice',
			'open' => cmplz_quebec_notice(),
			'url' => 'https://complianz.io/quebec-bill-64/',
			'admin_notice' => true,
			'dismissible' => true,
		),
		// 'new_gutenberg_consentarea' => array(
		// 	'warning_condition'  => 'cmplz_upgraded_to_current_version',
		// 	'open' => __( 'New: Gutenberg Block with consent capabilities.', 'complianz-gdpr' ),
		// 	'admin_notice' => false,
		// 	'plus_one' => true,
		// 	'url' => 'https://complianz.io/gutenberg-block-consent/'
		// ),

		'no-dnt' => array(
			'success_conditions'  => array(
				'get_value_respect_dnt==yes'
			),
			'completed'    => __( 'Do Not Track and Global Privacy Control are respected.', 'complianz-gdpr' ),
			// 'open' => __( 'Do Not Track and Global Privacy Control are not yet respected.', 'complianz-gdpr' ),
			'url' => 'https://complianz.io/browser-privacy-controls/',
		),

		'ajax_fallback' => array(
			'warning_condition'  =>'get_option_cmplz_ajax_fallback_active',
			'urgent' => __( "Please check if your REST API is loading correctly. Your site currently is using the slower Ajax fallback method to load the settings.", 'complianz-gdpr' ),
			'url' => 'https://complianz.io/instructions/how-to-debug-a-blank-settings-page-in-complianz/',
			'plus_one' => true,
		),

		'has_formal' => array(
			'success_conditions'  => array(
				'NOT document->locale_has_formal_variant',
			),
			'open' =>  __( 'You have currently selected an informal language, which will result in informal use of language on the legal documents. If you prefer the formal style, you can activate this in the general settings.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'url' =>'https://complianz.io/informal-language-in-legal-documents/'

		),
		'google-fonts' => array(
			'plus_one' => true,
			'warning_condition' => 'banner_loader->show_google_fonts_notice',
			'success_conditions'  => array(
			),
			'open' => __( 'Google Fonts requires your attention.', 'complianz-gdpr' ) ." ". __( 'We have added additional support and recommend reviewing your settings.', 'complianz-gdpr' )." " . cmplz_sprintf( __( 'Please read this %sarticle%s to read our position on self-hosting Google Fonts and Privacy by Design.', 'complianz-gdpr' ),  '<a href="http://complianz.io/self-hosting-google-fonts-for-wordpress/" target="_blank">', '</a>'),
			'include_in_progress' => true,
			'url' => 'https://complianz.io/self-hosting-google-fonts-for-wordpress/',
		),

		'cookies-changed' => array(
			'plus_one' => true,
			'warning_condition' => 'scan->cookies_changed',
			'success_conditions'  => array(
			),
			'completed'    => __( 'No cookie changes have been detected.', 'complianz-gdpr' ),
			'open' => __( 'Cookie changes have been detected.', 'complianz-gdpr' ) . " " . __( 'Please review your cookies for changes.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),
		'no-cookie-scan' => array(
			'success_conditions'  => array(
				'banner_loader->get_last_cookie_scan_date',
			),
			'completed'    => cmplz_sprintf( __( 'Last site scan completed on %s.', 'complianz-gdpr' ), COMPLIANZ::$banner_loader->get_last_cookie_scan_date() ),
			'open' => __( 'No site scan has been completed yet.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => false,
		),

		'all-pages-created' => array(
			'warning_condition' => 'get_option_cmplz_wizard_completed_once',
			'success_conditions'  => array(
				'documents_admin->all_required_pages_created',
			),
			'completed'    => __( 'All required pages have been generated.', 'complianz-gdpr' ),
			'open' => __( 'Not all required pages have been generated.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),

		'hardening' => array(
			'warning_condition' => 'admin->no_security_plugin_active',
			'open' =>  __( "Harden your website and quickly detect vulnerabilities with Really Simple Security", 'complianz-gdpr' ),
			'include_in_progress' => true,
			'url' => '#tools/security'
		),

		'ga-needs-configuring'     => array(
			'warning_condition' => 'banner_loader->uses_google_analytics',
			'success_conditions'  => array(
				'banner_loader->analytics_configured',
			),
			'open' => __( 'Google Analytics is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),

		'gtm-needs-configuring'    => array(
			'warning_condition' => 'banner_loader->uses_google_tagmanager',
			'success_conditions'  => array(
				'banner_loader->tagmanager_configured',
			),
			'open' => __( 'Google Tag Manager is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),

		'matomo-needs-configuring' => array(
			'warning_condition' => 'banner_loader->uses_matomo',
			'success_conditions'  => array(
				'banner_loader->matomo_configured',
			),
			'open' => __( 'Matomo is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),
		'docs-need-updating'       => array(
			'success_conditions'  => array(
				'NOT document->documents_need_updating'
			),
			'open' => __( 'Your documents have not been updated in the past 12 months. Run the wizard to check your settings.', 'complianz-gdpr' ),
			'include_in_progress' => true,
		),
		'cookies-incomplete'       => array(
			'warning_condition' => 'NOT banner_loader->use_cdb_api',
			'success_conditions'  => array(
				'NOT sync->has_empty_cookie_descriptions',
			),
			'open' => __( 'You have cookies with incomplete descriptions.', 'complianz-gdpr' ) . " "
			          .  __( 'Enable the cookiedatabase.org API for automatic descriptions, or add these manually.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'url' => '#wizard/cookie-descriptions'
		),

		'double-stats' => array(
			'success_conditions'  => array(
				'NOT get_option_cmplz_double_stats',
			),
			'warning_condition' => 'cmplz_uses_statistics',
			'open' => __( 'You have a duplicate implementation of your statistics tool on your site.', 'complianz-gdpr' ) .
			          __( 'After the issue has been resolved, please re-run a scan to clear this message.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => true,
			'url' => 'https://complianz.io/duplicate-implementation-of-analytics/',
		),

		'console-errors' => array(
			'warning_condition' => 'banner_loader->site_needs_cookie_warning',
			'success_conditions'  => array(
				'NOT cmplz_get_console_errors',
			),
			'open' => __( 'JavaScript errors are detected on the front-end of your site. This may break the consent banner functionality.', 'complianz-gdpr' )
			          . '<br />'.__("Last error in the console:", "complianz-gdpr")
			          .'<div style="color:red">'
			          . cmplz_get_console_errors()
			          .'</div>',
			'include_in_progress' => true,
			'url' => 'https://complianz.io/cookie-banner-does-not-appear/',
		),

		'cookie-banner-enabled' => array(
			'success_conditions'  => array(
				'cmplz_cookiebanner_should_load(true)',
			),
			'completed' => __( 'Your site requires a consent banner, which has been enabled.', 'complianz-gdpr' ),
			'urgent' => __( 'Your site is not configured to show a consent banner at the moment.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => true,
			'url' => 'https://complianz.io/cookie-banner-does-not-appear/'
		),

		'pretty-permalinks-error' => array(
			'success_conditions'  => array(
				'get_option_permalink_structure',
			),
			'plus_one' => true,
			'urgent' => __( 'Pretty permalinks are not enabled on your site. This can cause issues with the REST API, used by Complianz.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => false,
			'url' => admin_url('options-permalink.php'),
		),
		'uploads-folder-writable' => array(
			'success_conditions'  => array(
				'cmplz_uploads_folder_writable',
			),
			'plus_one' => true,
			'urgent' => __( 'Your uploads folder is not writable. Complianz needs this folder to save the consent banner CSS.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => false,
			'url' => 'https://complianz.io/folder-permissions/'
		),
		'custom-google-maps' => array(
			'warning_condition' => 'cmplz_uses_google_maps',
			'success_conditions'  => array(
				'cmplz_google_maps_integration_enabled',
			),
			'plus_one' => false,
			'open' => __( 'We see you have enabled Google Maps as a service, but we can\'t find an integration. You can integrate manually if needed.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'url' => 'https://complianz.io/custom-google-maps-integration/',
		),

		'other-cookie-plugins' => array(
			'warning_condition'  => 'cmplz_detected_cookie_plugin',
			'plus_one' => true,
			'urgent' => cmplz_sprintf(__( 'We have detected the %s plugin on your website.', 'complianz-gdpr' ),cmplz_detected_cookie_plugin(true)).'&nbsp;'.__( 'As Complianz handles all the functionality this plugin provides, you should disable this plugin to prevent unexpected behaviour.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => false,
		),

		'advertising-enabled' => array(
			'warning_condition' => 'get_value_uses_ad_cookies==yes',
			'premium' => __( 'Are you showing ads on your site? Consider implementing TCF.', 'complianz-gdpr' ),
			'include_in_progress' => false,
			'dismissible' => false,
			'url' => 'https://complianz.io/implementing-tcf-on-your-website/',
		),

		'sync-privacy-statement' => array(
			'premium' => __( 'Create a Privacy Statement and other Legal Documents with Complianz.', 'complianz-gdpr' ),
			'include_in_progress' => false,
			'dismissible' => false,
			'url' => 'https://complianz.io/pricing/?src=cmplz-plugin',
		),

		'ecommerce-legal' => array(
			'warning_condition' => 'cmplz_ecommerce_legal',
			'premium' => __( 'Legal compliance for webshops.', 'complianz-gdpr' ),
			'include_in_progress' => false,
			'dismissible' => false,
			'url' => 'https://complianz.io/legal-compliance-for-ecommerce/',
		),

		'configure-tag-manager' => array(
			'warning_condition' => 'cmplz_uses_google_tagmanager_or_analytics',
			'premium' => __( 'Learn more about Google Consent Mode V2.', 'complianz-gdpr' ),
			'include_in_progress' => false,
			'dismissible' => false,
			'url' => 'https://complianz.io/consent-mode-for-7-0/'
		),

		'targeting-multiple-regions' => array(
			'warning_condition' => 'cmplz_targeting_multiple_regions',
			'premium' => __( 'Are you targeting multiple regions?', 'complianz-gdpr' ),
			'include_in_progress' => false,
			'dismissible' => false,
			'url' => 'https://complianz.io/what-regions-do-i-target/',
		),
		'textdomain-just-in-time-notice' => array(
			'dismissible' => true,
			'open' => __( 'You might see a notice about Complianz when debugging your WordPress website. Although not a breaking issue, pleasee know we\'re attending to this issue.', 'complianz-gdpr' ),
			'warning_condition' => (defined('WP_DEBUG') && WP_DEBUG) ? '_true_' : '_false_',
			'include_in_progress' => false,
			'url' => 'https://complianz.io/doing-it-wrong-notice',
		)
	) );
}
