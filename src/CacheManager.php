<?php
/**
 * Defines the main plugin class.
 *
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * This class manages all aspects of this plugin from cache classes and toolbar
 * nodes to hooking in to WordPress to handling user actions.
 */
class CacheManager {
	/**
	 * List of registered cache classes.
	 *
	 * @var array
	 */
	protected $cache_classes = [];

	/**
	 * Array of cache instances where each instance represents a single URL.
	 *
	 * @var array
	 */
	protected $cache_instances = [];

	/**
	 * The default cache class.
	 *
	 * @var mixed Null or string
	 */
	protected $default_cache_class = null;

	/**
	 * Index of normalized and parsed URLs.
	 *
	 * @var array
	 */
	protected $urls;

	/**
	 * Constructor
	 *
	 * @param string $name Plugin name.
	 * @param string $version Current plugin version.
	 */
	public function __construct() {
		$this->urls = [
			'normalized' => [],
			'parsed'     => [],
		];
	}

	/**
	 * Register a cache handler class.
	 *
	 * @param string $id Handler ID.
	 * @param string $class Fully qualified handler class name.
	 *
	 * @return bool
	 */
	public function add_cache_class( $id, $class ) {
		if ( ! isset( $this->cache_classes[ $id ] ) ) {
			$this->cache_classes[ $id ] = $class;

			if ( is_null( $this->default_cache_class ) ) {
				$this->set_default_cache_class( $id );
			}

			return true;
		}

		return false;
	}

	/**
	 * Adds URLs and cache instances as needed and returns the cache instance.
	 *
	 * @param string $url Current URL.
	 *
	 * @return mixed Cache handler class (e.g. SSNepenthe\CacheManager\FullPageCache) or false.
	 */
	public function cache_instance( $url ) {
		if ( ! $normalized = $this->get_normalized_url( $url ) ) {
			$this->add_url( $url );
			$normalized = $this->get_normalized_url( $url );
		}

		if ( $instance = $this->get_cache_instance( $normalized ) ) {
			return $instance;
		}

		$this->add_cache_instance( $normalized );

		return $this->get_cache_instance( $normalized );
	}

	/**
	 * Toolbar callback for creating a cache item.
	 */
	public function create_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$this->cache_instance( $url )->create();
	}

	/**
	 * Create cache instance based on the page currently being viewed.
	 *
	 * @return mixed Cache handler class or false
	 */
	public function current_instance() {
		$url = false;

		if ( is_admin() ) {
			global $pagenow;

			$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
			$post = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

			if ( 'post.php' === $pagenow ) {
				if (
					! is_null( $action ) &&
					'edit' === $action &&
					! is_null( $post )
				) {
					$url = get_permalink( absint( $post ) );
				}
			}
		} else {
			$request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
			$parsed_url = parse_url( home_url( $request_uri ) );

			$url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
		}

		if ( $url ) {
			return $this->cache_instance( $url );
		}

		return false;
	}

	/**
	 * Toolbar callback for deleting a cache item.
	 */
	public function delete_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$instance = $this->cache_instance( $url )->delete();
	}

	/**
	 * Toolbar callback for flushing all cache items.
	 */
	public function flush_callback() {
		// URL isn't important, but necessary to instantiate a cache class.
		$url = home_url();
		$instance = $this->cache_instance( $url )->flush();
	}

	/**
	 * Get a registered cache class.
	 *
	 * @param string $id Handler class ID.
	 *
	 * @return mixed String or false
	 */
	public function get_cache_class( $id ) {
		return $this->get_cache_classes( $id );
	}

	/**
	 * Getter for cache_classes.
	 *
	 * @param mixed $id Null or string of handler class ID.
	 *
	 * @return mixed Array of cache classes, string of specific class ID or false.
	 */
	public function get_cache_classes( $id = null ) {
		if ( is_null( $id ) ) {
			return $this->cache_classes;
		}

		if ( isset( $this->cache_classes[ $id ] ) ) {
			return $this->cache_classes[ $id ];
		}

		return false;
	}

	/**
	 * Getter for a single cache instance.
	 *
	 * @param string $url Cache URL.
	 *
	 * @return mixed Handler instance or false
	 */
	public function get_cache_instance( $url ) {
		return $this->get_cache_instances( $url );
	}

	/**
	 * Getter for cache_instances.
	 *
	 * @param mixed $url Null or string of cache URL.
	 *
	 * @return mixed Array of cache handler instances, single cache handler
	 *               instance or false.
	 */
	public function get_cache_instances( $url = null ) {
		if ( is_null( $url ) ) {
			return $this->cache_instances;
		}

		if ( isset( $this->cache_instances[ $url ] ) ) {
			return $this->cache_instances[ $url ];
		}

		return false;
	}

	/**
	 * Getter for single normalized URL.
	 *
	 * @param string $url Single URL.
	 *
	 * @todo Consider calling add_url() if not already set.
	 *
	 * @return mixed String or false.
	 */
	public function get_normalized_url( $url ) {
		if ( isset( $this->urls['normalized'][ $url ] ) ) {
			return $this->urls['normalized'][ $url ];
		}

		return false;
	}

	/**
	 * Getter for single parsed URL.
	 *
	 * @param string $url Single URL.
	 *
	 * @todo Consider calling add_url() if not already set.
	 *
	 * @return mixed String or false.
	 */
	public function get_parsed_url( $url ) {
		if ( $normalized = $this->get_normalized_url( $url ) ) {
			return $this->urls['parsed'][ $normalized ];
		}

		return false;
	}

	/**
	 * Hook in to WordPress.
	 *
	 * @throws \Exception If no default is set or no matching handler is found.
	 */
	public function init() {
		if ( is_null( $this->default_cache_class ) ) {
			throw new \Exception( 'No default cache class set.' );
		}

		if ( ! $this->get_cache_class( $this->default_cache_class ) ) {
			throw new \Exception( 'Default cache class does not exist.' );
		}

		if ( ! empty( $this->toolbar_nodes ) && $this->current_instance() ) {
			$this->toolbar = new Toolbar;

			$this->toolbar->add_nodes( $this->toolbar_nodes );

			add_action( 'admin_bar_menu', [ $this->toolbar, 'admin_bar_menu' ], 999 );

			add_action( 'wp_head', [ $this, 'toolbar_styles' ] );
			add_action( 'admin_head', [ $this, 'toolbar_styles' ] );
		}

		add_action( 'admin_init', [ $this, 'admin_init' ], 999 );
		add_action(
			'transition_post_status',
			[ $this, 'transition_post_status' ],
			10,
			3
		);
	}

	/**
	 * Toolbar callback for regenerating cache item.
	 */
	public function refresh_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$instance = $this->cache_instance( $url )->refresh();
	}

	/**
	 * Setter for default_cache_class.
	 *
	 * @param string $id Handler ID.
	 *
	 * @return bool
	 */
	public function set_default_cache_class( $id ) {
		if ( isset( $this->cache_classes[ $id ] ) ) {
			$this->default_cache_class = $id;

			return true;
		}

		return false;
	}

	/**
	 * Calls the cache delete method for a post when a post status changes.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post WordPress post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $old_status && 'private' !== $old_status ) {
			return;
		}

		$url = get_permalink( $post );

		$this->cache_instance( $url )->delete();
	}

	/**
	 * Creates and saves new cache instances as needed.
	 *
	 * @param string $url Cache URL.
	 *
	 * @return bool
	 */
	protected function add_cache_instance( $url ) {
		if (
			! isset( $this->cache_instances[ $url ] ) &&
			! is_null( $this->default_cache_class ) &&
			$class = $this->get_cache_class( $this->default_cache_class )
		) {
			$this->cache_instances[ $url ] = new $class( $url );

			return true;
		}

		return false;
	}

	/**
	 * Adds a URL to the URL index if not previously set.
	 *
	 * @param string $url Cache URL.
	 *
	 * @return bool
	 */
	protected function add_url( $url ) {
		// A lot of this is basically wp_http_validate_url().
		$original = $url;

		$url = wp_kses_bad_protocol( $url, [ 'http', 'https' ] );

		if ( ! $url || strtolower( $url ) !== strtolower( $original ) ) {
			return false;
		}

		$parsed = parse_url( $url );

		if ( ! $parsed || ! isset( $parsed['host'] ) ) {
			return false;
		}

		if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
			return false;
		}

		if ( false !== strpbrk( $parsed['host'], ':#?[]' ) ) {
			return false;
		}

		if ( ! isset( $parsed['path'] ) ) {
			$parsed['path'] = '/';
		}

		$parsed_home = parse_url( home_url() );
		$s = strtolower( $parsed_home['host'] ) === strtolower( $parsed['host'] );

		if ( ! $s ) {
			return false;
		}

		$normalized = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
		$r = false;

		if ( ! isset( $this->urls['normalized'][ $url ] ) ) {
			$this->urls['normalized'][ $url ] = $normalized;

			$r = true;
		}

		if ( ! isset( $this->urls['parsed'][ $normalized ] ) ) {
			$this->urls['parsed'][ $normalized ] = [
				'scheme' => $parsed['scheme'],
				'host'   => $parsed['host'],
				'path'   => $parsed['path'],
			];

			$r = true;
		}

		return $r;
	}

	/**
	 * Parse args for new toolbar nodes.
	 *
	 * @param array $args Toolbar node args.
	 *
	 * @return array
	 */
	protected function parse_args( array $args ) {
		$args = wp_parse_args( $args, $this->node_defaults );

		if ( $args['id'] ) {
			$args['id'] = preg_replace( '/[^a-zA-Z0-9_-]/', '', $args['id'] );

			if ( '' === $args['id'] ) {
				$args['id'] = false;
			}
		}

		if ( $instance = $this->current_instance() ) {
			if ( ! $args['href'] && ! $args['no-href'] ) {
				$args['href'] = admin_url( 'index.php' );
			}

			if ( $args['href'] ) {
				// Assume that if there is a query string, URL is already prepared.
				if ( false === strpos( $args['href'], '?' ) ) {
					$path = urlencode( $instance->get_path() );

					$args['href'] = add_query_arg( [
						'action' => $args['id'],
						'path' => $path,
					], $args['href'] );
				}

				$args['href'] = wp_nonce_url(
					$args['href'],
					'SSNepenthe\\CacheManager\\' . $args['id']
				);
			}
		}

		return $args;
	}
}
