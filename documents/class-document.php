<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_document" ) ) {
	class cmplz_document {
		private static $_this;
		public $is_complianz_page = [];
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			//this shortcode is also available as gutenberg block
			add_shortcode( 'cmplz-document', array( $this, 'load_document' ) );
			add_shortcode( 'cmplz-consent-area', array( $this, 'show_consent_area' ) );
			add_shortcode( 'cmplz-cookies', array( $this, 'cookies' ) );
			add_shortcode( 'cmplz-manage-consent', array( $this, 'manage_consent_html' ) );
			add_shortcode( 'cmplz-revoke-link', array( $this, 'revoke_link' ) );
			add_shortcode( 'cmplz-accept-link', array( $this, 'accept_link' ) );

			//clear shortcode transients after post update
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'cmplz_document_email', array( $this, 'obfuscate_email' ) );
			add_filter( 'body_class', array( $this, 'add_body_class_for_complianz_documents' ) );
			add_action( 'wp', array( $this, 'maybe_autoredirect' ) );

			add_filter( 'the_content', array( $this, 'revert_divs_to_summary' ) );


        }

		static function this() {
			return self::$_this;
		}


		/**
		 * Get list of all required pages for current setup
		 *
		 * @return array $pages
		 *
		 *
		 */

		public function get_required_pages() {
			$regions  = cmplz_get_regions( $add_all_cat = true );
			$required = array();
			foreach ( $regions as $region ) {
				if ( ! isset( COMPLIANZ::$config->pages[ $region ] ) ) {
					continue;
				}

				$pages = COMPLIANZ::$config->pages[ $region ];
				foreach ( $pages as $type => $page ) {
					if ( ! $page['public'] ) {
						continue;
					}
					if ( $this->page_required( $page, $region ) ) {
						$required[ $region ][ $type ] = $page;
					}
				}
			}


			return $required;
		}

		/**
		 * Check if a page is required. If no condition is set, return true.
		 * condition is "AND", all conditions need to be met.
		 *
		 * @param array|string $page
		 * @param string       $region
		 *
		 * @return bool
		 */

		public function page_required( $page, $region ) {
			if ( ! is_array( $page ) ) {
				if ( ! isset( COMPLIANZ::$config->pages[ $region ][ $page ] ) ) {
					return false;
				}

				$page = COMPLIANZ::$config->pages[ $region ][ $page ];
			}

			//if it's not public, it's not required
			if ( isset( $page['public'] ) && $page['public'] == false ) {
				return false;
			}

			//if there's no condition, we set it as required
			if ( ! isset( $page['condition'] ) ) {
				return true;
			}

			if ( isset( $page['condition'] ) ) {
				$conditions    = $page['condition'];
				$condition_met = true;
				$invert = false;
				foreach (
					$conditions as $condition_question => $condition_answer
				) {
					$value  = cmplz_get_option( $condition_question );
					$invert = false;
					if ( ! is_array( $condition_answer )
						 && strpos( $condition_answer, 'NOT ' ) !== false
					) {
						$condition_answer = str_replace( 'NOT ', '', $condition_answer );
						$invert           = true;
					}

					$condition_answer = is_array( $condition_answer ) ? $condition_answer : array( $condition_answer );
					foreach ( $condition_answer as $answer_item ) {
						if ( is_array( $value ) ) {
							if ( !in_array($answer_item, $value ) ) {
								$condition_met = false;
							} else {
								$condition_met = true;
							}
						} else {
							$condition_met = ( $value == $answer_item );
						}

						//if one condition is met, we break with this condition, so it will return true.
						if ( $condition_met ) {
							break;
						}

					}

					//if one condition is not met, we break with this condition, so it will return false.
					if ( ! $condition_met ) {
						break;
					}
				}
				return $invert ? !$condition_met : $condition_met;
			}

			return false;

		}

		public function get_permalink($type, $region, $auto_redirect_region=false)
		{
			$url = "#";
			$page_id = $this->get_shortcode_page_id($type, $region);
			if ($page_id) {
				$url = get_permalink($page_id);
			}

			if ($auto_redirect_region){
				$url = $url.'?cmplz_region_redirect=true';
			}

			return $url;
		}

		/**
		 * Generate array of page links
		 *
		 * @return array
		 */
		public function get_page_links(): array {
			$page_links = array();
			$pages = COMPLIANZ::$config->pages;
			foreach ( $pages as $region => $region_pages ) {
				foreach ( $region_pages as $type => $page ) {
					if ( !$page['public'] ) continue;
					$title = COMPLIANZ::$document->get_page_title( $type, $region );
					$url = COMPLIANZ::$document->get_page_url( $type, $region );
					if ( $url !== '#') {
						$page_links[ $region ][$type]['title'] = $title;
						$page_links[ $region ][$type]['url'] = $url;
					}
				}
			}
			//now, make sure the general documents are added to each region: they're generic, so each region should have them.
			if ( isset($page_links['all']) ) {
				foreach ( $pages as $region => $region_pages ) {
					if ( $region === 'all' ) continue; //don't add the page to the 'all' region, only the an actual region
					foreach ($page_links['all'] as $type => $general_pages ) {
						$page_links[$region][$type] = $general_pages;
					}
				}
				unset($page_links['all']);
			}
			return $page_links;
		}

		/**
		 * Get list of documents, based on selected regions
		 * @return array
		 */

		public function get_available_documents(){
			$documents = COMPLIANZ::$config->pages;
			$output = array();
			foreach( $documents as $region => $region_documents ){
				foreach( $region_documents as $type => $data ){
					if (!in_array( $type, $output )) {
						$output[] = $type;
					}
				}
			}

			return $output;
		}

		/**
		 * Check if current locale supports formal
		 *
		 * @return bool
		 */
		public function locale_has_formal_variant() {
			$locale = get_locale();
			if ( in_array( $locale, COMPLIANZ::$config->formal_languages) ) {
				return true;
			}

			return false;
		}

		/**
		 * If a document is loaded with the autoredirect parameter, we redirect automatically
		 */

		public function maybe_autoredirect() {
			//if the autoredirect parameter is used, we look for the region of the passed type, and if necessary redirect to the redirect region
			if ( isset( $_GET['cmplz_region_redirect'] )
			     && isset( $_GET['cmplz-region'] )
			) {
				//get region from current page.
				global $post;
				$type = false;

				if ( $post ) {
					if ( preg_match( $this->get_shortcode_pattern( "gutenberg" ),
						$post->post_content, $matches )
					) {
						$type = $matches[1];
					} elseif ( preg_match( $this->get_shortcode_pattern( "classic" ),
						$post->post_content, $matches )
					) {
						$type = $matches[1];
					}
				}

				if ( !$type ){
					$slug = esc_url_raw($_SERVER['REQUEST_URI']);
					$documents = $this->get_available_documents();
					foreach($documents as $doc_type){
						if (strpos($slug, $doc_type)!==FALSE){
							$type = $doc_type;
						}
					}
				}

				$current_region = false;
				if ( substr( $type, - 3, 1 ) === '-' ) {
					$current_region = cmplz_get_region_from_legacy_type($type);
					$type = str_replace("-$current_region", '', $type);
				} elseif (isset($matches[2])) {
					$current_region = $matches[2];
				}

				if ($current_region) $type = str_replace("-$current_region", '', $type);
				$new_region = sanitize_title( $_GET['cmplz-region'] );

				//if region is "other", get the default region
				if ( $new_region === 'other') {
					$new_region = COMPLIANZ::$company->get_default_region();
				}

				if ( ! isset( COMPLIANZ::$config->pages[ $new_region ][ $type ] ) ) {
					return;
				}

				if ( in_array( $new_region, cmplz_get_regions() ) && $current_region !== $new_region ) {
					//get the URL of the new document
					$new_url = COMPLIANZ::$document->get_permalink( $type, $new_region );

					// Extract the anchor from the current URL
					$anchor = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT );
					if ( $anchor ) {
						$anchor = esc_attr( $anchor );
						$new_url .= '#' . $anchor;
					}

					wp_redirect( $new_url );
					exit;
				}
			}
		}

		/**
		 * Enqueue assets
		 */

		public function enqueue_assets() {
			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			if ( $this->is_complianz_page() ) {
				$load_css = cmplz_get_option( 'use_document_css' );
				if ( $load_css ) {
					$v = filemtime(cmplz_path . "assets/css/document$min.css");
					wp_register_style( 'cmplz-document',
						cmplz_url . "assets/css/document$min.css", false,
						$v );
					wp_enqueue_style( 'cmplz-document' );
				} else {
					$v = filemtime(cmplz_path . "assets/css/document-grid$min.css");
                    wp_register_style( 'cmplz-document-grid', cmplz_url . "assets/css/document-grid$min.css", false, $v );
                    wp_enqueue_style( 'cmplz-document-grid' );
                }
				add_action( 'wp_head', array( $this, 'inline_styles' ), 100 );
			}

			if ( cmplz_get_option( 'disable_cookie_block' ) !== 1 ) {
				$v = filemtime(cmplz_path . "assets/css/cookieblocker$min.css");
				wp_register_style( 'cmplz-general', cmplz_url . "assets/css/cookieblocker$min.css", false, $v );
				wp_enqueue_style( 'cmplz-general' );
			}

		}

		/**
		 * Get custom CSS for documents
		 *
		 * */

		public function inline_styles() {

			//basic color style for revoke button
			$custom_css = '';
			if ( cmplz_get_option( 'use_custom_document_css' ) ) {
				$custom_css .= cmplz_get_option( 'custom_document_css' );
			}

			$custom_css = apply_filters( 'cmplz_custom_document_css',
				$custom_css );
			if ( empty( $custom_css ) ) {
				return;
			}

			echo '<style>' . $custom_css . '</style>';
		}

		/**
		 * Check if the page is public
		 *
		 * @param string $type
		 * @param string $region
		 *
		 * @return bool
		 */

		public function is_public_page( $type, $region ) {
			if ( ! isset( COMPLIANZ::$config->pages[ $region ][ $type ] ) ) {
				return false;
			}

			if ( isset( COMPLIANZ::$config->pages[ $region ][ $type ]['public'] )
			     && COMPLIANZ::$config->pages[ $region ][ $type ]['public']
			) {
				return true;
			}

			return false;
		}

		/**
		 * period in seconds the wizard wasn't updated
		 *
		 * @param int $period
		 *
		 * @return bool not_updated_in
		 * */

		public function not_updated_in( $period ) {
			//if the wizard is never completed, we don't want any update warnings.
			if ( ! get_option( 'cmplz_wizard_completed_once' ) ) {
				return false;
			}

			$date = get_option( 'cmplz_documents_update_date' );
			if ( ! $date ) {
				return false;
			}

			$time_passed = time() - $date;
			if ( $time_passed > $period ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if an element should be inserted. AND implementation s
		 *
		 *
		 * */

		public function insert_element( $element, $post_id ) {
			return $this->callback_condition_applies( $element, $post_id ) && $this->condition_applies( $element, $post_id );
		}

		/**
		 * @param $element
		 *
		 * @return bool
		 */

		public function callback_condition_applies( $element, $post_id ) {

			if ( isset( $element['callback_condition'] ) ) {
				$conditions = is_array( $element['callback_condition'] ) ? $element['callback_condition'] : array( $element['callback_condition'] );
				foreach ( $conditions as $func ) {
					$invert = false;
					if ( strpos( $func, 'NOT ' ) !== false ) {
						$invert = true;
						$func   = str_replace( 'NOT ', '', $func );
					}

					if ( ! function_exists( $func ) ) {
						break;
					}
					$show_field = $post_id ? $func($post_id) : $func();
					if ( $invert ) {
						$show_field = ! $show_field;
					}
					if ( ! $show_field ) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Check if the passed condition applies
		 *
		 * @param array $element
		 * @param int   $post_id
		 *
		 * @return bool
		 */

		public function condition_applies( $element, $post_id ) {
			if ( isset( $element['condition'] ) ) {

				$condition_met = true;
				foreach (
					$element['condition'] as $question => $condition_answer
				) {
					$invert        = false;
					if ( $condition_answer === 'loop' ) {
						continue;
					}
					$field = cmplz_get_field($question);

					if ( ! $field ) {
						continue;
					}

					$type  = $field['type'];
					$value = $post_id ? get_post_meta($post_id, $question, true) : cmplz_get_option( $question, $post_id );
					if ( strpos( $condition_answer, 'NOT ' ) !== false ) {
						$condition_answer = str_replace( 'NOT ', '', $condition_answer );
						$invert           = true;
					}

					//check for emptiness af a value. in case of arrays, it is also empty if all values are 0.
					if ($condition_answer === 'EMPTY') {
						if (!empty($value) && is_array($value)){
							$is_empty = true;
							if (count($value)>0) {
								$is_empty = false;
							}
						} else {
							$is_empty = empty($value);
						}
						$current_condition_met = $is_empty;
					} else if ( $type === 'multicheckbox' ) {
						$current_condition_met = is_array($value) && in_array($condition_answer, $value);
					} else {
						$current_condition_met = $value == $condition_answer ;
					}
					$current_condition_met = $invert ? !$current_condition_met : $current_condition_met;
					$condition_met = $condition_met && $current_condition_met;
				}
				return $condition_met;

			}

			return true;
		}

		/**
		 * Check if this element should loop through dynamic multiple values
		 *
		 * @param array $element
		 *
		 * @return bool
		 * */

		public function is_loop_element( $element ) {
			if ( isset( $element['condition'] ) ) {
				foreach (
					$element['condition'] as $question => $condition_answer
				) {
					if ( $condition_answer === 'loop' ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Build a legal document by type
		 *
		 * @param string   $type
		 * @param          $region
		 * @param bool|int $post_id
		 *
		 * @return string
		 */

		public function get_document_html( string $type, $region = false, $post_id = false ) {
			//legacy, if region is not passed, we get it from the type string
			if ( ! $region ) {
				$region = cmplz_get_region_from_legacy_type( $type );
				$type   = str_replace( '-' . $region, '', $type );
			}

			if ( ! cmplz_has_region( $region ) || ! isset( COMPLIANZ::$config->pages[ $region ][ $type ] ) ) {
				return cmplz_sprintf( __( 'Region %s not activated for %s.', 'complianz-gdpr' ), strtoupper( $region ), $type );
			}

			if ( $post_id ) {
				$elements = cmplz_custom_document_data( $post_id, 'elements', $region );
			} else {
				$elements = COMPLIANZ::$config->pages[ $region ][ $type ]["document_elements"];
			}

			$elements =  is_array($elements) ? $elements : [];
			$html             = "";
			$paragraph        = 0;
			$sub_paragraph    = 0;
			$annex            = 0;
			$annex_arr        = array();
			$paragraph_id_arr = array();
			foreach ( $elements as $id => $element ) {
				//count paragraphs
				if ( $this->insert_element( $element, $post_id ) || $this->is_loop_element( $element ) ) {
					if ( isset( $element['title'] ) && ( ! isset( $element['numbering'] ) || $element['numbering'] ) ) {
						$sub_paragraph = 0;
						$paragraph ++;
						$paragraph_id_arr[ $id ]['main'] = $paragraph;
					}

					//count subparagraphs
					if ( isset( $element['subtitle'] ) && $paragraph > 0 && ( ! isset( $element['numbering'] ) || $element['numbering'] ) ) {
						$sub_paragraph++;
						$paragraph_id_arr[ $id ]['main'] = $paragraph;
						$paragraph_id_arr[ $id ]['sub']  = $sub_paragraph;
					}

					//count dropdowns as sub parapgraphs
					if ( isset( $element['dropdown-open'] ) && $paragraph > 0 && ( ! isset( $element['numbering'] ) || $element['numbering'] ) ) {
						$sub_paragraph ++;
						$paragraph_id_arr[ $id ]['main'] = $paragraph;
						$paragraph_id_arr[ $id ]['sub']  = $sub_paragraph;
					}

					//count annexes
					if ( isset( $element['annex'] ) ) {
						$annex ++;
						$annex_arr[ $id ] = $annex;
					}
				}
				if ( $this->is_loop_element( $element ) && $this->insert_element( $element, $post_id ) ) {
					$fieldname    = key( $element['condition'] );
					$values       = $post_id ? get_post_meta($post_id, $fieldname, true) : cmplz_get_option( $fieldname );
					$loop_content = '';
					if ( ! empty( $values ) ) {
						foreach ( $values as $value ) {
							if ( ! is_array( $value ) ) {
								$value = array( $value );
							}
							$fieldnames = array_keys( $value );
							if ( count( $fieldnames ) == 1 && $fieldnames[0] === 'key' ) {
								continue;
							}

							$loop_section = $element['content'];
							foreach ( $fieldnames as $c_fieldname ) {
								$field_value = ( isset( $value[ $c_fieldname ] ) ) ? $value[ $c_fieldname ] : '';
								if ( ! empty( $field_value ) && is_array( $field_value ) ) {
									$field_value = implode( ', ', $field_value );
								}
								$loop_section = str_replace( '[' . $c_fieldname . ']', $field_value, $loop_section );
							}
							$loop_content .= $loop_section;
						}
						$html .= $this->wrap_header( $element, $paragraph, $sub_paragraph, $annex );
						$html .= $this->wrap_content( $loop_content );
					}
				} elseif ( $this->insert_element( $element, $post_id ) ) {
					$html .= $this->wrap_header( $element, $paragraph, $sub_paragraph, $annex );
					if ( isset( $element['content'] ) ) {
						$html .= $this->wrap_content( $element['content'], $element );
					}
				}

				if ( isset( $element['callback'] ) && function_exists( $element['callback'] )
				) {
					$func = $element['callback'];
					$html .= $func();
				}
			}

			$html = $this->replace_fields( $html, $paragraph_id_arr, $annex_arr, $post_id, $type, $region );

			$comment = apply_filters( "cmplz_document_comment", "\n"
			                                                    . "<!-- Legal document generated by Complianz | GDPR/CCPA Cookie Consent https://wordpress.org/plugins/complianz-gdpr -->"
			                                                    . "\n" );

			$html         = $comment . '<div id="cmplz-document" class="cmplz-document '.$type.' cmplz-document-'.$region.'">' . $html . '</div>';
			$html         = wp_kses( $html, cmplz_allowed_html() );

			//in case we still have an unprocessed shortcode
			//this may happen when a shortcode is inserted in combination with gutenberg
			$html = do_shortcode($html);

			return apply_filters( 'cmplz_document_html', $html, $type, $post_id );
		}

		/**
		 * Wrap the header for a paragraph
		 *
		 * @param array $element
		 * @param int   $paragraph
		 * @param int   $sub_paragraph
		 * @param int   $annex
		 *
		 * @return string
		 */

		public function wrap_header( $element, $paragraph, $sub_paragraph, $annex ) {
			$nr = "";
			$html = "";
			if ( isset( $element['annex'] ) ) {
				$nr = __( "Annex", 'complianz-gdpr' ) . " " . $annex . ": ";
				if ( isset( $element['title'] ) ) {
					return '<h2 class="annex">' . esc_html( $nr )
					       . esc_html( $element['title'] ) . '</h2>';
				}
				if ( isset( $element['subtitle'] ) ) {
					return '<p class="subtitle annex">' . esc_html( $nr )
					       . esc_html( $element['subtitle'] ) . '</p>';
				}
			}

			if ( isset( $element['title'] ) ) {
				if ( empty( $element['title'] ) ) {
					return "";
				}
				$nr = '';
				if ( $paragraph > 0
				     && $this->is_numbered_element( $element )
				) {
					$nr         = $paragraph;
					$index_char = apply_filters( 'cmplz_index_char', '.' );
					$nr         = $nr . $index_char . ' ';
				}

				return '<h2>' . esc_html( $nr )
				       . esc_html( $element['title'] ) . '</h2>';
			}

			if ( isset( $element['subtitle'] ) ) {
				if ( $paragraph > 0 && $sub_paragraph > 0 && $this->is_numbered_element( $element ) ) {
					$nr = $paragraph . "." . $sub_paragraph . " ";
				}
				return '<p class="cmplz-subtitle">' . esc_html( $nr ) . esc_html( $element['subtitle'] ) . '</p>';
			}


			// Adds a dropdown to the Privacy Statement. Opens a div and should be closed with dropdown-close
			if ( isset( $element['dropdown-open'] ) ) {
				if ( $paragraph > 0 && $sub_paragraph > 0 && $this->is_numbered_element( $element ) ) {
					$nr = $paragraph . "." . $sub_paragraph . " ";
				}
				$dp_class = isset($element['dropdown-class']) ? $element['dropdown-class'] : '';
				$html .= '<details class="cmplz-dropdown '.$dp_class.'">';
				if ( isset( $element['dropdown-title'] ) ) {
					$html .= '<summary><div><h3>'. esc_html( $nr ) . esc_html( $element['dropdown-title'] ) . '</h3></div></summary>';
				}
				return $html;
			}
			if ( isset( $element['dropdown-close'] ) ) {
				return '</details>';
			}

			return "";

		}

		/**
		 * Check if this element should be numbered
		 * if no key is set, default is true
		 *
		 * @param array $element
		 *
		 * @return bool
		 */

		public function is_numbered_element( $element ) {
			if ( ! isset( $element['numbering'] ) ) {
				return true;
			}
			return $element['numbering'];
		}

		/**
		 * Wrap subheader in html
		 *
		 * @param string $header
		 * @param int    $paragraph
		 * @param int    $subparagraph
		 *
		 * @return string $html
		 */

		public function wrap_sub_header( $header, $paragraph, $subparagraph ) {
			if ( empty( $header ) ) {
				return "";
			}

			return '<b>' . esc_html( $header ) . '</b><br>';
		}

		/**
		 * Wrap content in html
		 *
		 * @param string $content
		 * @param bool   $element
		 *
		 * @return string
		 */
		public function wrap_content( string $content, $element = false ) {
			if ( empty( $content ) ) {
				return "";
			}
			$class = isset( $element['class'] ) ? 'class="' . esc_attr( $element['class'] ) . '"' : '';
			if (isset($element['p']) && !$element['p']) {
				return $content;
			}
			return "<p $class>" . $content . "</p>";
		}

		/**
		 * Replace all fields in the resulting output
		 *
		 * @param string $html
		 * @param array  $paragraph_id_arr
		 * @param array  $annex_arr
		 * @param int    $post_id
		 * @param string $type
		 * @param string $region
		 *
		 * @return string $html
		 */

		private function replace_fields( string $html, $paragraph_id_arr, $annex_arr, $post_id, $type, $region ): string {
			//replace references
			foreach ( $paragraph_id_arr as $id => $paragraph ) {
				$html = str_replace( "[article-$id]",
					 cmplz_sprintf( __( '(See paragraph %s)', 'complianz-gdpr' ),
						esc_html( $paragraph['main'] ) ), $html );
			}

			foreach ( $annex_arr as $id => $annex ) {
				$html = str_replace( "[annex-$id]",
					 cmplz_sprintf( __( '(See annex %s)', 'complianz-gdpr' ),
						esc_html( $annex ) ), $html );
			}

			$active_cookiebanner_id = cmplz_get_default_banner_id();
			$banner = cmplz_get_cookiebanner($active_cookiebanner_id);
			//some custom elements
			$html = str_replace( "[cookie_accept_text]", ( $banner->accept_x ?? ''), $html );
			$html = str_replace( "[cookie_save_preferences_text]", ($banner->save_preferences_x ?? ''), $html );

			$html = str_replace( "[domain]", '<a href="' . esc_url_raw( get_home_url() ) . '">' . esc_url_raw( get_home_url() ) . '</a>', $html );
			$html = str_replace( "[cookie-statement-url]", cmplz_get_document_url( 'cookie-statement', $region ), $html );
			$html = str_replace( "[privacy-statement-url]", $this->get_page_url( 'privacy-statement', $region ), $html );
			$html = str_replace( "[privacy-statement-children-url]", $this->get_page_url( 'privacy-statement-children', $region ), $html );
			$html = str_replace( "[site_url]", site_url(), $html );

			//us can have two types of titles
			$cookie_policy_title = esc_html( $this->get_document_title( 'cookie-statement', $region ) );
			$html = str_replace( '[cookie-statement-title]', $cookie_policy_title, $html );

			$date = $post_id ? strtotime(get_the_date( 'd F Y', $post_id )) : get_option( 'cmplz_publish_date' );//use default date format, to ensure that strtotime works.
			$date = cmplz_localize_date( $date );
			$html = str_replace( array( "[publish_date]", "[sync_date]" ), array( esc_html( $date ), esc_html( COMPLIANZ::$banner_loader->get_last_cookie_sync_date() ) ), $html );
			$checked_date = cmplz_localize_date( get_option( 'cmplz_documents_update_date' ) );
			$html         = str_replace( "[checked_date]", esc_html( $checked_date ), $html );

			//because the phonenumber is not required, we need to allow for an empty phonenr, making a dynamic string necessary.
			$contact_dpo = cmplz_get_option( 'email_dpo' );
			$phone_dpo   = cmplz_get_option( 'phone_dpo' );
			if ( !empty( $phone_dpo ) ) {
				$contact_dpo .= " " . cmplz_sprintf( _x( "or by telephone on %s", 'if phonenumber is entered, this string is part of the sentence "you may contact %s, via %s or by telephone via %s"', "complianz-gdpr" ), $phone_dpo );
			}
			$html = str_replace( "[email_dpo]", $contact_dpo, $html );

			$contact_dpo_uk = cmplz_get_option( 'email_dpo_uk' );
			$phone_dpo_uk   = cmplz_get_option( 'phone_dpo_uk' );
			if ( !empty( $phone_dpo ) ) {
				$contact_dpo_uk .= " " . cmplz_sprintf( _x( "or by telephone on %s", 'if phonenumber is entered, this string is part of the sentence "you may contact %s, via %s or by telephone via %s"', "complianz-gdpr" ), $phone_dpo_uk );
			}
			$html = str_replace( "[email_dpo_uk]", $contact_dpo_uk, $html );

			//load data for processing agreements and dataleaks
			$fields = [];
			if ($post_id) {
				$fields = cmplz_custom_document_data( $post_id, 'fields', $region );
			}
			$fields = array_merge(COMPLIANZ::$config->fields, $fields);
			//replace all fields.
			foreach ( $fields  as $field ) {
				$fieldname = $field['id'];
				if ( strpos( $html, "[$fieldname]" ) !== false ) {
					$html = str_replace( "[$fieldname]", $this->get_plain_text_value( $field , true ), $html );
					//when there's a closing shortcode it's always a link
					$html = str_replace( "[/$fieldname]", "</a>", $html );
				}

				if ( strpos( $html, "[comma_$fieldname]" ) !== false ) {
					$html = str_replace( "[comma_$fieldname]", $this->get_plain_text_value( $field, false ), $html );
				}
			}


			return $html;

		}

		/**
		 *
		 * Get the plain text value of an option
		 *
		 * @param string $fieldname
		 * @param int    $value
		 * @param bool   $list_style
		 *
		 * @return string
		 */

		private function get_plain_text_value($field, $list_style ): string {

			$front_end_label = $field['document_label'] ?? false;
			$value = cmplz_get_option($field['id']);

			if ($field['type'] === 'url' ) {
				$value = '<a href="' . $value . '" target="_blank">';
			} elseif ($field['type'] === 'email' ) {
				$value = apply_filters( 'cmplz_document_email', $value );
			} elseif ($field['type'] === 'radio' ) {
				$options =$field['options'];
				$value   = isset( $options[ $value ] ) ? $options[ $value ] : '';
			} elseif ($field['type'] === 'textarea' ) {
				//preserve linebreaks
				$value = nl2br( $value );
			} elseif ( is_array( $value ) ) {
				$options =$field['options'];
				$labels = "";
				foreach ( $value as $index ) {
					//trying to fix strange issue where index is not set
					if ( ! isset( $options[ $index ] ) ) {
						continue;
					}

					if ( $list_style ) {
						$labels .= "<li>" . esc_html( $options[ $index ] ) . '</li>';
					} else {
						$labels .= $options[ $index ] . ', ';
					}
				}
				if ( $list_style ) {
					$labels = "<ul>" . $labels . "</ul>";
				} else {
					$labels = esc_html( rtrim( $labels, ', ' ) );
					$labels = strrev( implode( strrev( ' ' . __( 'and',
							'complianz-gdpr' ) ),
						explode( strrev( ',' ), strrev( $labels ), 2 ) ) );
				}

				$value = $labels;
			} else {
				if ( isset($field['options'] ) ) {
					$options
						=$field['options'];
					if ( isset( $options[ $value ] ) ) {
						$value = $options[ $value ];
					}
				}
			}

			if ( $front_end_label && ! empty( $value ) ) {
				$value = $front_end_label . $value;
			}

			return $value;
		}

		/**
		 * Get list of cookie statement snapshots
		 * @param array $args
		 *
		 * @return array|false
		 */
		public function get_cookie_snapshot_list( $args = array() ) {
			$defaults   = array(
					'number' => 10,
					'offset' => 0,
					'order'  => 'DESC',
					'start_date'    => 0,
					'end_date'      => 9999999999999,
			);
			$args       = wp_parse_args( $args, $defaults );
			$uploads    = wp_upload_dir();
			$upload_dir = $uploads['basedir'];
			$upload_url = $uploads['baseurl'];
			$path       = $upload_dir . '/complianz/snapshots/';
			$url        = $upload_url . '/complianz/snapshots/';
			$filelist   = array();
			$extensions = array( "pdf" );
			$index = 0;
			if ( file_exists( $path ) && $handle = opendir( $path ) ) {
				while ( false !== ( $file = readdir( $handle ) ) ) {
					if ( $file != "." && $file != ".." ) {
						$file = $path . $file;
						$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
						if ( is_file( $file ) && in_array( $ext, $extensions ) ) {
							$index++;
							if ( empty( $args['search'] ) || strpos( $file, $args['search'] ) !== false) {
								if ($args['start_date'] < filemtime( $file ) && filemtime( $file ) < $args['end_date'] ) {
									$filelist[filemtime($file) . $index]["path"] = $file;
									$filelist[filemtime($file) . $index]["url"]  = trailingslashit($url).basename($file);
									$filelist[filemtime($file) . $index]["file"] = basename($file);
									$filelist[filemtime($file) . $index]["time"] = filemtime($file);
								}
							}
						}
					}
				}
				closedir( $handle );
			}

			if ( $args['order'] === 'DESC' ) {
				krsort( $filelist );
			} else {
				ksort( $filelist );
			}

			if ( empty( $filelist ) ) {
				return array();
			}

			$page       = (int) $args['offset'];
			$total      = count( $filelist ); //total items in array
			$limit      = $args['number'];
			$totalPages = ceil( $total / $limit ); //calculate total pages
			$page       = max( $page, 1 ); //get 1 page when $_GET['page'] <= 0
			$page       = min( $page, $totalPages ); //get last page when $_GET['page'] > $totalPages
			$offset     = ( $page - 1 ) * $limit;
			if ( $offset < 0 ) {
				$offset = 0;
			}

			$filelist = array_slice( $filelist, $offset, $limit );

			if ( empty( $filelist ) ) {
				return array();
			}

			return $filelist;

		}

		/**
		 * Get the region for a post id, based on the post type.
		 *
		 * @param int|array $post_id
		 *
		 * @return string|bool $region
		 * */

		public function get_region( $post_id ) {

			if ( ! is_numeric( $post_id )  ) {
				if ( isset( $post_id['source'] )) {
					return substr( $post_id['source'], - 2 );
				}

				if ( strpos($post_id, 'cmplz-') !== false ) {
					return substr( $post_id, - 2 );
				}
			}

			if ( $post_id ) {
				$term = wp_get_post_terms( $post_id, 'cmplz-region' );
				if ( is_wp_error( $term ) ) {
					return false;
				}

				if ( isset( $term[0] ) ) {
					return $term[0]->slug;
				}

				return false;
			}

			return false;
		}

		/**
		 * Set the region in a post
		 *
		 * @param      $post_id
		 * @param bool $region
		 */

		public function set_region( $post_id, $region = false ) {
			if ( ! $region ) {
				$region = $this->get_region( $post_id );
			}

			if ( ! $region ) {
				$regions = cmplz_get_regions();

				if ( isset( $_GET['page'] ) ) {
					$page = sanitize_title( $_GET['page'] );
					foreach ( $regions as $r ) {
						if ( strpos( $page, '-' . $r ) !== false ) {
							$region = $r;
						}
					}
				}
			}

			$term = get_term_by( 'slug', $region, 'cmplz-region' );
			if ( ! $term ) {
				wp_insert_term( COMPLIANZ::$config->regions[ $region ]['label'],
					'cmplz-region', array(
						'slug' => $region,
					) );
				$term = get_term_by( 'slug', $region, 'cmplz-region' );
			}

			if ( empty( $term ) ) {
				return;
			}

			$term_id = $term->term_id;

			wp_set_object_terms( $post_id, array( $term_id ), 'cmplz-region' );
		}

		/**
		 * Check if legal documents should be updated
		 *
		 * @return bool
		 */

		public function documents_need_updating() {
			if ( cmplz_has_region('us')
			     && $this->not_updated_in( MONTH_IN_SECONDS * 12 )
			) {
				return true;
			}

			return false;
		}

		/**
		 * Check if legal documents should be updated, and send mail to admin if so
		 */

		public function cron_check_last_updated_status() {
			$subject='';
			if ( $this->documents_need_updating()
			     && ! get_option( 'cmplz_update_legal_documents_mail_sent' )
			) {
				update_option( 'cmplz_update_legal_documents_mail_sent', true );
				$to = get_option( 'admin_email' );

				$headers = array();
				if ( empty( $subject ) ) {
					$subject
						= cmplz_sprintf( _x( 'Your legal documents on %s need to be updated.',
						'Subject in notification email', 'complianz-gdpr' ),
						home_url() );
				}
				$link = '<a href="'.add_query_arg( array('page' => 'cmplz-wizard'), admin_url('admin.php?page=cmplz-wizard') ).'">';

				$message
					= cmplz_sprintf( _x( 'Your legal documents on %s have not been updated in 12 months. Please log in and run the %swizard%s in the Complianz plugin to check if everything is up to date.',
					'notification email', 'complianz-gdpr' ),
					home_url(), $link, "</a>" );

				$message .= '<br><br>'.__("This message was generated by Complianz GDPR/CCPA.", "complianz-gdpr");

				add_filter( 'wp_mail_content_type', function ( $content_type ) {
					return 'text/html';
				} );

				wp_mail( $to, $subject, $message, $headers );

				// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
				remove_filter( 'wp_mail_content_type',
					'set_html_content_type' );
			}
		}

		/**
		 * Render html for the manage consent shortcode
		 * @param array  $atts
		 * @param null   $content
		 * @param string $tag
		 *
		 * @return string
		 */

		public function manage_consent_html( $atts = array(), $content = null, $tag = ''
		) {

			$html = '<div id="cmplz-manage-consent-container-nojavascript">'.
					_x( "You have loaded the Cookie Policy without javascript support.", "cookie policy", "complianz-gdpr" ).'&nbsp;'.
					_x( "On AMP, you can use the manage consent button on the bottom of the page.", "cookie policy", "complianz-gdpr" ).
					'</div>';
			$html .= '<div id="cmplz-manage-consent-container" name="cmplz-manage-consent-container" class="cmplz-manage-consent-container"></div>';
			return $html;
		}


		public function revoke_link( $atts = array(), $content = null, $tag = '' ) {
			// normalize attribute keys, lowercase
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );
			ob_start();
			$atts = shortcode_atts( array( 'text' => false ), $atts, $tag );
			echo cmplz_revoke_link($atts['text']);
			return ob_get_clean();
		}

		/**
		 * Display an accept hyperlink
		 *
		 * @param array  $atts
		 * @param null   $content
		 * @param string $tag
		 *
		 * @return string
		 */

		public function accept_link( $atts = array(), $content = null, $tag = ''
		) {
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );
			ob_start();
			$atts = shortcode_atts( array( 'text' => false ), $atts, $tag );
			$accept_text = $atts['text'] ?: __("Click to accept marketing cookies", "complianz-gdpr");
			$html = '<div class="cmplz-custom-accept-btn cmplz-accept"><a href="#">' . $accept_text . '</a></div>';
			echo $html;
			return ob_get_clean();
		}


		/**
		 * add a class to the body telling the page it's a complianz doc. We use this for the soft cookie wall
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		public function add_body_class_for_complianz_documents( $classes ) {
			global $post;
			if ( $post && $this->is_complianz_page( $post->ID ) ) {
				$classes[] = 'cmplz-document';
			}

			return $classes;
		}

		/**
		 * obfuscate the email address
		 *
		 * @param $email
		 *
		 * @return string
		 */

		public function obfuscate_email( $email ) {
			return antispambot( $email );
		}

		/**
		 * Render shortcode for cookie list
		 *
		 * @hooked shortcode hook
		 *
		 * @param array  $atts
		 * @param null   $content
		 * @param string $tag
		 *
		 * @return false|string
		 * @since  2.0
		 */

		public function cookies( $atts = array(), $content = null, $tag = '' ) {
			ob_start();
			echo cmplz_used_cookies();
			return ob_get_clean();
		}

		/**
		 * get the shortcode or block for a page type
		 *
		 * @param string $type
		 * @param string $region
		 * @param bool   $force_classic
		 *
		 * @return string $shortcode
		 *
		 */

		public function get_shortcode( $type, $region, $force_classic = false
		) {
			//even if on gutenberg, with elementor we have to use classic shortcodes.
			if ( ! $force_classic && cmplz_uses_gutenberg()
			     && ! $this->uses_elementor()
			) {
				$page = COMPLIANZ::$config->pages[ $region ][ $type ];
				$ext  = $region === 'eu' ? '' : '-' . $region;
				return '<!-- wp:complianz/document {"title":"' . $page['title'] . '","selectedDocument":"' . $type . $ext . '"} /-->';
			}

			return '[cmplz-document type="' . $type . '" region="' . $region . '"]';
		}

		/**
		 * Get shortcode pattern for this site, gutenberg or classic
		 *
		 * @param string $type
		 * @param bool   $legacy
		 *
		 * @return string
		 */
		public function get_shortcode_pattern(
			$type = "classic", $legacy = false
		) {
			if ( $type === 'classic' && $legacy ) {
				return '/\[cmplz\-document.*?type="(.*?)".*?]/i';
			}
			if ( $type === 'classic' && ! $legacy ) {
				return '/\[cmplz\-document.*?type="(.*?)".*?region="(.*?)".*?]/i';
			} else {
				return '/<!-- wp:complianz\/document {.*?"selectedDocument":"([^\"].*?)\".*?} \/-->/i';
			}
		}

		/**
		 * Check if this site uses Elementor
		 * When Elementor is used, the classic shortcode should be used, even when on Gutenberg
		 *
		 * @return bool $uses_elementor
		 */

		public function uses_elementor() {
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				return true;
			}

			return false;
		}


		/**
		 *
		 * Get type of document
		 *
		 * @param int $post_id
		 *
		 * @return array
		 *
		 *
		 */

		public function get_document_data( $post_id ) {

			$pattern = $this->get_shortcode_pattern('classic' );
			$pattern_legacy = $this->get_shortcode_pattern('classic' , true );
			$pattern_gutenberg = $this->get_shortcode_pattern('gutenberg' );
			$post    = get_post( $post_id );

			$content = $post->post_content;
			$output = array(
				'type' => '',
				'region' => false,
			);
			if ( preg_match_all( $pattern, $content, $matches, PREG_PATTERN_ORDER ) ) {
				if ( isset( $matches[1][0] ) ) {
					$output['type'] = $matches[1][0];
				}
				if ( isset( $matches[2][0] ) ) {
					$output['region'] = $matches[2][0];
				}
			} else if ( preg_match_all( $pattern_gutenberg, $content, $matches, PREG_PATTERN_ORDER ) ) {
				if ( isset( $matches[1][0] ) ) {
					$output['type'] = $matches[1][0];
					//gutenberg can have the region appended to the type. Remove it.
					//remove the string after the last -
					$string_to_remove = substr($output['type'], strrpos($output['type'], '-')+1);
					$regions = array_keys(COMPLIANZ::$config->regions);
					$regions[]='all';
					if ( in_array( $string_to_remove, $regions, true ) ){
						$output['type'] = substr($output['type'], 0, strrpos($output['type'], '-'));
					}
				}
				if ( isset( $matches[2][0] ) ) {
					$output['region'] = $matches[2][0];
				}
			} else if ( preg_match_all( $pattern_legacy, $content, $matches, PREG_PATTERN_ORDER ) ) {
				if ( isset( $matches[1][0] ) ) {
					$output['type'] = $matches[1][0];
				}
				if ( isset( $matches[2][0] ) ) {
					$output['region'] = $matches[2][0];
				}
			}
			return $output;
		}


		/**
		 * Lightens/darkens a given colour (hex format), returning the altered colour in hex format.7
		 *
		 * @param string $hex Colour as hexadecimal (with or without hash);
		 *
		 * @percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
		 * @return string Lightened/Darkend colour as hexadecimal (with hash);
		 */

		function color_luminance( $hex, $percent ) {
			if ( empty( $hex ) ) {
				return $hex;
			}
			// validate hex string
			$hex     = preg_replace( '/[^0-9a-f]/i', '', $hex );
			$new_hex = '#';

			if ( strlen( $hex ) < 6 ) {
				$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2]
				       + $hex[2];
			}

			// convert to decimal and change luminosity
			for ( $i = 0; $i < 3; $i ++ ) {
				$dec     = hexdec( substr( $hex, $i * 2, 2 ) );
				$dec     = min( max( 0, $dec + $dec * $percent ), 255 );
				$new_hex .= str_pad( dechex( $dec ), 2, 0, STR_PAD_LEFT );
			}

			return $new_hex;
		}


		/**
		 * loads document content on shortcode call
		 *
		 * @param array  $atts
		 * @param null   $content
		 * @param string $tag
		 *
		 * @return string $html
		 *
		 *
		 */

		public function load_document(
			$atts = array(), $content = null, $tag = ''
		) {
			// normalize attribute keys, lowercase
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );


			ob_start();

			// override default attributes with user attributes
			$atts   = shortcode_atts( array(
				'type'   => false,
				'region' => false
			),
				$atts, $tag );
			$type   = sanitize_title( $atts['type'] );
			$region = sanitize_title( $atts['region'] );
			if ( $type ) {
				$html         = $this->get_document_html( $type, $region );
				$allowed_html = cmplz_allowed_html();
				echo wp_kses( $html, $allowed_html );
			}

			return ob_get_clean();
		}

		/**
		 * Show content conditionally, based on consent
		 * @param array  $atts
		 * @param string   $content
		 * @param string $tag
		 *
		 * @return false|string
		 */

		public function show_consent_area_clientside(
				$atts = array(), $content = null, $tag = ''
		) {
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );

			//should always be wrapped in closing tag
			if (empty($content)) {
				return '';
			}
			$blocked_text = __('Click to accept the cookies for this service', 'complianz-gdpr');
			// override default attributes with user attributes
			$atts = shortcode_atts( array(
					'id'       => 'default',
					'service'  => false,
					'category' => false,
					'text'     => $blocked_text,
			), $atts, $tag );

			$category = $atts['category'] ? cmplz_sanitize_category($atts['category']) : 'marketing';
			$service = $atts['service'] ? COMPLIANZ::$cookie_blocker->sanitize_service_name($atts['service']) : 'general';
			$blocked_text = sanitize_text_field( $atts['text'] );
			$blocked_text = apply_filters( 'cmplz_accept_cookies_blocked_content', $blocked_text );
			$block_id = sanitize_title($atts['id']);

			global $post;
			$post_id = $post->ID ?? 0;
			ob_start();
			?>
			<div class="cmplz-consent-area cmplz-placeholder" data-post_id="<?php echo esc_attr($post_id)?>" data-block_id="<?php echo esc_attr($block_id)?>" data-category="<?php echo esc_attr($category)?>" data-service="<?php echo esc_attr($service)?>">
				<a href="#" class="<?php echo 'cmplz_'. esc_attr($category);?>_consentarea" ><?php echo wp_kses_post($blocked_text)?></a>
			</div>
			<?php
			return  ob_get_clean();
		}

		/**
		 * Show content conditionally, based on consent
		 * @param array  $atts
		 * @param string   $content
		 * @param string $tag
		 *
		 * @return false|string
		 */

		public function show_consent_area(
				$atts = array(), $content = null, $tag = ''
		) {
			// normalize attribute keys, lowercase
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );
			ob_start();

			//should always be wrapped in closing tag
			if (empty($content)) return '';
			$blocked_text = __('Click to accept the cookies for this service', 'complianz-gdpr');
			// override default attributes with user attributes
			$atts   = shortcode_atts( array(
					'id'       => 'default',
					'clientside' => false,
					'cache_redirect'   => false,
					'scroll_into_view'   => true,
					'service'   => 'general',
					'category'   => false,
					'text'   => $blocked_text,
			), $atts, $tag );

			//new option: clientside rendering of consent area, like in gutenberg.
			if ($atts['clientside']) {
				return $this->show_consent_area_clientside($atts, $content, $tag);
			}

			if ($atts['cache_redirect']==="true" || $atts['cache_redirect']=== 1 || $atts['cache_redirect']=== "1" || $atts['cache_redirect'] === true) {
				$cache_redirect = true;
			} else {
				$cache_redirect = false;
			}
			$scroll_into_view = $atts['scroll_into_view']==="true" || $atts['scroll_into_view']=== 1 || $atts['scroll_into_view'] === true;
			$category = $atts['category'] ? cmplz_sanitize_category($atts['category']) : 'no-category';

			$service = $atts['category'] ? 'no-service' : COMPLIANZ::$cookie_blocker->sanitize_service_name($atts['service']);
			$blocked_text = sanitize_text_field( $atts['text'] );

			if ( cmplz_has_service_consent($service) || cmplz_has_consent($category) ) {
				if ($cache_redirect) {
					//redirect if not on redirect url, to prevent caching issues
					?>
					<script>
						var url = window.location.href;
						if (url.indexOf('cmplz_consent=1') === -1) {
							if (url.indexOf('?') !== -1) {url += '&';} else {url += '?';}
							url += 'cmplz_consent=1';
							window.location.replace(url);
						} else{
							console.log("already on redirect url");
						}
					</script>
					<?php
				}
				echo '<a id="cmplz_consent_area_anchor"></a>'.do_shortcode($content);
			} else {
				//no consent
				$blocked_text = apply_filters( 'cmplz_accept_cookies_blocked_content', $blocked_text );
				$redirect_uri = $scroll_into_view ? 'cmplz_consent=1#cmplz_consent_area_anchor' : 'cmplz_consent=1';
				if ( $cache_redirect ) {
					?>
					<script>
						var url = window.location.href;
						var consented_area_visible = document.getElementById('cmplz_consent_area_anchor');
						if (url.indexOf('cmplz_consent=1') !== -1 && !consented_area_visible ) {
							url = url.replace('cmplz_consent=1', '');
							url = url.replace('#cmplz_consent_area_anchor', '');
							url = url.replace('?&', '?');
							url = url.replace('&&', '?');
							//if last character is ? or &, drop it
							if (url.substring(url.length-1) === "&" || url.substring(url.length-1) === "?")
							{
								url = url.substring(0, url.length-1);
							}
							window.location.replace(url);
						}

						document.addEventListener("cmplz_enable_category", cmplzEnableCustomBlockedContent);
						function cmplzEnableCustomBlockedContent(e) {
							if ( cmplz_has_service_consent('<?php echo esc_attr($service)?>' ) || cmplz_has_consent('<?php echo esc_attr($category)?>' )){
								if (url.indexOf('cmplz_consent=1') === -1 ) {
									if (url.indexOf('?') !== -1) {url += '&';} else {url += '?';}
									url += '<?php echo $redirect_uri?>';
									window.location.replace(url);
								}
							}
						}
					</script>
				<?php } else { ?>
					<script>
						document.addEventListener("cmplz_enable_category", cmplzEnableCustomBlockedContent);
						function cmplzEnableCustomBlockedContent(e) {
							if ( cmplz_has_service_consent('<?php echo esc_attr($service)?>', 'marketing' ) && !document.getElementById("cmplz_consent_area_anchor") ){
								location.reload();
							}
							if ( e.detail.category === '<?php echo esc_attr($category)?>' && !document.getElementById("cmplz_consent_area_anchor") ){
								location.reload();
							}
						}
					</script>
				<?php } ?>
				<div class="cmplz-consent-area">
					<?php if ($atts['category']) {?>
						<a href="#" data-category="<?php echo esc_attr($category)?>" class="cmplz-accept-category <?php echo ' cmplz_'. esc_attr($category);?>_consentarea" ><?php echo wp_kses_post($blocked_text)?></a>
					<?php } else {?>
						<a href="#" data-service="<?php echo esc_attr($service)?>" class="cmplz-accept-service <?php echo ' cmplz_'. esc_attr($service);?>_consentarea" ><?php echo wp_kses_post($blocked_text)?></a>
					<?php }?>
				</div>
				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Check if we should use caching
		 * @param string $type
		 *
		 * @return bool
		 */
		private function use_cache( $type ) {

			//do not cache on multilanguage environments
			if ( function_exists( 'pll__' )
			     || function_exists( 'icl_translate' )
			) {
				return false;
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				return false;
			}

			//do not cache for these types
			if ( ( $type === 'processing' ) || ( $type === 'dataleak' ) ) {
				return false;
			}

			return true;

		}


		/**
		 * checks if the current page contains the shortcode.
		 *
		 * @param int|bool $post_id
		 *
		 * @return boolean
		 * @since 1.0
		 */

		public function is_complianz_page( $post_id = false ) {
			$shortcode = 'cmplz-document';
			$block     = 'complianz/document';
			$cookies_shortcode = 'cmplz-cookies';
			if (isset($this->is_complianz_page[$post_id])) {
				return $this->is_complianz_page[$post_id];
			}

			if ( $post_id ) {
				$post = get_post( $post_id );
			} else {
				global $post;
				$post_id = $post->ID ?? false;
			}
			//set default
			$this->is_complianz_page[$post_id] = false;
			$post_meta = get_post_meta( $post_id, 'cmplz_shortcode', true );
			if ( $post_meta ) {
				$this->is_complianz_page[$post_id] = true;
			} else if ( $post && isset($post->post_content)) {
				//terms conditions has it's own shortcode.
				if (strpos($post->post_content, '[cmplz-terms-conditions') !== FALSE ) {
					$this->is_complianz_page[$post_id] = false;
				} else if (strpos($post->post_content, '[cmplz-consent-area') !== FALSE ) {
					$this->is_complianz_page[$post_id] =  false;
				} else if ( cmplz_uses_gutenberg() && has_block( $block, $post ) ) {
					$this->is_complianz_page[$post_id] = true;
				} else if ( has_shortcode( $post->post_content, $shortcode ) ) {
					$this->is_complianz_page[$post_id] = true;
				} else if ( has_shortcode( $post->post_content, $cookies_shortcode ) ) {
					$this->is_complianz_page[$post_id] = true;
				} else if (strpos($post->post_content, '[cmplz-') !== FALSE ) {
					$this->is_complianz_page[$post_id] = true;
				}
			}
			return $this->is_complianz_page[$post_id];
		}

		/**
		 * gets the  page that contains the shortcode or the gutenberg block
		 *
		 * @param string $type
		 * @param string $region
		 *
		 * @return int $page_id
		 * @since 1.0
		 */

		public function get_shortcode_page_id( $type, $region , $cache = true) {

			global $wpdb;

			$shortcode = 'cmplz-document';
			$page_id   = $cache ? cmplz_get_transient( 'cmplz_shortcode_' . $type . '-' . $region ) : false;
			if ( $page_id === 'none') {
				return false;
			}

			if ( ! $page_id ) {
				//ensure a transient, in case none is found. This prevents continuing requests on the page list
				cmplz_set_transient( "cmplz_shortcode_$type-$region", 'none', HOUR_IN_SECONDS );
				$query = $wpdb->prepare(
					"SELECT * FROM $wpdb->posts WHERE (post_content LIKE %s OR post_content LIKE %s) AND post_status = 'publish' AND post_type = 'page'",
					'%' . '[cmplz-document' . '%',
					'%' . 'wp:complianz\/document' . '%'
				);

				$pages = $wpdb->get_results($query);

				$type_region = ( $region === 'eu' ) ? $type : $type . '-' . $region;

				/**
				 * Gutenberg block check
				 *
				 * */
				foreach ( $pages as $page ) {
					$post_meta = get_post_meta( $page->ID, 'cmplz_shortcode', true );
					if ( $post_meta ) {
						$html = $post_meta;
					} else {
						$html = $page->post_content;
					}

					//check if block contains property
					if ( preg_match( '/"selectedDocument":"(.*?)"/i', $html,
							$matches )
					) {
						if ( $matches[1] === $type_region ) {
							cmplz_set_transient( "cmplz_shortcode_$type-$region", $page->ID, WEEK_IN_SECONDS );
							return $page->ID;
						}
					}
				}

				/**
				 * If nothing found, or if not Gutenberg, check for shortcodes.
				 * Classic Editor, modern shortcode check
				 *
				 * */

				foreach ( $pages as $page ) {
					$post_meta = get_post_meta( $page->ID, 'cmplz_shortcode', true );
					if ( $post_meta ) {
						$html = $post_meta;
					} else {
						$html = $page->post_content;
					}

					if ( has_shortcode( $html, $shortcode ) && strpos( $html, 'type="' . $type . '"' ) !== false
						 && strpos( $html, 'region="' . $region . '"' ) !== false
					) {
						cmplz_set_transient( "cmplz_shortcode_$type-$region", $page->ID, HOUR_IN_SECONDS );
						return $page->ID;
					}
				}

				/**
				 * 	legacy check
				 */

				foreach ( $pages as $page ) {
					$post_meta = get_post_meta( $page->ID, 'cmplz_shortcode', true );
					if ( $post_meta ) {
						$html = $post_meta;
					} else {
						$html = $page->post_content;
					}

					//if the region is eu, we should not match if there's a region defined.
					if ( $region==='eu' && strpos($html, ' region="') !== FALSE ) {
						continue;
					}

					if ( has_shortcode( $html, $shortcode ) && strpos( $html, 'type="' . $type_region . '"' ) !== false ) {
						cmplz_set_transient( "cmplz_shortcode_$type-$region", $page->ID, HOUR_IN_SECONDS );
						return $page->ID;
					}
				}
			} else {
				return $page_id;
			}


			return false;
		}

		/**
		 *
		 * get the URl of a specific page type
		 *
		 * @param string $type cookie-policy, privacy-statement, etc
		 * @param string $region
		 * @return string
		 *
		 *
		 */

		public function get_page_title( $type, $region ) {
			if ( ! cmplz_has_region( $region ) ) {
				return '';
			}

			if ( cmplz_get_option( $type ) === 'none' ) {
				return '#';
			} else if ( cmplz_get_option( $type ) === 'custom' ) {
				$id = get_option( "cmplz_" . $type . "_custom_page" );
				//get correct translated id
				$id = apply_filters( 'wpml_object_id', $id, 'page', true, substr( get_locale(), 0, 2 ) );
				$title = (int)  $id === 0 ? '' : get_the_title( $id );
			} else if ( cmplz_get_option( $type ) === 'url' ) {
				$title = COMPLIANZ::$config->generic_documents_list[$type]['title'];
			} else {
				$policy_page_id = $this->get_shortcode_page_id( $type, $region );
				//get correct translated id
				$policy_page_id = apply_filters( 'wpml_object_id', $policy_page_id, 'page', true, substr( get_locale(), 0, 2 ) );
				if ( !$policy_page_id ) {
					return '';
				}
				$title =  get_the_title( $policy_page_id );
			}

			return preg_replace( '/(\(.*\))/i', '', cmplz_translate($title, 'cmplz_link_title_'.$type) );
		}

		/**
		 *
		 * get the URl of a specific page type
		 *
		 * @param string $type cookie-policy, privacy-statement, etc
		 * @param string $region
		 * @return string
		 *
		 *
		 */

		public function get_page_url( $type, $region ) {
			if ( ! cmplz_has_region( $region ) ) {
				return '#';
			}

			if ( cmplz_get_option( $type ) === 'none' ) {
				return '#';
			}

			if ( cmplz_get_option( $type ) === 'custom' ) {
				$id = get_option( "cmplz_" . $type . "_custom_page" );
				//get correct translated id
				$id = apply_filters( 'wpml_object_id', $id, 'page', true, substr( get_locale(), 0, 2 ) );
				return (int) $id === 0 || !get_permalink( $id ) ? '#' : esc_url_raw( get_permalink( $id ) );
			}

			if ( cmplz_get_option( $type ) === 'url' ) {
				$url = get_option("cmplz_".$type."_custom_page_url");
				return esc_url_raw( cmplz_translate( $url, "cmplz_".$type."_custom_page_url") );
			}

			$policy_page_id = $this->get_shortcode_page_id( $type, $region );

			//get correct translated id
			$policy_page_id = apply_filters( 'wpml_object_id', $policy_page_id, 'page', true, substr( get_locale(), 0, 2 ) );

			$permalink = get_permalink( $policy_page_id );
			return $permalink ?: '#';
		}

		/**
		 *
		 * get the title of a specific page type. Only in use for generated docs from Complianz.
		 *
		 * @param string $type cookie-policy, privacy-statement, etc
		 * @param string $region
		 *
		 * @return string $title
		 */

		public function get_document_title( $type, $region ) {

			if ( cmplz_get_option( $type ) === 'custom' || cmplz_get_option( $type ) === 'generated' ) {
				if ( cmplz_get_option( $type ) === 'custom' ) {
					$policy_page_id = get_option( "cmplz_" . $type . "_custom_page" );
				} else if ( cmplz_get_option( $type ) === 'generated' ) {
					$policy_page_id = $this->get_shortcode_page_id( $type, $region );
				}

				//get correct translated id
				$policy_page_id = apply_filters( 'wpml_object_id',
					$policy_page_id,
					'page', true, substr( get_locale(), 0, 2 ) );

				$post = get_post( $policy_page_id );
				if ( $post ) {
					return $post->post_title;
				}
			}

			return str_replace('-', ' ', $type);
		}

		/**
		 * Function to generate a pdf file, either saving to file, or echo to browser
		 *
		 * @param $page
		 * @param $region
		 * @param $post_id
		 * @param $save_to_file
		 * @param $intro
		 * @param $append //if we want to add addition html
		 *
		 * @throws \Mpdf\MpdfException
		 */

		public function generate_pdf( $page, $region, $post_id = false, $save_to_file = false, $intro = '', $append = '' ) {
			if ( ! defined( 'DOING_CRON' ) &&  ! cmplz_user_can_manage() ) {
				die( "invalid command" );
			}

			$error      = false;
			$uploads    = wp_upload_dir();
			$upload_dir = $uploads['basedir'];

			if ( ! isset( COMPLIANZ::$config->pages[ $region ] ) ) {
				return;
			}

			$pages = COMPLIANZ::$config->pages[ $region ];
			//double check if it exists
			if ( ! isset( $pages[ $page ] ) ) {
				return;
			}

			$title         = $pages[ $page ]['title'];
			$document_html = $intro . COMPLIANZ::$document->get_document_html( $page, $region, $post_id ) . $append;
			$document_html = apply_filters( 'cmplz_cookie_policy_snapshot_html', $document_html , $save_to_file );

			//prevent hidden fields
			$document_html = str_replace('cmplz-service-hidden', '', $document_html);
			$load_css      = cmplz_get_option( 'use_document_css' );
			$css           = '';

			if ( $load_css ) {
				$css = file_get_contents( cmplz_path . "assets/css/document.css" );
			}
			$title_html = $save_to_file ? '' : '<h4 class="center">' . $title . '</h4>';

			$html = '
                    <style>
                    ' . $css . '
                    #cmplz-datarequest-form, #cmplz-manage-consent-container-nojavascript, #cmplz-tcf-vendor-container {
                      display:none;
                    }
                    body {
                      font-family: sans;
                      margin-top:100px;
                      color :#000;
                    }
                    h2 {
                        font-size:12pt;
                    }
                    h3 {
                        font-size:12pt;
                    }
                    h4 {
                        font-size:10pt;
                        font-weight: bold;
                    }
                    .center {
                      text-align:center;
                    }
					 #cmplz-tcf-buttons-template, #cmplz-tcf-vendor-template, #cmplz-tcf-type-template {
						display:none !important;
					}
                    </style>

                    <body id="cmplz-document">
                    ' . $title_html . '
                    ' . $document_html . '
                    </body>';

			//==============================================================
			//==============================================================
			//==============================================================

			$html = preg_replace('/<input type="checkbox".*?>/', '', $html);

			require cmplz_path . '/assets/vendor/autoload.php';

			//obsolete function
			if ( get_option( 'cmplz_pdf_dir_token' ) ) {
				delete_option( 'cmplz_pdf_dir_token');
			}

			if ( $save_to_file && ! is_writable( $upload_dir ) ) {
				$error = true;
			}

			if ( ! $error ) {
				// create snapshot directories
				// cmplz_upload_dir will create the directory if it doesn't exist
				// and will return the path to the directory
				$save_dir = cmplz_upload_dir('snapshots');
				// set a default mpdf temporary dir
				$mpdf_default_temp_dir = cmplz_upload_dir('snapshots/tmp');
			}

			if ( ! $error) {
				$mpdf_args = apply_filters( 'cmplz_mpdf_args', array(
					'setAutoTopMargin'  => 'stretch',
					'autoMarginPadding' => 5,
					'margin_left'       => 20,
					'margin_right'      => 20,
					'margin_top'        => 30,
					'margin_bottom'     => 30,
					'margin_header'     => 30,
					'margin_footer'     => 10,
					'tempDir'			=> $mpdf_default_temp_dir
				) );

				$mpdf = new Mpdf\Mpdf($mpdf_args);

				$mpdf->SetDisplayMode( 'fullpage' );
				$mpdf->SetTitle( $title );
				$img  = '';//'<img class="center" src="" width="150px">';
				$date = date_i18n( get_option( 'date_format' ), time() );
				$mpdf->SetHTMLHeader( $img );
				$footer_text = cmplz_sprintf( "%s $title $date", get_bloginfo( 'name' ) );
				$mpdf->SetFooter( $footer_text );
				$mpdf->WriteHTML( $html );
				// Save the pages to a file
				if ( $save_to_file ) {
					$file_title = $save_dir . sanitize_file_name( get_bloginfo( 'name' )
					                                    . '-' . $region
					                                    . "-proof-of-consent-"
					                                    . $date );
				} else {
					$file_title = sanitize_file_name( get_bloginfo( 'name' ) . "-export-" . $date );
				}

				$output_mode = $save_to_file ? 'F' : 'I';
				$mpdf->Output( $file_title . ".pdf", $output_mode );
			} else {
				$_POST['cmplz_generate_snapshot_error'] = true;
				unset( $_POST['cmplz_generate_snapshot'] );
			}
			//clear files
			$mpdf_tempDir = $mpdf_args['tempDir'];
			$this->recursively_clear_directory($mpdf_tempDir);

			// if the user change/filter the temporary directory, we will clear the default directory created before 'snapshots/tmp'
			if ($mpdf_default_temp_dir !== $mpdf_tempDir) {
				$this->recursively_clear_directory($mpdf_default_temp_dir);
			}
		}

		/**
		 * Clear up mdpf directory
		 * @param $dir
		 *
		 * @return bool|void
		 */
		private function recursively_clear_directory($dir) {
			if ( !cmplz_admin_logged_in() ) {
				return false;
			}

			$files = array_diff(scandir($dir), array('.','..'));
			foreach ($files as $file) {
				(is_dir("$dir/$file") && !is_link("$dir/$file")) ? $this->recursively_clear_directory("$dir/$file") : unlink("$dir/$file");
			}
			return rmdir($dir);
		}

		/**
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function get_pages_list( WP_REST_Request $request): array {
			if ( !cmplz_user_can_manage() ) {
				return [
					'pages' => [],
					'pageId' => [],
				];
			}
			$type = sanitize_title($request->get_param('type'));
			$search = sanitize_text_field($request->get_param('search'));
			$custom_page_id = get_option('cmplz_'.$type.'_custom_page');
			$selected_page_found = false;
			$options = [];
			$doc_args = array(
				'post_type' => 'page',
				'posts_per_page' => 30,
			);

			if ( $search ){
				$doc_args['s'] = $search;
				$doc_args['posts_per_page'] = -1;
			}

			$pages = get_posts($doc_args);
			$pages = wp_list_pluck($pages, 'post_title','ID' );
			foreach ($pages as $id => $label ) {
				if (empty($label)) $label = __("No title", "complianz-gdpr");
				$options[] = [ 'label' => $label, 'value'=> $id ];
				if ( $id === $custom_page_id ) {
					$selected_page_found = true;
				}
			}

			#if our selected page is not added to the list yet, do this now
			if ( $custom_page_id && !$selected_page_found ) {
				array_unshift($options, ['label'=> get_the_title($custom_page_id), 'value'=>$custom_page_id]);
			}


			#If there's no active privacy statement, use the wp privacy statement, if available
			if ( $type === 'privacy-statement' && !$custom_page_id ){
				$wp_privacy_policy = get_option('wp_page_for_privacy_policy');
				if ( $wp_privacy_policy ){
					$custom_page_id = $wp_privacy_policy;
				}
			}
			return [
				'pages' => $options,
				'pageId' => $custom_page_id,
			];
		}


		/**
		 * @param string $dir
		 * Delete files and directories recursively. Used to clear the tmp folder
		 * @since 6.3.0
		 */

		private function delete_files_directories_recursively( $dir ) {
			if ( strpos( $dir, 'complianz/tmp' ) !== false ) {
				foreach ( glob( $dir . '/*' ) as $file ) {
					if ( is_dir( $file ) ) {
						$this->delete_files_directories_recursively( $file );
					} else {
						unlink( $file );
					}
				}
				rmdir( $dir );
			}
		}


		/**
		 * Replace custom summary shortcode back to summary tags
		 *
		 * @return null|string //type may be null sometimes
		 */
		public function revert_divs_to_summary( $content ) {
			//only on front-end
			if ( is_admin() ) {
				return $content;
			}

			// only for classic.
			if ( cmplz_uses_gutenberg() ) {
				return $content;
			}

			// Make sure content is a string
			$content = $content ?? '';

			// Return $data if this is not a Complianz document
			global $post;
			if ( !$post || !is_object($post) || !property_exists($post, 'ID')) {
                return $content;
			}

			// Check if post is unlinked, otherwise return
			if ( get_post_meta($post->ID, 'cmplz_document_status', true	) !== 'unlink') {
				return $content;
			}

			//quotest get encoded for some strange reason. Decode.
			$content = str_replace( '&#8221;', '"', $content );
			$content = preg_replace('/\[cmplz-details-open([^>]*?)\]/', '<details $1>', $content);

			$content = preg_replace('/\[cmplz-details-close\]/', '</details>', $content);
			// Replace <summary> tags with custom <div>
			$content = preg_replace('/\[cmplz-summary-open([^>]*?)\]/', '<summary $1>', $content);
			$content =  preg_replace('/\[cmplz-summary-close\]/', '</summary>', $content);
			return $content;
		}

	}
}
