<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_filter( 'cmplz_known_script_tags', 'cmplz_googlemaps_script' );
function cmplz_googlemaps_script( $tags ) {
	$tags[] = array(
		'name'        => 'google-maps',
		'placeholder' => 'google-maps',
		'category'    => 'marketing',
		'urls'        => array(
			'new google.maps.',
			'maps.google.com',
			'google.com/maps',
			'apis.google.com',
			'maps.google.de',
		),
	);
	return $tags;
}

add_filter( 'cmplz_image_tags', 'cmplz_googlemaps_imagetags' );
function cmplz_googlemaps_imagetags( $tags ) {
	$tags[] = 'maps.googleapis.com/maps/api/staticmap';
	return $tags;
}

/**
 * Whether a URL is a Google Static Maps image: http(s), host maps.googleapis.com, path /maps/api/staticmap.
 *
 * The image tag marker is only a substring match on the page, so this check decides what may be
 * downloaded: a URL on another host that merely mentions the marker in its path or query is not one.
 *
 * @param string $src Image URL as found in the page (may be entity-encoded).
 *
 * @return bool
 */
function cmplz_google_maps_is_static_map_url( $src ) {
	$src = html_entity_decode( (string) $src, ENT_QUOTES );
	if ( strpos( $src, '//' ) === 0 ) {
		$src = 'https:' . $src;
	}
	$parts = wp_parse_url( $src );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
		return false;
	}
	if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
		return false;
	}
	if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return false;
	}
	if ( strtolower( $parts['host'] ) !== 'maps.googleapis.com' ) {
		return false;
	}

	return strpos( $parts['path'], '/maps/api/staticmap' ) === 0;
}

/**
 * Declare a placeholder
 *
 * @param string $new_src
 * @param string $src
 *
 * @return mixed|string
 */
function cmplz_google_maps_placeholder( $new_src, $src ) {

	if ( cmplz_google_maps_is_static_map_url( $src ) ) {
		$id = str_replace( array( 'http://', 'https://', 'maps.googleapis.com/maps/api/staticmap' ), '', $src );
		// To prevent issues with the url as ID, we create a separate ID, and look it up by the url of this image.
		$cached = cmplz_get_transient( 'cmplz_googlemaps_image_' . sanitize_title( $id ) );

		// A cached local file is reused, and so is a cached fallback to a default placeholder after a failed
		// download: that is any URL outside the uploads directory (plugin asset, theme override or filter).
		if ( $cached && ( strpos( $cached, cmplz_upload_url() ) !== 0 || cmplz_file_exists_on_url( $cached ) ) ) {
			return $cached;
		}

		$guid    = time();
		$new_src = cmplz_download_to_site( html_entity_decode( $src ), sanitize_title( 'cmplz_googlemaps_image_' . $guid ), false );
		// A failed download is cached for a short while only, so a transient error does not stick for a month.
		$ttl = cmplz_file_exists_on_url( $new_src ) ? MONTH_IN_SECONDS : HOUR_IN_SECONDS;
		cmplz_set_transient( 'cmplz_googlemaps_image_' . sanitize_title( $id ), $new_src, $ttl );
	}
	return $new_src;
}

add_filter( 'cmplz_placeholder_google-maps', 'cmplz_google_maps_placeholder', 10, 2 );
