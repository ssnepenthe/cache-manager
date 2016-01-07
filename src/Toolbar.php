<?php
/**
 * Toolbar wrapper to simplify the process of adding new actions to the cache menu.
 *
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * This class provides a wrapper for the WordPress toolbar functionality.
 *
 * This class cuts the amount of boilerplate code needed to add a new item to
 * the cache menu in the WordPress toolbar. It automatically validates the
 * passed args, assigns the parent node as necessary and adds the node based on
 * the display callback.
 */
class Toolbar {
	/**
	 * Default toolbar node args.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * List of nodes and args to be added on admin_bar_menu hook.
	 *
	 * @var array
	 */
	protected $nodes = [];

	/**
	 * Contructor
	 */
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

	/**
	 * Validates args and creates a new toolbar node.
	 *
	 * @param array $args Node args.
	 *
	 * @return bool
	 */
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

	/**
	 * Add multiple nodes at once.
	 *
	 * @param array $nodes Array of arrays of node args.
	 */
	public function add_nodes( array $nodes ) {
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$this->add_node( $node );
		}
	}

	/**
	 * Registers all nodes with WordPress.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin bar instance.
	 */
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

	/**
	 * Node getter.
	 *
	 * @param string $id Node id to fetch.
	 *
	 * @return array
	 */
	public function get_node( $id ) {
		return $this->get_nodes( $id );
	}

	/**
	 * Nodes getter.
	 *
	 * @param mixed $id String or null.
	 *
	 * @return array
	 */
	public function get_nodes( $id = null ) {
		if ( is_null( $id ) ) {
			return $this->nodes;
		}

		if ( isset( $this->nodes[ $id ] ) ) {
			return $this->nodes[ $id ];
		}

		return false;
	}

	/**
	 * Remove a single node.
	 *
	 * @param string $id Node ID.
	 *
	 * @return bool
	 */
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

	/**
	 * Remove multiple nodes.
	 *
	 * @param array $ids Array of node IDs.
	 */
	public function remove_nodes( array $ids ) {
		foreach ( $ids as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->remove_node( $id );
		}
	}

	/**
	 * Parse and sanitize args.
	 *
	 * @param array $args Node args.
	 *
	 * @return array
	 */
	protected function parse_args( $args ) {
		$args = wp_parse_args( $args, $this->defaults );

		if ( $args['id'] ) {
			$args['id'] = preg_replace( '/[^a-zA-Z0-9_-]/', '', $args['id'] );

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
