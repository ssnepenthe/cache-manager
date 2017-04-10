<?php
/**
 * A WordPress mu-plugin for managing Nginx fastcgi cache.
 *
 * @package cache-manager
 */

/**
 * Plugin Name: Cache Manager
 * Plugin URI: https://github.com/ssnepenthe/cache-manager
 * Description: A WordPress mu-plugin for managing Nginx fastcgi cache.
 * Version: 0.2.3
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

$autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

unset( $autoload );

/**
 * Initializes the plugin on the init hook.
 */
function cache_manager_init() {
	if ( ! is_admin() && ! is_admin_bar_showing() ) {
		return;
	}

	$cm_plugin = new SSNepenthe\CacheManager\CacheManager;

	add_action( 'init', [ $cm_plugin, 'init' ], 99 );
	add_action( 'admin_enqueue_scripts', [ $cm_plugin, 'toolbar_styles' ] );
	add_action( 'wp_enqueue_scripts', [ $cm_plugin, 'toolbar_styles' ] );
	add_action( 'transition_post_status', [ $cm_plugin, 'transition_post_status' ] );
}
add_action( 'init', 'cache_manager_init', 1 );

/**
 * Outputs a cache timestamp in wp_head.
 */
function cache_manager_timestamp() {
	include_once plugin_dir_path( __FILE__ ) . 'partials/timestamp.php';
}
add_action( 'wp_head', 'cache_manager_timestamp', 0 );
