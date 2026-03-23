<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_admin_DNSMPD" ) ) {
	class cmplz_admin_DNSMPD {
		private static $_this;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;

			add_filter( 'cmplz_do_action', array( $this, 'get_datarequests_data' ), 10, 3 );
			add_action( 'cmplz_install_tables', array( $this, 'update_db_check' ), 10 );
			add_filter( 'cmplz_warning_types', array($this, 'new_datarequests_notice') );
		}

		static function this() {
			return self::$_this;
		}

		public function sanitize_status($status){
			$statuses = array('open', 'resolved', 'all');
			if (in_array($status, $statuses)) return $status;
			return 'open';
		}
		/**
		 * Get a list of processors
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return []
		 */

		public function get_datarequests_data($data, $action, $request){
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}
			if ( $action==='get_datarequests' ){
				$data = $request->get_params();
				$per_page = $data['per_page'] ?? 10;
				$page = $data['page'] ?? 1;
				$search = $data['search'] ?? false;
				$order = $data['order'] ?? 'ASC';
				$orderby = $data['orderBy'] ?? 'id';
				$status = $data['status'] ?? 'open';
				$offset  = $per_page * ( $page - 1 );
				$args = array(
					'number'  => $per_page,
					'offset'  => $offset,
					'order'   => $order,
					'orderby' => $orderby,
					'status' => $this->sanitize_status( $status)
				);


				if ( is_email( $search ) ) {
					$args['email'] = $search;
				} else {
					$args['name'] = $search;
				}
				$records  = $this->get_requests( $args );
				foreach ($records as $key => $record ) {
					$records[ $key ]->type = $this->get_request_type( $record );
					$records[ $key ]->request_date = $this->format_request_date( $record->request_date );
				}
				$open_args = $args;
				$open_args['status'] = 'open';
				$data = [
					'records' => $records,
					'totalRecords' => $this->count_requests($args),
					'totalOpen' => $this->count_requests($open_args),
				];
				return $data;
			} else if ($action==='delete_datarequests') {
				$records = $request->get_param('records');
				foreach ($records as $record) {
					$this->delete($record['ID']);
				}
				$data = [];
			} else if ($action==='resolve_datarequests') {
				$records = $request->get_param('records');
				foreach ($records as $record) {

					$this->resolve($record['ID']);
				}
				$data = [];

			} else if ( $action === 'export_datarequests' ) {
				$data = $request->get_params();
				$dateStart = $data['startDate'] ?? false;
				$dateEnd = $data['endDate'] ?? false;
				$statusOnly = $data['statusOnly'] ?? false;

				$data = $this->run_export_to_csv($dateStart, $dateEnd, $statusOnly);
			}
			return $data;
		}

		private function get_request_type($record){
			$options = COMPLIANZ::$DNSMPD->datarequest_options();
			if ($record->global_optout) {
				return isset($options['global_optout']) ? $options['global_optout'] : '';
			}
			if ($record->limit_sensitive){
				return isset($options['limit_sensitive']) ? $options['limit_sensitive'] : '';
			}
			if ($record->cross_context){
				return isset($options['cross_context']) ? $options['cross_context'] : '';
			}
			//deprecated
			if ($record->request_for_access ) {
				return isset($options['request_for_access']) ? $options['request_for_access'] : '';
			}
			if ($record->right_to_be_forgotten){
				return isset($options['right_to_be_forgotten']) ? $options['right_to_be_forgotten'] : '';
			}
			if ($record->right_to_data_portability){
				return isset($options['right_to_data_portability']) ? $options['right_to_data_portability'] : '';
			}
		}

		/**
		 * Add new datarequests
		 *
		 * @param array $warnings
		 *
		 * @return array
		 */

		public function new_datarequests_notice($warnings){
			$warnings['new_datarequest'] = [
				'warning_condition'  => 'admin_DNSMPD->has_open_requests',
				'include_in_progress' => true,
				'plus_one' => true,
				'open' => __( 'You have open data requests.', 'complianz-gdpr' ).'&nbsp;'.cmplz_sprintf(__( 'Please check the data requests <a href="%s">overview page</a>.', 'complianz-gdpr' ), add_query_arg(array('page'=>'complianz#tools/data-requests'),admin_url('admin.php'))),
				'dismissible' => false,
			];
			return $warnings;
		}

		/**
		 * Check if there are open requests
		 *
		 * @return bool
		 */

		public function has_open_requests(){
			$has_requests = false;
			if ( cmplz_has_region('us') || cmplz_datarequests_active() ) {
				global $wpdb;
				$count        = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}cmplz_dnsmpd WHERE NOT resolved = 1" );
				$has_requests = $count > 0;
			}
			return $has_requests;
		}

		/**
		 * Get users or count users
		 * @param array $args
		 * @param string $type the type of query to be run (SELECT or COUNT)
		 *
		 * @return array|int
		 */
		public function get_requests( $args, $type = "SELECT" ) {
			global $wpdb;
			$defaults = array(
				'number'     => false,
				'offset'     => 0,
				'order'      => 'DESC',
				'orderby'    => 'request_date',
				'start_date' => 0,
				'end_date'   => false,
				'search'	 => false,
				'status'     => 'all'
			);

			$args = wp_parse_args( array_filter($args), $defaults );
			
			// Build the base SQL query
			$select_clause = $type === "COUNT" ? "SELECT COUNT(*)" : "SELECT *";
			$sql = $select_clause . " from {$wpdb->prefix}cmplz_dnsmpd WHERE request_date>0 ";
			$sql .= $args['end_date'] ? $wpdb->prepare( " AND request_date> %s AND request_date < %s", (int) $args['start_date'], (int) $args['end_date'] ) : "";
			$sql .= $args['search'] ? " AND (name like='%".esc_sql( $args['search'])."%' OR email like='%".esc_sql( $args['search'])."%' )" : "";
			//$sql .= isset($args['resolved']) ? $wpdb->prepare( " AND resolved = %d ", (int) $args['resolved'] ) : "";
			if ( 'all' !== $args['status'] ) {
				$sql .= $wpdb->prepare( " AND resolved = %d ", $args['status']==='resolved' ? 1 : 0 );
			}
			
			// Only add ORDER BY and LIMIT for SELECT queries, not COUNT queries
			if ( $type === "SELECT" ) {
				$limit   = (int) $args['number'];
				$orderby = $args['orderby'] ?? 'ID';
				$order = $args['order'] ?? 'ASC';
				$orderby = sanitize_title( $orderby );
				$order   = sanitize_title( $order );
				$sql .= " ORDER BY " . esc_sql( $orderby ) . " " . esc_sql( $order );
				$sql .= $limit>0 ? " LIMIT " . (int) $limit . " OFFSET " . (int) $args["offset"] : '';
				return $wpdb->get_results( $sql );
			} else {
				// For COUNT queries, return the count as integer
				return (int) $wpdb->get_var( $sql );
			}
		}

		/**
		 * Count number of users
		 * @param $args
		 *
		 * @return int
		 */
		
		public function count_requests( $args ) {
			unset( $args['number'] );
			return $this->get_requests( $args, "COUNT" );
		}

		/**
		 * Handle  resolve request
		 *
		 * @param int $id
		 */

		public function resolve(int $id): void {
			if ( !cmplz_user_can_manage() ) {
				return;
			}
			global $wpdb;
			$wpdb->update( $wpdb->prefix . 'cmplz_dnsmpd',
				array(
					'resolved' => 1
				),
				array( 'ID' => (int) $id )
			);

		}

		/**
		 * Handle delete request
		 * @param int $id
		 */

		public function delete($id): void {
			if ( !cmplz_user_can_manage() ) {
				return;
			}
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'cmplz_dnsmpd', array( 'ID' => (int) $id ) );
		}

		/**
		 * Export all records in the current selection to a csv file
		 */

		public function run_export_to_csv($dateStart, $dateEnd, $statusOnly = false ){
			$page_batch = 500;
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}

			$offset = get_option( 'cmplz_current_datarequest_export_offset' ) ?: 0;
			if ( $statusOnly ) {
				$progress = get_option( 'cmplz_current_datarequest_export_progress' ) ?: 100;
				$total = 1;
			} else {
				if ($offset===0) {
					//cleanup old file
					$file = $this->filepath();
					if ( file_exists($file) ){
						unlink($file);
					}
				}

				$args = array(
					'number' => $page_batch,
					'offset' => $offset * $page_batch,
					'start_date' => strtotime($dateStart),
					'end_date' => strtotime($dateEnd),
				);
				$offset++;
				$pages_completed = $offset * $page_batch;
				update_option('cmplz_datarequest_export_args', $args, false );
				update_option('cmplz_current_datarequest_export_offset', $offset , false );
				$total = $this->count_requests( $args );
				if ($total>0) {
					$data = $this->get_requests($args);
					$add_header = $offset==1;
					$this->create_csv_file( $data, $add_header);
					$progress = 100 * ($pages_completed/$total);
					$progress = $progress>100 ? 100 : $progress;
				} else {
					$progress = 100;
				}
				update_option('cmplz_current_datarequest_export_progress', $progress, false );
			}

			if ( $progress === 100 ) {
				delete_option('cmplz_current_datarequest_export_offset' );
				delete_option('cmplz_datarequest_export_args');
			}

			return array(
				'progress' => round($progress, 0),
				'exportLink' => $this->fileurl(),
				'noData' => $total ===0,
			);
		}

		/**
		 * create csv file from array
		 *
		 * @param array $data
		 * @param bool $add_header
		 * @throws Exception
		 */

		private function create_csv_file($data, $add_header = true ){
			$delimiter=",";
			$enclosure = '"';
			$escape = '\\';

			require_once(ABSPATH . 'wp-admin/includes/file.php');
			$upload_dir = cmplz_upload_dir();

			//generate random filename for storage
			if ( !get_option('cmplz_datarequest_file_name') ) {
				$token = str_shuffle ( time() );
				update_option('cmplz_datarequest_file_name', $token, false );
			}
			$filename = get_option('cmplz_datarequest_file_name');

			//set the path
			$file = $upload_dir .$filename.".csv";

			//'a' creates file if not existing, otherwise appends.
			$csv_handle = fopen ($file,'a');

			//create a line with headers
			if ( $add_header ) {
				$headers = $this->parse_headers_from_array( $data );
				fputcsv( $csv_handle, $headers, $delimiter, $enclosure, $escape );
			}

			if ( is_array($data) ) {
				foreach ( $data as $line ) {
					$date = $this->localize_date($line->request_date);
					$line = array_values(get_object_vars($line));
					$line = array_map( 'sanitize_text_field', $line );
					$line[] = $date;
					fputcsv( $csv_handle, $line, $delimiter, $enclosure, $escape );
				}
			}
			fclose ($csv_handle);
		}

		/**
		 * Get headers from an array
		 * @param array $array
		 *
		 * @return array|bool
		 */

		private function parse_headers_from_array($array){
			if (!isset($array[0])) return array();
			$array = $array[0];
			//parse object property names from object
			$headers = array_keys(get_object_vars($array));
			$options = COMPLIANZ::$DNSMPD->datarequest_options();
			foreach ($headers as $key => $header) {
				if (isset($options[$header])) {
					$headers[$key] = $options[$header]['short'];
				}
			}
			$headers[] = __("Date","complianz-gdpr");
			return $headers;
		}

		/**
		 * Format request date with proper validation
		 *
		 * @param mixed $request_date
		 * @param bool $include_time Whether to include time in the format
		 *
		 * @return string
		 */
		public function format_request_date($request_date, $include_time = false): string {
			if (empty($request_date) || $request_date == 0) {
				return __('No date available', 'complianz-gdpr');
			}

			$timestamp = (int) $request_date;

			// Get WordPress date format, fallback to default if empty
			$date_format = get_option('date_format');
			if (empty($date_format)) {
				$date_format = 'F j, Y';
			}

			// For CSV export, use a more compact format
			if ($include_time) {
				$time_format = get_option('time_format');
				if (empty($time_format)) {
					$time_format = 'g:i a';
				}
				$formatted_date = date_i18n($date_format, $timestamp) . ' at ' . date_i18n($time_format, $timestamp);
				
				// Fallback if date_i18n fails
				if (empty($formatted_date) || $formatted_date === ' at ') {
					$formatted_date = date($date_format, $timestamp) . ' at ' . date($time_format, $timestamp);
				}
				
				// Final fallback
				if (empty($formatted_date) || $formatted_date === ' at ') {
					$formatted_date = date('Y-m-d H:i:s', $timestamp);
				}
			} else {
				// For API responses, just date
				$formatted_date = date_i18n($date_format, $timestamp);
				if (empty($formatted_date)) {
					$formatted_date = date($date_format, $timestamp);
				}
				if (empty($formatted_date)) {
					$formatted_date = date('Y-m-d', $timestamp);
				}
			}
			
			return $formatted_date;
		}

		/**
		 * Get a localized date for this row (for backward compatibility)
		 *
		 * @param int $unix
		 *
		 * @return string
		 */
		public function localize_date(int $unix): string {
			return $this->format_request_date($unix, true);
		}

		/**
		 * Get a filepath
		 * @return string
		 */

		private function filepath(){
			$upload_dir = cmplz_upload_dir();
			return $upload_dir .get_option('cmplz_datarequest_file_name').".csv";
		}

		/**
		 * Get a file URL
		 * @return string
		 */

		private function fileurl(){
			if  ( file_exists($this->filepath() ) ) {
				return untrailingslashit( cmplz_upload_url( get_option('cmplz_datarequest_file_name').".csv" ) ) . '?' . time();
			}
			return '';
		}



		/**
		 * Check if the table needs to be created or updated
		 * @return void
		 */
		public function update_db_check() {
			//only load on front-end if it's a cron job
			if ( !is_admin() && !wp_doing_cron() ) {
				return;
			}

			if (!wp_doing_cron() && !cmplz_user_can_manage() ) {
				return;
			}
			if ( get_option( 'cmplz_dnsmpd_db_version' ) != CMPLZ_VERSION ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				global $wpdb;
				$charset_collate = $wpdb->get_charset_collate();
				$table_name = $wpdb->prefix . 'cmplz_dnsmpd';
				$sql        = "CREATE TABLE $table_name (
				  `ID` int(11) NOT NULL AUTO_INCREMENT,
				  `name` varchar(255) NOT NULL,
				  `email` varchar(255) NOT NULL,
				  `region` TEXT NOT NULL,
				  `global_optout` int(11) NOT NULL,
				  `cross_context` int(11) NOT NULL,
				  `limit_sensitive` int(11) NOT NULL,
				  `request_for_access` int(11) NOT NULL,
				  `right_to_be_forgotten` int(11) NOT NULL,
				  `right_to_data_portability` int(11) NOT NULL,
				  `request_date` int(11) NOT NULL,
				  `resolved` int(11) NOT NULL,
				  PRIMARY KEY  (ID)
				) $charset_collate;";

				dbDelta( $sql );
				update_option( 'cmplz_dnsmpd_db_version', CMPLZ_VERSION, false );
			}
		}
	} //class closure
}
