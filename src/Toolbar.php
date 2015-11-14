<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Toolbar {
	protected $cache_instance;
	protected $defaults;
	protected $nodes;

	public function __construct( FullPageCache $cache_instance ) {
		$this->cache_instance = $cache_instance;

		$this->defaults = [
			// Mirror the defaults for WP_Admin_Bar->add_node().
			'group'      => false,
			'href'       => false,
			'id'         => false,
			'meta'       => [],
			'parent'     => false,
			'title'      => false,

			// And some plugin-specific extras.
			'action-cb'  => false,
			'capability' => 'edit_theme_options',
			'display-cb' => '__return_true',
			'no-href'    => false,
		];
	}

	public function add_node( $args ) {
		if ( ! is_admin_bar_showing() ) {
			return false;
		}

		if ( ! is_array( $args ) || ! isset( $args['id'] ) ) {
			return false;
		}

		$args = $this->parse_args( $args );

		if ( ! current_user_can( $args['capability'] ) ) {
			return false;
		}

		if ( ! is_callable( $args['display-cb'] ) ) {
			return false;
		}

		$this->nodes[ $args['id'] ] = $args;

		// After the first node is added, set it as the default parent node.
		if ( 1 === count( $this->nodes ) ) {
			$this->defaults['parent'] = $args['id'];
		}

		return true;
	}

	public function admin_bar_menu( $wp_admin_bar ) {
		if ( ! empty( $this->nodes ) ) {
			foreach ( $this->nodes as $id => $args ) {
				if ( is_callable( $args['display-cb'] ) && call_user_func( $args['display-cb'] ) ) {
					$wp_admin_bar->add_node( $args );
				}
			}
		}
	}

	public function admin_init() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( empty( $this->nodes ) ) {
			return;
		}

		$action = preg_replace( "/[^a-zA-Z0-9_-]/", '', $_GET['action'] );
		$nonce = preg_replace( "/[^a-fA-F0-9]/", '', $_GET['_wpnonce'] );

		if ( '' === $action || '' === $nonce ) {
			return;
		}

		if ( ! $node = $this->get_nodes( $action ) ) {
			return;
		}

		$intended = wp_verify_nonce( $nonce, 'SSNepenthe\\CacheManager\\' . $action );
		$allowed = current_user_can( $node['capability'] );

		if ( ! $intended || ! $allowed ) {
			return;
		}

		if ( ! is_callable( $node['action-cb'] ) ) {
			return;
		}

		call_user_func( $node['action-cb'], $path );
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	public function get_cache_instance() {
		return $this->cache_instance;
	}

	public function get_nodes( $id = null ) {
		if ( is_null( $id ) ) {
			return $this->nodes;
		}

		if ( isset( $this->nodes[ $id ] ) ) {
			return $this->nodes[ $id ];
		}

		return false;
	}

	public function remove_node( $id ) {
		if ( isset( $this->nodes[ $id ] ) ) {
			unset( $this->nodes[ $id ] );

			return true;
		}

		return false;
	}

	protected function parse_args( $args ) {
		$args = wp_parse_args( $args, $this->defaults );

		if ( $args['id'] ) {
			$args['id'] = preg_replace( "/[^a-zA-Z0-9_-]/", '', $args['id'] );

			if ( '' === $args['id'] ) {
				$args['id'] = false;
			}
		}

		if ( ! $args['title'] && $args['id'] ) {
			$args['title'] = ucwords( str_replace( [ '-', '_' ], ' ', $id ) );
		}

		if ( ! $args['href'] && ! $args['no-href'] ) {
			$args['href'] = admin_url( 'index.php' );
		}

		if ( $args['href'] ) {
			// Assume that if there is a query string, URL is already prepared.
			if ( false === strpos( $args['href'], '?' ) ) {
				$path = urlencode( $this->cache_instance->get_path() );

				$args['href'] = add_query_arg( [
					'action' => $args['id'],
					'path' => $path,
				], $args['href'] );
			}

			$args['href'] = wp_nonce_url( $args['href'], 'SSNepenthe\\CacheManager\\' . $args['id'] );
		}

		return $args;
	}
}
