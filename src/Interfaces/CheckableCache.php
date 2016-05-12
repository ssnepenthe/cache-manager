<?php

namespace SSNepenthe\CacheManager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface CheckableCache {
	public function exists( $url );
	public function writable( $url );
}
