<?php

namespace SSNepenthe\CacheManager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface RefreshableCache {
	public function refresh( string $url );
}
