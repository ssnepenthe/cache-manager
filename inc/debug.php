<?php

function debug_enabled() {
	return defined( 'CACHE_MANAGER_DEBUG' )
		&& CACHE_MANAGER_DEBUG
		&& defined( 'WP_DEBUG' )
		&& WP_DEBUG;
}

function debug_notice( $message, $severity = 'info' ) {
	if ( ! debug_enabled() ) {
		return;
	}

	if ( ! in_array( $severity, [ 'error', 'warning', 'success', 'info' ], true ) ) {
		$severity = 'info';
	}

	add_action( 'admin_notices', function() use ( $severity, $message ) {
		$template = [
			sprintf(
				'<div class="notice notice-%s is-dismissible">',
				esc_attr( $severity )
			),
			sprintf( '<p>Cache Manager: %s</p>', esc_html( $message ) ),
			'</div>',
		];

		echo implode( '', $template );
	} );
}

function debug_warning( $message ) {
	debug_notice( $message, 'warning' );
}
