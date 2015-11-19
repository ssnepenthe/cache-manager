<?php
/**
 * A WordPress mu-plugin to help manage various full-page caches.
 *
 * @package cache-manager
 */

/**
 * Plugin Name: Cache Manager
 * Plugin URI: https://github.com/ssnepenthe/cache-manager
 * Description: This plugin provides tools to manage various full-page caches.
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

	$classes = [ 'cache-manager-icon' ];

	if ( $instance = $manager->current_instance() ) {
		if ( $instance->exists() ) {
			$classes[] = 'exists';
		} else {
			$classes[] = 'does-not-exist';
		}
	}

	$manager->add_toolbar_nodes( [
		[
			'id'         => 'ssn-cache-manager',
			'title'      => sprintf(
				'Cache<div class="%1$s"></div>',
				implode( ' ', $classes )
			),
			'display-cb' => '__return_true',
			'no-href'    => true,
		],
		[
			'id'         => 'ssn-refresh-cache',
			'title'      => 'Refresh Cache',
			'action-cb'  => [ $manager, 'refresh_callback' ],
			'display-cb' => '__return_true',
		],
		[
			'id'         => 'ssn-delete-cache',
			'title'      => 'Delete Cache',
			'action-cb'  => [ $manager, 'delete_callback' ],
			'display-cb' => '__return_true',
		],
		[
			'id'         => 'ssn-create-cache',
			'title'      => 'Create Cache',
			'action-cb'  => [ $manager, 'create_callback' ],
			'display-cb' => '__return_true',
		],
		[
			'id'         => 'ssn-flush-cache',
			'href'       => add_query_arg(
				[ 'action' => 'ssn-flush-cache' ],
				admin_url( 'index.php' )
			),
			'title'      => 'Flush Cache',
			'action-cb'  => [ $manager, 'flush_callback' ],
			'display-cb' => '__return_true',
		],
	] );

	// Allow user to register new cache handlers and change default.
	do_action( __NAMESPACE__ . '\\init', $manager );

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
	$time = current_time( 'Y-m-d H:i:s', true );

	echo sprintf(
		'<!-- Page generated on %1$s UTC. -->',
		esc_html( $time )
	);

	echo "\n";
}
add_action( 'wp_head', 'cache_manager_timestamp', 0 );
