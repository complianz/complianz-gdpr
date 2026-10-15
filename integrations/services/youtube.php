<?php
defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );
add_filter( 'cmplz_known_script_tags', 'cmplz_youtube_script' );
function cmplz_youtube_script( $tags ) {
	$tags[] = array(
		'name'        => 'youtube',
		'placeholder' => 'youtube',
		'category'    => 'marketing',
		'urls'        => array(
			'www.youtube.com/iframe_api',
			'youtube.com',
			'youtube-nocookie.com',
			'youtu.be',
		),
	);
	return $tags;
}

/**
 * Get the first video id from a video series
 *
 * @param string $src
 *
 * @return string
 */

/**
 * Whether $src is a YouTube URL that is safe to fetch server-side.
 *
 * The YouTube placeholder detection upstream is intentionally permissive
 * (substring match + greedy regex), so the actual server-side fetch must verify
 * that the parsed host is a real YouTube domain over http(s) before any request
 * is made. Prevents SSRF via a crafted iframe src pointing at an arbitrary host.
 *
 * @param string $src URL to validate.
 * @return bool True when $src targets an allowed YouTube host over http(s).
 */
function cmplz_youtube_is_fetchable_url( $src ) {
	$parts  = wp_parse_url( $src );
	$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
	$host   = strtolower( rtrim( (string) ( $parts['host'] ?? '' ), '.' ) );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host ) {
		return false;
	}
	$allowed_hosts = array( 'youtube.com', 'youtube-nocookie.com', 'youtu.be' );
	foreach ( $allowed_hosts as $allowed_host ) {
		if ( $host === $allowed_host || substr( $host, - ( strlen( $allowed_host ) + 1 ) ) === '.' . $allowed_host ) {
			return true;
		}
	}
	return false;
}

function cmplz_youtube_get_video_id_from_series( $src ) {
	// SSRF guard: only fetch real YouTube hosts, and use wp_safe_remote_get() so
	// WordPress re-validates every redirect hop and rejects private/reserved IPs.
	if ( ! cmplz_youtube_is_fetchable_url( $src ) ) {
		return false;
	}

	$output     = wp_safe_remote_get( $src, array( 'redirection' => 2 ) );
	$youtube_id = false;
	if ( isset( $output['body'] ) ) {
		$body           = $output['body'];
		$body           = stripcslashes( $body );
		$series_pattern = '/VIDEO_ID\': "([^#\&\?].*?)"/i';
		if ( preg_match( $series_pattern, $body, $matches ) ) {
			$youtube_id = $matches[1];
		}
	}
	return $youtube_id;
}

/**
 * Get screenshot from youtube as placeholder
 *
 * @param $new_src
 * @param $src
 *
 * @return mixed|string
 */
function cmplz_youtube_placeholder( $new_src, $src ) {
	$youtube_pattern = '/.*(?:youtu.be\/|v\/|u\/\w\/|embed\/videoseries\?list=RD|embed\/|watch\?v=)([^#\&\?]*).*/i';
	if ( preg_match( $youtube_pattern, $src, $matches ) ) {
		$youtube_id = $matches[1];
		// check if it's a video series. If so, we get the first video
		if ( $youtube_id === 'videoseries' ) {
			// get the videoseries id
			$series_pattern = '/.*(?:youtu.be\/|v\/|u\/\w\/|embed\/videoseries\?list=RD|embed\/|watch\?v=)[^#\&\?]*\?list=(.*)/i';
			// if we find the unique id, we save it in the cache
			if ( preg_match( $series_pattern, $src, $matches ) ) {
				$series_id = $matches[1];

				$youtube_id = cmplz_get_transient( "cmplz_youtube_videoseries_video_id_$series_id" );
				if ( ! $youtube_id ) {
					// we do a get on the url to retrieve the first video
					$youtube_id = cmplz_youtube_get_video_id_from_series( $src );
					cmplz_set_transient( "cmplz_youtube_videoseries_video_id_$series_id", $youtube_id, WEEK_IN_SECONDS );
				}
			} else {
				$youtube_id = cmplz_youtube_get_video_id_from_series( $src );
			}
		}
		/**
		 * The highest resolution of youtube thumbnail is the maxres, but it does not
		 * always exist. In that case, we take the hq thumb
		 * To lower the number of file exists checks, we cache the result.
		 * */
		$new_src = cmplz_get_transient( "cmplz_youtube_image_$youtube_id" );
		if ( ! $new_src || ! cmplz_file_exists_on_url( $new_src ) ) {
			$new_src = "https://img.youtube.com/vi/$youtube_id/maxresdefault.jpg";
			if ( ! cmplz_remote_file_exists( $new_src ) ) {
				$new_src = "https://img.youtube.com/vi/$youtube_id/hqdefault.jpg";
			}
			$new_src = cmplz_download_to_site( $new_src, 'youtube' . $youtube_id );
			cmplz_set_transient( "cmplz_youtube_image_$youtube_id", $new_src, WEEK_IN_SECONDS );
		}
	}

	return $new_src;
}

add_filter( 'cmplz_placeholder_youtube', 'cmplz_youtube_placeholder', 10, 2 );
