<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );
/**
 *
 * API for Gutenberg blocks
 *
 * @return array documents (id, title, content)
 */

add_action( 'rest_api_init', 'cmplz_documents_rest_route' );
function cmplz_documents_rest_route() {
	if ( isset( $_GET['locale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch_to_locale( sanitize_text_field( wp_unslash( $_GET['locale'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	register_rest_route(
		'complianz/v1',
		'documents/',
		array(
			'methods'             => 'GET',
			'callback'            => 'cmplz_rest_api_documents',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'complianz/v1',
		'banner/',
		array(
			'methods'             => 'GET',
			'callback'            => 'cmplz_rest_api_banner_data',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'complianz/v1',
		'track/',
		array(
			'methods'             => 'POST',
			'callback'            => 'cmplz_rest_api_ajax_track_status',
			'args'                => array(),
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'complianz/v1',
		'manage_consent_html/',
		array(
			'methods'             => 'GET',
			'callback'            => 'cmplz_rest_api_manage_consent_html',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'complianz/v1',
		'store_cookies/',
		array(
			'methods'             => 'POST',
			'callback'            => 'cmplz_store_detected_cookies',
			'permission_callback' => function () {
				return cmplz_user_can_manage();
			},
		)
	);

	$id_pattern     = '([0-9]+)';
	$string_pattern = '([a-z0-9-_]+)';
	register_rest_route(
		'complianz/v1',
		'consent-area/(?P<post_id>' . $id_pattern . ')/(?P<block_id>' . $string_pattern . ')',
		array(
			'methods'             => 'GET',
			'callback'            => 'cmplz_rest_consented_content',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Output category consent checkboxes html
 */
function cmplz_rest_consented_content( WP_REST_Request $request ) {
	$post_id  = (int) ( $request->get_param( 'post_id' ) );
	$block_id = sanitize_title( $request->get_param( 'block_id' ) );
	$post     = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post->ID ) ) {
		return new WP_Error( 'rest_forbidden', '', array( 'status' => 403 ) );
	}

	$html   = $post->post_content;
	$output = '';
	if ( has_block( 'complianz/consent-area', $html ) ) {
		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			if ( 'complianz/consent-area' === $block['blockName'] && $block['attrs']['blockId'] === $block_id ) {
				$output = $block['attrs']['consentedContent'];
				break;
			}
		}
	} elseif ( strpos( $html, '[cmplz-consent-area' ) !== false ) {
		// get content of the shortcode.
		$pattern = '/\[cmplz-consent-area[^\]]*?id=[\"\']' . $block_id . '[\"\'][^\]]*?\](.*?)\[\/cmplz-consent-area\]/is';
		if ( 'default' === $block_id ) {
			$pattern = '/\[cmplz-consent-area[^\]]*?\](.*?)\[\/cmplz-consent-area\]/is';
		}
		if ( preg_match( $pattern, $post->post_content, $matches ) ) {
			$output = $matches[1] ?? '';
		}
	}

	$output = do_shortcode( $output );
	if ( ob_get_length() ) {
		ob_clean();
	}
	return $output;
}

/**
 * Track the status selected by the user, for statistics.
 * */
function cmplz_rest_api_ajax_track_status( WP_REST_Request $request ) {
	$params               = $request->get_json_params();
	$consented_categories = isset( $params['consented_categories'] ) ? array_map( 'sanitize_title', $params['consented_categories'] ) : array( 'no_choice' );
	$consented_services   = isset( $params['consented_services'] ) ? array_map( 'sanitize_title', $params['consented_services'] ) : array();
	$consenttype          = isset( $params['consenttype'] ) ? sanitize_title( $params['consenttype'] ) : COMPLIANZ::$company->get_default_consenttype();
	$prefix               = COMPLIANZ::$banner_loader->get_cookie_prefix();
	foreach ( $consented_categories as $key => $consented_category ) {
		$consented_categories[ $key ] = str_replace( $prefix, '', $consented_category );
	}
	do_action( 'cmplz_store_consent', $consented_categories, $consented_services, $consenttype );

	$response = array(
		'success' => true,
	);
	if ( ob_get_length() ) {
		ob_clean();
	}
	return $response;
}

/**
 * Get Banner data
 *
 * @param WP_REST_Request $request
 */
function cmplz_rest_api_banner_data( WP_REST_Request $request ) {

	/**
	 * By default, the region which is returned is the region as selected in the wizard settings.
	 * */

	$region                   = apply_filters( 'cmplz_user_region', COMPLIANZ::$company->get_default_region() );
	$data                     = apply_filters( 'cmplz_user_data', array() );
	$data['consenttype']      = apply_filters( 'cmplz_user_consenttype', COMPLIANZ::$company->get_default_consenttype() );
	$data['region']           = $region;
	$data['version']          = CMPLZ_VERSION;
	$data['forceEnableStats'] = ! COMPLIANZ::$banner_loader->cookie_warning_required_stats( $region );
	// We need this here because the integrations are not loaded yet, so the filter will return empty, overwriting the loaded data.
	unset( $data['set_cookies'] );
	$banner_id              = cmplz_get_default_banner_id();
	$banner                 = cmplz_get_cookiebanner( $banner_id );
	$data['banner_version'] = $banner->banner_version;
	$data                   = apply_filters( 'cmplz_ajax_loaded_banner_data', $data );

	if ( ob_get_length() ) {
		ob_clean();
	}
	return $data;
}

/**
 * @param WP_REST_Request $request
 *
 * @return array
 */
function cmplz_rest_api_documents( WP_REST_Request $request ) {
	$documents = COMPLIANZ::$document->get_required_pages();
	$output    = array();
	if ( is_array( $documents ) ) {
		foreach ( $documents as $region => $region_documents ) {
			foreach ( $region_documents as $type => $document ) {
				$html       = COMPLIANZ::$document->get_document_html( $type, $region );
				$region_ext = ( 'eu' === $region ) ? '' : '-' . $region;
				$output[]   = array(
					'id'      => $type . $region_ext,
					'title'   => $document['title'],
					'content' => $html,
				);
			}
		}
	}
	if ( ob_get_length() ) {
		ob_clean();
	}
	return $output;
}


/**
 * Output category consent checkboxes html
 */
function cmplz_rest_api_manage_consent_html( WP_REST_Request $request ) {
	$html         = '';
	$do_not_track = cmplz_dnt_enabled();
	if ( $do_not_track ) {
		$html
			= cmplz_sprintf(
				// translators: %s is the site name.
				_x(
					'We have received a privacy signal from your browser. For this reason we have set your privacy settings on this website to strictly necessary. If you want to have full functionality, please consider excluding %s from your privacy settings.',
					'cookie policy',
					'complianz-gdpr'
				),
				site_url()
			);
	} else {
		$consent_type = apply_filters( 'cmplz_user_consenttype', COMPLIANZ::$company->get_default_consenttype() );
		$path         = trailingslashit( CMPLZ_PATH ) . 'cookiebanner/templates/';
		$banner_html  = cmplz_get_template( 'cookiebanner.php', array( 'consent_type' => $consent_type ), $path );
		$banner_html  = apply_filters( 'cmplz_banner_html', $banner_html );
		if ( preg_match( '/<!-- categories start -->(.*?)<!-- categories end -->/s', $banner_html, $matches ) ) {
			$html            = $matches[0];
			$banner_id       = apply_filters( 'cmplz_user_banner_id', cmplz_get_default_banner_id() );
			$banner          = cmplz_get_cookiebanner( $banner_id );
			$cookie_settings = $banner->get_html_settings();

			foreach ( $cookie_settings as $fieldname => $value ) {
				if ( isset( $value['text'] ) ) {
					$value = $value['text'];
				}
				if ( is_array( $value ) ) {
					continue;
				}
				$html = str_replace( '{' . $fieldname . '}', $value, $html );
			}
		}
	}
	if ( ob_get_length() ) {
		ob_clean();
	}
	return $html;
}


/**
 * Store the detected cookies in the cookies table
 */
function cmplz_store_detected_cookies( WP_REST_Request $request ) {
	$params = $request->get_json_params();

	if ( ! cmplz_user_can_manage() ) {
		return;
	}
	if ( isset( $params['token'] ) && wp_verify_nonce( $params['token'], 'complianz_scan_token' ) ) {
		$post_cookies = isset( $params['cookies'] ) && is_array( $params['cookies'] ) ? $params['cookies'] : array();
		$cookies      = array_map(
			function ( $el ) {
				return sanitize_title( $el );
			},
			$post_cookies
		);
		if ( ! is_array( $cookies ) ) {
			$cookies = array();
		}

		$post_storage = isset( $params['lstorage'] ) && is_array( $params['lstorage'] ) ? $params['lstorage'] : array();
		$localstorage = array_map(
			function ( $el ) {
				return sanitize_title( $el );
			},
			$post_storage
		);
		if ( ! is_array( $localstorage ) ) {
			$localstorage = array();
		}

		// Add local storage data.
		$localstorage = array_map( 'sanitize_text_field', $localstorage );
		foreach ( $localstorage as $key => $value ) {

			// Let's skip cookies with this site url in the name.
			if ( strpos( $key, site_url() ) !== false ) {
				continue;
			}
			if ( apply_filters( 'cmplz_exclude_from_scan', false, $key, 'localstorage' ) ) {
				continue;
			}

			$cookie = new CMPLZ_COOKIE();
			$cookie->add( $key, COMPLIANZ::$banner_loader->get_supported_languages() );
			$cookie->type   = 'localstorage';
			$cookie->domain = 'self';
			$cookie->save( true );
		}

		// Add cookies.
		$cookies = apply_filters( 'cmplz_detected_cookies', array_merge( $cookies, $_COOKIE ) );
		$cookies = array_map( 'sanitize_text_field', $cookies );
		foreach ( $cookies as $key => $value ) {
			// Let's skip cookies with this site url in the name.
			if ( strpos( $key, site_url() ) !== false ) {
				continue;
			}
			if ( apply_filters( 'cmplz_exclude_from_scan', false, $key, 'cookie' ) ) {
				continue;
			}

			$cookie = new CMPLZ_COOKIE();
			$cookie->add( $key, COMPLIANZ::$banner_loader->get_supported_languages() );
			$cookie->type = 'cookie';
			// What we detect here is only on the own domain.
			$cookie->domain = 'self';
			$cookie->save( true );
		}

		// Store current requested page.
		COMPLIANZ::$scan->set_page_as_processed( $params['complianz_id'] );
	}
}
