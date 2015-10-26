<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Toolbar {
	protected $default_args;
	protected $items;

	public function __construct( $args ) {
		// Mirrors the defaults for WP_Admin_Bar->add_node()
		$this->default_args = [
			'capability' => 'edit_theme_options',
			'display'    => [ $this, 'display' ],
			'group'      => false,
			'href'       => false,
			'id'         => false,
			'meta'       => [],
			'parent'     => $args['id'],
			'title'      => false,
		];

		$this->items = [];

		// Create parent node.
		$args = $this->parse_args( $args );

		if ( ! $args['title'] ) {
			return;
		}

		$this->add_item( $args );
	}

	public function admin_bar_menu( $wp_admin_bar ) {
		foreach( $this->items as $item ) {
			$wp_admin_bar->add_node( $item );
		}
	}

	public function add_item( $args ) {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$args = $this->parse_args( $args );

		if ( ! $args['title'] ) {
			return;
		}

		if ( ! current_user_can( $args['capability'] ) ) {
			return;
		}

		if ( ! call_user_func( $args['display'] ) ) {
			return;
		}

		$this->items[] = $args;
	}

	public function display() {
		$r = true;

		if ( is_admin() ) {
			$r = false;
		}

		return $r;
	}

	protected function parse_args( $args ) {
		$args = wp_parse_args( $args, $this->default_args );

		if ( ! $args['id'] ) {
			$args['id'] = sanitize_title_with_dashes( $args['title'] );
		}

		return $args;
	}
}
