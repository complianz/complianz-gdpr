<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Make sure it's set as not anonymous when tracking enabled
 *
 * @param bool $stats_category_required The current value.
 *
 * @return bool The modified value.
 */
function cmplz_wc_google_analytics_integration_set_statistics_required( $stats_category_required ) {
	$settings = get_option( 'woocommerce_google_analytics_settings' );
	if ( $settings && isset( $settings['ga_support_display_advertising'] ) && 'yes' === $settings['ga_support_display_advertising'] ) {
		$stats_category_required = true;
	}
	return $stats_category_required;
}
add_filter( 'cmplz_cookie_warning_required_stats', 'cmplz_wc_google_analytics_integration_set_statistics_required' );

/**
 * Set analytics as suggested stats tool in the wizard
 */

add_filter( 'cmplz_default_value', 'cmplz_wc_google_analytics_integration_set_default', 20, 3 );
/**
 * Set default value for compile_statistics field when WooCommerce Google Analytics Integration is active
 *
 * @param mixed  $value     The current value.
 * @param string $fieldname The field name.
 * @param array  $field     The field configuration.
 *
 * @return mixed The modified value.
 */
function cmplz_wc_google_analytics_integration_set_default( $value, $fieldname, $field ) {
	if ( 'compile_statistics' === $fieldname ) {
		return 'google-analytics';
	}
	return $value;
}
/**
 * If display ads is enabled, ensure a marketing category is added to the banner
 *
 * @param bool $uses_marketing_cookies The current value.
 *
 * @return bool The modified value.
 */
function cmplz_wc_google_analytics_integration_uses_marketing_cookies( $uses_marketing_cookies ) {
	$settings = get_option( 'woocommerce_google_analytics_settings' );
	if ( $settings && isset( $settings['ga_support_display_advertising'] ) && 'yes' === $settings['ga_support_display_advertising'] ) {
		$uses_marketing_cookies = true;
	}

	return $uses_marketing_cookies;
}
add_filter( 'cmplz_uses_marketing_cookies', 'cmplz_wc_google_analytics_integration_uses_marketing_cookies', 20, 2 );

add_filter( 'cmplz_known_script_tags', 'cmplz_wc_google_analytics_integration_script' );
/**
 * Add script tags for WooCommerce Google Analytics Integration
 *
 * @param array $tags The script tags array.
 *
 * @return array The modified script tags array.
 */
function cmplz_wc_google_analytics_integration_script( $tags ) {
	$tags[] = array(
		'name'     => 'google-analytics',
		'category' => 'statistics',
		'urls'     => array(
			'add_to_cart_button:not(.product_type_variable',
			"ga( 'send', 'pageview' )",
			'_gaq.push',
			'stats.g.doubleclick.net/dc.js',
			'gaProperty',
			'ga_orders',
		),
	);
	return $tags;
}

/**
 * Add notice to tell a user to choose Analytics
 *
 * @param array $notices The notices array.
 *
 * @return array The modified notices array.
 */
function cmplz_wc_google_analytics_integration_show_compile_statistics_notice( $notices ) {
	// Find notice with field_id 'compile_statistics' and replace it with our own.
	$found_key = false;
	foreach ( $notices as $key => $notice ) {
		if ( 'compile_statistics' === $notice['field_id'] ) {
			$found_key = $key;
		}
	}
	$notice = array(
		'field_id' => 'compile_statistics',
		'label'    => 'default',
		'title'    => __( 'Statistics plugin detected', 'complianz-gdpr' ),
		// translators: %s is the plugin name.
		'text'     => cmplz_sprintf( __( 'You use %s, which means the answer to this question should be Google Analytics.', 'complianz-gdpr' ), 'WooCommerce Google Analytics Integration' ),
	);
	if ( $found_key ) {
		$notices[ $found_key ] = $notice;
	} else {
		$notices[] = $notice;
	}
	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_wc_google_analytics_integration_show_compile_statistics_notice' );

/**
 * Hide the stats configuration options when wc_google_analytics_integration is enabled.
 *
 * @param array $fields The fields array.
 *
 * @return array The filtered fields array.
 */
function cmplz_wc_google_analytics_integration_filter_fields( array $fields ): array {
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
			'gtm_code_head',
			'cmplz-tm-template',
		)
	);
}
add_filter( 'cmplz_fields', 'cmplz_wc_google_analytics_integration_filter_fields', 200 );

/**
 * Make sure there's no warning about configuring GA anymore
 *
 * @param array $warnings The warnings array.
 *
 * @return array The filtered warnings array.
 */
function cmplz_wc_google_analytics_integration_filter_warnings( $warnings ) {
	unset( $warnings['ga-needs-configuring'] );
	return $warnings;
}
add_filter( 'cmplz_warning_types', 'cmplz_wc_google_analytics_integration_filter_warnings' );
