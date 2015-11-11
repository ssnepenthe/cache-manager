<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

abstract class FullPageCache {
	protected $url;

	public function __construct( $url ) {
		$this->url = $url;
	}

	abstract public function create();

	abstract public function delete();

	abstract public function exists();

	abstract public function flush();

	abstract public function refresh();

	public function get_url() {
		return $this->url;
	}
}
