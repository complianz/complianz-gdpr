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
						obj.classList.removeAttribute('data-cmplz_elementor_widget_type');
						obj.classList.setAttribute('data-widget_type', attr);
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
 */
function cmplz_elementor_cookieblocker( $output ) {

	if ( cmplz_uses_thirdparty( 'youtube' ) ) {
		$iframe_pattern = '/elementor-widget elementor-widget-video[ |\"][^>]+?data-settings="[^"]+?youtube_url[^;]*?&quot;:&quot;(.+?(?=&quot;))&quot;/is';
		if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
			foreach ( $matches[0] as $key => $total_match ) {
				$placeholder = '';
				if ( cmplz_use_placeholder( 'youtube' ) && isset( $matches[1][ $key ] ) ) {
					$youtube_url = $matches[1][ $key ];
					$placeholder = 'data-placeholder-image="' . cmplz_placeholder( false, stripcslashes( $youtube_url ) ) . '" ';
				}

				$new_match = str_replace( 'data-settings', $placeholder . ' data-category="marketing" data-service="youtube" data-cmplz-elementor-settings', $total_match );
				$new_match = str_replace( 'elementor-widget-video', 'elementor-widget-video cmplz-placeholder-element', $new_match );
				$output    = str_replace( $total_match, $new_match, $output );
			}
		}
		/**
		 * Playlist
		 */
		$iframe_pattern = '/elementor-widget elementor-widget-video-playlist[^>]+?data-settings="[^"]+?youtube_url[^;]*?&quot;:&quot;(.+?(?=&quot;))&quot;/is';
		if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
			foreach ( $matches[0] as $key => $total_match ) {
				$placeholder = '';
				if ( cmplz_use_placeholder( 'youtube' ) && isset( $matches[1][ $key ] ) ) {
					$youtube_url = $matches[1][ $key ];
					$placeholder = 'data-placeholder-image="' . cmplz_placeholder( false, stripcslashes( $youtube_url ) ) . '" ';
				}

				$new_match = str_replace( 'data-settings', $placeholder . ' data-category="marketing" data-service="youtube" data-cmplz-elementor-settings', $total_match );
				$new_match = str_replace( 'data-widget_type', 'data-cmplz_elementor_widget_type', $new_match );
				$new_match = str_replace( 'elementor-widget-video-playlist', 'cmplz-elementor-widget-video-playlist cmplz-placeholder-element', $new_match );
				$output    = str_replace( $total_match, $new_match, $output );
			}
		}
	}

	if ( cmplz_uses_thirdparty( 'facebook' ) ) {
		$iframe_pattern = '/<[^>]*\bclass\s*=\s*"[^"]*\belementor-widget-facebook-[^"]*".{0,500}?\bdata-href="([^"]*)"/is';
		if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
			foreach ( $matches[0] as $key => $total_match ) {
				$placeholder = '';

				if ( cmplz_use_placeholder( 'facebook' ) ) {
					$placeholder = 'data-placeholder-image="' . cmplz_placeholder( 'facebook' ) . '" ';
				}
				$new_match = str_replace( 'data-href="', $placeholder . 'data-category="marketing" data-service="facebook" data-cmplz-elementor-href="', $total_match );
				$new_match = str_replace( 'fb-video', 'cmplz-fb-video', $new_match );

				$new_match = str_replace( 'elementor-facebook-widget', 'elementor-facebook-widget cmplz-placeholder-element', $new_match );
				$output    = str_replace( $total_match, $new_match, $output );
			}
		}
	}

	if ( cmplz_uses_thirdparty( 'twitter' ) ) {
		$iframe_pattern = '/<[^>]*\bclass\s*=\s*"[^"]*\belementor-widget-twitter-[^"]*".{0,500}?\bdata-href="([^"]*)"/is';
		if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
			foreach ( $matches[0] as $key => $total_match ) {
				$placeholder = '';
				if ( cmplz_use_placeholder( 'twitter' ) ) {
					$placeholder = 'data-placeholder-image="' . cmplz_placeholder( 'twitter' ) . '" ';
				}
				$new_match = str_replace( 'data-href="', $placeholder . 'data-category="marketing" data-service="twitter" data-cmplz-elementor-href="', $total_match );
				$output    = str_replace( $total_match, $new_match, $output );
			}
		}
	}

	return $output;
}
add_filter( 'cmplz_cookie_blocker_output', 'cmplz_elementor_cookieblocker' );

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
