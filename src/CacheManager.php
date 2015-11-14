<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class CacheManager {
	protected $plugin_name;
	protected $plugin_version;

	protected $cache_instances = [];
	protected $default_cache_class = null;
	protected $registered_cache_classes = [];
	protected $toolbar;

	public function __construct( $name, $version ) {
		$this->plugin_name = $name;
		$this->plugin_version = $version;
	}

	public function add_cache_class( $id, $class ) {
		if ( ! isset( $this->registered_cache_classes[ $id ] ) ) {
			$this->registered_cache_classes[ $id ] = $class;

			return true;
		}

		return false;
	}

	public function add_cache_classes( array $classes ) {
		foreach ( $classes as $id => $class ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->add_cache_class( $id, $class );
		}
	}

	public function get_cache_class( $id ) {
		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			return $this->registered_cache_classes[ $id ];
		}

		return false;
	}

	public function get_cache_instances( $url = null ) {
		if ( is_null( $url ) ) {
			return $this->cache_instances;
		}

		$url = $this->normalize_url( $url );

		if ( isset( $this->cache_instances[ $url ] ) ) {
			return $this->cache_instances[ $url ];
		}

		return false;
	}

	public function get_default_cache_class() {
		return $this->default_cache_class;
	}

	public function init() {
		if ( is_null( $this->default_cache_class ) ) {
			throw new \Exception( 'No default cache class set.' );
		}

		if ( ! $this->get_cache_class( $this->default_cache_class ) ) {
			throw new \Exception( 'Default cache class does not exist.' );
		}

		if ( is_admin_bar_showing() && $url = $this->maybe_get_current_page_url() ) {
			$this->create_cache_instance( $url );

			$this->toolbar = new Toolbar( $this->get_cache_instances( $url ) );
			$this->add_toolbar_nodes();

			do_action( __NAMESPACE__ . '\\toolbar_init', $this->toolbar );

			add_action( 'admin_bar_menu', [ $this->toolbar, 'admin_bar_menu' ], 999 );
			add_action( 'admin_init', [ $this->toolbar, 'admin_init' ], 999 );

			/**
			 TEMPORARY
			 */
			foreach ( [ 'wp_head', 'admin_head' ] as $action ) {
				add_action( $action, function() {
					echo '<style>';
					echo '#wpadminbar .cache-manager-icon { border-radius: 50%; display: inline-block; float: left; height: 12px; margin: 10px 6px 0 0; width: 12px; }';
					echo '#wpadminbar #wp-admin-bar-purge-cache .ab-item { height: auto; padding-bottom: 12px; }';
					echo '.exists { background: green; }';
					echo '.does-not-exist { background: red; }';
					echo '</style>';
				} );
			}
			/**
			 TEMPORARY
			 */
		}

		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
	}

	public function normalize_url( $url ) {
		// Needs a lot of work...
		$parsed_url = parse_url( $url );

		if ( ! $url || ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
			throw new \Exception( 'Malformed URL.' );
		}

		if ( ! isset( $parsed_url['path'] ) ) {
			$parsed_url['path'] = '/';
		}

		return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
	}

	public function remove_cache_classes( $id ) {
		$r = false;

		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			unset( $this->registered_cache_classes[ $id ] );

			if ( $id === $this->default_cache_class ) {
				$this->default_cache_class = null;
			}

			$r = true;
		}

		return $r;
	}

	public function set_default_cache_class( $id ) {
		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			$this->default_cache_class = $id;

			return true;
		}

		return false;
	}

	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $old_status && 'private' !== $old_status ) {
			return;
		}

		$url = get_permalink( $post );

		$this->create_cache_instance( $url )->delete();
	}

	protected function add_toolbar_nodes() {
		$classes = [ 'cache-manager-icon' ];

		if ( $this->toolbar->get_cache_instance()->exists() ) {
			$classes[] = 'exists';
		} else {
			$classes[] = 'does-not-exist';
		}

		$this->toolbar->add_node( [
			'id'         => 'ssn-cache-manager',
			'title'      => sprintf( 'Cache<div class="%1$s"></div>', implode( ' ', $classes ) ),
			'no-href'    => true,
		] );

		$this->toolbar->add_node( [
			'id'         => 'ssn-refresh-cache',
			'title'      => 'Refresh Cache',
			'action-cb'  => [ $this, 'refresh_callback' ],
		] );

		$this->toolbar->add_node( [
			'id'         => 'ssn-delete-cache',
			'title'      => 'Delete Cache',
			'action-cb'  => [ $this, 'delete_callback' ],
		] );

		$this->toolbar->add_node( [
			'id'         => 'ssn-create-cache',
			'title'      => 'Create Cache',
			'action-cb'  => [ $this, 'create_callback' ],
		] );

		$this->toolbar->add_node( [
			'id'         => 'ssn-flush-cache',
			'title'      => 'Flush Cache',
			'action-cb'  => [ $this, 'flush_callback' ],
			'href'       => add_query_arg( [ 'action' => 'ssn-flush-cache' ], admin_url( 'index.php' ) ),
		] );
	}

	protected function create_cache_instance( $url ) {
		if ( $instance = $this->get_cache_instances( $url ) ) {
			return $instance;
		}

		if (
			! is_null( $this->default_cache_class ) &&
			$class = $this->get_cache_class( $this->default_cache_class )
		) {
			$url = $this->normalize_url( $url );
			$this->cache_instances[ $url ] = new $class( $url );

			return $this->cache_instances[ $url ];
		}

		return false;
	}

	protected function maybe_get_current_page_url() {
		$url = false;

		if ( is_admin() ) {
			global $pagenow;

			if ( 'post.php' === $pagenow ) {
				if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['post'] ) ) {
					$url = get_permalink( absint( $_GET['post'] ) );
				}
			}
		} else {
			$parsed_url = parse_url( home_url( $_SERVER['REQUEST_URI'] ) );

			$url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
		}

		return $url;
	}
}
