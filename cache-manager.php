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

/**
 * Require a given file once if it exists.
 *
 * @param  string $file File to check existence of and require.
 *
 * @return void
 */
function _cm_require_if_exists( $file ) {
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

$cm_dir = plugin_dir_path( __FILE__ );

_cm_require_if_exists( $cm_dir . 'vendor/autoload.php' );

$cm_checker = new WP_Requirements\Plugin_Checker( 'Cache Manager', __FILE__ );
$cm_checker->php_at_least( '5.4' );

if ( $cm_checker->requirements_met() ) {
	require_once $cm_dir . 'inc/debug.php';
	require_once $cm_dir . 'inc/functions.php';

	add_action( 'plugins_loaded', 'Cache_Manager\\initialize' );
} else {
	$cm_checker->deactivate_and_notify();
}

unset( $cm_checker, $cm_dir );
