<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_filter( 'cmplz_known_script_tags', 'cmplz_vimeo_iframetags' );
function cmplz_vimeo_iframetags( $tags ) {
	$tags[] = array(
		'name'        => 'vimeo',
		'placeholder' => 'vimeo',
		'category'    => 'statistics',
		'urls'        => array(
			'vimeo.com',
			'i.vimeocdn.com',
		),
	);
	return $tags;
}

add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_vimeo_whitelist' );
function cmplz_vimeo_whitelist( $tags ) {
	$tags[] = 'dnt=1';
	$tags[] = 'dnt=true';
	return $tags;
}

function cmplz_vimeo_placeholder( $placeholder_src, $src ) {
	// Get id, used only for storing in transient.
	$vimeo_pattern
		= '/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i';
	if ( preg_match( $vimeo_pattern, $src, $matches ) ) {
		$vimeo_id = $matches[1];
		$new_src  = cmplz_get_transient( "cmplz_vimeo_image_$vimeo_id" );
		if ( ! $new_src || ! cmplz_file_exists_on_url( $new_src ) ) {
			$response = wp_remote_get( 'http://vimeo.com/api/oembed.json?url=' . $src );
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( ! empty( $data ) && ! empty( $data->thumbnail_url ) ) {
					$thumbnail_url = $data->thumbnail_url;

					// Clean the URL by removing query parameters that might cause issues.
					$parsed_url = wp_parse_url( $thumbnail_url );
					$clean_url  = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];

					// Download the clean URL.
					$placeholder_src = cmplz_download_to_site( $clean_url, 'vimeo' . $vimeo_id );

					// Basic validation - check if it's actually an image.
					if ( $placeholder_src && function_exists( 'getimagesize' ) ) {
						$image_info = getimagesize( $placeholder_src );
						if ( false === $image_info ) {
							// Invalid image, don't use it.
							$placeholder_src = false;
						}
					}

					if ( $placeholder_src ) {
						cmplz_set_transient( "cmplz_vimeo_image_$vimeo_id", $placeholder_src, WEEK_IN_SECONDS );
					}
				}
			}
		} else {
			$placeholder_src = $new_src;
		}
	}
	return $placeholder_src;
}

add_filter( 'cmplz_placeholder_vimeo', 'cmplz_vimeo_placeholder', 10, 2 );
