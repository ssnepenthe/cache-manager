<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class NginxFastCGICache extends FullPageCache {
	const METHOD = 'GET';

	protected $cache_dir;
	protected $cache_file;
	protected $parsed_url;

	public function __construct( $url ) {
		$parsed_url = parse_url( $url );

		if ( ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
			throw new \Exception( 'Malformed URL' );
		}

		if ( ! isset( $parsed_url['path'] ) ) {
			$parsed_url['path'] = '/';
		}

		$this->parsed_url = $parsed_url;

		$this->cache_dir = apply_filters( __NAMESPACE__ . '\\cache_dir', '/var/cache/nginx');

		$this->cache_file = $this->cache_file_path();

		parent::__construct( $url );
	}

	public function create() {
		$r = false;

		if ( ! $this->exists() ) {
			$args = [
				'blocking' => false,
				'timeout'  => 1, // wish it could be lower but WP_Http won't allow it.
			];

			$response = wp_safe_remote_get( $this->url, $args );

			if ( ! is_wp_error( $response ) ) {
				$r = true;
			}
		}

		return $r;
	}

	public function delete() {
		$success = false;

		if ( $this->exists() && $this->writable() ) {
			$success = unlink( $this->cache_file );
		}

		return $success;
	}

	public function exists() {
		return file_exists( $this->cache_file );
	}

	public function flush() {}

	public function get_scheme() {
		return $this->get_parsed_url( 'scheme' );
	}

	public function get_host() {
		return $this->get_parsed_url( 'host' );
	}

	public function get_path() {
		return $this->get_parsed_url( 'path' );
	}

	public function refresh() {
		$r = false;

		$args = [
			'blocking' => false,
			'headers'  => [ 'X-Nginx-Cache-Purge' => '1' ], // filterable header?
			'timeout'  => 1, // wish it could be lower but WP_Http won't allow it.
		];

		$response = wp_safe_remote_get( $this->url, $args );

		if ( ! is_wp_error( $response ) ) {
			$r = true;
		}

		return $r;
	}

	public function writable() {
		return is_writable( $this->cache_file );
	}


	protected function cache_file_path() {
		$key = sprintf( '%1$s%2$s%3$s%4$s',
			$this->get_scheme(),
			self::METHOD,
			$this->get_host(),
			$this->get_path()
		);

		$md5 = md5( $key );

		// We should be able to set number of levels to match nginx config.
		$level_one = substr( $md5, -1 );
		$level_two = substr( $md5, -3, 2 );

		return sprintf(
			'%1$s/%2$s/%3$s/%4$s',
			$this->cache_dir,
			$level_one,
			$level_two,
			$md5
		);
	}

	protected function get_parsed_url( $key = null ) {
		if ( is_null( $key ) ) {
			return $this->parsed_url;
		}

		if ( isset( $this->parsed_url[ $key ] ) ) {
			return $this->parsed_url[ $key ];
		}

		return false;
	}
}
