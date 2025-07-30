<?php
/**
 * FunnelKit integration for Complianz.
 *
 * This file customizes Complianz behavior for FunnelKit, including default statistics tool selection,
 * script detection, notices, social media detection, and field filtering.
 *
 * @package ComplianzIntegrations
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Set analytics as suggested statistics tool in the wizard (pre-checked).
 *
 * @param mixed  $value     The current value.
 * @param string $fieldname The field name.
 * @param mixed  $field     The field array. Unused.
 *
 * @noinspection PhpUnusedParameterInspection $field is required by filter signature but not used.
 *
 * @return mixed
 */
function cmplz_funnelkit_set_default( $value, $fieldname, $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'compile_statistics' === $fieldname ) {
		return 'google-analytics';
	}
	return $value;
}
add_filter( 'cmplz_default_value', 'cmplz_funnelkit_set_default', 20, 3 );

/**
 * Add FunnelKit script to known script tags.
 *
 * @param array $tags The current script tags.
 *
 * @return array
 */
function cmplz_funnelkit_script( $tags ) {
	$tags[] = array(
		'name'     => 'funnelkit',
		'category' => 'statistics',
		'urls'     => array(
			'tracks.min.js', // site wide tracking
			'native-tracks.min.js', // basic and pro, site-wide tracking
			'/plugins/funnel-builder/modules/optins/modules/optin-pages/assets/js/public.min.js', //FunnelKit Pages (Sales, Optin, OptinConfirmation, Upsells, Thank You Pages)
			'/plugins/funnel-builder/modules/checkouts/assets/js/public.min.js', // FunnelKit Checkout Pages
			'/plugins/funnel-builder-basic/modules/checkout/assets/js/public.min.js', // FunnelKit Checkout Pages - BASIC
			'/plugins/funnel-builder-pro/modules/checkout/assets/js/public.min.js', // FunnelKit Checkout Pages - PRO
		),
	);
	return $tags;
}
add_filter( 'cmplz_known_script_tags', 'cmplz_funnelkit_script' );

/**
 * Add notice to tell a user to choose Analytics.
 *
 * @param array $notices The current notices.
 *
 * @return array
 */
function cmplz_funnelkit_compile_statistics_notice( $notices ) {
	$text      = '';
	$found_key = false;
	// Find notice with field_id 'compile_statistics' and replace it with our own.
	foreach ( $notices as $key => $notice ) {
		if ( isset( $notice['field_id'] ) && 'compile_statistics' === $notice['field_id'] ) {
			$found_key = $key;
		}
	}
	// translators: %s: FunnelKit.
	$notice = array(
		'field_id' => 'compile_statistics',
		'label'    => 'default',
		// translators: %s: FunnelKit.
		'title'    => __( 'Statistics plugin detected', 'complianz-gdpr' ),
		// translators: %s: FunnelKit.
		'text'     => cmplz_sprintf( __( 'You use %s, which means the answer to this question should be Google Analytics.', 'complianz-gdpr' ), 'FunnelKit' )
					. ' ' . $text,
	);

	if ( false !== $found_key ) {
		$notices[ $found_key ] = $notice;
	} else {
		$notices[] = $notice;
	}
	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_funnelkit_compile_statistics_notice' );

/**
 * Add social media to the list of detected items, so it will get set as default, and will be added to the notice about it.
 *
 * @param array $social_media The current detected social media.
 *
 * @return array
 */
function cmplz_funnelkit_detected_social_media( $social_media ) {
	if ( ! in_array( 'facebook', $social_media, true ) ) {
		$social_media[] = 'facebook';
	}
	if ( ! in_array( 'tiktok', $social_media, true ) ) {
		$social_media[] = 'tiktok';
	}
	if ( ! in_array( 'pinterest', $social_media, true ) ) {
		$social_media[] = 'pinterest';
	}
	if ( ! in_array( 'snapchat', $social_media, true ) ) {
		$social_media[] = 'snapchat';
	}
	return $social_media;
}
add_filter( 'cmplz_detected_social_media', 'cmplz_funnelkit_detected_social_media' );

/**
 * Hide specific fields in Google Analytics settings.
 *
 * @param array $fields The fields array.
 *
 * @return array
 */
function cmplz_funnelkit_filter_fields( $fields ) {
	$index = cmplz_get_field_index( 'compile_statistics_more_info', $fields );
	if ( false !== $index ) {
		unset( $fields[ $index ]['help'] );
	}

	return cmplz_remove_field(
		$fields,
		array(
			'configuration_by_complianz',
			'ua_code',
			'aw_code',
			'additional_gtags_stats',
			'additional_gtags_marketing',
			'consent-mode',
			'gtag-basic-consent-mode',
			'cmplz-gtag-urlpassthrough',
			'cmplz-gtag-ads_data_redaction',
			'gtm_code',
			'cmplz-tm-template',
			'gtm_code_head',
		)
	);
}
add_filter( 'cmplz_fields', 'cmplz_funnelkit_filter_fields', 2000, 1 );
