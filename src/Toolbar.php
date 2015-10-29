<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Toolbar {
	protected $defaults;
	protected $nodes;

	public function __construct( $args ) {
		if ( ! is_array( $args ) || ! isset( $args['id'] ) ) {
			// TODO: trigger error of some sort.
			return;
		}

		$this->defaults = [
			// Mirror the defaults for WP_Admin_Bar->add_node().
			'group'      => false,
			'href'       => false,
			'id'         => false,
			'meta'       => [],
			'parent'     => false,
			'title'      => false,

			// And some plugin-specific extras.
			'callback'   => false,
			'capability' => 'edit_theme_options',
			'display'    => [ $this, 'display_item' ],
		];

		$this->nodes = [];

		$this->add_node( $args );

		// All future nodes should be children of this first node.
		$this->defaults['parent'] = $args['id'];

		// And all children nodes should be links.
		$this->defaults['href'] = admin_url( 'index.php' );
	}

	public function add_node( $args ) {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! is_array( $args ) || ! isset( $args['id'] ) ) {
			return;
		}

		$args = $this->parse_args( $args );

		if ( ! current_user_can( $args['capability'] ) ) {
			return;
		}

		if ( ! is_callable( $args['display'] ) ) {
			return;
		}

		$args['display'] = call_user_func( $args['display'] ) ? true : false;

		$this->nodes[ $args['id'] ] = $args;
	}

	public function get_node( $id ) {
		$r = false;

		if ( isset( $this->nodes[ $id ] ) ) {
			$r = $this->nodes[ $id ];
		}

		return $r;
	}

	public function get_nodes() {
		return $this->nodes;
	}

	public function remove_node( $id ) {
		$r = false;

		if ( isset( $this->nodes[ $id ] ) ) {
			unset( $this->nodes[ $id ] );
			$r = true;
		}

		return $r;
	}

	public function display_item() {
		$r = true;

		// Need to put some thought into this one.
		// It is the default display callback, though it is unused at the moment.

		return $r;
	}

	public function parse_args( $args ) {
		$args = wp_parse_args( $args, $this->defaults );

		if ( ! $args['title'] ) {
			$args['title'] = ucwords( str_replace( [ '-', '_' ], ' ', $args['id'] ) );
		}

		$path = urlencode( $_SERVER['REQUEST_URI'] );

		if ( $args['href'] ) {
			$args['href'] = wp_nonce_url( add_query_arg(
				[
					'action' => $args['id'],
					'path' => $path,
				],
				$args['href']
			), $args['id'] . $path );
		}

		return $args;
	}

	public function admin_init() {
		$nodes = $this->get_nodes();

		if ( ! empty( $nodes ) && isset( $_GET['action'] ) && in_array( $_GET['action'], array_keys( $nodes ) ) ) {
			$id = $_GET['action'];
			$path = $_GET['path'];
			$nonce = $_GET['_wpnonce'];
			$node = $this->get_node( $id );

			$valid = wp_verify_nonce( $nonce, $id . urlencode( $path ) );

			if ( $valid && current_user_can( $node['capability'] ) ) {
				if ( is_callable( $node['callback'] ) ) {
					call_user_func( $node['callback'] );
				}
			}

			wp_safe_redirect( wp_get_referer() );
		}
	}

	public function admin_bar_menu( $wp_admin_bar ) {
		if ( ! empty( $this->get_nodes() ) ) {
			foreach ( $this->get_nodes() as $id => $args ) {
				if ( $args['display'] ) {
					$wp_admin_bar->add_node( $args );
				}
			}
		}
	}
}
