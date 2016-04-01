<?php

namespace SSNepenthe\CacheManager;

use SSNepenthe\CacheManager\Cache;
use SSNepenthe\CacheManager\Interfaces\CheckableCache;
use SSNepenthe\CacheManager\Interfaces\CreatableCache;
use SSNepenthe\CacheManager\Interfaces\DeletableCache;
use SSNepenthe\CacheManager\Interfaces\FlushableCache;
use SSNepenthe\CacheManager\Interfaces\RefreshableCache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * @todo Exists method?
 */
class MultiCache {
	protected $has = [
		'checkable' => false,
		'creatable' => false,
		'deletable' => false,
		'flushable' => false,
		'refreshable' => false,
	];
	protected $providers = [];

	public function add_provider( $provider ) {
		if ( ! in_array( $provider, $this->providers ) ) {
			$this->providers[] = $provider;

			if ( $provider instanceof CheckableCache ) {
				$this->has['checkable'] = true;
			}

			if ( $provider instanceof CreatableCache ) {
				$this->has['creatable'] = true;
			}

			if ( $provider instanceof DeletableCache ) {
				$this->has['deletable'] = true;
			}

			if ( $provider instanceof FlushableCache ) {
				$this->has['flushable'] = true;
			}

			if ( $provider instanceof RefreshableCache ) {
				$this->has['refreshable'] = true;
			}

			return true;
		}

		return false;
	}

	public function create( string $url ) {
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
	public function delete( string $url ) {
		$success = false;

		if ( ! $this->has_deletable() ) {
			return $success;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof DeletableCache ) {
				$success = $provider->delete( $url ) || $success;
			}
		}

		return $success;
	}

	public function exists( string $url ) {
		$exists = false;

		if ( ! $this->has_checkable() ) {
			return $exists;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof CheckableCache ) {
				$exists = $provider->exists( $url ) || $exists;
			}
		}

		return $exists;
	}

	public function flush() {
		$success = false;

		if ( ! $this->has_flushable() ) {
			return $success;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof FlushableCache ) {
				$success = $provider->flush() || $success;
			}
		}

		return $success;
	}

	public function has_checkable() {
		return $this->has['checkable'];
	}

	public function has_creatable() {
		return $this->has['creatable'];
	}

	public function has_deletable() {
		return $this->has['deletable'];
	}

	public function has_flushable() {
		return $this->has['flushable'];
	}

	public function has_refreshable() {
		return $this->has['refreshable'];
	}

	public function refresh( string $url ) {
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

	public function writable( string $url ) {
		$writable = false;

		if ( ! $this->has_checkable() ) {
			return $writable;
		}

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof CheckableCache ) {
				$writable = $provider->writable( $url ) || $writable;
			}
		}

		return $writable;
	}
}
