<?php

namespace Cache_Manager;

use WP_Admin_Bar;

function admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! should_display_menu() ) {
		debug_warning( 'Menu should not be displayed on this page' );

		return;
	}

	$wp_admin_bar->add_node( [
		'id' => 'cm-cache',
		'title' => 'Cache',
	] );

	$wp_admin_bar->add_node( [
		'href' => wp_nonce_url(
			add_query_arg(
				'path',
				urlencode( get_current_cache_path() ),
				admin_url( 'index.php?action=cm-create' )
			),
			'cache_manager_create'
		),
		'id' => 'cm-create',
		'parent' => 'cm-cache',
		'title' => 'Create',
	] );

	$wp_admin_bar->add_node( [
		'href' => wp_nonce_url(
			add_query_arg(
				'path',
				urlencode( get_current_cache_path() ),
				admin_url( 'index.php?action=cm-refresh' )
			),
			'cache_manager_refresh'
		),
		'id' => 'cm-refresh',
		'parent' => 'cm-cache',
		'title' => 'Refresh',
	] );
}

function create_handler() {
	$path = get_current_cache_path();

	if ( ! $path ) {
		debug_warning( 'No path set in create handler' );

		return;
	}

	$args = [
		'blocking' => false,
		'sslverify' => apply_filters( 'cache_manager_sslverify', true ),
		'timeout'  => 1, // WP_Http won't go any lower.
	];

	wp_safe_remote_get( $path, $args );
}

function get_current_cache_path() {
	// @todo Automatically return false on non frontend pages like wp-api, wp-login?
	$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

	if ( $path ) {
		return $path;
	}

	if ( is_admin() ) {
		return get_current_cache_path_from_admin();
	}

	// https://secure.php.net/manual/en/function.filter-input.php#77307
	$request_uri = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );

	return home_url( $request_uri );
}

function get_current_cache_path_from_admin() {
	global $pagenow;

	if ( ! is_admin() || 'post.php' !== $pagenow ) {
		return false;
	}

	$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
	$post = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

	if ( ! $action || 'edit' !== $action || ! $post ) {
		return false;
	}

	$post_object = get_post( absint( $post ) );

	if ( ! get_post_type_object( $post_object->post_type )->public ) {
		return false;
	}

	return get_permalink( $post_object );
}

function handle_cache_manager_action() {
	global $pagenow;

	if ( ! is_admin() ) {
		// This is only hooked to admin init, but just to be safe...
		return;
	}

	if ( 'index.php' !== $pagenow || ! should_display_menu() ) {
		debug_warning( 'Actions should not be handled on this page' );

		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		debug_warning( 'Current user is not allowed to trigger cache handlers' );

		return;
	}

	$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

	if ( ! $action || ! in_array( $action, [ 'cm-create', 'cm-refresh' ], true ) ) {
		debug_warning( 'Invalid or no action provided' );

		return;
	}

	$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
	$id = str_replace( 'cm-', '', $action );
	$name = "cache_manager_{$id}";

	if ( ! $nonce || 1 !== wp_verify_nonce( $nonce, $name ) ) {
		debug_warning( 'Invalid or no nonce provided' );

		return;
	}

	$handler = __NAMESPACE__ . "\\{$id}_handler";

	$handler();

	wp_safe_redirect( wp_get_referer() );
	die;
}

function initialize() {
	add_action( 'admin_init', __NAMESPACE__ . '\\handle_cache_manager_action' );
	add_action( 'wp_head', __NAMESPACE__ . '\\print_timestamp', 0 );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\admin_bar_menu', 99 );
}

function print_timestamp() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/timestamp.php';
}

function refresh_handler() {
	$path = get_current_cache_path();

	if ( ! $path ) {
		debug_warning( 'No path set in refresh handler' );

		return;
	}

	$args = [
		'blocking' => false,
		'headers'  => (array) apply_filters(
			'cache_manager_refresh_headers',
			[ 'X-Nginx-Cache-Purge' => '1' ]
		),
		'sslverify' => apply_filters( 'cache_manager_sslverify', true ),
		'timeout'  => 1, // WP_Http won't go any lower.
	];

	wp_safe_remote_get( $path, $args );
}

function should_display_menu() {
	return current_user_can( 'manage_options' ) && (bool) get_current_cache_path();
}
