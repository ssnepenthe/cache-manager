<?php
/**
 * Debugging helper functions.
 *
 * @package cache-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Determine whether debug mode is enabled.
 *
 * @return bool
 */
function debug_enabled() {
	return defined( 'CACHE_MANAGER_DEBUG' )
		&& CACHE_MANAGER_DEBUG
		&& defined( 'WP_DEBUG' )
		&& WP_DEBUG;
}

/**
 * Print a debugging notice in admin_notices if debug mode is enabled.
 *
 * @param  string $message  Message to display to the user.
 * @param  string $severity Message severity used to apply proper CSS styling.
 *
 * @return void
 */
function debug_notice( $message, $severity = 'info' ) {
	if ( ! debug_enabled() ) {
		return;
	}

	if ( ! in_array( $severity, [ 'error', 'warning', 'success', 'info' ], true ) ) {
		$severity = 'info';
	}

	add_action( 'admin_notices', function() use ( $severity, $message ) {
		printf(
			'<div class="notice notice-%s is-dismissible">',
			esc_attr( $severity )
		);

		printf( '<p>Cache Manager: %s</p>', esc_html( $message ) );

		echo '</div>';
	} );
}

/**
 * Print a debugging notice of severity "warning".
 *
 * @param  string $message Message to display to the user.
 *
 * @return void
 */
function debug_warning( $message ) {
	debug_notice( $message, 'warning' );
}
