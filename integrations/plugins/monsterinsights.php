<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Set analytics as suggested stats tool in the wizard
 */
/**
 * Set default value for compile_statistics field when MonsterInsights is active
 *
 * @param mixed  $value     The current value.
 * @param string $fieldname The field name.
 * @param array  $field     The field configuration.
 *
 * @return mixed The modified value.
 */
function cmplz_monsterinsights_set_default( $value, $fieldname, $field ) {
	if ( 'compile_statistics' === $fieldname ) {
		return 'google-analytics';
	}
	return $value;
}
add_filter( 'cmplz_default_value', 'cmplz_monsterinsights_set_default', 20, 3 );

/**
 * Add blocked scripts
 *
 * @param array $tags The script tags array.
 *
 * @return array The modified script tags array.
 */
function cmplz_monsterinsights_script( $tags ) {
	$tags[] = array(
		'name'     => 'google-analytics',
		'category' => 'statistics',
		'urls'     => array(
			'monsterinsights_scroll_tracking_load',
			'google-analytics-premium/pro/assets/',
			'mi_version',
		),
	);

	return $tags;
}
add_filter( 'cmplz_known_script_tags', 'cmplz_monsterinsights_script' );

/**
 * Show compile statistics notice for MonsterInsights
 *
 * @param array $notices The notices array.
 *
 * @return array The modified notices array.
 */
function cmplz_monsterinsights_show_compile_statistics_notice( $notices ) {
	$text = '';
	if ( cmplz_no_ip_addresses() ) {
		$text .= __( 'You have selected you anonymize IP addresses. This setting is now enabled in MonsterInsights.', 'complianz-gdpr' );
	}

	if ( cmplz_statistics_no_sharing_allowed() ) {
		$text .= __( 'You have selected you do not share data with third-party networks. Demographics is now disabled in MonsterInsights.', 'complianz-gdpr' );
	}

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
		'text'     => cmplz_sprintf( __( 'You use %s, which means the answer to this question should be Google Analytics.', 'complianz-gdpr' ), 'Monsterinsights' )
						. ' ' . $text,
	);
	if ( $found_key ) {
		$notices[ $found_key ] = $notice;
	} else {
		$notices[] = $notice;
	}
	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_monsterinsights_show_compile_statistics_notice' );

/**
 * We remove some actions to integrate fully
 * */
function cmplz_monsterinsights_remove_scripts_others() {
	remove_action( 'wp_head', 'monsterinsights_tracking_script', 6 );
	remove_action( 'cmplz_statistics_script', array( COMPLIANZ::$banner_loader, 'get_statistics_script' ), 10 );
}
add_action( 'after_setup_theme', 'cmplz_monsterinsights_remove_scripts_others' );

/**
 * Execute the monsterinsights script at the right point
 */
add_action( 'cmplz_before_statistics_script', 'monsterinsights_tracking_script', 10, 1 );


/**
 * Hide the stats configuration options when monsterinsights is enabled.
 *
 * @param array $fields The fields array.
 *
 * @return array The filtered fields array.
 */
function cmplz_monsterinsights_filter_fields( $fields ) {
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
add_filter( 'cmplz_fields', 'cmplz_monsterinsights_filter_fields', 200 );

/**
 * Make sure there's no warning about configuring GA anymore
 *
 * @param array $warnings The warnings array.
 *
 * @return array The filtered warnings array.
 */
function cmplz_monsterinsights_filter_warnings( $warnings ) {
	unset( $warnings['ga-needs-configuring'] );
	return $warnings;
}

add_filter( 'cmplz_warning_types', 'cmplz_monsterinsights_filter_warnings' );

/**
 * Make sure Monsterinsights returns true for anonymize IP's when this option is selected in the wizard
 *
 * @param mixed  $value   The current value.
 * @param string $key     The option key.
 * @param mixed  $default The default value.
 *
 * @return bool The modified value.
 */
function cmplz_monsterinsights_force_anonymize_ips( $value, $key, $default ) {
	if ( cmplz_no_ip_addresses() ) {
		return true;
	}
	return $value;
}
add_filter( 'monsterinsights_get_option_anonymize_ips', 'cmplz_monsterinsights_force_anonymize_ips', 30, 3 );

/**
 * Make sure Monsterinsights returns false for third party sharing when this option is selected in the wizard
 *
 * @param mixed  $value   The current value.
 * @param string $key     The option key.
 * @param mixed  $default The default value.
 *
 * @return bool The modified value.
 */
function cmplz_monsterinsights_force_demographics( $value, $key, $default ) {
	if ( cmplz_statistics_no_sharing_allowed() ) {
		return false;
	}
	return $value;
}
add_filter( 'monsterinsights_get_option_demographics', 'cmplz_monsterinsights_force_demographics', 30, 3 );
