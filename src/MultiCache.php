<?php

namespace SSNepenthe\CacheManager;

use SSNepenthe\CacheManager\Cache;
use SSNepenthe\CacheManager\Interfaces\CreatableCache;
use SSNepenthe\CacheManager\Interfaces\RefreshableCache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * @todo Exists method?
 */
class MultiCache {
	protected $has = [
		'creatable' => false,
		'refreshable' => false,
	];
	protected $providers = [];

	public function add_provider( $provider ) {
		if ( ! in_array( $provider, $this->providers ) ) {
			$this->providers[] = $provider;

			if ( $provider instanceof CreatableCache ) {
				$this->has['creatable'] = true;
			}

			if ( $provider instanceof RefreshableCache ) {
				$this->has['refreshable'] = true;
			}

			return true;
		}

		return false;
	}

	public function create( $url ) {
		if ( ! is_string( $url ) ) {
			throw new \InvalidArgumentException( sprintf(
				'The url parameter is required to be string, was: %s',
				gettype( $url )
			) );
		}

		$success = false;

		if ( ! $this->has_creatable() ) {
			return $success;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof CreatableCache ) {
				$success = $provider->create( $url ) || $success;
			}
		}

		return $success;
	}

	public function has_creatable() {
		return $this->has['creatable'];
	}

	public function has_refreshable() {
		return $this->has['refreshable'];
	}

	public function refresh( $url ) {
		if ( ! is_string( $url ) ) {
			throw new \InvalidArgumentException( sprintf(
				'The url parameter is required to be string, was: %s',
				gettype( $url )
			) );
		}

		$success = false;

		if ( ! $this->has_refreshable() ) {
			return $success;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof RefreshableCache ) {
				$success = $provider->refresh( $url ) || $success;
			}
		}

		return $success;
	}
}
