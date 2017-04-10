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

function _cm_require_if_exists( $file ) {
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

_cm_require_if_exists( __DIR__ . '/vendor/autoload.php' );

$cm_checker = new WP_Requirements\Plugin_Checker( 'Cache Manager', __FILE__ );
$cm_checker->php_at_least( '5.4' );

if ( $cm_checker->requirements_met() ) {
	add_action( 'init', 'cache_manager_init', 1 );
	add_action( 'wp_head', 'cache_manager_timestamp', 0 );
} else {
	$cm_checker->deactivate_and_notify();
}

/**
 * Initializes the plugin on the init hook.
 */
function cache_manager_init() {
	if ( ! is_admin() && ! is_admin_bar_showing() ) {
		return;
	}

	$cm_plugin = new SSNepenthe\Cache_Manager\Cache_Manager;

	add_action( 'init', array( $cm_plugin, 'init' ), 99 );
	add_action(
		'transition_post_status',
		array( $cm_plugin, 'transition_post_status' )
	);
}

/**
 * Outputs a cache timestamp in wp_head.
 */
function cache_manager_timestamp() {
	include_once plugin_dir_path( __FILE__ ) . 'partials/timestamp.php';
}
