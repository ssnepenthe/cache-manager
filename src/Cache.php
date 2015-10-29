<?php

namespace SSNepenthe\CacheManager;

use \DateInterval;
use \DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Cache {
	protected $cache_age = false;
	protected $cache_dir;
	protected $cache_mtime = false;
	protected $cache_path;
	protected $cache_valid;
	protected $page_url;

	public function __construct( $page_url ) {
		$url_components = parse_url( $page_url );

		if ( ! $url_components ) {
			// TODO: trigger some sort of error.
			return;
		}

		$this->page_url = [
			'host'   => isset( $url_components['host'] ) ? $url_components['host'] : $_SERVER['HTTP_HOST'],
			'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET',
			'path'   => isset( $url_components['path'] ) ? $url_components['path'] : $_SERVER['REQUEST_URI'],
		];

		if ( isset( $url_components['scheme'] ) ) {
			$this->page_url['scheme'] = $url_components['scheme'];
		} else {
			$this->page_url['scheme'] = is_ssl() ? 'https' : 'http';
		}

		$this->page_url['url'] = $this->page_url['scheme'] . '://' . $this->page_url['host'] . $this->page_url['path'];

		$this->cache_dir       = apply_filters( __NAMESPACE__ . 'cache_dir', '/var/cache/nginx' );
		$this->cache_valid     = apply_filters( __NAMESPACE__ . 'cache_valid', 'PT60M' ); // 60 minutes

		$this->set_cache_path();

		if ( $this->cache_exists() ) {
			$this->cache_mtime = filemtime( $this->cache_path );
			$this->set_cache_age();
		}
	}

	public function cache_exists() {
		return file_exists( $this->get_cache_path() );
	}

	public function not_cache_exists() {
		return ! $this->cache_exists();
	}

	public function cache_writable() {
		return is_writable( $this->get_cache_path() );
	}

	public function cache_expired() {
		$r = false;

		if ( $this->get_cache_mtime() ) {
			$mtime = new DateTime( '@' . $this->get_cache_mtime() );
			$valid = new DateInterval( $this->get_cache_valid() );
			$now = new DateTime( 'now' );

			if ( $mtime->add( $valid ) < $now ) {
				$r = true;
			}
		}

		return $r;
	}

	public function delete_cache() {
		$success = false;

		if ( $this->cache_exists() && $this->cache_writable() ) {
			$success = unlink( $this->get_cache_path() );
		}

		return $success;
	}

	public function delete_all_cache_files() {
		// Should we really include this functionality?
	}

	// The wp_remote_get requests seem to be hitting the server
	// But $response is almost always a wp error object
	public function generate_cache() {
		$r = false;

		if ( ! $this->cache_exists() ) {
			$args = [
				'blocking' => false,
				'timeout'  => 1,
			];
			$response = wp_remote_get( $this->get_page_url( 'url' ) );

			if ( ! is_wp_error( $response ) ) {
				$r = true;
			}
		}

		return $r;
	}

	public function refresh_cache() {
		$r = false;

		$args = [
			'blocking' => false,
			'headers'  => [ 'X-Nginx-Cache-Purge' => 1 ],
			'timeout'  => 1, // wish it could be lower but WP_Http won't allow it.
		];

		$response = wp_remote_get( $this->get_page_url( 'url' ), $args );

		if ( ! is_wp_error( $response ) ) {
			$r = true;
		}

		return $r;
	}

	protected function set_cache_path() {
		$key = $this->get_page_url( 'scheme' ) . $this->get_page_url( 'method' ) . $this->get_page_url( 'host' ) . $this->get_page_url( 'path' );

		$md5 = md5( $key );

		// We should be able to set number of levels to match nginx config.
		$level_one = substr( $md5, -1 );
		$level_two = substr( $md5, -3, 2 );

		$this->cache_path = sprintf(
			'%1$s/%2$s/%3$s/%4$s',
			$this->get_cache_dir(),
			$level_one,
			$level_two,
			$md5
		);
	}

	protected function set_cache_age() {
		$now   = new DateTime( 'now' );
		$cache = new DateTime( '@' . $this->get_cache_mtime() );

		$age = $now->diff( $cache );

		$this->cache_age = $age->format( create_date_time_format( $age ) );
	}

	public function get_cache_dir() {
		return $this->cache_dir;
	}

	public function get_cache_mtime() {
		return $this->cache_mtime;
	}

	public function get_cache_path() {
		return $this->cache_path;
	}

	public function get_cache_valid() {
		return $this->cache_valid;
	}

	public function get_cache_age() {
		return $this->cache_age;
	}

	public function get_page_url( $component = false ) {
		if ( ! $component ) {
			return $this->page_url;
		}

		if ( isset( $this->page_url[ $component ] ) ) {
			return $this->page_url[ $component ];
		}

		return false;
	}
}
