<?php
defined( 'ABSPATH' ) or die();

/**
 * Whitelist Elementor Add on
 */
function cmplz_elementor_whitelist( $tags ) {
	$tags[] = 'elementorFrontendConfig';
	return $tags;
}
add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_elementor_whitelist' );

/**
 * Add script to remove the placeholders which are left in place when the consent is already given and the popup is opened.
 *
 * @return void
 */
function cmplz_elementor_popup_content_blocking() {
	ob_start();
	?>
	<script>
		if ('undefined' != typeof window.jQuery) {
			jQuery(document).ready(function ($) {
				$(document).on('elementor/popup/show', () => {
					let rev_cats = cmplz_categories.reverse();
					for (let key in rev_cats) {
						if (rev_cats.hasOwnProperty(key)) {
							let category = cmplz_categories[key];
							if (cmplz_has_consent(category)) {
								document.querySelectorAll('[data-category="' + category + '"]').forEach(obj => {
									cmplz_remove_placeholder(obj);
								});
							}
						}
					}

					let services = cmplz_get_services_on_page();
					for (let key in services) {
						if (services.hasOwnProperty(key)) {
							let service = services[key].service;
							let category = services[key].category;
							if (cmplz_has_service_consent(service, category)) {
								document.querySelectorAll('[data-service="' + service + '"]').forEach(obj => {
									cmplz_remove_placeholder(obj);
								});
							}
						}
					}
				});
			});
		}
	</script>
	<?php
	$script = ob_get_clean();
	$script = str_replace( array( '<script>', '</script>' ), '', $script );
	wp_add_inline_script( 'cmplz-cookiebanner', $script );
}
add_action( 'wp_enqueue_scripts', 'cmplz_elementor_popup_content_blocking', PHP_INT_MAX );

/**
 *
 */
function cmplz_elementor_initDomContentLoaded() {
	if ( cmplz_uses_thirdparty( 'youtube' ) || cmplz_uses_thirdparty( 'facebook' ) || cmplz_uses_thirdparty( 'twitter' ) ) {
		ob_start();
		?>
		<script>
			document.addEventListener("cmplz_enable_category", function(consentData) {
				var category = consentData.detail.category;
				var services = consentData.detail.services;
				var blockedContentContainers = [];
				let selectorVideo = '.cmplz-elementor-widget-video-playlist[data-category="'+category+'"],.elementor-widget-video[data-category="'+category+'"]';
				let selectorGeneric = '[data-cmplz-elementor-href][data-category="'+category+'"]';
				for (var skey in services) {
					if (services.hasOwnProperty(skey)) {
						let service = skey;
						selectorVideo +=',.cmplz-elementor-widget-video-playlist[data-service="'+service+'"],.elementor-widget-video[data-service="'+service+'"]';
						selectorGeneric +=',[data-cmplz-elementor-href][data-service="'+service+'"]';
					}
				}
				document.querySelectorAll(selectorVideo).forEach(obj => {
					let elementService = obj.getAttribute('data-service');
					if ( cmplz_is_service_denied(elementService) ) {
						return;
					}
					if (obj.classList.contains('cmplz-elementor-activated')) return;
					obj.classList.add('cmplz-elementor-activated');

					if ( obj.hasAttribute('data-cmplz_elementor_widget_type') ){
						let attr = obj.getAttribute('data-cmplz_elementor_widget_type');
						obj.removeAttribute('data-cmplz_elementor_widget_type');
						obj.setAttribute('data-widget_type', attr);
					}
					if (obj.classList.contains('cmplz-elementor-widget-video-playlist')) {
						obj.classList.remove('cmplz-elementor-widget-video-playlist');
						obj.classList.add('elementor-widget-video-playlist');
					}
					obj.setAttribute('data-settings', obj.getAttribute('data-cmplz-elementor-settings'));
					blockedContentContainers.push(obj);
				});

				document.querySelectorAll(selectorGeneric).forEach(obj => {
					let elementService = obj.getAttribute('data-service');
					if ( cmplz_is_service_denied(elementService) ) {
						return;
					}
					if (obj.classList.contains('cmplz-elementor-activated')) return;

					if (obj.classList.contains('cmplz-fb-video')) {
						obj.classList.remove('cmplz-fb-video');
						obj.classList.add('fb-video');
					}

					obj.classList.add('cmplz-elementor-activated');
					obj.setAttribute('data-href', obj.getAttribute('data-cmplz-elementor-href'));
					blockedContentContainers.push(obj.closest('.elementor-widget'));
				});

				/**
				 * Trigger the widgets in Elementor
				 */
				for (var key in blockedContentContainers) {
					if (blockedContentContainers.hasOwnProperty(key) && blockedContentContainers[key] !== undefined) {
						let blockedContentContainer = blockedContentContainers[key];
						if (elementorFrontend.elementsHandler) {
							elementorFrontend.elementsHandler.runReadyTrigger(blockedContentContainer)
						}
						var cssIndex = blockedContentContainer.getAttribute('data-placeholder_class_index');
						blockedContentContainer.classList.remove('cmplz-blocked-content-container');
						blockedContentContainer.classList.remove('cmplz-placeholder-' + cssIndex);
					}
				}

			});
		</script>
		<?php
		$script = ob_get_clean();
		$script = str_replace( array( '<script>', '</script>' ), '', $script );
		wp_add_inline_script( 'cmplz-cookiebanner', $script );
	}
}
add_action( 'wp_enqueue_scripts', 'cmplz_elementor_initDomContentLoaded', PHP_INT_MAX );

/**
 * Filter cookie blocker output
 *
 * @param string $output The front-end output buffer.
 *
 * @return string
 */
function cmplz_elementor_cookieblocker( $output ) {

	if ( cmplz_uses_thirdparty( 'youtube' ) ) {
		$output = cmplz_elementor_rewrite_video_widgets( $output );
	}

	foreach ( array( 'facebook', 'twitter' ) as $service ) {
		if ( cmplz_uses_thirdparty( $service ) ) {
			$output = cmplz_elementor_rewrite_social_widgets( $output, $service );
		}
	}

	return $output;
}
add_filter( 'cmplz_cookie_blocker_output', 'cmplz_elementor_cookieblocker' );

/**
 * Pattern for one opening tag whose class attribute carries the given token.
 *
 * The attributes are tokenised from the start of the tag, so a token that only appears inside
 * another attribute's quoted value can never anchor a match. That is what visitor markup looks
 * like after kses: title and href survive on comments, class does not.
 *
 * @param string $class_token Regex fragment for the class token, without delimiters.
 *
 * @return string
 */
function cmplz_elementor_tag_pattern( $class_token ) {
	$attribute = '(?:\s+[\w:.-]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)';

	return '/<\w+\b' . $attribute . '*\s+class\s*=\s*"[^"]*(?<![\w-])(?:' . $class_token . ')(?![\w-])[^"]*"' . $attribute . '*\s*\/?>/i';
}

/**
 * Pattern for one opening tag that carries the given attribute, tokenised like cmplz_elementor_tag_pattern().
 *
 * Group 1 is the attribute name up to and including its opening quote, group 2 the value.
 *
 * @param string $attribute_name Attribute name, e.g. data-href.
 *
 * @return string
 */
function cmplz_elementor_attribute_tag_pattern( $attribute_name ) {
	$attribute = '(?:\s+[\w:.-]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)';

	return '/<\w+\b' . $attribute . '*(\s+' . preg_quote( $attribute_name, '/' ) . '\s*=\s*")([^"]*)"' . $attribute . '*\s*\/?>/i';
}

/**
 * Block the Elementor Facebook and Twitter widgets in the page output.
 *
 * The widget wrapper is a real opening tag with the elementor-widget-{service}-* class; the
 * element that carries data-href is a child of it, within the next 500 characters. Only that
 * element is rewritten, at the attribute-name position: the buffer also holds visitor content
 * (comments), so rewriting any other text could change how the browser parses it (same class
 * as CVE-2026-97339).
 *
 * @param string $output  The front-end output buffer.
 * @param string $service 'facebook' or 'twitter'.
 *
 * @return string
 */
function cmplz_elementor_rewrite_social_widgets( $output, $service ) {
	$wrapper_pattern = cmplz_elementor_tag_pattern( 'elementor-widget-' . preg_quote( $service, '/' ) . '-[\w-]+' );
	if ( ! is_string( $output ) || ! preg_match_all( $wrapper_pattern, $output, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $output;
	}

	$href_pattern = cmplz_elementor_attribute_tag_pattern( 'data-href' );
	$wrappers     = $matches[0];
	// Rewrite from the end, so the offsets of the earlier wrappers stay valid.
	for ( $i = count( $wrappers ) - 1; $i >= 0; $i-- ) {
		list( $wrapper, $wrapper_offset ) = $wrappers[ $i ];
		$search_from                      = $wrapper_offset + strlen( $wrapper );

		if ( ! preg_match( $href_pattern, $output, $tag_match, PREG_OFFSET_CAPTURE, $search_from )
			|| $tag_match[0][1] - $search_from > 500
		) {
			continue;
		}
		list( $tag, $offset )       = $tag_match[0];
		list( $name, $name_offset ) = $tag_match[1];

		$placeholder = '';
		if ( cmplz_use_placeholder( $service ) ) {
			$placeholder = 'data-placeholder-image="' . esc_url( cmplz_placeholder( $service ) ) . '" ';
		}

		// Rename data-href at its attribute-name position, the only place known to be inside the tag.
		$attributes = ' ' . $placeholder . 'data-category="marketing" data-service="' . $service . '" data-cmplz-elementor-href="';
		$new_tag    = substr_replace( $tag, $attributes, $name_offset - $offset, strlen( $name ) );

		if ( 'facebook' === $service ) {
			$new_tag = preg_replace( '/(\bclass\s*=\s*"[^"]*?)(?<![\w-])fb-video(?![\w-])/i', '${1}cmplz-fb-video', $new_tag, 1 );
			$new_tag = preg_replace(
				'/(\bclass\s*=\s*"[^"]*?)(?<![\w-])elementor-facebook-widget(?![\w-])/i',
				'${1}elementor-facebook-widget cmplz-placeholder-element',
				$new_tag,
				1
			);
		}
		$output = substr_replace( $output, $new_tag, $offset, strlen( $tag ) );
	}

	return $output;
}

/**
 * Block the Elementor YouTube video and video-playlist widgets in the page output.
 *
 * Only the opening tag of a real Elementor widget is rewritten: the buffer also holds
 * visitor content (comments), so rewriting any other text could change how the browser
 * parses it (same class as CVE-2026-97339).
 *
 * @param string $output The front-end output buffer.
 *
 * @return string
 */
function cmplz_elementor_rewrite_video_widgets( $output ) {
	// A single opening tag whose class attribute holds the video or video-playlist widget token.
	$tag_pattern = cmplz_elementor_tag_pattern( 'elementor-widget-video(?:-playlist)?' );
	if ( ! is_string( $output ) || ! preg_match_all( $tag_pattern, $output, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $output;
	}

	$settings_pattern = cmplz_elementor_attribute_tag_pattern( 'data-settings' );
	$tags             = $matches[0];
	// Rewrite from the end, so the offsets of the earlier tags stay valid.
	for ( $i = count( $tags ) - 1; $i >= 0; $i-- ) {
		list( $tag, $offset ) = $tags[ $i ];

		// The YouTube URL sits in the JSON of the data-settings attribute, HTML-escaped: &quot;youtube_url&quot;:&quot;...&quot;.
		// Read the attribute tokenised, so data-settings=" text inside another value is never taken for it.
		if ( ! preg_match( $settings_pattern, $tag, $settings, PREG_OFFSET_CAPTURE )
			|| 0 !== $settings[0][1]
			|| ! preg_match( '/&quot;youtube_url&quot;:&quot;(.+?)&quot;/i', $settings[2][0], $url )
		) {
			continue;
		}
		$is_playlist = (bool) preg_match( '/\bclass\s*=\s*"[^"]*(?<![\w-])elementor-widget-video-playlist(?![\w-])/i', $tag );

		$placeholder = '';
		if ( cmplz_use_placeholder( 'youtube' ) ) {
			$placeholder = 'data-placeholder-image="' . esc_url( cmplz_placeholder( false, stripcslashes( $url[1] ) ) ) . '" ';
		}

		// Rename data-settings at its attribute-name position, the only place known to be inside the tag.
		$attributes = ' ' . $placeholder . 'data-category="marketing" data-service="youtube" data-cmplz-elementor-settings="';
		$new_tag    = substr_replace( $tag, $attributes, $settings[1][1], strlen( $settings[1][0] ) );

		if ( $is_playlist ) {
			$new_tag = preg_replace( '/\sdata-widget_type(\s*=\s*")/i', ' data-cmplz_elementor_widget_type${1}', $new_tag, 1 );
			$new_tag = preg_replace(
				'/(\bclass\s*=\s*"[^"]*?)(?<![\w-])elementor-widget-video-playlist(?![\w-])/i',
				'${1}cmplz-elementor-widget-video-playlist cmplz-placeholder-element',
				$new_tag,
				1
			);
		} else {
			$new_tag = preg_replace(
				'/(\bclass\s*=\s*"[^"]*?)(?<![\w-])elementor-widget-video(?![\w-])/i',
				'${1}elementor-widget-video cmplz-placeholder-element',
				$new_tag,
				1
			);
		}
		$output = substr_replace( $output, $new_tag, $offset, strlen( $tag ) );
	}

	return $output;
}

add_action( 'cmplz_banner_css', 'cmplz_elementor_css' );
function cmplz_elementor_css() {
	if ( cmplz_get_option( 'block_recaptcha_service' ) === 'yes' ) {
		?>
	.cmplz-blocked-content-container.elementor-g-recaptcha  {
		max-width: initial !important;
		height: 80px !important;
		margin-bottom: 20px;
	}

	@media only screen and (max-width: 400px) {
		.cmplz-blocked-content-container.elementor-g-recaptcha{
			height: 100px !important
		}
	}
	.cmplz-blocked-content-container.elementor-g-recaptcha .cmplz-blocked-content-notice {
		max-width: initial;
		padding: 7px;
		position:relative !important;
		transform:initial;
		top:initial;
		left:initial;
	}
		<?php
	}
}
