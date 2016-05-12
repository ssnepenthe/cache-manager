<?php

namespace SSNepenthe\CacheManager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface DeletableCache {
	public function delete( $url );
}
