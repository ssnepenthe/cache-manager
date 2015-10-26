<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class CacheManager {
	protected $plugin_name;
	protected $plugin_version;

	protected $cache;
	protected $toolbar;

	public function __construct( $name, $version ) {
		$this->plugin_name = $name;
		$this->plugin_version = $version;
	}

	public function init() {
		$this->cache = new Cache;

		$classes = [ 'cache-manager-icon' ];

		if ( $this->cache->cache_file_exists() ) {
			$classes[] = 'cache-file-exists';
		} else {
			$classes[] = 'cache-file-does-not-exist';
		}

		$class = implode( ' ', $classes );

		$icon = sprintf(
			'<div class="%1$s"></div>',
			esc_attr( $class )
		);

		$args = [
			'id'     => 'cache-manager',
			'parent' => false,
			'title'  => 'Cache Manager ' . $icon,
		];

		$this->toolbar = new Toolbar( $args );

		$this->add_items();

		$this->public_hooks();
	}

	public function public_hooks() {
		add_action( 'wp_head', [ $this->cache, 'cache_timestamp' ] );

		add_action( 'admin_bar_menu', [ $this->toolbar, 'admin_bar_menu' ], 100 );

		/**
		 TEMPORARY
		 */
		add_action( 'wp_head', function() {
			echo '<style>';
			echo '#wpadminbar .cache-manager-icon { border-radius: 50%; display: inline-block; float: left; height: 12px; margin: 10px 6px 0 0; width: 12px; }';

			echo '#wpadminbar #wp-admin-bar-purge-cache .ab-item { height: auto; padding-bottom: 12px; }';
			echo '#wp-admin-bar-purge-cache span { height: 18px; }';
			echo '#wp-admin-bar-purge-cache .age { color: #999; font-size: 11px; }';

			echo '.cache-file-exists { background: green; }';
			echo '.cache-file-does-not-exist { background: red; }';
			echo '.purge, .age { display: block; }';
			echo '</style>';
		} );
		/**
		 TEMPORARY
		 */
	}

	protected function add_items() {
		$defaults = [];

		if ( ! $this->cache->cache_file_exists() ) {
			$defaults[] = [ 'title' => 'Generate Cache' ];
		} else {
			$age = $this->cache->get_cache_file_age();
			$title = sprintf(
				'<span class="age">Age: %1$s</span>',
				$age
			);

			$defaults[] = [
				'href'  => '#',
				'id'    => 'purge-cache',
				'title' => '<span class="purge">Purge Cache</span>' . $title,
			];
		}

		foreach ( $defaults as $item ) {
			$this->toolbar->add_item( $item );
		}
	}
}
