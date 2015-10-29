<?php

namespace SSNepenthe\CacheManager;

function output_cache_timestamp() {
	$time = current_time( 'Y-m-d H:i:s', true );

	echo sprintf(
		'<!-- Page generated on %1$s UTC. -->',
		esc_html( $time )
	);

	echo "\n";
}
add_action( 'wp_head', __NAMESPACE__ . '\\output_cache_timestamp', 0 );

// Will need multiple variations of this function...
function create_date_time_format( $date_time ) {
	$date = [];
	$time = [];
	$r    = 'P';

	if ( $date_time->y ) {
		$date[] = '%yY';
	}

	if ( $date_time->m ) {
		$date[] = '%mM';
	}

	if ( $date_time->d ) {
		$date[] = '%dD';
	}

	if ( ! empty( $date ) ) {
		$r .= implode( '', $date );
	}

	if ( $date_time->h ) {
		$time[] = '%hH';
	}

	if ( $date_time->i ) {
		$time[] = '%iM';
	}

	if ( $date_time->s ) {
		$time[] = '%sS';
	}

	if ( ! empty( $time ) ) {
		$r .= 'T' . implode( '', $time );
	}

	return $r;
}
