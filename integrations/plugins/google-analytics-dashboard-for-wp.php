<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Keep GADWP settings in sync with Complianz
 *
 * @param string $value   The current value.
 * @param string $key     The option key.
 * @param string $default The default value.
 *
 * @return mixed
 */
function cmplz_gadwp_options( $value, $key, $default ) {

	if ( 'anonymize_ips' === $key ) {
		if ( cmplz_no_ip_addresses() ) {
			return true;
		}

		return false;
	}

	if ( 'demographics' === $key ) {
		if ( cmplz_statistics_no_sharing_allowed() ) {
			return false;
		}

		return true;
	}
	return $value;
}
add_filter( 'exactmetrics_get_option', 'cmplz_gadwp_options', 10, 3 );
/**
 * Set analytics as suggested stats tool in the wizard
 *
 * @param mixed  $value     The current value.
 * @param string $fieldname The field name.
 * @param array  $field     The field configuration.
 *
 * @return mixed The modified value.
 */
function cmplz_gadwp_set_default( $value, $fieldname, $field ) {
	if ( 'compile_statistics' === $fieldname ) {
		return 'google-analytics';
	}
	return $value;
}
add_filter( 'cmplz_default_value', 'cmplz_gadwp_set_default', 20, 3 );

/**
 * Add notice to tell a user to choose Analytics
 *
 * @param array $notices The notices array.
 *
 * @return array The modified notices array.
 */
function cmplz_gadwp_show_compile_statistics_notice( $notices ) {
	$text = '';
	if ( cmplz_no_ip_addresses() ) {
		// translators: %s is the plugin name.
		$text .= cmplz_sprintf(
			__(
				'You have selected you anonymize IP addresses. This setting is now enabled in %s.',
				'complianz-gdpr'
			),
			'Google Analytics Dashboard for WP'
		);
	}
	if ( cmplz_statistics_no_sharing_allowed() ) {
		// translators: %s is the plugin name.
		$text .= cmplz_sprintf(
			__(
				'You have selected you do not share data with third-party networks. Display advertising is now disabled in %s.',
				'complianz-gdpr'
			),
			'Google Analytics Dashboard for WP'
		);
	}
	$found_key = false;
	// Find notice with field_id 'compile_statistics' and replace it with our own.
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
		'text'     => cmplz_sprintf( __( 'You use %s, which means the answer to this question should be Google Analytics.', 'complianz-gdpr' ), 'ExactMetrics' )
						. ' ' . $text,
	);

	if ( $found_key ) {
		$notices[ $found_key ] = $notice;
	} else {
		$notices[] = $notice;
	}
	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_gadwp_show_compile_statistics_notice' );

/**
 * Make sure there's no warning about configuring GA anymore
 *
 * @param array $warnings The warnings array.
 *
 * @return array The filtered warnings array.
 */
function cmplz_gadwp_filter_warnings( $warnings ) {
	unset( $warnings['ga-needs-configuring'] );
	unset( $warnings['gtm-needs-configuring'] );
	return $warnings;
}
add_filter( 'cmplz_warning_types', 'cmplz_gadwp_filter_warnings' );

/**
 * Hide the stats configuration options when gadwp is enabled.
 *
 * @param array $fields The fields array.
 *
 * @return array The filtered fields array.
 */
function cmplz_gadwp_filter_fields( $fields ) {

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
add_filter( 'cmplz_fields', 'cmplz_gadwp_filter_fields', 200, 1 );

/**
 * We remove some actions to integrate fully
 * */
function cmplz_gadwp_remove_scripts_others() {
	remove_action( 'cmplz_statistics_script', array( COMPLIANZ::$banner_loader, 'get_statistics_script' ), 10 );
}
add_action( 'after_setup_theme', 'cmplz_gadwp_remove_scripts_others' );
