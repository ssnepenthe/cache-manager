<?php
/**
 * @package cache-manager
 */

namespace SSNepenthe\Cache_Manager\Nginx;

use SSNepenthe\Cache_Manager\Interfaces\Creatable_Cache;
use SSNepenthe\Cache_Manager\Interfaces\Refreshable_Cache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Fast_CGI_HTTP implements Creatable_Cache, Refreshable_Cache {
	public function create( $url ) {
		if ( ! is_string( $url ) ) {
			throw new \InvalidArgumentException( sprintf(
				'The url parameter is required to be string, was: %s',
				gettype( $url )
			) );
		}

		$r = false;

		$args = [
			'blocking' => false,
			'sslverify' => apply_filters(
				sprintf( '%s\\sslverify', __NAMESPACE__ ),
				true
			),
			'timeout'  => 1, // WP_Http won't go any lower.
		];

		$response = wp_safe_remote_get( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$r = true;
		}

		return $r;
	}

	public function refresh( $url ) {
		if ( ! is_string( $url ) ) {
			throw new \InvalidArgumentException( sprintf(
				'The url parameter is required to be string, was: %s',
				gettype( $url )
			) );
		}

		$r = false;

		$args = [
			'blocking' => false,
			'headers'  => apply_filters(
				sprintf( '%s\\refresh_headers', __NAMESPACE__ ),
				[ 'X-Nginx-Cache-Purge' => '1' ]
			),
			'sslverify' => apply_filters(
				sprintf( '%s\\sslverify', __NAMESPACE__ ),
				true
			),
			'timeout'  => 1, // WP_Http won't go any lower.
		];

		$response = wp_safe_remote_get( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$r = true;
		}

		return $r;
	}
}