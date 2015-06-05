<?php
/*
	Plugin Name:  WP Central Checksums
	Plugin URI:   http://wpcentral.io
	Description:  Add API endpoint for the Node.js microservice
	Version:      1.0.0
	Author:       WP Central
	Author URI:   http://wpcentral.io
	License:      GPL
*/


if ( ! defined('ABSPATH') ) {
	die();
}


class WP_Central_Checksums {

	/**
	 * Base route name
	 */
	protected $base = '/checksums';

	/**
	 * Microservice IP
	 */
	protected $service_url = 'http://10.133.166.181/checksums';


	public function __construct() {
		add_filter( 'json_endpoints', array( $this, 'register_routes' ), 30 );
	}


	/**
	 * Register the routes for the post type
	 *
	 * @since 1.0.0
	 *
	 * @param array $routes Routes for the post type
	 * @return array Modified routes
	 */
	public function register_routes( $routes ) {
		$routes[ $this->base . '/plugin/(?P<plugin>[a-z-]+)/(?P<version>\w.+)' ] = array(
			array( array( $this, 'get_plugin_checksums' ), WP_JSON_Server::READABLE ),
		);

		$routes[ $this->base . '/theme/(?P<theme>[a-z-]+)/(?P<version>\w.+)' ] = array(
			array( array( $this, 'get_theme_checksums' ), WP_JSON_Server::READABLE ),
		);

		return $routes;
	}

	public function get_plugin_checksums( $plugin, $version ) {
		return $this->retrieve_data( 'plugin', $plugin, $version );
	}

	public function get_theme_checksums( $theme, $version ) {
		return $this->retrieve_data( 'theme', $theme, $version );
	}

	private function retrieve_data( $type, $slug, $version ) {
		$slug     = sanitize_text_field( $slug );
		$version  = sanitize_text_field( $version );
		$url      = esc_url_raw( $this->service_url . '/' . $type . '/' . $slug . '/' . $version );

		$response = wp_remote_get( $url );
		$data     = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! $data ) {
			return new WP_Error( 'wpcentral_server_down', __( 'Internal service unavailable.' ), array( 'status' => 503 ) );
		}

		if ( ! $data->success ) {
			return new WP_Error( 'wpcentral_server_error', $data->error, array( 'status' => 500 ) );
		}

		$rest_response = new WP_JSON_Response();
		$rest_response->set_data( $data->checksums );

		return $rest_response;
	}

}

$wp_central_checksums = new WP_Central_Checksums;
