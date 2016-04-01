<?php
/**
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager\Nginx;

use SSNepenthe\CacheManager\Interfaces\CreatableCache;
use SSNepenthe\CacheManager\Interfaces\RefreshableCache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class FastCGIHTTP implements CreatableCache, RefreshableCache {
	public function create( string $url ) {
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

	public function refresh( string $url ) {
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
