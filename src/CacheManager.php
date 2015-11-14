<?php
/**
 * Defines the main plugin class.
 *
 * @package cache-manager
 *
 * @todo replace all uses of $_GET, $_SERVER supers with filter_input().
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class CacheManager {
	protected $cache_classes = [];
	protected $cache_instances = [];
	protected $default_cache_class = null;
	protected $node_defaults;
	protected $plugin_name;
	protected $plugin_version;
	protected $toolbar = null;
	protected $toolbar_nodes = [];
	protected $urls;

	public function __construct( $name, $version ) {
		$this->node_defaults = [
			'href'       => false,
			'id'         => false,
			'action-cb'  => false,
			'capability' => 'edit_theme_options',
			'no-href'    => false,
		];

		$this->plugin_name = $name;
		$this->plugin_version = $version;

		$this->urls = [
			'normalized' => [],
			'parsed'     => [],
		];
	}

	public function add_cache_class( $id, $class ) {
		if ( ! isset( $this->cache_classes[ $id ] ) ) {
			$this->cache_classes[ $id ] = $class;

			return true;
		}

		return false;
	}

	public function add_cache_classes( array $classes ) {
		foreach ( $classes as $id => $class ) {
			if ( ! is_string( $id ) || ! is_string( $class ) ) {
				continue;
			}

			$this->add_cache_class( $id, $class );
		}
	}

	public function add_toolbar_node( array $args ) {
		$args = $this->parse_args( $args );

		if ( ! $args['id'] ) {
			return false;
		}

		if ( $args['href'] && ! $args['action-cb'] ) {
			return false;
		}

		if ( ! isset( $this->toolbar_nodes[ $args['id'] ] ) ) {
			$this->toolbar_nodes[ $args['id'] ] = $args;

			return true;
		}

		return false;
	}

	public function add_toolbar_nodes( array $nodes ) {
		foreach ( $nodes as $args ) {
			if ( ! is_array( $args ) ) {
				continue;
			}

			$this->add_toolbar_node( $args );
		}
	}

	public function admin_init() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( empty( $this->toolbar_nodes ) ) {
			return;
		}

		$action = preg_replace( "/[^a-zA-Z0-9_-]/", '', $_GET['action'] );
		$nonce = preg_replace( "/[^a-fA-F0-9]/", '', $_GET['_wpnonce'] );

		if ( '' === $action || '' === $nonce ) {
			return;
		}

		if ( ! $node = $this->get_toolbar_node( $action ) ) {
			return;
		}

		$intended = wp_verify_nonce(
			$nonce,
			'SSNepenthe\\CacheManager\\' . $action
		);
		$allowed = current_user_can( $node['capability'] );

		if ( ! $intended || ! $allowed ) {
			return;
		}

		if ( ! is_callable( $node['action-cb'] ) ) {
			return;
		}

		call_user_func( $node['action-cb'] );
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

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

	public function create_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$this->cache_instance( $url )->create();
	}

	public function current_instance() {
		$url = false;

		if ( is_admin() ) {
			global $pagenow;

			if ( 'post.php' === $pagenow ) {
				if (
					isset( $_GET['action'] ) &&
					'edit' === $_GET['action'] &&
					isset( $_GET['post'] )
				) {
					$url = get_permalink( absint( $_GET['post'] ) );
				}
			}
		} else {
			$parsed_url = parse_url( home_url( $_SERVER['REQUEST_URI'] ) );

			$url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
		}

		if ( $url ) {
			return $this->cache_instance( $url );
		}

		return false;
	}

	public function delete_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$instance = $this->cache_instance( $url )->delete();
	}

	public function flush_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$instance = $this->cache_instance( $url )->flush();
	}

	public function get_cache_class( $id ) {
		return $this->get_cache_classes( $id );
	}

	public function get_cache_classes( $id = null ) {
		if ( is_null( $id ) ) {
			return $this->cache_classes;
		}

		if ( isset( $this->cache_classes[ $id ] ) ) {
			return $this->cache_classes[ $id ];
		}

		return false;
	}

	public function get_cache_instance( $url ) {
		return $this->get_cache_instances( $url );
	}

	public function get_cache_instances( $url = null ) {
		if ( is_null( $url ) ) {
			return $this->cache_instances;
		}

		if ( isset( $this->cache_instances[ $url ] ) ) {
			return $this->cache_instances[ $url ];
		}

		return false;
	}

	public function get_default_cache_class() {
		return $this->default_cache_class;
	}

	// Should probably call add_url() if not set.
	public function get_normalized_url( $url ) {
		if ( isset( $this->urls['normalized'][ $url ] ) ) {
			return $this->urls['normalized'][ $url ];
		}

		return false;
	}

	// Should probably call add_url() if not set.
	public function get_parsed_url( $url ) {
		if ( $normalized = $this->get_normalized_url( $url ) ) {
			return $this->urls['parsed'][ $normalized ];
		}

		return false;
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_plugin_version() {
		return $this->plugin_version;
	}

	public function get_toolbar() {
		return $this->toolbar;
	}

	public function get_toolbar_node( $id ) {
		return $this->get_toolbar_nodes( $id );
	}

	public function get_toolbar_nodes( $id = null ) {
		if ( is_null( $id ) ) {
			return $this->toolbar_nodes;
		}

		if ( isset( $this->toolbar_nodes[ $id ] ) ) {
			return $this->toolbar_nodes[ $id ];
		}

		return false;
	}

	// Should probably call add_url() if not already set.
	public function get_url( $url ) {
		if ( isset( $this->urls['normalized'][ $url ] ) ) {
			$normalized = $this->urls['normalized'][ $url ];

			$parsed = $this->urls['parsed'][ $normalized ];

			return [
				'normalized' => $normalized,
				'parsed'     => $parsed,
			];
		}

		return false;
	}

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

	public function refresh_callback() {
		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( is_null( $path ) || ! $path ) {
			return;
		}

		$url = home_url( $path );
		$instance = $this->cache_instance( $url )->refresh();
	}

	public function remove_cache_class( $id ) {
		if ( isset( $this->cache_classes[ $id ] ) ) {
			unset( $this->cache_classes[ $id ] );

			if ( $id === $this->default_cache_class ) {
				$this->default_cache_class = null;
			}

			return true;
		}

		return false;
	}

	public function remove_cache_classes( array $ids ) {
		foreach ( $ids as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->remove_cache_class( $id );
		}
	}

	public function remove_toolbar_node( $id ) {
		if ( isset( $this->toolbar_nodes[ $id ] ) ) {
			unset( $this->toolbar_nodes[ $id ] );

			return true;
		}

		return false;
	}

	public function remove_toolbar_nodes( array $ids ) {
		foreach ( $ids as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->remove_toolbar_node( $id );
		}
	}

	public function set_default_cache_class( $id ) {
		if ( isset( $this->cache_classes[ $id ] ) ) {
			$this->default_cache_class = $id;

			return true;
		}

		return false;
	}

	public function toolbar_styles() {
		if ( ! is_admin_bar_showing() && ! is_admin() ) {
			return;
		}

		if ( ! $this->current_instance() ) {
			return;
		}

		$style = <<<TOOLBARSTYLE
<style type="text/css">
	#wpadminbar .cache-manager-icon {
		border-radius: 50%;
		display: inline-block;
		float: left;
		height: 12px;
		margin: 10px 6px 0 0;
		width: 12px;
	}

	#wpadminbar #wp-admin-bar-purge-cache .ab-item {
		height: auto;
		padding-bottom: 12px;
	}

	.exists {
		background: green;
	}

	.does-not-exist {
		background: red;
	}
</style>
TOOLBARSTYLE;

		// Pseudo minification...
		echo preg_replace( '/\s+/', ' ', $style );
	}

	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $old_status && 'private' !== $old_status ) {
			return;
		}

		$url = get_permalink( $post );

		$this->cache_instance( $url )->delete();
	}

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

	protected function parse_args( array $args ) {
		$args = wp_parse_args( $args, $this->node_defaults );

		if ( $args['id'] ) {
			$args['id'] = preg_replace( "/[^a-zA-Z0-9_-]/", '', $args['id'] );

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
