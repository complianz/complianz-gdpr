<?php
defined( 'ABSPATH' ) or die();
/**
 * Filter Custom method from Thrive
 *
 * @param string $html
 *
 * @return mixed|null
 */
function cmplz_thrive_page_content_cookieblocker( $html ) {
	if ( ! is_admin() && ! cmplz_is_pagebuilder_preview() ) {
		$html = COMPLIANZ::$cookie_blocker->replace_tags( $html );
	}

	return $html;
}

add_filter( 'tve_landing_page_content', 'cmplz_thrive_page_content_cookieblocker' );

/**
 * Whitelist youtube and video from being blocked, in the text/templates scripts of the Trhive quiz builder
 *
 * @param $tags
 *
 * @return mixed
 */
function cmplz_thrive_whitelist( $tags ) {
	$tags[] = '//www.youtube.com/embed/<#=';
	$tags[] = '//player.vimeo.com/video/<#=';

	return $tags;
}

add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_thrive_whitelist' );

/**
 * Add inline css, as Thrive removes it
 *
 * @param string $html
 *
 * @return string
 */
function cmplz_thrive_inline_css( $html ) {
	$html .= '<style> .cmplz-hidden{display:none !important;}</style>';

	return $html;
}

add_filter( 'cmplz_banner_html', 'cmplz_thrive_inline_css' );

/**
 * Filter cookie blocker output
 *
 * @param string $output The front-end output buffer.
 *
 * @return string
 */
function cmplz_thrive_cookieblocker( $output ) {
	$services = array();
	foreach ( array( 'youtube', 'vimeo' ) as $service ) {
		if ( cmplz_uses_thirdparty( $service ) ) {
			$services[] = $service;
		}
	}

	if ( ! empty( $services ) ) {
		$output = cmplz_thrive_rewrite_videos( $output, $services );
	}

	return $output;
}

add_filter( 'cmplz_cookie_blocker_output', 'cmplz_thrive_cookieblocker' );

/**
 * Attributes of one opening tag, tokenised from the tag name on.
 *
 * Reading the tag one attribute at a time means text inside a quoted value (title, href, ...)
 * can never be taken for an attribute name: that is what visitor markup looks like after kses.
 * Same tokens as cmplz_elementor_tag_pattern(), which is only loaded with Elementor.
 *
 * @param string $tag One opening tag, e.g. <div class="x" data-url="y">.
 *
 * @return array[] Each attribute: name (lowercase), name_offset, name_length, value (raw, quotes included, or null).
 */
function cmplz_thrive_tag_attributes( $tag ) {
	$attributes = array();
	if ( ! preg_match( '/^<\w+/', $tag, $tag_name ) ) {
		return $attributes;
	}
	preg_match_all(
		'/\G\s+([\w:.-]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'>]+))?/',
		$tag,
		$matches,
		PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
		strlen( $tag_name[0] )
	);
	foreach ( $matches as $match ) {
		$attributes[] = array(
			'name'        => strtolower( $match[1][0] ),
			'name_offset' => $match[1][1],
			'name_length' => strlen( $match[1][0] ),
			'value'       => ( isset( $match[2] ) && $match[2][1] >= 0 ) ? $match[2][0] : null,
		);
	}

	return $attributes;
}

/**
 * Block the Thrive responsive videos in the page output.
 *
 * Only the opening tag of a real Thrive video wrapper is rewritten: the buffer also holds
 * visitor content (comments), so rewriting any other text could change how the browser
 * parses it (same class as CVE-2026-97339). The tag is matched and read one attribute at
 * a time, so a class or data-url that only appears inside another attribute's value can
 * neither anchor a match nor be renamed, and a match cannot run past the end of its tag.
 *
 * @param string   $output   The front-end output buffer.
 * @param string[] $services The video services in use on the site ('youtube', 'vimeo').
 *
 * @return string
 */
function cmplz_thrive_rewrite_videos( $output, $services ) {
	// A single opening tag whose class attribute holds the thrv_responsive_video token, attributes tokenised from the start.
	$attribute   = '(?:\s+[\w:.-]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)';
	$token       = '(?<![\w-])thrv_responsive_video(?![\w-])';
	$tag_pattern = '/<\w+\b' . $attribute . '*\s+class\s*=\s*"[^"]*' . $token . '[^"]*"' . $attribute . '*\s*\/?>/i';
	if ( ! is_string( $output ) || ! preg_match_all( $tag_pattern, $output, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $output;
	}

	$categories = array(
		'youtube' => 'marketing',
		'vimeo'   => 'statistics',
	);

	$tags = $matches[0];
	// Rewrite from the end, so the offsets of the earlier tags stay valid.
	for ( $i = count( $tags ) - 1; $i >= 0; $i-- ) {
		list( $tag, $offset ) = $tags[ $i ];

		// The first class and data-url attributes are the ones the browser uses.
		$class    = null;
		$data_url = null;
		foreach ( cmplz_thrive_tag_attributes( $tag ) as $found ) {
			if ( 'class' === $found['name'] && null === $class ) {
				$class = $found;
			} elseif ( 'data-url' === $found['name'] && null === $data_url ) {
				$data_url = $found;
			}
		}
		if ( null === $class || null === $data_url
			|| ! preg_match( '/^"[^"]*' . $token . '/i', (string) $class['value'] )
			|| ! preg_match( '/^"([^"]*)"$/', (string) $data_url['value'], $url )
		) {
			continue;
		}
		$video_url = $url[1];

		// Determine if it's YouTube or Vimeo based on the URL in the data-url attribute.
		$service = '';
		foreach ( $services as $candidate ) {
			if ( isset( $categories[ $candidate ] ) && strpos( $video_url, $candidate . '.com' ) !== false ) {
				$service = $candidate;
				break;
			}
		}
		if ( '' === $service ) {
			continue;
		}

		$placeholder = '';
		if ( cmplz_use_placeholder( $service ) ) {
			$placeholder = 'data-placeholder-image="' . esc_url( cmplz_placeholder( false, stripcslashes( $video_url ) ) ) . '" ';
		}

		// Both edits sit at attribute positions found above; apply the later one first so the earlier offset stays valid.
		$class_value_offset = $class['name_offset'] + strpos( substr( $tag, $class['name_offset'] ), '"' );
		$edits              = array(
			array(
				$class_value_offset,
				strlen( $class['value'] ),
				preg_replace( '/' . $token . '/i', 'thrv_responsive_video cmplz-placeholder-element', $class['value'], 1 ),
			),
			// Rename data-url at its attribute-name position.
			array(
				$data_url['name_offset'],
				$data_url['name_length'],
				$placeholder . 'data-category="' . $categories[ $service ] . '" data-service="' . $service . '" data-src-cmplz',
			),
		);
		usort(
			$edits,
			function ( $a, $b ) {
				return $b[0] - $a[0];
			}
		);
		$new_tag = $tag;
		foreach ( $edits as $edit ) {
			$new_tag = substr_replace( $new_tag, $edit[2], $edit[0], $edit[1] );
		}
		$output = substr_replace( $output, $new_tag, $offset, strlen( $tag ) );
	}

	return $output;
}

/**
 * @param string $target
 * @param string $total_match
 *
 * @return string
 */
function cmplz_thrive_data_target( $target, $total_match ) {
	// Look for thrive class in iframe here
	if ( strpos( $total_match, 'data-url' ) !== false &&
		( cmplz_uses_thirdparty( 'youtube' ) || cmplz_uses_thirdparty( 'vimeo' ) ) ) {
		return 'data-url';
	}

	return $target;
}

add_filter( 'cmplz_data_target', 'cmplz_thrive_data_target', 100, 2 );

/**
 * Initialize thrive youtube iframe
 */
function cmplz_thrive_initDomContentLoaded() {
	if ( ! cmplz_uses_thirdparty( 'youtube' ) ) {
		return;
	}
	ob_start();
	?>
	<script>
		document.addEventListener("cmplz_run_after_all_scripts", cmplz_thrive_fire_domContentLoadedEvent);

		function cmplz_thrive_fire_domContentLoadedEvent() {
			dispatchEvent(new Event('load'));
		}
	</script>
	<?php
	$script = ob_get_clean();
	$script = str_replace( array( '<script>', '</script>' ), '', $script );
	wp_add_inline_script( 'cmplz-cookiebanner', $script );
}

add_action( 'wp_enqueue_scripts', 'cmplz_thrive_initDomContentLoaded', PHP_INT_MAX );
