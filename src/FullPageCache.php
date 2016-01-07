<?php
/**
 * Base class for full page cache managers.
 *
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * This class defines the base structure for a full page cache manager class.
 */
abstract class FullPageCache {
	/**
	 * Current URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Constructor
	 *
	 * @param string $url Current URL.
	 */
	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * Create a cache item.
	 */
	abstract public function create();

	/**
	 * Delete a cache item.
	 */
	abstract public function delete();

	/**
	 * Check if an item is present in the cache.
	 */
	abstract public function exists();

	/**
	 * Flush all cache items.
	 * @return [type] [description]
	 */
	abstract public function flush();

	/**
	 * Regenerate a cache item.
	 */
	abstract public function refresh();

	/**
	 * Getter for current URL.
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}
}
