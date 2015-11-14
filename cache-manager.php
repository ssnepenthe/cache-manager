<?php
/**
 * A WordPress mu-plugin to help manage various full-page caches.
 *
 * @package cache-manager
 */

/**
 * Plugin Name: Cache Manager
 * Plugin URI: https://github.com/ssnepenthe/cache-manager
 * Description:
 * Version:
 * Author:
 * Author URI:
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:
 * Domain Path:
 */

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$plugin_root = __DIR__;

if ( file_exists( $plugin_root . '/vendor/autoload.php' ) ) {
	require_once $plugin_root . '/vendor/autoload.php';
}

/**
 * Initializes the plugin on the init hook.
 */
function init() {
	$manager = new CacheManager( 'cache-manager', '0.1.0' );

	$manager->add_cache_class( 'fastcgi', 'SSNepenthe\\CacheManager\\NginxFastCGICache' );
	$manager->set_default_cache_class( 'fastcgi' );

	// Allow user to register new cache handlers and change default.
	do_action( __NAMESPACE__ . '\\init', $manager );

	try {
		$manager->init();
	} catch ( \Exception $e ) {
		// Should handle this better.
		echo $e->getMessage();
	}
}
add_action( 'init', __NAMESPACE__ . '\\init' );

/**
 * Outputs a cache timestamp in wp_head.
 */
function timestamp() {
	$time = current_time( 'Y-m-d H:i:s', true );

	echo sprintf(
		'<!-- Page generated on %1$s UTC. -->',
		esc_html( $time )
	);

	echo "\n";
}
add_action( 'wp_head', __NAMESPACE__ . '\\timestamp', 0 );
