<?php
/**
 * Functionality for managing Nginx fastcgi cache.
 *
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * This class defines the various callbacks for managing Nginx fastcgi cache items.
 *
 * Assumes that Nginx has not been built with ngx_cache_purge module but is
 * instead configured to recognize the X-Nginx-Cache-Purge header as a signal
 * to rebuild the cache.
 *
 * @see https://markjaquith.wordpress.com/2012/05/15/how-i-built-have-baby-need-stuff/
 */
class NginxFastCGICache extends FullPageCache {
	const METHOD = 'GET';

	/**
	 * Absolute path which should match fastcgi_cache_path in Nginx config.
	 *
	 * @var string
	 */
	protected $cache_dir;

	/**
	 * Absolute path to an individual cache item.
	 *
	 * @var string
	 */
	protected $cache_file;

	/**
	 * Array of URL parts that identify the current page.
	 *
	 * @var array
	 */
	protected $parsed_url;

	/**
	 * Constructor
	 *
	 * @throws \Exception If the URL cannot be properly parsed.
	 *
	 * @param string $url Page URL.
	 */
	public function __construct( $url ) {
		$parsed_url = parse_url( $url );

		if ( ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
			throw new \Exception( 'Malformed URL' );
		}

		if ( ! isset( $parsed_url['path'] ) ) {
			$parsed_url['path'] = '/';
		}

		$this->parsed_url = $parsed_url;

		$this->cache_dir = apply_filters( __NAMESPACE__ . '\\cache_dir', '/var/cache/nginx' );

		$this->cache_file = $this->cache_file_path();

		parent::__construct( $url );
	}

	/**
	 * Callback for generating cache of the current URL.
	 *
	 * @return bool
	 */
	public function create() {
		$r = false;

		if ( ! $this->exists() ) {
			$args = [
				'blocking' => false,
				'timeout'  => 1, // Wish it could be lower but WP_Http won't allow it.
			];

			$response = wp_safe_remote_get( $this->url, $args );

			if ( ! is_wp_error( $response ) ) {
				$r = true;
			}
		}

		return $r;
	}

	/**
	 * Callback for deleting cache of the current URL.
	 *
	 * @todo Use WP Filesystem API instead?
	 *
	 * @return bool
	 */
	public function delete() {
		$success = false;

		if ( $this->exists() && $this->writable() ) {
			$success = unlink( $this->cache_file );
		}

		return $success;
	}

	/**
	 * Determine whether the current URL is in the cache.
	 *
	 * @return bool
	 */
	public function exists() {
		return file_exists( $this->cache_file );
	}

	public function flush() {}

	/**
	 * Getter for the current URL scheme.
	 *
	 * @return string
	 */
	public function get_scheme() {
		return $this->get_parsed_url( 'scheme' );
	}

	/**
	 * Getter for the current URL host.
	 *
	 * @return string
	 */
	public function get_host() {
		return $this->get_parsed_url( 'host' );
	}

	/**
	 * Getter for the current URL path.
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->get_parsed_url( 'path' );
	}

	/**
	 * Callback for regenerating the cache of the current URL.
	 *
	 * @return bool
	 */
	public function refresh() {
		$r = false;

		$args = [
			'blocking' => false,
			'headers'  => [ 'X-Nginx-Cache-Purge' => '1' ], // Filterable header?
			'timeout'  => 1, // Wish it could be lower but WP_Http won't allow it.
		];

		$response = wp_safe_remote_get( $this->url, $args );

		if ( ! is_wp_error( $response ) ) {
			$r = true;
		}

		return $r;
	}

	/**
	 * Determine whether the current cache file is writable.
	 *
	 * @todo Use WP Filesystem API?
	 *
	 * @return bool
	 */
	public function writable() {
		return is_writable( $this->cache_file );
	}

	/**
	 * Get the absolute path to cache file based on current URL.
	 *
	 * @return string
	 */
	protected function cache_file_path() {
		$key = sprintf( '%s%s%s%s',
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
			'%s/%s/%s/%s',
			$this->cache_dir,
			$level_one,
			$level_two,
			$md5
		);
	}

	/**
	 * Getter for the current URL.
	 *
	 * @param mixed $key String or null.
	 *
	 * @return mixed Full parsed URL as an array or string with individual piece.
	 */
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
