<?php
defined( 'ABSPATH' ) or die();

/**
 * Block the YotuWP playlists when YouTube is used on the site.
 *
 * @param string $output The front-end output buffer.
 *
 * @return string
 */
function cmplz_yotuwp_cookieblocker( $output ) {
	if ( cmplz_uses_thirdparty( 'youtube' ) ) {
		$output = cmplz_yotuwp_rewrite_playlists( $output );
	}
	return $output;
}
add_filter( 'cmplz_cookie_blocker_output', 'cmplz_yotuwp_cookieblocker' );

/**
 * Block the YotuWP playlists in the page output.
 *
 * Only the opening tag of a real YotuWP wrapper is rewritten: the buffer also holds
 * visitor content (comments), so rewriting any other text could change how the
 * browser parses it (CVE-2026-97339).
 *
 * @param string $output The front-end output buffer.
 *
 * @return string
 */
function cmplz_yotuwp_rewrite_playlists( $output ) {
	// A single opening <div> tag whose class attribute holds the yotu-playlist token.
	$tag_pattern = '/<div\b[^>]*\bclass\s*=\s*"[^"]*(?<![\w-])yotu-playlist(?![\w-])[^"]*"[^>]*>/i';
	if ( ! is_string( $output ) || ! preg_match_all( $tag_pattern, $output, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $output;
	}

	$tags = $matches[0];
	// Rewrite from the end, so the offsets of the earlier tags stay valid.
	for ( $i = count( $tags ) - 1; $i >= 0; $i-- ) {
		list( $tag, $offset ) = $tags[ $i ];

		$placeholder = '';
		if ( cmplz_use_placeholder( 'youtube' ) ) {
			// Look for the first video of this playlist, before the next playlist starts.
			$start  = $offset + strlen( $tag );
			$end    = isset( $tags[ $i + 1 ] ) ? $tags[ $i + 1 ][1] : strlen( $output );
			$window = substr( $output, $start, min( $end - $start, 20000 ) );
			if ( preg_match( '/<a\b[^>]*\bdata-videoid\s*=\s*"([A-Za-z0-9_-]{6,20})"/i', $window, $video ) ) {
				$youtube_url = 'https://www.youtube.com/watch?v=' . $video[1];
				$placeholder = ' data-placeholder-image="' . esc_url( cmplz_placeholder( false, $youtube_url ) ) . '"';
			}
		}

		$new_tag = preg_replace(
			'/(\bclass\s*=\s*"[^"]*?)(?<![\w-])yotu-playlist(?![\w-])/i',
			'${1}cmplz-yotu-playlist cmplz-placeholder-element',
			$tag,
			1
		);
		// Insert right after the tag name ('<div', 4 characters), the only position known to be inside the tag.
		$new_tag = substr( $new_tag, 0, 4 ) . $placeholder . ' data-service="youtube"' . substr( $new_tag, 4 );
		$output  = substr_replace( $output, $new_tag, $offset, strlen( $tag ) );
	}

	return $output;
}

/**
 * Add some css
 *
 * @return void
 */
function cmplz_yotu_css() {
	if ( cmplz_uses_thirdparty( 'youtube' ) ) { ?>
		.cmplz-yotu-playlist  {
		max-height:400px;
		}
		<?php
	}
}
add_action( 'cmplz_banner_css', 'cmplz_yotu_css' );

/**
 * Add script to handle placeholder and reload on consent
 *
 * @return void
 */
function cmplz_yotuwp_handle_youtube() {
	if ( cmplz_uses_thirdparty( 'youtube' ) || 1 === 1 ) {
		ob_start();
		?>
		<script>
			function cmplz_maybe_trigger_yotuwp(){
				//if not defined, wait a bit
				if (typeof yotuwp === 'undefined') {
					setTimeout(cmplz_maybe_trigger_yotuwp, 500);
				} else {
					yotuwp.init();
				}
			}
			document.addEventListener("cmplz_enable_category", function(consentData) {
				var category = consentData.detail.category;
				var service = consentData.detail.service;
				let selectorVideo = '.cmplz-yotu-playlist';
				if (category!=='marketing' && service !== 'youtube' ) {
					return;
				}
				dispatchEvent(new Event('load'));
				document.querySelectorAll(selectorVideo).forEach(obj => {
					obj.classList.remove('cmplz-yotu-playlist');
					obj.classList.remove('cmplz-blocked-content-container');
					obj.classList.add('yotu-playlist');
					let index = obj.getAttribute('data-placeholder_class_index');
					obj.classList.remove('cmplz-placeholder-'+index);
					cmplz_maybe_trigger_yotuwp();
				});
			});
		</script>
		<?php
		$script = ob_get_clean();
		$script = str_replace( array( '<script>', '</script>' ), '', $script );
		wp_add_inline_script( 'cmplz-cookiebanner', $script );
	}
}
add_action( 'wp_enqueue_scripts', 'cmplz_yotuwp_handle_youtube', PHP_INT_MAX );

/**
 * Whitelist the consent handler script, so the cookie blocker leaves it alone.
 *
 * @param array $tags Whitelisted script tags.
 *
 * @return array
 */
function cmplz_yotuwp_whitelist( $tags ) {
	$tags[] = 'cmplz_maybe_trigger_yotuwp';

	return $tags;
}
add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_yotuwp_whitelist' );
add_filter( 'cmplz_known_script_tags', 'cmplz_yotuwp_iframetags' );
function cmplz_yotuwp_iframetags( $tags ) {
	$tags[] = array(
		'name'        => 'youtube',
		'placeholder' => 'youtube',
		'category'    => 'marketing',
		'urls'        => array(
			'yotuwp-easy-youtube-embed',
			'yotuwp-pro',
			'yotuwp.data',
			'var yotujs',
		),
	);
	return $tags;
}

/**
 * Add services to the list of detected items, so it will get set as default, and will be added to the notice about it
 *
 * @param $services
 *
 * @return array
 */
function cmplz_yotuwp_detected_services( $services ) {
	if ( ! in_array( 'youtube', $services ) ) {
		$services[] = 'youtube';
	}
	return $services;
}
add_filter( 'cmplz_detected_services', 'cmplz_yotuwp_detected_services' );
