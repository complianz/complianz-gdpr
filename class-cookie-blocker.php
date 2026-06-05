<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( 'cmplz_cookie_blocker' ) ) {
	class cmplz_cookie_blocker {
		private static $_this;
		public $cookie_list = [];
		public $delete_cookies_list = [];

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			add_action( 'rest_api_init', array($this, 'cmplz_cookie_data_rest_route') );
			add_action( 'init', array( $this, 'create_delete_cookies_list'));
			add_action( 'send_headers', array( $this, 'delete_cookies'));
			self::$_this = $this;
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Get list of cookies for the cookie shredder
		 * @return void
		 */
		public function load_cookie_data(){
			if ( cmplz_get_option( 'safe_mode' ) == 1 || cmplz_get_option( 'consent_per_service' ) !== 'yes' ) {
				return;
			}
			$this->cookie_list = cmplz_get_transient('cmplz_cookie_shredder_list' );
			if ( !$this->cookie_list ) {
				$this->cookie_list = [];
				$cookie_list = COMPLIANZ::$banner_loader->get_cookies( array(
					'ignored'           => false,
					'hideEmpty'         => false,
					'language'          => 'en', //only for one language
					'showOnPolicy'      => true,
					'deleted'           => false,
					'isMembersOnly'     => cmplz_get_option( 'wp_admin_access_users' ) === 'yes' ? 'all' : false,
				) );
				$this->get_cookies($cookie_list, 'preferences');
				$this->get_cookies($cookie_list, 'statistics');
				$this->get_cookies($cookie_list, 'marketing');
				cmplz_set_transient('cmplz_cookie_shredder_list', $this->cookie_list, HOUR_IN_SECONDS);
			}
		}

		/**
		 * Create a list of cookies that should be deleted
		 *
		 * @return void
		 */
		public function create_delete_cookies_list(){
			if ( cmplz_get_option( 'safe_mode' ) == 1 || cmplz_get_option( 'consent_per_service' ) !== 'yes' ) {
				return;
			}
			if ( is_admin() ) {
				return;
			}
			$this->load_cookie_data();

			$current_cookies = array_keys($_COOKIE);
			foreach ( $this->cookie_list as $category => $cookies){
				if ( cmplz_has_consent( $category)) continue;

				if (!is_array($cookies) ) {
					continue;
				}

				foreach ($cookies as $service => $cookie_list ) {
					if (cmplz_has_service_consent($service)) continue;
					foreach ($current_cookies as $key => $current_cookie ) {
						$found = cmplz_strpos_arr($current_cookie, $cookie_list);
						if ( $found ){
							$this->delete_cookies_list[] = $current_cookie;
						}
					}
				}
			}
			//ensure there are no duplicate arrays
			$this->delete_cookies_list = array_unique($this->delete_cookies_list);
		}

		/**
		 * Clear cookies on header send
		 * @return void
		 */
		public function delete_cookies(){
			$max = 20;
			$count=0;
			foreach ($this->delete_cookies_list as $name ) {
				//limit header size by limiting number of cookies to delete in one go.
				if ($count>$max) {
					continue;
				}
				$count++;
				unset($_COOKIE[$name]);
				$name = $this->sanitize_cookie_name($name);
				setcookie($name, "", -1, COMPLIANZ::$banner_loader->get_cookie_path() );
				setcookie($name, "", -1, '/' );
			}
		}

		/**
		 * Sanitize cookie name. Remove any characters that are not alphanumeric, underscore, or dash to prevent fatal errors in the setcookie function
		 *
		 * @param string $name
		 *
		 * @return string
		 */

		public function sanitize_cookie_name(string $name): string {
			// Remove any characters that are not alphanumeric, underscore, or dash
			return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
		}

		/**
		 * Add cookie data rest route
		 * @return void
		 */
		public function cmplz_cookie_data_rest_route() {
			register_rest_route( 'complianz/v1', 'cookie_data/', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'cookie_data'),
				'permission_callback' => '__return_true',
			) );
		}

		/**
		 * Add cookies to list by category
		 *
		 * @param array  $cookie_list
		 * @param string $category
		 *
		 * @return void
		 */
		public function get_cookies( $cookie_list, $category) {
			if (is_array($cookie_list)) {
				foreach ( $cookie_list as $cookie ) {
					if ( stripos( (string) $cookie->purpose, $category ) !== false ) {
						$this->cookie_list[ $category ][ $this->sanitize_service_name( $cookie->service ) ][] = str_replace( '*', '', $cookie->name );
					}
				}
			}
		}

		/**
		 * Get a blocked content notice
		 * @return string
		 */
		public function blocked_content_text(){
			if (cmplz_get_option( 'consent_per_service' ) === 'yes') {
				$agree_text = cmplz_get_option( 'agree_text_per_service' );
				$placeholdertext = cmplz_get_option( 'blocked_content_text_per_service' );
				$placeholdertext = '<div class="cmplz-blocked-content-notice-body">'.$placeholdertext.'&nbsp;<div class="cmplz-links"><a href="#" class="cmplz-link cookie-statement">{title}</a></div></div><button class="cmplz-accept-service">'.$agree_text.'</button>';
			} else {
				$placeholdertext = cmplz_get_option( 'blocked_content_text' );
			}

			return apply_filters('cmplz_accept_cookies_blocked_content', $placeholdertext);
		}


		/**
		 * REST API for cookie data
		 * @param WP_REST_Request $request
		 */

		public function cookie_data( WP_REST_Request $request ){
			$this->load_cookie_data();
			$response = json_encode( $this->cookie_list );
			header( "Content-Type: application/json" );
			echo $response;
			exit;
		}
		/**
		 * Get array of scripts to block in correct format
		 * This is the base array, of which dependencies and placeholder lists also are derived
		 *
		 * @return array
		 */
		public function blocked_styles()
		{
			$blocked_styles = apply_filters( 'cmplz_known_style_tags', [] );
			//make sure every item has the default array structure
			foreach ($blocked_styles as $key => $blocked_style ){
				$default = [
					'name'               => 'general',//default service name
					'enable_placeholder' => 1,
					'placeholder'        => '',
					'category'           => 'marketing',
					'urls'               => array( $blocked_style ),
					'enable'             => 1,
					'enable_dependency'  => 0,
					'dependency'         => '',
				];

				if ( !is_array($blocked_style) ){
					$service = cmplz_get_service_by_src( $blocked_style );
					$default['name'] = $service;
					$default['placeholder'] = $service;
					$blocked_styles[$key] = $default;
				} else {
					$blocked_styles[$key] = wp_parse_args( $blocked_style, $default);
				}
			}

			$formatted_custom_style_tags = [];
			foreach ( $blocked_styles as $blocked_style ) {
				$blocked_style['name'] = $this->sanitize_service_name($blocked_style['name']);
				if ( isset($blocked_style['urls']) ) {
					foreach ($blocked_style['urls'] as $url ) {
						$formatted_custom_style_tags[$url] = $blocked_style;
					}
				} else if (isset($blocked_style['editor'])) {
					$formatted_custom_style_tags[$blocked_style['editor']] = $blocked_style;
				}
			}
			return $formatted_custom_style_tags;
		}
		/**
		 * Get array of scripts to block in correct format
		 * This is the base array, of which dependencies and placeholder lists also are derived
		 *
		 * @return array
		 */
		public function blocked_scripts()
		{
			$blocked_scripts = apply_filters( 'cmplz_known_script_tags', array() );
			$scripts = get_option("complianz_options_custom-scripts");
			if ( is_array($scripts) && isset($scripts['block_script']) && is_array($scripts['block_script']) ) {
				$custom_script_tags = array_filter( $scripts['block_script'], function($script) {
					return $script['enable'] == 1;
				});
				$blocked_scripts = array_merge($blocked_scripts, $custom_script_tags);
			}

			$blocked_scripts = apply_filters_deprecated( 'cmplz_known_iframe_tags', array($blocked_scripts), '6.0.0', 'cmplz_known_script_tags', 'The cmplz_known_iframe_tags filter is deprecated');

			//make sure every item has the default array structure
			foreach ($blocked_scripts as $key => $blocked_script ){
				$default = [
					'name'               => 'general',//default service name
					'enable_placeholder' => 1,
					'placeholder'        => '',
					'category'           => 'marketing',
					'urls'               => array( $blocked_script ),
					'enable'             => 1,
					'enable_dependency'  => 0,
					'dependency'         => '',
				];

				if ( !is_array($blocked_script) ){
					$service = cmplz_get_service_by_src( $blocked_script );
					$default['name'] = $service;
					$default['placeholder'] = $service;
					$blocked_scripts[$key] = $default;
				} else {
					$blocked_scripts[$key] = wp_parse_args( $blocked_script, $default);
				}

			}

			$formatted_custom_script_tags = [];
			foreach ( $blocked_scripts as $blocked_script ) {
				$blocked_script['name'] = $this->sanitize_service_name($blocked_script['name']);
				if ( cmplz_placeholder_disabled($blocked_script['name']) ) {
					$blocked_script['enable_placeholder'] = 0;
				}
				if ( isset($blocked_script['urls']) ) {
					foreach ($blocked_script['urls'] as $url ) {
						$formatted_custom_script_tags[$url] = $blocked_script;
					}
				} else if (isset($blocked_script['editor'])) {
					$formatted_custom_script_tags[$blocked_script['editor']] = $blocked_script;
				}
			}
			return $formatted_custom_script_tags;
		}

		/**
		 * @param $title
		 *
		 * @return array|string|string[]
		 */
		public function sanitize_service_name($title) {
			if (empty($title)) {
				return 'general';
			}
			return cmplz_sanitize_title_preserve_uppercase($title);
		}

		/**
		 * Get array of placeholder - placeholder_classes for non iframe blocked content
		 * @param array $blocked_scripts
		 * @return array
		 */
		public function placeholder_markers( $blocked_scripts )
		{
			$placeholder_markers = apply_filters( 'cmplz_placeholder_markers', array() );

			//current format: array('facebook' = array('class1','class2') )
			//force into new structure
			foreach ( $placeholder_markers as $name => $placeholders ) {
				foreach ( $placeholders as $class ) {
					$name = $this->sanitize_service_name($name);
					$blocked_scripts[] = [
						'name' => $name,
						'placeholder' => $name,
						'placeholder_class' => $class,
						'category' => 'marketing',
						'enable_placeholder' => 1,
						'iframe' => 0,
					];
				}
			}

			//add script center data. add_script arrays aren't included in the "known_script_tags" function
			$scripts = get_option("complianz_options_custom-scripts");
			if ( is_array($scripts) && isset($scripts['add_script']) && is_array($scripts['add_script'] ) ) {
				$added_scripts = array_filter( $scripts['add_script'], static function ( $script ) {
					return $script['enable'] == 1;
				} );
				if (!empty($added_scripts)) $blocked_scripts = array_merge($blocked_scripts, $added_scripts);
			}

			//filter out non-iframe and disabled placeholders.
			//'add_script' items do not have an iframe
			return array_filter( $blocked_scripts, static function($script) {
				return isset($script['enable_placeholder']) && $script['enable_placeholder'] == 1 && (!isset($script['iframe']) || $script['iframe'] == 0) && !empty($script['placeholder_class']);
			});
		}

		/**
		 * Get dependencies and merge with dependencies from the script center
		 * @param array $blocked_scripts
		 * @return array
		 */

		function dependencies( $blocked_scripts ) {
			//array['wait-for-this-script'] = 'script-that-should-wait';
			$dependencies = apply_filters( 'cmplz_dependencies', array() );
			$scripts = get_option( "complianz_options_custom-scripts" );
			if ( is_array( $scripts ) && isset( $scripts['block_script'] ) && is_array( $scripts['block_script'] ) ) {
				$added_scripts = array_filter( $scripts['block_script'], function ( $script ) {
					return $script['enable'] == 1;
				} );
				$blocked_scripts = array_merge($blocked_scripts, $added_scripts);
			}
			$blocked_scripts = array_filter( $blocked_scripts, function ( $script ) {
				return isset($script['enable_dependency']) && $script['enable_dependency'] == 1 && !empty($script['dependency']);
			} );

			$flat = array();
			foreach ( $blocked_scripts as $data ) {
				$flat = array_merge($flat, $data['dependency']);
			}
			return array_merge($dependencies, $flat);
		}

		/**
		 * Get array of whitelisted scripts, and add flattened scriptcenter whitelist
		 *
		 * @return array
		 */

		public function whitelisted_scripts(  ) {
			//whitelist our localized inline scripts
			$whitelisted_script_tags = apply_filters( 'cmplz_whitelisted_script_tags', array('user_banner_id') );
			$scripts = get_option("complianz_options_custom-scripts");
			if ( is_array($scripts) && isset($scripts['whitelist_script']) && is_array($scripts['whitelist_script']) ) {
				$custom_whitelisted_script_tags = array_filter( $scripts['whitelist_script'], function($script) {
					return $script['enable'] == 1;
				});

				//flatten array
				$flat = array();
				foreach ( $custom_whitelisted_script_tags as $data ) {
					$flat = array_merge($flat, $data['urls']);
				}

				$whitelisted_script_tags = array_merge($flat, $whitelisted_script_tags );
			}
			return $whitelisted_script_tags;
		}

		/**
		 * Apply the mixed content fixer.
		 *
		 * @since  1.0
		 *
		 * @access public
		 *
		 */

		public function filter_buffer( $buffer ) {
			if ( cmplz_is_amp() ) {
				$buffer = apply_filters( 'cmplz_cookieblocker_amp',  $buffer );
			} else {
				$buffer = $this->replace_tags( $buffer );
			}

			return $buffer;
		}

		/**
		 * Start buffering the output
		 *
		 * @since  1.0
		 *
		 * @access public
		 *
		 */

		public function start_buffer() {
			/**
			 * Don't activate the cookie blocker is AMP is active, but the AMP integration is not enabled
			 * This problem only occurs for manually included iframes, not for WP generated embeds
			 */

			if ( cmplz_is_amp_endpoint() && !cmplz_amp_integration_active() ) {
				return;
			}

			ob_start( array( $this, "filter_buffer" ) );
		}

		/**
		 * Flush the output buffer
		 *
		 * @since  1.0
		 *
		 * @access public
		 *
		 */

		public function end_buffer() {

			/**
			 * Don't activate the cookie blocker is AMP is active, but the AMP integration is not enabled
			 */

			if ( cmplz_is_amp_endpoint() && !cmplz_amp_integration_active() ) {
				return;
			}

			if ( ob_get_length() ) {
				ob_end_flush();
			}
		}

		/**
		 * Just before the page is sent to the visitor's browser, remove all tracked third party scripts
		 *
		 * @since  1.0
		 *
		 * @access public
		 *
		 */
		public function replace_tags( $output ) {
			/**
			 * Get style tags
			 *
			 * */
			$known_style_tags = $this->blocked_styles();

            /**
             * Get script tags, including custom user scripts
             *
             * */
			$blocked_scripts = cmplz_get_transient('cmplz_blocked_scripts');
			if ( isset($_GET['cmplz_nocache']) ) {
				$blocked_scripts = false;
			}

			if ( !$blocked_scripts ) {
				$blocked_scripts = $this->blocked_scripts();
				cmplz_set_transient('cmplz_blocked_scripts', $blocked_scripts, 30 * MINUTE_IN_SECONDS );
			}

			/**
			 * Get placeholder markers for non iframe blocked content
			 */

			$placeholder_markers = $this->placeholder_markers( $blocked_scripts );

            /**
             * Get whitelisted script tags
             *
             * */
            $whitelisted_script_tags = $this->whitelisted_scripts();

			/**
			 * Get dependencies between scripts
             *
			 * */
			$dependencies = $this->dependencies( $blocked_scripts );

			/**
			 * Get list of tags that require post scribe to be enabled on the page. Currently only for instawidget.js
			 *
			 * */

			$post_scribe_list = apply_filters( 'cmplz_post_scribe_tags', array() );


			//not meant as a "real" URL pattern, just a loose match for URL type strings.
			//edit: instagram uses ;width, so we need to allow ; as well.
			$url_pattern = '([\w.,;ß@?^=%&:()\/~+#!\-*]*?)';

			/**
			 * Handle images from third party services, e.g. google maps
			 *
			 * */
			$image_tags = apply_filters( 'cmplz_image_tags', array() );
			$image_pattern = '/<img.*?src=[\'|"](\X*?)[\'|"].*?>/s'; //matches multiline with s operator, for FB pixel
			if ( preg_match_all( $image_pattern, $output, $matches, PREG_PATTERN_ORDER )
			) {
				foreach ( $matches[1] as $key => $image_url ) {
					$total_match = $matches[0][ $key ];
					$found = cmplz_strpos_arr( $image_url, $image_tags );
					if ( $found !== false ) {
						$placeholder = cmplz_placeholder( false, $image_url );
						$service_name = cmplz_get_service_by_src( $image_url );
						$service_name = !$service_name ? 'general' :$service_name;
						$new = $total_match;
						$new = $this->add_data( $new, 'img', 'src-cmplz', $image_url );
						$new = $this->add_data( $new, 'img', 'service', $service_name );
						$new = $this->add_data( $new, 'img', 'category', 'marketing' );
						//remove lazy loading for images, as it is breaking on activation
						$new = str_replace('loading="lazy"', 'data-deferlazy="1"', $new );
						$new = $this->add_class( $new, 'img', apply_filters( 'cmplz_image_class', 'cmplz-image', $total_match, $found ) );
						$new = $this->replace_src( $new, apply_filters( 'cmplz_source_placeholder', $placeholder ) );
						$new = apply_filters('cmplz_image_html', $new, $image_url);

						if ( cmplz_use_placeholder( $image_url ) ) {
							$new = $this->add_class( $new, 'img', " cmplz-placeholder-element " );
							$new = '<div class="cmplz-placeholder-parent">' . $new . '</div>';
						}
						$output = str_replace( $total_match, $new, $output );
					}
				}
			}

			/**
			 * Handle styles (e.g. google fonts)
			 *
			 * */
			$style_pattern = '/<link.*?rel=[\'|"]stylesheet[\'|"][^>].*?href=[\'|"](\X*?)[\'|"][^>]*?>/i';
			if ( preg_match_all( $style_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
				foreach ( $matches[1] as $key => $style_url ) {
					$total_match = $matches[0][ $key ];
					//we don't block scripts with the functional data attribute
					if ( strpos( $total_match, 'data-category="functional"' ) !== false ) {
						continue;
					}

					//check if we can skip blocking this array if a specific string is included
					if ( cmplz_strpos_arr($total_match, $whitelisted_script_tags) ) {
						continue;
					}
					$found = cmplz_strpos_arr( $style_url, array_keys($known_style_tags) );
					if ( $found !== false ) {
						$match = $known_style_tags[$found];
						$new = $total_match;
						$service_name = $this->sanitize_service_name($match['name']);
						$new = $this->add_data( $new, 'link', 'category', apply_filters('cmplz_service_category', $match['category'], $total_match, $found) );
						$new = $this->add_data( $new, 'link', 'service', $service_name );
						$new = $this->replace_href( $new );
						$output = str_replace( $total_match, $new, $output );
					}
				}
			}

			/**
			 * Handle iframes from third parties
			 *
			 * */
			//the iframes URL pattern allows for a space, which may be included in a Google Maps embed.
			$iframe_pattern = '/<(iframe)[^>].*?src=[\'"](.*?)[\'"].*?>.*?<\/iframe>/is';
			if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
				foreach ( $matches[0] as $key => $total_match ) {
					$iframe_src = $matches[2][ $key ];
					if ( ( $tag_key = cmplz_strpos_arr($iframe_src, array_keys($blocked_scripts)) ) !== false ) {
                        $tag = $blocked_scripts[$tag_key];
						if ($tag['category']==='functional') {
							continue;
						}

						$is_video = $this->is_video( $iframe_src );
						$service_name = $this->sanitize_service_name($tag['name']);
						$new         = $total_match;
						$new         = preg_replace( '~<iframe\\s~i', '<iframe data-cmplz-target="'.apply_filters('cmplz_data_target', 'src', $total_match).'" data-src-cmplz="' . $iframe_src . '" ', $new , 1 ); // make sure we replace it only once

						//remove lazy loading for iframes, as it is breaking on activation
						$new = str_replace('loading="lazy"', 'data-deferlazy="1"', $new );
                        //check if we can skip blocking this array if a specific string is included
                        if ( cmplz_strpos_arr($total_match, $whitelisted_script_tags) ) continue;
						//we insert video/no-video class for specific video styling
						$video_class = $is_video ? 'cmplz-video' : 'cmplz-no-video';
						$video_class = apply_filters( 'cmplz_video_class', $video_class );

						$new = $this->replace_src( $new, apply_filters( 'cmplz_source_placeholder', 'about:blank' ) );
						$new = $this->add_class( $new, 'iframe', "cmplz-iframe cmplz-iframe-styles $video_class " );
						$new = $this->add_data( $new, 'iframe', 'service', $service_name );
						$new = $this->add_data( $new, 'iframe', 'category', $tag['category'] );

						if ( cmplz_use_placeholder( $iframe_src ) ) {
							$placeholder = cmplz_placeholder($tag['placeholder'], $iframe_src );
							$new = $this->add_class( $new, 'iframe', "cmplz-placeholder-element" );
							$new = $this->add_data( $new, 'iframe', 'placeholder-image', $placeholder );
							//allow for integrations to override html
							$new = apply_filters( 'cmplz_iframe_html', $new );

							//make sure there is a parent element which contains this iframe only, to attach the placeholder to
							if ( ! $is_video
							     && ! $this->no_div( $iframe_src )
							) {
								$new = '<div class="cmplz-placeholder-parent">' . $new . '</div>';
							}
						}
						$output = str_replace( $total_match, $new, $output );
					}
				}
			}

			/**
			 * specific classic wp video shortcode integration
			 */
			if ( cmplz_uses_thirdparty('youtube') || cmplz_uses_thirdparty('vimeo') ) {
				$iframe_pattern = '/<video class="wp-video-shortcode".*?<(source) type="video.*?src="(.*?)".*?>.*?<\/video>/is';
				if ( preg_match_all( $iframe_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
					foreach ( $matches[0] as $key => $total_match ) {
						$iframe_src = $matches[2][ $key ];
						if ( ( $tag_key = cmplz_strpos_arr( $iframe_src, array_keys( $blocked_scripts ) ) ) !== false ) {
							$tag = $blocked_scripts[ $tag_key ];
							if ( $tag['category'] === 'functional' ) {
								continue;
							}
							$service_name = sanitize_title( $tag['name'] );
							$new          = $total_match;
							//check if we can skip blocking this array if a specific string is included
							if ( cmplz_strpos_arr( $total_match, $whitelisted_script_tags ) ) {
								continue;
							}
							//we add an additional class to make it possible to link some css to the blocked html.
							$video_class_pattern = '/(["| ])(wp-video)(["| ])/is';
							$output              = preg_replace( $video_class_pattern, '$1wp-video cmplz-wp-video$3', $output );

							$video_class = apply_filters( 'cmplz_video_class', 'cmplz-video' );
							$new         = $this->add_class( $new, 'video', " $video_class " );
							$new         = $this->add_data( $new, 'video', 'service', $service_name );
							$new         = $this->add_data( $new, 'video', 'category', $tag['category'] );
							$new         = str_replace( array( 'wp-video-shortcode', 'controls="controls"' ), array( 'cmplz-wp-video-shortcode', '' ), $new );
							if ( cmplz_use_placeholder( $iframe_src ) ) {
								$placeholder = cmplz_placeholder( $tag['placeholder'], $iframe_src );
								$new         = $this->add_class( $new, 'video', "cmplz-placeholder-element" );
								$new         = $this->add_data( $new, 'video', 'placeholder-image', $placeholder );
								//allow for integrations to override html
								$new = apply_filters( 'cmplz_source_html', $new );
							}

							$output = str_replace( $total_match, $new, $output );
						}
					}
				}
			}

			/**
			 * set non iframe placeholders
			 *
			 * */
			if ( cmplz_use_placeholder() ) {
				foreach ( $placeholder_markers as $placeholder ) {
					//placeholder class can be comma separated list e.g. facebook service integration
					$classes = array_map('trim', explode(',',$placeholder['placeholder_class']) );
					foreach ( $classes as $placeholder_class ) {
						$placeholder_pattern = '/<(a|section|div|blockquote|twitter-widget)*[^>]*(id|class)=[\'" ]*[^>]*(' . $placeholder_class . ')[\'" ].*?>/is';
						if ( preg_match_all( $placeholder_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
							foreach ( $matches[0] as $key => $html_match ) {
								$el = $matches[1][ $key ];
								if ( ! empty( $el ) ) {
									$type        = $placeholder['placeholder'];
									$new_html    = $this->add_data( $html_match, $el, 'placeholder-image', cmplz_placeholder( $type, $placeholder_class ) );
									$new_html    = $this->add_data( $new_html, $el, 'category', $placeholder['category'] );
									$new_html    = $this->add_data( $new_html, $el, 'service', $placeholder['name'] );
									$new_html    = $this->add_class( $new_html, $el, "cmplz-placeholder-element" );
									$output      = str_replace( $html_match, $new_html, $output );
								}
							}
						}
					}

				}
			}

			/**
			 * Handle scripts from third parties
			 *
			 * */
			$script_pattern = '/(<script.*?>)(\X*?)<\/script>/is';
			$index          = 0;
			if ( preg_match_all( $script_pattern, $output, $matches, PREG_PATTERN_ORDER ) ) {
				foreach ( $matches[1] as $key => $script_open ) {
					//we don't block scripts with the functional data attribute
					if ( strpos( $script_open, 'data-category="functional"' ) !== false ) {
						continue;
					}

					//exclude ld+json
					if ( strpos( $script_open, 'application/ld+json' ) !== false ) {
						continue;
					}

                    //check if we can skip blocking this array if a specific string is included
                    $total_match = $matches[0][ $key ];
                    $content     = $matches[2][ $key ];
					if ( cmplz_strpos_arr($total_match, $whitelisted_script_tags) ) {
						continue;
					}

                    //if there is inline script here, it has some content
					if ( ! empty( $content ) )
					{
						if ( strpos( $content, 'avia_preview' ) !== false ) {
							continue;
						}

						$found = cmplz_strpos_arr( $content, array_keys($blocked_scripts) );
						if ( $found !== false ) {

                            $match = $blocked_scripts[$found];
							$service_name = $this->sanitize_service_name($match['name']);
							$new = $total_match;
							$category = apply_filters_deprecated( 'cmplz_script_class', array($match['category'], $total_match, $found), '6.0.0', 'cmplz_service_category', 'The cmplz_script_class filter has been deprecated since 6.0');
							$category = apply_filters('cmplz_service_category', $category, $total_match, $found);

							//skip if functional
							if ( $category === 'functional' ) {
								continue;
							}

							$new = $this->add_data( $new, 'script', 'category', $category );
							$new = $this->add_data( $new, 'script', 'service', $service_name );
							$new = $this->set_javascript_to_plain( $new );
							$waitfor = cmplz_strpos_arr( $content, $dependencies );
							if ( $waitfor !== false ) {
								$new = $this->add_data( $new, 'script', 'waitfor', $waitfor );
							}

							$output = str_replace( $total_match, $new, $output );
						}
					}

					//when script contains src
					$script_src_pattern = '/<script[^>]*?src=[\'"]' . $url_pattern . '[\'"].*?>/is';
					if ( preg_match_all( $script_src_pattern, $total_match, $src_matches, PREG_PATTERN_ORDER ) ) {
						foreach ( $src_matches[1] as $src_key => $script_src ) {
							$script_src = $src_matches[1][ $src_key ];
							$found = cmplz_strpos_arr( $script_src, array_keys($blocked_scripts) );
							if ( $found !== false ) {
                                $match = $blocked_scripts[$found];
                                $new = $total_match;
								$service_name = $this->sanitize_service_name($match['name']);
								$category = apply_filters_deprecated( 'cmplz_script_class', array($match['category'], $total_match, $found), '6.0.0', 'cmplz_service_category', 'The cmplz_script_class filter has been deprecated since 6.0');
	                            $new = $this->add_data( $new, 'script', 'category', apply_filters('cmplz_service_category', $category, $total_match, $found) );
								$new = $this->add_data( $new, 'script', 'service', $service_name );
								//native scripts don't have to be blocked
								if ( strpos( $new, 'data-category="functional"' ) === false
								) {
									$new = $this->set_javascript_to_plain( $new );
									$new = str_replace( 'src=', 'data-cmplz-src=', $new );

									if ( cmplz_strpos_arr( $found, $post_scribe_list )
									) {
										//will be to late for the first page load, but will enable post scribe on next page load
										if (!get_option('cmplz_post_scribe_required')) {
											update_option('cmplz_post_scribe_required', true);
										}
										$index ++;
										$new = $this->add_data( $new, 'script', 'post_scribe_id', 'cmplz-ps-' . $index );
										$new .= '<div class="cmplz-blocked-content-container">'
										        . COMPLIANZ::$cookie_blocker->blocked_content_text()
										        . '<div id="cmplz-ps-' . $index . '"><img src="' . cmplz_placeholder( 'div' ) . '"></div></div>';

									}

									//maybe add dependency
									$waitfor = cmplz_strpos_arr( $script_src, $dependencies );
									if ( $waitfor !== false ) {
										$new = $this->add_data( $new, 'script', 'waitfor', $waitfor );
									}
								}

								$output = str_replace( $total_match, $new, $output );
							}
						}
					}
				}
			}

			//add a marker so we can recognize if this function is active on the front-end
			$id = 1;
			if ( cmplz_get_option( 'consent_per_service' ) === 'yes' ) {
				$id = 2;
			}
			$output = str_replace( "<body ", "<body data-cmplz=$id ", $output );


			return apply_filters('cmplz_cookie_blocker_output', $output);
		}

		/**
		 * Set the javascript attribute of a script element to plain
		 *
		 * @param string $script
		 *
		 * @return string
		 */

		private function set_javascript_to_plain( string $script ): string {
			//check if it's already set to plain
			if ( strpos( $script, 'text/plain')!== false ) {
				return $script;
			}

			// Check text/javascript
			$pattern = '/<script[^>].*?\K(type=[\'|\"]text\/javascript[\'|\"])(?=.*>)/i';
			preg_match( $pattern, $script, $matches );
			if ( $matches ) {
				return preg_replace( $pattern, 'type="text/plain"', $script, 1 );
			}

			// Check type="module"
			$pattern_module = '/(<script[^>]*?)(type=[\'|\"]module[\'|\"])([^>]*?>)/i';
			preg_match($pattern_module, $script, $matches_module);
			if ($matches_module) {
				return preg_replace($pattern_module, '$1 type="text/plain" data-script-type="module" $3', $script, 1);
			}

			$pos = strpos( $script, "<script" );
			if ( $pos !== false ) {
				return substr_replace( $script, '<script type="text/plain"', $pos, strlen( "<script" ) );
			}

			return $script;
		}

		/**
		 * replace the src attribute with a placeholder of choice
		 *
		 * @param string $script
		 * @param string $new_src
		 *
		 * @return string
		 */

		private function replace_src( $script, $new_src ) {

			$pattern = '/src=[\'"](http:\/\/|https:\/\/|\/\/)([\s\wêëèéēėęàáâæãåāäöôòóœøüÄÖÜß.,@!?^=%&:\/~+#-;]*[\w@!?^=%&\/~+#-;]?)[\'"]/i';
			$new_src = ' src="' . $new_src . '" ';
			preg_match( $pattern, $script, $matches );
			$script = preg_replace( $pattern, $new_src, $script );

			return $script;
		}

		/**
		 * replace the href attribute with a data-href attribute
		 *
		 * @param string $link
		 *
		 * @return string
		 */

		private function replace_href( $link ) {
			return str_replace( 'href=', 'data-href=', $link );
		}

		/**
		 * Add a class to an HTML element
		 *
		 * @param string $html
		 * @param string $el
		 * @param string $class
		 *
		 * @return string
		 */

		public function add_class( $html, $el, $class ) {
			$classes = array_filter( explode(' ', $class) );
			preg_match( '/<' . $el . '[^\>]*[^\>\S]+\K(class=")(.*)"/i', $html, $matches );
			if ( $matches ) {
				foreach ($classes as $class){
					//check if class is already added
					if (strpos($matches[2], $class) === false && strlen(trim($class))>0) {
						$html = preg_replace( '/<' . $el . '[^\>]*[^\>\S]+\K(class=")/i', 'class="' . esc_attr($class) . ' ', $html, 1 );
					}
				}

			} else {
				$pos = strpos( $html, "<$el" );
				if ( $pos !== false ) {
					$html = substr_replace( $html,
						'<' . $el . ' class="' . esc_attr($class) . '"', $pos,
						strlen( "<$el" ) );
				}
			}

			return $html;
		}

		/**
		 * Add a data attribute to an html element
		 *
		 * @param string $html
		 * @param string $el
		 * @param string $id
		 * @param string $content
		 *
		 * @return string $html
		 */

		public function add_data( $html, $el, $id, $content ) {
			$content = esc_attr( $content );
			$id      = esc_attr( $id );
			$pattern = '/<'.$el.'[^>].*?\K(data-'.preg_quote($id, '/').'=[\'|\"]'.preg_quote($content, '/').'[\'|\"])(?=.*>)/i';
			preg_match( $pattern, $html, $matches );
			if ( !$matches ) {
				$pos = strpos( $html, "<$el" );
				if ( $pos !== false ) {
					$html = substr_replace( $html, '<' . $el . ' data-' . $id . '="' . $content . '"', $pos, strlen( "<$el" ) );
				}
			}

			return $html;
		}

		/**
		 * Check if this iframe source is a video
		 *
		 * @param $iframe_src
		 *
		 * @return bool
		 */

		private function is_video( $iframe_src ) {
			if ( strpos( $iframe_src, 'dailymotion' ) !== false
			     || strpos( $iframe_src, 'youtube' ) !== false
			     || strpos( $iframe_src, 'vimeo' ) !== false
			) {
				return true;
			}

			return false;
		}

		/**
		 * Check if this iframe source is soundcloud
		 *
		 * @param $iframe_src
		 *
		 * @return bool
		 */

		private function no_div( $iframe_src ) {
			if ( strpos( $iframe_src, 'soundcloud' ) !== false ) {
				return true;
			}

			return false;
		}

	}
}



