<?php
defined( 'ABSPATH' ) or die();

/**
 * Creates an array of fields for the back-end
 * - a premium field can be marked as such with the premium label
 * 	'premium' => [
 *      'label' => __("premium label','complianz-gdpr') //if the label is different in premium
 *      'url' => 'https://complianz.io/pricing/',
 *      'default' => 'no', //if the default is different in premium
 *      'react_conditions //you can also override the default conditions
 *  ],
 * - react_conditions => conditions checked in the react app
 * - server_conditions => conditions which are checked on the server.
 * - revoke_consent_onchange => if the value changes, consent is revoked for all users
 * - example of a condition:
 *  react_conditions => [
 *  'relation' => 'AND', //can be AND, OR. You can nest conditions like in wordpress meta_queries
 *  [
 *      '!field_name' => 'field_value' //you can do a NOT condition with !.
 *  ]
 *  server_conditions => [
 *  'relation' => 'AND',
 *  [
 *      '!function_callback()' => true //with server side conditions, you can call a function with ()
 *  ]
 * ]
 *
 *  - Add a help text
 * 	'help'               => [
 *      'label' => 'default',
 *      'title' => __( "Configuration", "complianz-gdpr" ),
 *      'text'  => __( "The URL depends on your configuration of Matomo.", 'complianz-gdpr' ),
 *      'url'   => 'https://complianz.io/configuring-matomo-for-wordpress-with-complianz/',
 * ],
 *
 * - a tooltip or comment cannot contain html or a URL.
 *
 * @param $load_values //set false to prevent the values from loading. Use false when the value is not required
 *
 * @return array
 */

require_once( __DIR__ .'/fields/wizard/general.php' );
require_once( __DIR__ .'/fields/wizard/purposes.php' );
require_once( __DIR__ .'/fields/wizard/consent.php' );
require_once( __DIR__ .'/fields/wizard/services.php' );
require_once( __DIR__ .'/fields/wizard/plugins.php' );
require_once( __DIR__ .'/fields/wizard/cookiedatabase.php' );
require_once( __DIR__ .'/fields/wizard/documents.php' );
require_once( __DIR__ .'/fields/wizard/finish.php' );
require_once( __DIR__ .'/fields/general-settings/settings.php' );
require_once( __DIR__ .'/fields/tools/datarequests.php' );
require_once( __DIR__ .'/fields/tools/ab-testing.php' );
require_once( __DIR__ .'/fields/tools/placeholders.php' );
require_once( __DIR__ .'/fields/tools/processing-agreements.php' );
require_once( __DIR__ .'/fields/tools/proof-of-consent.php' );
require_once( __DIR__ .'/fields/tools/support.php' );
require_once( __DIR__ .'/fields/tools/security.php' );
require_once( __DIR__ .'/fields/tools/documents.php' );
require_once( __DIR__ .'/fields/tools/data.php' );
require_once( __DIR__ .'/fields/integrations/services.php' );
require_once( __DIR__ .'/disable-fields-filter.php' );
require_once( __DIR__ . '/fields/defaults.php' );

function cmplz_fields( $load_values = true, $options=false ): array {
	if ( ! cmplz_user_can_manage() ) {
		return [];
	}
	$stored_options = [];
	if ($load_values) {
		$stored_options = $options ?: get_option( 'cmplz_options' );
	}

	$fields = COMPLIANZ::$config->fields;

	$default_order_index = 10;
	foreach ( $fields as $key => $field ) {
		$fields[$key] = $field = wp_parse_args( $field, [
				'default' => '',
				'id' => false,
				'visible' => true,
				'disabled' => false,
				'order' => $default_order_index,
				]
		);

		$default_order_index+=10;
		//handle server side conditions. If front-end loaded, the conditions are not checked.
		if ( isset( $field['server_conditions'] ) && function_exists('cmplz_conditions_apply') ) {
			if ( ! cmplz_conditions_apply( $field['server_conditions'] ) ) {
				unset( $fields[ $key ] );
				continue;
			}
		}

		if ( $load_values ) {
			$value = $stored_options[ $field['id'] ] ?? false;
			$field['default'] = apply_filters( 'cmplz_default_value', $field['default'], $field['id'], $field );
			//the never_saved flag is used to determine if a radio field should be saved, to prevent empty values, as radio fields look completed, but might be empty.
			$never_saved = !isset( $stored_options[ $field['id'] ] );
			$field['never_saved'] = $never_saved;
			if ( $never_saved && !empty($field['default']) ) {
				$value = $field['default'];
			}

			/*
			 * Some fields are duplicate, but opposite, like safe_mode vs 'enable_cookie_blocker'.
			 * This function will get the value from the related field.
			 */
			$value = cmplz_maybe_get_from_source($value, $field);
			$field['value'] = apply_filters( 'cmplz_field_value_' . $field['id'], $value, $field );
			$fields[ $key ] = apply_filters( 'cmplz_field', $field, $field['id'] );
		}
	}

	$fields = apply_filters( 'cmplz_fields_values', $fields );
	uasort($fields, function($a, $b) {
		return $a["order"] - $b["order"];
	});
	return array_values( $fields );
}

/**
 * Some fields are duplicate, but opposite, like safe_mode vs 'enable_cookie_blocker'.
 * This function will get the value from its related field.
 * @param $value
 * @param $field
 *
 * @return int|mixed|string
 */
function cmplz_maybe_get_from_source($value, $field ){
	if ( !isset($field['source_id']) ) {
		return $value;
	}

	//get value from source
	$config_fields = COMPLIANZ::$config->fields;
	$config_ids = array_column($config_fields, 'id');
	$config_field_index = array_search( $field['source_id'], $config_ids);
	if ( $config_field_index === false ){
		return $value;
	}
	$source_field = $config_fields[$config_field_index];
	$options = get_option( 'cmplz_options' );
	$value        = $options[ $source_field['id'] ] ?? 'not-set';//the mapped value could be false.
	if ( $value !=='not-set' && isset($source_field['default']) ) {
		$value = $source_field['default'];
	}

	//map to source_mapping
	if ( isset($field['source_mapping']) ) {
		$source_mapping = $field['source_mapping'];
		if ( isset($source_mapping[$value]) ) {
			$value = $source_mapping[$value];
		}
	}
	return $value;
}
