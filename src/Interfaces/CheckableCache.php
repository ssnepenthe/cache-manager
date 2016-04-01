<?php

namespace SSNepenthe\CacheManager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface CheckableCache {
	public function exists( string $url );
	public function writable( string $url );
}
