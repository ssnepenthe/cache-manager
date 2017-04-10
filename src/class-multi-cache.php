<?php

namespace SSNepenthe\Cache_Manager;

use SSNepenthe\Cache_Manager\Cache;
use SSNepenthe\Cache_Manager\Interfaces\Creatable_Cache;
use SSNepenthe\Cache_Manager\Interfaces\Refreshable_Cache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * @todo Exists method?
 */
class Multi_Cache {
	protected $has = [
		'creatable' => false,
		'refreshable' => false,
	];
	protected $providers = [];

	public function add_provider( $provider ) {
		if ( ! in_array( $provider, $this->providers ) ) {
			$this->providers[] = $provider;

			if ( $provider instanceof Creatable_Cache ) {
				$this->has['creatable'] = true;
			}

			if ( $provider instanceof Refreshable_Cache ) {
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
			if ( $provider instanceof Creatable_Cache ) {
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
			if ( $provider instanceof Refreshable_Cache ) {
				$success = $provider->refresh( $url ) || $success;
			}
		}

		return $success;
	}
}
