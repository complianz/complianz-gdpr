<?php
defined( 'ABSPATH' ) or die();
class cmplz_placeholders {
	private static $_this;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
		}
		self::$_this = $this;
		add_filter( "cmplz_do_action", array( $this, 'load_placeholders' ), 10, 3 );
	}

	static function this() {
		return self::$_this;
	}

	/**
	 * Add some placeholder data
	 * @return array
	 */
	public function load_placeholders( $data, $action, $request ) {
		if (!cmplz_user_can_manage() ) {
			return $data;
		}
		if ( $action === 'get_processing_agreements' ) {
			$documents = [];
			$services = [
				0 => 'Spotify',
				1 => 'Google Analytics',
				2 => 'Facebook',
				3 => 'X (Formerly Twitter)',
				4 => 'LinkedIn',
				5 => 'YouTube',
				6 => 'Instagram',
				7 => 'Pinterest',
				8 => 'Vimeo',
				9 => 'Soundcloud',
			];
			for ( $i = 0; $i < 10; $i++ ) {
				$region = COMPLIANZ::$company->get_default_region();
				$documents[] = 					[
					'id' =>$i,
					'title' =>$services[$i],
					'region' =>  $region,
					'service' => $services[$i],
					'date' => date_i18n( get_option( 'date_format' )),
					'edit_url' => '#',
					'download_url' => '#',
				];
			}
			$regions = cmplz_get_regions(false, 'full');
			$data = [
				'documents' =>$documents,
				'regions' => $regions,
			];
		}
		if ( $action==='get_databreach_reports' ){
			$documents = [];
			for ( $i = 0; $i < 2; $i++ ) {
				$region = COMPLIANZ::$company->get_default_region();
				$documents[] = 					[
					'id' =>$i,
					'title' => __("Report for:","complianz-gdpr").' '.date_i18n( get_option( 'date_format' )),
					'region' =>  $region,
					'date' => date_i18n( get_option( 'date_format' )),
					'edit_url' => '#',
					'download_url' => '#',
				];
			}
			$regions = cmplz_get_regions(false, 'full');
			$data = [
				'documents' =>$documents,
				'regions' => $regions,
			];
		}
		return $data;
	}
}
$placeholders = new cmplz_placeholders();
