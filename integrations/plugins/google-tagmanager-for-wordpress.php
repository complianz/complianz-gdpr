<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Set analytics as suggested stats tool in the wizard
 */
add_filter( 'cmplz_default_value', 'cmplz_gtm4wp_set_default', 20, 3 );
/**
 * Set default value for compile_statistics field when GTM4WP is active
 *
 * @param mixed  $value     The current value.
 * @param string $fieldname The field name.
 * @param array  $field     The field configuration.
 *
 * @return mixed The modified value.
 */
function cmplz_gtm4wp_set_default( $value, $fieldname, $field ) {
	if ( 'compile_statistics' === $fieldname ) {
		return 'google-tag-manager';
	}
	return $value;
}

/**
 * Show compile statistics notice for GTM4WP
 *
 * @param array $notices The notices array.
 *
 * @return array The modified notices array.
 */
function cmplz_gtm4wp_show_compile_statistics_notice( $notices ) {
	$text = '';
	if ( cmplz_no_ip_addresses() ) {
		// translators: %s is the plugin name.
		$text .= cmplz_sprintf( __( 'You have selected you anonymize IP addresses. This setting is now enabled in %s.', 'complianz-gdpr' ), 'Google Tag Manager for WordPress' );
	}
	if ( cmplz_statistics_no_sharing_allowed() ) {
		// translators: %s is the plugin name.
		$text .= cmplz_sprintf( __( 'You have selected you do not share data with third-party networks. Remarketing is now disabled in %s.', 'complianz-gdpr' ), 'Google Tag Manager for WordPress' );
	}
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
		'text'     => cmplz_sprintf( __( 'You use %s, which means the answer to this question should be Google Tag Manager.', 'complianz-gdpr' ), 'Google Tag Manager for WordPress' )
						. ' ' . $text,
	);

	if ( $found_key ) {
		$notices[ $found_key ] = $notice;
	} else {
		$notices[] = $notice;
	}
	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_gtm4wp_show_compile_statistics_notice' );


/**
 * Configure options for GTM4WP
 */
function cmplz_gtm4wp_options() {
	if ( ! defined( 'GTM4WP_OPTIONS' ) ) {
		return;
	}

	$storedoptions = (array) get_option( GTM4WP_OPTIONS );
	$save          = false;

	if ( defined( 'GTM4WP_OPTION_INCLUDE_VISITOR_IP' ) ) {
		if ( isset( $storedoptions[ GTM4WP_OPTION_INCLUDE_VISITOR_IP ] ) ) {
			if ( cmplz_no_ip_addresses() && $storedoptions[ GTM4WP_OPTION_INCLUDE_VISITOR_IP ]
			) {
				$storedoptions[ GTM4WP_OPTION_INCLUDE_VISITOR_IP ] = false;
				$save = true;
			} elseif ( ! cmplz_no_ip_addresses() && (bool) $storedoptions[ GTM4WP_OPTION_INCLUDE_VISITOR_IP ]
			) {
				$save = true;
				$storedoptions[ GTM4WP_OPTION_INCLUDE_VISITOR_IP ] = true;
			}
		}
	}

	// Handle sharing of data.
	// Since 1.15.1 remarketing constant has been removed.
	if ( defined( 'GTM4WP_OPTION_INCLUDE_REMARKETING' ) ) {
		if ( isset( $storedoptions[ GTM4WP_OPTION_INCLUDE_REMARKETING ] ) ) {
			if ( cmplz_statistics_no_sharing_allowed()
				&& $storedoptions[ GTM4WP_OPTION_INCLUDE_REMARKETING ]
			) {
				$save = true;
				$storedoptions[ GTM4WP_OPTION_INCLUDE_REMARKETING ] = false;

			} elseif ( ! cmplz_statistics_no_sharing_allowed()
						&& ! $storedoptions[ GTM4WP_OPTION_INCLUDE_REMARKETING ]
			) {
				$save = true;
				$storedoptions[ GTM4WP_OPTION_INCLUDE_REMARKETING ] = true;
			}
		}
	}

	if ( $save ) {
		update_option( GTM4WP_OPTIONS, $storedoptions );
	}
}
add_action( 'admin_init', 'cmplz_gtm4wp_options' );

/**
 * Make sure there's no warning about configuring GA anymore
 *
 * @param array $warnings The warnings array.
 *
 * @return array The filtered warnings array.
 */
function cmplz_gtm4wp_filter_warnings( $warnings ) {
	unset( $warnings['gtm-needs-configuring'] );
	return $warnings;
}

add_filter( 'cmplz_warning_types', 'cmplz_gtm4wp_filter_warnings' );

/**
 * Hide the stats configuration options when gtm4wp is enabled.
 *
 * @param array $fields The fields array.
 *
 * @return array The filtered fields array.
 */
function cmplz_gtm4wp_filter_fields( $fields ) {
	$index = cmplz_get_field_index( 'compile_statistics_more_info_tag_manager', $fields );
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

add_filter( 'cmplz_fields', 'cmplz_gtm4wp_filter_fields', 2000, 1 );
