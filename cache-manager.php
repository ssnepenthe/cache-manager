<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$plugin_root = dirname( __FILE__ );

if ( file_exists( $plugin_root . '/vendor/autoload.php' ) ) {
	require_once $plugin_root . '/vendor/autoload.php';
}

require_once $plugin_root . '/functions.php';

function init() {
	$manager = new CacheManager( 'cache-manager', '0.1.0' );
	$manager->init();
}
add_action( 'init', __NAMESPACE__ . '\\init' );
