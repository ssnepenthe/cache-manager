<?php

namespace SSNepenthe\CacheManager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface FlushableCache {
	public function flush();
}
