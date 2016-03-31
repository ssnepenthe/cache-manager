<?php
/**
 * A WordPress mu-plugin to help manage various full-page caches.
 *
 * @package cache-manager
 */

/**
 * Plugin Name: Cache Manager
 * Plugin URI: https://github.com/ssnepenthe/cache-manager
 * Description: WordPress mu-plugin for managing the Nginx fastcgi cache. Easily extended to manage other caches.
 * Version: 0.1.0
 * Author: SSNepenthe
 * Author URI: https://github.com/ssnepenthe
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:
 * Domain Path:
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$plugin_root = __DIR__;

if ( file_exists( $plugin_root . '/vendor/autoload.php' ) ) {
	require_once $plugin_root . '/vendor/autoload.php';
}

/**
 * Initializes the plugin on the init hook.
 */
function cache_manager_init() {
	if ( ! is_admin() && ! is_admin_bar_showing() ) {
		return;
	}

	$manager = new \SSNepenthe\CacheManager\CacheManager( 'cache-manager', '0.1.0' );

	$manager->add_cache_class(
		'fastcgi',
		'SSNepenthe\\CacheManager\\NginxFastCGICache'
	);
	$manager->set_default_cache_class( 'fastcgi' );


	try {
		$manager->init();
	} catch ( \Exception $e ) {
		// How to handle???
	}
}
add_action( 'init', 'cache_manager_init' );

/**
 * Outputs a cache timestamp in wp_head.
 */
function cache_manager_timestamp() {
	echo sprintf(
		"<!-- Page generated on %s UTC. -->\n",
		esc_html( current_time( 'Y-m-d H:i:s', true ) )
	);
}
add_action( 'wp_head', 'cache_manager_timestamp', 0 );
