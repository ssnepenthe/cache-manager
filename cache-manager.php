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
 * Version: 0.2.0
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
 * Initializes the plugin on the init hook. We are hooking in late to ensure
 * CPTs with custom rewrites have been registered so that get_permalink() works
 * as it is supposed to.
 */
function cache_manager_init() {
	if ( ! is_admin() && ! is_admin_bar_showing() ) {
		return;
	}

	SSNepenthe\Metis\Loader::attach( new SSNepenthe\CacheManager\CacheManager );
}
add_action( 'init', 'cache_manager_init', 99 );

/**
 * Outputs a cache timestamp in wp_head.
 */
function cache_manager_timestamp() {
	include_once plugin_dir_path( __FILE__ ) . 'partials/timestamp.php';
}
add_action( 'wp_head', 'cache_manager_timestamp', 0 );
