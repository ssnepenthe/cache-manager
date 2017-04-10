<?php

namespace SSNepenthe\Cache_Manager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface Refreshable_Cache {
	public function refresh( $url );
}
