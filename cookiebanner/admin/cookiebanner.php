<?php
/**
 * Add api to retrieve banner html
 *
 * @param array           $data
 * @param string          $action
 * @param WP_REST_Request $request
 *
 * @return array
 */
function cmplz_banner_data( array $data, string $action, WP_REST_Request $request ): array {
	if ( ! cmplz_user_can_manage() ) {
		return array();
	}
	if ( $action === 'update_banner_data' ) {
		$data      = $request->get_json_params();
		$fields    = $data['fields'] ?? array();
		$banner_id = $data['banner_id'] ?? 0;
		$banner    = cmplz_get_cookiebanner( $banner_id );
		foreach ( $banner as $property => $value ) {
			$field_index = array_search( $property, array_column( $fields, 'id' ), true );
			if ( $field_index !== false ) {
				$value               = $fields[ $field_index ]['value'];
				$banner->{$property} = $value;
			}
		}
		$banner->save();
		$data = array();
	}

	if ( $action === 'get_debug_data' ) {
		$data = array(
			'script_debug_enabled' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'debug_data'           => get_option( 'cmplz_detected_console_errors' ),
		);
	}

	if ( $action === 'get_banner_data' ) {
		// ensure table is installed
		cmplz_install_cookiebanner_table( true );

		cmplz_check_minimum_one_banner();
		$banners    = cmplz_get_cookiebanners();
		$uploads    = wp_upload_dir();
		$upload_url = is_ssl() ? str_replace( 'http://', 'https://', $uploads['baseurl'] ) : $uploads['baseurl'];
		// check if the css file exists. if not, use default.
		$css_file        = $upload_url . '/complianz/css/banner-{banner_id}-{type}.css';
		$ordered_banners = array();

		// first add the default banner to the $ordered_banners array to ensure it is the first.
		foreach ( $banners as $index => $banner ) {
			if ( $banner->default ) {
				$ordered_banners[] = $banner;
				unset( $banners[ $index ] );
			}
		}
		// now add the remaining banners to the $ordered_banners array
		foreach ( $banners as $banner ) {
			$ordered_banners[] = $banner;
		}

		// leave only two banners. More are a legacy function, and not supported anymore.
		$banners        = array_slice( $ordered_banners, 0, 2 );
		$object_banners = array();
		foreach ( $banners as $banner ) {
			$object_banners[] = new CMPLZ_COOKIEBANNER( $banner->ID, true, true );
		}
		$path                = trailingslashit( CMPLZ_PATH ) . 'cookiebanner/templates/';
		$banner_html         = cmplz_get_template( 'cookiebanner.php', array(), $path );
		$manage_consent_html = cmplz_get_template( 'manage-consent.php', false, $path );
		$page_links          = COMPLIANZ::$document->get_page_links();
		$region              = COMPLIANZ::$company->get_company_region_code();
		$data                = array(
			'banner_html'          => apply_filters( 'cmplz_banner_html', $banner_html ),
			'manage_consent_html'  => apply_filters( 'cmplz_manage_consent_html', $manage_consent_html ),
			'consent_types'        => cmplz_get_used_consenttypes( true ),
			'default_consent_type' => COMPLIANZ::$company->get_default_consenttype(),
			'banners'              => $object_banners,
			'css_file'             => $css_file,
			'page_links'           => $page_links[ $region ] ?? array(),
			'customize_url'        => wp_customize_url(),
			'tcf_active'           => cmplz_tcf_active(),
		);
	}

	if ( $action === 'generate_preview_css' ) {
		$data      = $request->get_json_params();
		$banner_id = $data['banner_id'] ?? cmplz_get_default_banner_id();
		$fields    = $data['fields'] ?? array();
		$banner    = cmplz_get_cookiebanner( (int) $banner_id );
		foreach ( $fields as $field ) {
			if ( property_exists( $banner, $field['id'] ) ) {
				$banner->{$field['id']} = $field['value'];
			}
		}
		$banner->generate_css( true );

		$data = array( 'success' => true );
	}

	return $data;
}
add_filter( 'cmplz_do_action', 'cmplz_banner_data', 10, 3 );

/**
 * Adjust banner text value when advertising settings change
 *
 * @param string $name
 * @param mixed  $value
 * @param mixed  $prev_value
 * @param string $type
 *
 * @return void
 */
function cmplz_banner_adjustments_for_wizard_changes( string $name, $value, $prev_value, $type ) {
	if ( ! cmplz_user_can_manage() ) {
		return;
	}

	if ( $name === 'uses_ad_cookies_personalized' && ( $value === 'yes' || $value === 'tcf' ) ) {
		$banner_text = __( 'We use technologies like cookies to store and/or access device information. We do this to improve browsing experience and to show (non-) personalized ads. Consenting to these technologies will allow us to process data such as browsing behavior or unique IDs on this site. Not consenting or withdrawing consent, may adversely affect certain features and functions.', 'complianz-gdpr' );
		$banners     = cmplz_get_cookiebanners();
		if ( $banners ) {
			foreach ( $banners as $banner_item ) {
				$banner                = cmplz_get_cookiebanner( $banner_item->ID );
				$banner->message_optin = $banner_text;
				$banner->save();
			}
		}
	}

	// if a/b testing is enabled, ensure that there are two banners.
	if ( $name === 'a_b_testing_buttons' && $value ) {
		$banners = cmplz_get_cookiebanners();
		if ( count( $banners ) < 1 ) {
			$banner        = new CMPLZ_COOKIEBANNER();
			$banner->title = __( 'Banner A', 'complianz-gdpr' );
			$banner->save();
		}

		if ( count( $banners ) < 2 ) {
			$banner        = new CMPLZ_COOKIEBANNER();
			$banner->title = __( 'Banner B', 'complianz-gdpr' );
			$banner->save();
		}
	}

	if ( $name === 'enable_cookie_banner' && $value === 'yes' ) {
		cmplz_update_all_banners();
	}
}
add_action( 'cmplz_after_save_field', 'cmplz_banner_adjustments_for_wizard_changes', 20, 4 );

if ( ! function_exists( 'cmplz_cookiebanner_should_load' ) ) {
	function cmplz_cookiebanner_should_load( $check_banner_disabled = false ) {
		$wizard_completed     = COMPLIANZ::$banner_loader->wizard_completed_once();
		$needs_cookie_warning = COMPLIANZ::$banner_loader->site_needs_cookie_warning();
		if ( $check_banner_disabled ) {
			$default_banner = cmplz_get_default_banner_id();
			$banner         = cmplz_get_cookiebanner( $default_banner );
			return $wizard_completed && $needs_cookie_warning && ! $banner->disable_cookiebanner;
		}

		return $wizard_completed && $needs_cookie_warning;
	}
}
/**
 * Regenerate css and update banner version for all banners
 */
if ( ! function_exists( 'cmplz_update_all_banners' ) ) {
	function cmplz_update_all_banners() {
		if ( ! cmplz_user_can_manage() ) {
			return;
		}
		$banners = cmplz_get_cookiebanners();
		if ( $banners ) {
			foreach ( $banners as $banner_item ) {
				$banner = cmplz_get_cookiebanner( $banner_item->ID );
				$banner->save();
			}
		}
	}
}

if ( ! function_exists( 'cmplz_uploads_folder_not_writable' ) ) {
	/**
	 * Check if folder is writable
	 *
	 * @return bool
	 */
	function cmplz_uploads_folder_writable() {
		return is_writable( cmplz_upload_dir() );
	}
}

/**
 * Register banner logo image size
 */
function cmplz_register_banner_logo_size() {
	if ( ! cmplz_user_can_manage() ) {
		return;
	}
	$add_image_size = false;
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'upload-attachment' ) {
		$add_image_size = true;
	}
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'complianz' ) {
		$add_image_size = true;
	}

	if ( cmplz_is_logged_in_rest() ) {
		$add_image_size = true;
	}

	if ( $add_image_size && function_exists( 'add_image_size' ) ) {
		add_image_size( 'cmplz_banner_image', 350, 100, true );
	}
}
add_action( 'admin_init', 'cmplz_register_banner_logo_size' );

/**
 * Register our custom logo size so we can use it in the media uploader
 *
 * @param $response
 * @param $attachment
 * @param $meta
 *
 * @return array
 */
function cmplz_image_sizes_js( $response, $attachment, $meta ) {
	if ( isset( $meta['sizes']['cmplz_banner_image'] ) ) {
		$attachment_url = wp_get_attachment_url( $attachment->ID );
		$base_url       = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
		$size_meta      = $meta['sizes']['cmplz_banner_image'];

		$response['sizes']['cmplz_banner_image'] = array(
			'height'      => $size_meta['height'],
			'width'       => $size_meta['width'],
			'url'         => $base_url . $size_meta['file'],
			'orientation' => $size_meta['height'] > $size_meta['width'] ? 'portrait' : 'landscape',
		);

	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'cmplz_image_sizes_js', 10, 3 );

function cmplz_check_minimum_one_banner() {
	$admin_get_request = cmplz_admin_logged_in() && isset( $_GET['page'] ) && strpos( $_GET['page'], 'complianz' ) !== false;
	$rest_request      = cmplz_is_logged_in_rest();

	if ( ! $admin_get_request && ! $rest_request ) {
		return;
	}

	// make sure there's at least one banner
	$cookiebanners = cmplz_get_cookiebanners();
	$added_banner  = false;
	if ( count( $cookiebanners ) < 1 ) {
		$banner        = new CMPLZ_COOKIEBANNER();
		$banner->title = __( 'Banner A', 'complianz-gdpr' );
		$banner->save();
		$added_banner = true;
	}

	// if we have one (active) banner, but it's not default, make it default
	if ( $added_banner ) {
		$cookiebanners = cmplz_get_cookiebanners();
	}
	if ( count( $cookiebanners ) == 1 && ! $cookiebanners[0]->default ) {
		$banner = cmplz_get_cookiebanner( $cookiebanners[0]->ID );
		$banner->enable_default();
	}
}
add_action( 'admin_init', 'cmplz_check_minimum_one_banner' );


add_action( 'cmplz_cookiebanner_menu', 'cmplz_cookiebanner_admin_menu' );
function cmplz_cookiebanner_admin_menu() {
	if ( ! cmplz_user_can_manage() ) {
		return;
	}

	add_submenu_page(
		'complianz',
		__( 'Consent Banner', 'complianz-gdpr' ),
		__( 'Consent Banner', 'complianz-gdpr' ),
		apply_filters( 'cmplz_capability', 'manage_privacy' ),
		'complianz#banner',
		'cmplz_cookiebanner_overview'
	);
}

/**
 * Show meta box on edit pages
 *
 * @param $post_type
 */
function cmplz_add_hide_cookiebanner_meta_box( $post_type ) {
	if ( ! is_post_type_viewable( $post_type ) ) {
		return;
	}
	if ( ! cmplz_user_can_manage() ) {
		return;
	}

	// Flag the metabox as RTC-compatible so WordPress Real Time Collaboration
	// stays enabled on the block-editor screen when Complianz is active.
	add_meta_box( 'cmplz_hide_banner_meta_box', __( 'Cookiebanner', 'complianz-gdpr' ), 'cmplz_hide_cookiebanner_metabox', null, 'side', 'default', array( '__rtc_compatible_meta_box' => true ) );
}
add_action( 'add_meta_boxes', 'cmplz_add_hide_cookiebanner_meta_box' );

/**
 * Render meta box
 */
function cmplz_hide_cookiebanner_metabox() {
	if ( ! cmplz_user_can_manage() ) {
		return;
	}

	wp_nonce_field( 'cmplz_cookiebanner_hide_nonce', 'cmplz_cookiebanner_hide_nonce' );
	global $post;

	if ( ! $post ) {
		return;
	}

	// if there's no slug, don't offer this option
	if ( empty( $post->post_name ) ) {
		return;
	}

	$option_label = __( 'Disable Complianz on this page', 'complianz-gdpr' );
	$disabled     = cmplz_page_is_of_type( 'cookie-statement' ) ? 'disabled' : false;
	$checked      = ! $disabled && get_post_meta( $post->ID, 'cmplz_hide_cookiebanner', true ) ? 'checked' : '';

	echo '<label><input type="checkbox" ' . $checked . ' name="cmplz_hide_cookiebanner" value="1" ' . $disabled . ' />' . $option_label;
	if ( $disabled ) {
		echo '<br /><i>' . __( 'On a cookie policy, the banner will be minimized by default', 'complianz-gdpr' ) . '</i>';
	}
	echo '</label>';
}

/**
 * Save the chosen selection to hide the cookiebanner on a page
 *
 * @param int     $post_ID
 * @param WP_POST $post
 * @param bool    $update
 */
function cmplz_save_hide_page_cookiebanner_option( $post_ID, $post, $update ) {
	if ( ! $update ) {
		return;
	}

	if ( ! cmplz_user_can_manage() ) {
		return;
	}

	// check if this isn't an auto save
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// security check
	if ( ! isset( $_POST['cmplz_cookiebanner_hide_nonce'] ) || ! wp_verify_nonce( $_POST['cmplz_cookiebanner_hide_nonce'], 'cmplz_cookiebanner_hide_nonce' ) ) {
		return;
	}

	$hide = isset( $_POST['cmplz_hide_cookiebanner'] ) ? true : false;
	update_post_meta( $post_ID, 'cmplz_hide_cookiebanner', $hide );
	$excluded_posts_array = get_option( 'cmplz_excluded_posts_array', array() );
	$slug                 = $post->post_name;

	if ( $hide ) {
		if ( ! in_array( $slug, $excluded_posts_array ) ) {
			$excluded_posts_array[] = $slug;
		}
	} else {
		$key = array_search( $slug, $excluded_posts_array );
		if ( $key !== false ) {
			unset( $excluded_posts_array[ $key ] );
		}
	}

	update_option( 'cmplz_excluded_posts_array', array_filter( $excluded_posts_array ) );
}
add_action( 'save_post', 'cmplz_save_hide_page_cookiebanner_option', 10, 3 );
