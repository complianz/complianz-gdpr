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
		$vimeo_id      = $matches[1];
		$transient_key = "cmplz_vimeo_image_$vimeo_id";
		$new_src       = cmplz_get_transient( $transient_key );

		if ( 'failed' === $new_src ) {
			// A recent lookup failed; back off instead of hitting the oEmbed endpoint on every page load.
			return $placeholder_src;
		}

		if ( ! $new_src || ! cmplz_file_exists_on_url( $new_src ) ) {
			$fetched_src = false;
			$response    = wp_remote_get( 'https://vimeo.com/api/oembed.json?url=' . $src );
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( ! empty( $data ) && ! empty( $data->thumbnail_url ) ) {
					$thumbnail_url = $data->thumbnail_url;

					// Try the thumbnail URL as returned first, as its query parameters can be required for a valid image.
					$fetched_src = cmplz_download_to_site( $thumbnail_url, 'vimeo' . $vimeo_id, false );
					if ( ! $fetched_src || ( function_exists( 'getimagesize' ) && false === getimagesize( $fetched_src ) ) ) {
						// Fall back to the URL without query parameters.
						$parsed_url  = wp_parse_url( $thumbnail_url );
						$clean_url   = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
						$fetched_src = cmplz_download_to_site( $clean_url, 'vimeo' . $vimeo_id, false );

						// Basic validation - check if it's actually an image.
						if ( $fetched_src && function_exists( 'getimagesize' ) && false === getimagesize( $fetched_src ) ) {
							// Invalid image, don't use it.
							$fetched_src = false;
						}
					}
				}
			}

			if ( $fetched_src ) {
				$placeholder_src = $fetched_src;
				cmplz_set_transient( $transient_key, $placeholder_src, WEEK_IN_SECONDS );
			} else {
				// Cache the failure for a short while, so we don't retry the oEmbed request on every page load.
				cmplz_set_transient( $transient_key, 'failed', HOUR_IN_SECONDS );
			}
		} else {
			$placeholder_src = $new_src;
		}
	}
	return $placeholder_src;
}

add_filter( 'cmplz_placeholder_vimeo', 'cmplz_vimeo_placeholder', 10, 2 );
