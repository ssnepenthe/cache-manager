<?php

namespace SSNepenthe\Cache_Manager\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

interface Creatable_Cache {
	public function create( $url );
}
