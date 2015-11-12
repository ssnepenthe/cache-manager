<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$plugin_root = __DIR__;

if ( file_exists( $plugin_root . '/vendor/autoload.php' ) ) {
	require_once $plugin_root . '/vendor/autoload.php';
}

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

function timestamp() {
	$time = current_time( 'Y-m-d H:i:s', true );

	echo sprintf(
		'<!-- Page generated on %1$s UTC. -->',
		esc_html( $time )
	);

	echo "\n";
}
add_action( 'wp_head', __NAMESPACE__ . '\\timestamp', 0 );
