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
		$url = home_url();

		// Should probably check action to make sure this belongs to our plugin.
		// But toolbar object isn't available yet.
		if ( isset( $_GET['path'] ) ) {
			$url .= $_GET['path'];
		} else {
			$url .= $_SERVER['REQUEST_URI'];
		}
		$this->cache = new Cache( $url );

		$classes = [ 'cache-manager-icon' ];

		if ( $this->cache->cache_exists() ) {
			$classes[] = 'exists';
		} else {
			$classes[] = 'does-not-exist';
		}

		$this->toolbar = new Toolbar( [
			'id' => 'cache-manager',
			'title' => sprintf( 'Cache<div class="%1$s"></div>', implode( ' ', $classes ) ),
		] );

		$this->add_nodes();

		$this->public_hooks();
	}

	public function public_hooks() {
		add_action( 'admin_init', [ $this->toolbar, 'admin_init' ] );
		add_action( 'admin_bar_menu', [ $this->toolbar, 'admin_bar_menu' ], 999 );

		/**
		 TEMPORARY
		 */
		add_action( 'wp_head', function() {
			echo '<style>';
			echo '#wpadminbar .cache-manager-icon { border-radius: 50%; display: inline-block; float: left; height: 12px; margin: 10px 6px 0 0; width: 12px; }';
			echo '#wpadminbar #wp-admin-bar-purge-cache .ab-item { height: auto; padding-bottom: 12px; }';
			echo '.exists { background: green; }';
			echo '.does-not-exist { background: red; }';
			echo '</style>';
		} );
		/**
		 TEMPORARY
		 */
	}

	protected function add_nodes() {
		$this->toolbar->add_node( [
			'id' => 'refresh-cache',
			'title' => 'Refresh Cache',
			'callback' => [ $this->cache, 'refresh_cache' ],
			'display' => [ $this->cache, 'cache_exists' ],
		] );

		$this->toolbar->add_node( [
			'id' => 'delete-cache',
			'title' => 'Delete Cache',
			'callback' => [ $this->cache, 'delete_cache' ],
			'display' => [ $this->cache, 'cache_exists' ],
		] );

		$this->toolbar->add_node( [
			'id' => 'generate-cache',
			'title' => 'Generate Cache',
			'callback' => [ $this->cache, 'generate_cache' ],
			'display' => [ $this->cache, 'not_cache_exists' ],
		] );

		$this->toolbar->add_node( [
			'id' => 'delete-full-cache',
			'title' => 'Delete All Cache Files',
			'callback' => 'delete_all_callback', // not real... see below.
			'display' => '__return_false', // until I decide if I really want this option.
		] );

		do_action( __NAMESPACE__ . '\\add_nodes', $this->toolbar );
	}
}
