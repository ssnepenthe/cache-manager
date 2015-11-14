<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Toolbar {
	protected $defaults;
	protected $nodes = [];

	public function __construct() {
		$this->defaults = [
			// Mirror the defaults for WP_Admin_Bar->add_node().
			'group'      => false,
			'href'       => false,
			'id'         => false,
			'meta'       => [],
			'parent'     => false,
			'title'      => false,

			// And some plugin-specific extras.
			'capability' => 'edit_theme_options',
			'display-cb' => '__return_true',
		];
	}

	public function add_node( array $args ) {
		$args = $this->parse_args( $args );

		if ( ! $args['id'] ) {
			return false;
		}

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

	public function add_nodes( array $nodes ) {
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$this->add_node( $node );
		}
	}

	public function admin_bar_menu( $wp_admin_bar ) {
		if ( empty( $this->nodes ) ) {
			return;
		}

		foreach ( $this->nodes as $id => $args ) {
			if ( call_user_func( $args['display-cb'] ) ) {
				$wp_admin_bar->add_node( $args );
			}
		}
	}

	public function get_node( $id ) {
		return $this->get_nodes( $id );
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
		if ( ! isset( $this->nodes[ $id ] ) ) {
			return false;
		}

		// Don't remove the primary node unless it is the only node.
		if ( $id === $this->defaults['parent'] && 1 < count( $this->nodes ) ) {
			return false;
		}

		unset( $this->nodes[ $id ] );

		return true;
	}

	public function remove_nodes( array $ids ) {
		foreach( $ids as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->remove_node( $id );
		}
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
			$args['title'] = ucwords( str_replace( [ '-', '_' ], ' ', $args['id'] ) );
		}

		return $args;
	}
}
