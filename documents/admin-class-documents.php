<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_documents_admin" ) ) {
	class cmplz_documents_admin {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
			}

			self::$_this = $this;
			add_filter( 'cmplz_do_action', array( $this, 'documents_data' ), 10, 3 );
			add_filter( 'cmplz_do_action', array( $this, 'wp_privacy_policy_data' ), 10, 3 );
			//unlinking documents
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_metabox_data' ) );
			add_filter( 'display_post_states', array( $this, 'add_post_state') , 10, 2);
			add_action( 'save_post', array( $this, 'clear_shortcode_transients' ), 10);
			add_action( 'save_post', array($this, 'register_document_title_for_translations'), 10, 3);
			add_action( 'cmplz_install_tables', array( $this, 'preload_privacy_info' ) );
			add_action( 'admin_init', array( $this, 'add_privacy_info' ) );
		}

		static function this() {
			return self::$_this;
		}

		public function preload_privacy_info(){
			if ( !cmplz_user_can_manage() ) {
				return;
			}

			//if the plugins page is reviewed, we can reset the privacy statement suggestions from WordPress.
			$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
			if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
				if ( file_exists(ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php') ) {
					require_once( ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php' );
				} elseif ( file_exists(ABSPATH . 'wp-admin/misc.php') ) {
					require_once( ABSPATH . 'wp-admin/misc.php' );
				} else {
					return;
				}
			}

			if ( class_exists( 'WP_Privacy_Policy_Content' ) ) {
				WP_Privacy_Policy_Content::_policy_page_updated( $policy_page_id );
				//check again, to update the cache.
				WP_Privacy_Policy_Content::text_change_check();
				$data = WP_Privacy_Policy_Content::get_suggested_policy_text();
				update_option( 'cmplz_preloaded_privacy_info', $data, false );
			}
		}


		/**
		 * Get WordPress privacy policy data
		 *
		 * @param array           $data
		 * @param string          $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function wp_privacy_policy_data(array $data, string $action, WP_REST_Request $request): array {

			if ( $action === 'wp_privacy_policy_data') {

				$data = get_option('cmplz_preloaded_privacy_info', []);
				$consent_api_exists = function_exists('consent_api_registered');

				$data = array_filter($data, static function ($v) {
					return !isset($v['removed']) && $v['plugin_name'] !== 'Complianz';
				});
				foreach ($data as $index => $plugin_info ) {
					if ( $plugin_info['plugin_name'] === 'WordPress') {
						unset($data[$index]);
						continue;
					}

					$s_plugin_name = sanitize_text_field(str_replace(array('<h3>', '</h3>'), array('<h4>','</h4>'), $plugin_info['plugin_name']));
					$data[$index]['consent_api'] = 'na';
					if ($consent_api_exists) {
						$plugin_file = $this->get_plugin_by_name($s_plugin_name);
						$is_complianz = stripos($s_plugin_name, 'complianz') !== false;
						$data[$index]['consent_api'] = $is_complianz || consent_api_registered( $plugin_file );
					}
				}
				//reset the case in case we removed plugins, like wordpress. Otherwise we get an error in react.
				$data = [
						'privacyStatements' => empty($data) ? []: array_values($data)
				];
			}
			return $data;
		}

		/**
		 * Get plugin by name from policy texts
		 * @param string $name
		 *
		 * @return string|bool
		 */
		public function get_plugin_by_name($name){
			$plugins         = get_option( 'active_plugins' );
			foreach ($plugins as $plugin){
				$plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin);
				if ($name === $plugin_data['Name']) {
					return $plugin;
				}
			}
			return false;
		}

		/**
		 * Check if any plugin has changed the policy texts
		 *
		 * @return bool
		 */

		public function plugin_privacy_policies_changed(): bool {
			if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/misc.php' );
			}

			return class_exists( 'WP_Privacy_Policy_Content' ) && WP_Privacy_Policy_Content::text_change_check();
		}
		/**
		 * Check if the site has missing pages for the auto generated documents
		 * @param array           $data
		 * @param string          $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */

		public function documents_data(array $data, string $action, WP_REST_Request $request): array {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}

			if ( $action === 'update_custom_legal_document_url' ) {
				$this->clear_shortcode_transients();
				$data = $this->update_custom_legal_document_url($request);
			}

			if ( $action === 'get_custom_legal_document_url' ) {
				$data = $this->get_custom_legal_document_url($request);
			}

			if ( $action === 'documents_data' ) {
				$data = $this->get_documents_data($request);
			}

			if ( $action === 'documents_menu_data' ) {
				$data = $this->get_documents_menu_data($request);
			}

			if ( $action === 'documents_block_data' ) {
				$data = $this->get_documents_block_data();
			}

			if ( $action === 'save_documents_menu_data' ) {
				//clear document cache
				$this->clear_shortcode_transients();

				//menu link per document
				$created_documents = $request->get_param('createdDocuments');
				$generic_documents = $request->get_param('genericDocuments');
				foreach ( $created_documents as $document ) {
					$document_id = $document['page_id'] ?? false;
					$menu_id = $document['menu_id'] ?? -1;
					if (!$menu_id) $menu_id = -1;

					//if page_id is a string, it's region redirected.
					$this->assign_document_to_menu(sanitize_title($document_id), (int) $menu_id);
				}
				//region redirected
				foreach ( $generic_documents as $document ) {
					$document_id = $document['page_id'] ?? false;
					$menu_id = $document['menu_id'] ?? -1;
					if (!$menu_id) $menu_id = -1;
 					//if page_id is a string, it's region redirected.
					$this->assign_document_to_menu(sanitize_title($document_id), (int) $menu_id);
				}

				$data = [];
			}
			return $data;
		}

		/**
		 * Format required pages for javascript usage
		 * @return array
		 */
		private function required_pages_flattened(){
			$pages         = COMPLIANZ::$document->get_required_pages();
			$menu = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );

			$pages_flat = [];
			//check which ones are already created
			foreach ( $pages as $region => $region_pages ) {
				foreach ( $region_pages as $type => $page ) {
					//clean up unnecessary data
					unset( $page['document_elements'], $page['condition'], $page['public'] );
					$page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region, false );
					$page['page_id'] = $page_id;
					$page['title'] = $page_id ? get_the_title($page_id) : $page['title'];
					$page["region"] = $region;
					$page["type"] = $type;
					$page["shortcode"] = COMPLIANZ::$document->get_shortcode( $type, $region, true );
					$page['menu_id'] = false;
					if ( $page_id && is_array($menu) ) {
						foreach ( $menu as $menu_id => $menu_label ) {
							if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
								$page['menu_id'] = $menu_id;
							}
						}
					}

					$pages_flat[] = $page;
				}
			}
			return $pages_flat;
		}


		/**
		 * Get list of pages with created status, region, shortcode, etc.
		 * Create missing pages if the "generated" variable is true
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		private function get_documents_data( WP_REST_Request $request): array {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}
			$generate = (bool) $request->get_param('generate');
			if ($generate) {
				$documents = $request->get_param('documents');
				foreach ($documents as  $document ){
					$page_id = (int) $document['page_id'];
					$page_obj = get_post($page_id);
					if ( !$page_obj ){
						$this->create_page( sanitize_title($document['type']), sanitize_title($document['region']), sanitize_text_field($document['title']) );
					} else {
						//if the page already exists, just update it with the title
						$data = array(
								'ID'           => $page_id,
								'post_title'   => sanitize_text_field($document['title']),
								'post_type'    => "page",
						);
						wp_update_post( $data );
					}
				}
				$this->clear_shortcode_transients();
			}
			return [
					'required_pages' => $this->required_pages_flattened(),
			];
		}

		/**
		 * Documents array for dashboard documents block
		 *
		 * @return array
		 */

		public function get_documents_block_data(): array {
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}

			$pages = COMPLIANZ::$config->pages;
			$generic_documents_list = COMPLIANZ::$config->generic_documents_list;
			$documents = [];
			foreach ( $pages as $region => $page ) {
				$docs = [];
				foreach ( $page as $type => $page_data ) {
					if (!$page_data['public']) continue;
					unset( $page_data['document_elements'] );
					unset( $page_data['condition'] );
					//make title generic
					$page_data['title'] = $generic_documents_list[ $type ]['title'] ?? $page_data['title'];
					$page_data['type'] = $type;
					$page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region, false );
					//check if post is trashed
					$page_data['permalink'] = get_permalink( $page_id );
					$page_data['exists'] = $this->page_exists( $type, $region ) ;
					$page_data['required'] = COMPLIANZ::$document->page_required( $type, $region );
					$page_data['shortcode'] = COMPLIANZ::$document->get_shortcode( $type, $region, $force_classic = true );
					$page_data['generated'] = date( cmplz_short_date_format(), get_option( 'cmplz_documents_update_date' ) );
					$page_data['status'] = $this->syncStatus( $page_id );
					$docs[] = $page_data;
				}
				$documents[] = [
						'region' =>$region,
						'documents' =>$docs,
				];
			}


			//maybe add T&C
			if ( ! class_exists('COMPLIANZ_TC') ) {
				$page_data = [];
				$page_data['type'] = 'terms-conditions';
				$page_data['title'] = __("Terms & Conditions",'complianz-gdpr');
				$page_data['permalink'] = false;
				$page_data['exists'] = false;
				$page_data['required'] = false;
				$page_data['shortcode'] = false;
				$page_data['generated'] = '';
				$page_data['status'] = 'unlink';
				$page_data['install'] = add_query_arg( array('s'=>'complianz+terms+conditions+stand-alone', 'tab'=>'search','type'=>'term') );
				$index = array_search('all', array_column($documents, 'region'));
				if ($index!==false) {
					$documents[$index]['documents'][] = $page_data;
				} else {
					$documents[] = [
							'region' => 'all',
							'documents' => [$page_data],
					];
				}
			}

			$documents = apply_filters( 'cmplz_documents_block_data', $documents );
			$proofOfConsentDocuments = [];
			$docs = COMPLIANZ::$document->get_cookie_snapshot_list();
			foreach ( $docs as $doc ) {
				$filename = $doc['file'];
				//strip everything before proof of consent
				$pos = strpos($filename, '-proof-of-consent-');//leave region in place
				$region = substr( $filename, $pos-2, 2 );
				$filename = strtoupper($region). ' - '.str_replace('-', ' ', substr( $filename, $pos ) );
				$proofOfConsentDocuments[] = [
						'label' => $filename,
						'value' => $doc['url'],
				];
			}

			return [
					'documents' => $documents,
					'processingAgreementOptions' => apply_filters('cmplz_tools_processing_agreements', []),
					'proofOfConsentOptions' => $proofOfConsentDocuments,
					'dataBreachOptions' => apply_filters('cmplz_tools_databreaches', []),
				];
		}

		/**
		 *
		 * Check if page of certain type exists
		 *
		 * @param string $type
		 * @param string $region
		 *
		 * @return bool
		 *
		 */

		public function page_exists( $type, $region ) {
			if ( COMPLIANZ::$document->get_shortcode_page_id( $type, $region ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Create a page of certain type in wordpress
		 *
		 * @param string $type
		 * @param string $region
		 * @param string $title
		 *
		 * @return int|bool page_id
		 * @since 1.0
		 */

		public function create_page( string $type, string $region, string $title ) {
			if ( ! cmplz_user_can_manage() ) {
				return false;
			}

			$pages = COMPLIANZ::$config->pages;
			if ( ! isset( $pages[ $region ][ $type ] ) ) {
				return false;
			}

			$title = sanitize_text_field($title);
			if ( empty($title) ) {
				$title = $pages[ $region ][ $type ]['title'];
			}

			$page = array(
				'post_title'   => $title,
				'post_type'    => "page",
				'post_content' => COMPLIANZ::$document->get_shortcode( $type, $region ),
				'post_status'  => 'publish',
			);

			// Insert the post into the database
			$page_id = wp_insert_post( $page );
			do_action( 'cmplz_create_page', $page_id, $type, $region );
			return $page_id;
		}

		/**
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */

		public function get_documents_menu_data($request){
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}

			if ( current_theme_supports( 'menus' ) ) {
				$empty_menu_link = admin_url( 'nav-menus.php' ) ;
			} else {
				$empty_menu_link = cmplz_get_referral_url( 'articles', 'document-menu', "https://complianz.io/how-to-create-a-menu-in-wordpress/" );
			}

			$regions = cmplz_get_regions( true , 'full' );
			$regions_flat = cmplz_format_as_javascript_array( $regions );
			$menu = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );
			$menu_flat = [];
			foreach ($menu as $id => $label ) {
				$menu_flat[] = [
					'id' => $id,
					'label' => $label,
				];
			}

			$required_pages = $this->required_pages_flattened( );
			$generic_documents_list = COMPLIANZ::$config->generic_documents_list;
			$generic_documents_list_flat = [];
			foreach ($generic_documents_list as $type => $document ) {
				$document['page_id'] = $document['can_region_redirect'] ? $type : COMPLIANZ::$document->get_shortcode_page_id( $type, 'all' );
				$document['menu_id'] = false;
				foreach ( $menu as $menu_id => $menu_label ) {
					if ( $this->is_assigned_this_menu( $type, $menu_id ) ) {
						$document['menu_id'] = $menu_id;
					}
				}
				$document['type'] = $type;
				$generic_documents_list_flat[] = $document;
			}
			return [
				'menu' => $menu_flat,
				'empty_menu_link' => $empty_menu_link,
				'required_documents' => $required_pages,
				'documents_not_in_menu' => $this->pages_not_in_menu(),
				'regions' => $regions_flat,
				'generic_documents_list' => $generic_documents_list_flat,
				'page_types' => $this->get_active_page_types(),
			];
		}

		/**
		 * Assign a document to a menu
		 *
		 * @param string|int $page_id
		 * @param int        $menu_id
		 *
		 * @return bool
		 */

		public function assign_document_to_menu( $page_id, int $menu_id ): bool {
			if ( ! cmplz_user_can_manage() ) {
				return false;
			}

			if ( empty( $menu_id ) ) {
				return false;
			}

			if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
				return true;
			}

			if ( $menu_id === -1 ) {
				$this->clear_page_id_from_menu($page_id);
			}

			//remove current assignments
			$this->clear_page_id_from_menu($page_id);

			$page_id = sanitize_title($page_id);
			if ( is_numeric($page_id) ) {
				$page = get_post( $page_id );
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => get_the_title( $page ),
					'menu-item-object-id' => $page->ID,
					'menu-item-object'    => get_post_type( $page ),
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'post_type',
				) );

			} else {
				$title = COMPLIANZ::$config->generic_documents_list[$page_id]['title'];
				$page_id = $this->get_page_id_for_generic_document( $page_id );
				$url = add_query_arg( array('cmplz_region_redirect'=> 'true'), get_permalink($page_id) );

				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'object',
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'custom',
					'menu-item-url'       => $url,
				) );
			}
			return true;
		}

		/**
		 * Get all pages that are not assigned to any menu
		 *
		 * @return array|bool
		 * @since 1.2
		 *
		 * */

		public function pages_not_in_menu() {
			//search in menus for the current post
			$menus = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );
			$pages_in_menu = array();
			$region_redirected = cmplz_get_option('region_redirect') === 'yes';
			$pages = $this->get_created_pages(false , $region_redirected);
			if ( count( $pages ) > 0 ) {
				foreach ( $pages as $page_id ) {
					//check also for generic redirected documents
					$region_redirected_page_id = $this->get_page_id_for_generic_document($page_id);
					foreach ( $menus as $menu_id => $menu ) {
						if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
							$pages_in_menu[] = $page_id;
						} else if ($this->is_assigned_this_menu( $region_redirected_page_id, $menu_id )) {
							$pages_in_menu[] = $page_id;
						}

					}
				}
			}

			$pages_not_in_menu = array_diff( $pages, $pages_in_menu );
			if ( count( $pages_not_in_menu ) === 0 ) {
				return false;
			}
			if ($region_redirected){
				$output = array_map( static function($page_not_in_menu){
					$document_data = COMPLIANZ::$document->get_document_data($page_not_in_menu);
					$document_type = $document_data['type'];
					return COMPLIANZ::$config->generic_documents_list[$document_type]['title'];
				}, $pages_not_in_menu);
			} else {
				$output = array_map('get_the_title', $pages_not_in_menu);
			}
			return $output;
		}


		/**
		 * Check if a page is assigned to a menu
		 *
		 * @param int|string $page_id
		 * @param int        $menu_id
		 *
		 * @return bool
		 *
		 * @since 1.2
		 */

		public function is_assigned_this_menu( $page_id, int $menu_id ): bool {
			if (!cmplz_user_can_manage()) {
				return false;
			}

			if ( is_numeric($page_id) ) {
				$menu_items = wp_list_pluck( wp_get_nav_menu_items( $menu_id ), 'object_id' );
				return ( in_array( $page_id, $menu_items, true ) );
			}

			if ( $menu_id===-1 ) {
				return false;
			}
			$page_id = $this->get_page_id_for_generic_document( $page_id );
			$page    = get_post($page_id);
			//get only custom links
			$menu_items = wp_get_nav_menu_items( $menu_id );
			if (is_array($menu_items)) {
				foreach ($menu_items as $key => $menu_item ) {
					if ($menu_item->type!=='custom') {
						unset($menu_items[$key]);
					}
				}
			}

			$menu_items = wp_list_pluck( $menu_items, 'url' );
			if ( is_array($menu_items) ) {
				foreach ( $menu_items as $url ) {
					if ( $page && strpos( $url, $page->post_name ) !== false && strpos( $url, 'cmplz_region_redirect' ) !== false ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * delete a menu link
		 *
		 * @param $page_id
		 *
		 * @return void
		 */
		private function clear_page_id_from_menu($page_id){
			#per document linked
			$menus = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );
			foreach ( $menus as $menu_id => $menu_label ) {
				if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
					$menu_items = wp_get_nav_menu_items( $menu_id );
					foreach ( $menu_items as  $menu_item ) {
						if ( $menu_item->object_id === $page_id ) {
							wp_delete_post( $menu_item->ID );
						}
					}
				}
			}

			#for region redirected documents, where $page_id is actually the page type
			$type = $page_id;
			foreach ( $menus as $menu_id => $menuItem ) {
				if ( $this->is_assigned_this_menu( $type, $menu_id ) ) {
					$menu_items = wp_get_nav_menu_items( $menu_id );
					$post_id = $this->get_page_id_for_generic_document($type);
					$page       = get_post( $post_id );
					if ( ! $page ) {
						continue;
					}

					foreach ( $menu_items as $menu_item ) {
						if ( $menu_item->type === 'custom' ) {
							if ( strpos( $menu_item->url, $page->post_name ) !== false && strpos( $menu_item->url, 'cmplz_region_redirect' ) !== false ) {
								wp_delete_post( $menu_item->ID );
							}
						}
					}
				}
			}
		}

		/**
		 * For use in region redirect functionality only.
		 * We get the first page id for a page type
		 *
		 * @param string $type
		 *
		 * @return int|bool
		 */
		public function get_page_id_for_generic_document( string $type ) {
			$regions = cmplz_get_regions( true );
			//first, try the default region.
			$default_region = COMPLIANZ::$company->get_default_region();
			$detected_page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $default_region );
			//if not found, try all other regions.
			if ( !$detected_page_id ) {
				foreach ( $regions as $region ) {
					$detected_page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region );
					if ($detected_page_id) {
						break;
					}
				}
			}
			return $detected_page_id;
		}

		/**
		 * Delete a page of a type
		 *
		 * @param string $type
		 * @param string $region
		 *
		 */

		public function delete_page( string $type, string $region ) {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}

			$page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region );
			if ( $page_id ) {
				wp_delete_post( $page_id, false );
			}
		}


		/**
		 * Check if all required pages are created
		 */
		public function all_required_pages_created(): bool {
			$pages = COMPLIANZ::$document->get_required_pages();
			$total_pages = $existing_pages= 0;
			foreach ( $pages as $region => $region_pages ) {
				foreach ( $region_pages as $type => $page ) {
					if ( $this->page_exists( $type, $region ) ) {
						$existing_pages ++;
					}
					$total_pages ++;
				}
			}

			return $total_pages === $existing_pages;
		}


		/**
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function update_custom_legal_document_url($request): array {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}
			$url = esc_url_raw( (string) $request->get_param( 'pageUrl' ) );
			$type = sanitize_title($request->get_param('type'));
			cmplz_register_translation($url, "cmplz_".$type."_custom_page_url");
			update_option('cmplz_'.$type.'_custom_page_url', $url);
			return [];
		}


		/**
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function get_custom_legal_document_url($request): array {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}
			$type = sanitize_title($request->get_param('type'));
			$url = get_option('cmplz_'.$type.'_custom_page_url', '');
			return [
				'pageUrl' => $url,
			];
		}

		/**
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function update_custom_legal_document_id( WP_REST_Request $request): array {
			if ( !cmplz_user_can_manage() ) {
				return [];
			}
			$pageId = (int) $request->get_param( 'pageId' );
			$type = sanitize_title($request->get_param('type'));
			update_option('cmplz_'.$type.'_custom_page', $pageId);
			//if we have an actual privacy statement, custom, set it as privacy url for WP
			if ($type==='privacy-statement' && $pageId > 0){
				$this->set_wp_privacy_policy($pageId, 'privacy-statement');
			}
			return [];
		}

		/**
		 * Set default privacy page for WP, based on primary region
		 *
		 * @param int    $page_id
		 * @param string $type
		 * @param bool   $region
		 */

		public function set_wp_privacy_policy( int $page_id, string $type, bool $region=false){
			$primary_region = COMPLIANZ::$company->get_default_region();

			if ($region && $region !== $primary_region) return;

			if ($type === 'privacy-statement') {
				update_option('wp_page_for_privacy_policy', $page_id );
			}

		}

		/**
		 * Add document post state
		 *
		 * @param $post_states //don't specify array type here, as some setups will pass something else
		 * @param $post //don't specify type here, as some setups will pass an int, while we need a WP_Post
		 *
		 * @return array
		 */

		public function add_post_state($post_states, $post): array {
			if ( !is_array($post_states) ) {
				$post_states = [];
			}

			if ( ! $post instanceof WP_Post ) {
				return $post_states;
			}

			if ( $post && COMPLIANZ::$document->is_complianz_page( $post->ID ) ) {
				$post_states['page_for_privacy_policy'] = __("Legal Document", "complianz-gdpr");
			}
			return $post_states;
		}

		/**
		 * Add metabox to post
		 *
		 * @param string $post_type
		 *
		 * @return void
		 */
		public function add_meta_box( string $post_type ) {
			global $post;

			if ( ! $post ) {
				return;
			}

			if ( COMPLIANZ::$document->is_complianz_page( $post->ID )
			     && ! cmplz_uses_gutenberg()
			) {
				add_meta_box( 'cmplz_edit_meta_box',
					__( 'Document status', 'complianz-gdpr' ),
					array( $this, 'metabox_unlink_from_complianz' ), null,
					'side', 'high', array() );
			}
		}

		/**
		 * Unlink a page from the shortcode, and use the html instead
		 *
		 */
		function metabox_unlink_from_complianz() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			wp_nonce_field( 'cmplz_unlink_nonce', 'cmplz_unlink_nonce' );

			global $post;
			$sync = $this->syncStatus( $post->ID );
			?>
			<select name="cmplz_document_status">
				<option value="sync" <?php echo $sync === 'sync'
					? 'selected="selected"'
					: '' ?>><?php esc_html_e(__( "Synchronize document with Complianz",
						"complianz-gdpr" )); ?></option>
				<option value="unlink" <?php echo $sync === 'unlink'
					? 'selected="selected"'
					: '' ?>><?php esc_html_e(__( "Edit document and stop synchronization",
						"complianz-gdpr" )); ?></option>
			</select>
			<?php

		}

		/**
		 * Get sync status of post
		 *
		 * @param $post_id
		 *
		 * @return string
		 */

		public function syncStatus( $post_id ) {
			$post = get_post( $post_id );
			$sync = 'unlink';

			if ( ! $post ) {
				return $sync;
			}

			$shortcode = 'cmplz-document';
			$block     = 'complianz/document';

			$html = $post->post_content;
			if ( cmplz_uses_gutenberg() && has_block( $block, $html ) ) {
				$elements = parse_blocks( $html );
				foreach ( $elements as $element ) {
					if ( $element['blockName'] === $block ) {
						if ( isset( $element['attrs']['documentSyncStatus'] )
						     && $element['attrs']['documentSyncStatus']
						        === 'unlink'
						) {
							$sync = 'unlink';
						} else {
							$sync = 'sync';
						}
					}
				}
			} elseif ( has_shortcode( $post->post_content, $shortcode ) ) {
				$sync = get_post_meta( $post_id, 'cmplz_document_status',
					true );
				if ( ! $sync ) {
					$sync = 'sync';
				}
			}

			//default
			return $sync;
		}

		/**
		 * Save data posted from the metabox
		 */
		public function save_metabox_data() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			// check if this isn't an auto save
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// security check
			if ( ! isset( $_POST['cmplz_unlink_nonce'] )
			     || ! wp_verify_nonce( $_POST['cmplz_unlink_nonce'],
					'cmplz_unlink_nonce' )
			) {
				return;
			}

			if ( ! isset( $_POST['cmplz_document_status'] ) ) {
				return;
			}

			global $post;

			if ( ! $post ) {
				return;
			}
			//prevent looping
			remove_action( 'save_post', array( $this, 'save_metabox_data' ) );

			$sync = sanitize_text_field( $_POST['cmplz_document_status'] ) === 'unlink' ? 'unlink' : 'sync';

			//save the document's shortcode in a meta field
			if ( $sync === 'unlink' ) {
				//get shortcode from page
				$shortcode = false;

				if ( preg_match( COMPLIANZ::$document->get_shortcode_pattern( "gutenberg" ),
					$post->post_content, $matches )
				) {
					$shortcode = $matches[0];
					$type      = $matches[1];
					$region    = cmplz_get_region_from_legacy_type( $type );
					$type      = str_replace( '-' . $region, '', $type );
				} elseif ( preg_match( COMPLIANZ::$document->get_shortcode_pattern( "classic" ),
					$post->post_content, $matches )
				) {
					$shortcode = $matches[0];
					$type      = $matches[1];
					$region    = $matches[2];
				} elseif ( preg_match( COMPLIANZ::$document->get_shortcode_pattern( "classic", $legacy = true ), $post->post_content, $matches ) ) {
					$shortcode = $matches[0];
					$type      = $matches[1];
					$region    = cmplz_get_region_from_legacy_type( $type );
					$type      = str_replace( '-' . $region, '', $type );
				}

				if ( $shortcode ) {
					//store shortcode
					update_post_meta( $post->ID, 'cmplz_shortcode', $post->post_content );
					$document_html = COMPLIANZ::$document->get_document_html( $type, $region );
					$args = array(
							'post_content' => $this->convert_summary_to_div($document_html, $post->ID),
							'ID'           => $post->ID,
					);
					wp_update_post( $args );
				}
			} else {
				$shortcode = get_post_meta( $post->ID, 'cmplz_shortcode',
					true );
				if ( $shortcode ) {
					$args = array(
						'post_content' => $shortcode,
						'ID'           => $post->ID,
					);
					wp_update_post( $args );
				}
				delete_post_meta( $post->ID, 'cmplz_shortcode' );
			}
			update_post_meta( $post->ID, 'cmplz_document_status', $sync );
			add_action( 'save_post', array( $this, 'save_metabox_data' ) );
		}

		/**
		 * clear shortcode transients after page update
		 *
		 * @param int|bool    $post_id
		 * @param object|bool $post
		 *
		 * @hooked save_post which is why the $post param is passed without being used.
		 *
		 * @return void
		 */
		public function clear_shortcode_transients()
		{
			global $post;
			if (!$post || $post->post_type !== 'page') return;

			$pages = COMPLIANZ::$document->get_required_pages();

			foreach ($pages as $region => $region_pages) {
				foreach ($region_pages as $type => $page) {
					if ($this->page_exists($type, $region)) {
						cmplz_delete_transient('cmplz_shortcode_' . $type . '-' . $region);
					}
				}
			}
		}

		/**
		 * Add some text to the privacy statement suggested texts in free.
		 */

		public function add_privacy_info() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			$content = __( "This website uses the Privacy Suite for WordPress by Complianz to collect and record Browser and Device-based Consent. For this functionality, your IP address is anonymized and stored in our database.", 'complianz-gdpr' )
			           .'&nbsp;'
			           . __( "This service does not process any personally identifiable information and does not share any data with the service provider.", 'complianz-gdpr' )
			           .'&nbsp;'
			           . cmplz_sprintf(
				           __( "For more information, see the Complianz %sPrivacy Statement%s.", 'complianz-gdpr' ),
				           '<a href="https://complianz.io/legal/privacy-statement/">',
				           '</a>'
			           );

			$content = apply_filters( 'cmplz_privacy_info', $content );
			wp_add_privacy_policy_content(
				'Complianz | The Privacy Suite for WordPress',
				wp_kses_post( wpautop( $content, false ) )
			);
		}

		/**
		 * Make sure the document title can be translated
		 * @param int $post_ID
		 * @param WP_POST $post
		 * @param bool $update
		 */
		public function register_document_title_for_translations($post_ID, $post, $update) {
			if ( cmplz_user_can_manage() && COMPLIANZ::$document->is_complianz_page($post_ID)) {
				$pattern = '/type="(.*?)"/i';
				if ( preg_match( $pattern, $post->post_content, $matches )
				) {
					$type      = $matches[1];
					cmplz_register_translation( $post->post_title, 'cmplz_link_title_'.$type );
				}
			}
		}


		/**
		 * Get list of all created pages with page id for current setup
		 * @param bool $filter_region
		 * @return array $pages
		 *
		 */

		public function get_created_pages( $filter_region = false) {
			$required_pages = COMPLIANZ::$document->get_required_pages();
			$pages          = array();
			if ( $filter_region ) {
				if ( isset( $required_pages[ $filter_region ] ) ) {
					foreach ( $required_pages[ $filter_region ] as $type => $page ) {
						$page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $filter_region , false);
						if ( $page_id ) $pages[] = $page_id;
					}
				}
			} else {
				$regions = cmplz_get_regions(true);
				foreach ( $regions as $region ) {
					if ( !isset($required_pages[ $region ]) ) {
						continue;
					}
					foreach ( $required_pages[ $region ] as $type => $page ) {
						$page_id = COMPLIANZ::$document->get_shortcode_page_id( $type, $region, false);
						if ( $page_id ) $pages[] = $page_id;
					}
				}
			}

			return $pages;
		}

		/**
		 * Get list of all created pages with page id for current setup
		 * @return array
		 *
		 */

		public function get_active_page_types(): array {
			$required_pages = COMPLIANZ::$document->get_required_pages();
			$generic_documents_list = COMPLIANZ::$config->generic_documents_list;
			$types = [];
			$regions = cmplz_get_regions(true);
			$menu = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );

			foreach ( $regions as $region ) {
				if ( !isset($required_pages[ $region ]) ) {
					continue;
				}
				foreach ( $required_pages[ $region ] as $type => $page ) {
					if ( $generic_documents_list[ $type ]['can_region_redirect'] ) {
						$existing_types = array_column($types, 'type');
						if ( in_array($type, $existing_types, true) ) {
							continue;
						}
						$document = [];
						$document['type'] = $type;
						$document['title'] = $generic_documents_list[ $type ]['title'];
						$page_id = $this->get_page_id_for_generic_document($type);
						$document['menu_id'] = false;
						if ( $page_id && is_array($menu) ) {
							foreach ( $menu as $menu_id => $menu_label ) {
								if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
									$document['menu_id'] = $menu_id;
								}
							}
						}
						$types[] = $document;
					}
				}
			}
			return $types;
		}

		/**
		 * Replace <summary> tags with summary shortcode to fix the issue with tinyMce, which drops summary because it is not supported.
		 *
		 * @param string $content
		 * @param int    $post_id
		 *
		 * @return string
		 */
		public function convert_summary_to_div( string $content, int $post_id): string {
			//only on back-end
			if (!cmplz_user_can_manage()) {
				return $content;
			}

			// only for classic.
			if ( cmplz_uses_gutenberg() ) {
				return $content;
			}

			// Return content if this is not a Complianz document
			if ( !COMPLIANZ::$document->is_complianz_page($post_id ) ) {
				return $content;
			}

			$content = preg_replace('/<details([^>]*?)>/', '[cmplz-details-open$1]', $content);
			$content = preg_replace('/<\/details>/', '[cmplz-details-close]', $content);

			// Replace <summary> tags with custom <div>
			$content = preg_replace('/<summary([^>]*?)>/', '[cmplz-summary-open$1]', $content);
			return preg_replace('/<\/summary>/', '[cmplz-summary-close]', $content);
		}
	}
}
