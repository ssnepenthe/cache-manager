<?php

namespace SSNepenthe\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class CacheManager {
	protected $plugin_name;
	protected $plugin_version;

	protected $default_cache_class = null;
	protected $registered_cache_classes = [];
	protected $cache_instances = [];

	public function __construct( $name, $version ) {
		$this->plugin_name = $name;
		$this->plugin_version = $version;
	}

	public function add_cache_class( $id, $class ) {
		if ( ! isset( $this->registered_cache_classes[ $id ] ) ) {
			$this->registered_cache_classes[ $id ] = $class;

			return true;
		}

		return false;
	}

	public function add_cache_classes( array $classes ) {
		foreach ( $classes as $id => $class ) {
			if ( ! is_string( $id ) ) {
				continue;
			}

			$this->add_cache_class( $id, $class );
		}
	}

	public function get_cache_class( $id ) {
		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			return $this->registered_cache_classes[ $id ];
		}

		return false;
	}

	public function get_cache_instances( $url = null ) {
		if ( is_null( $url ) ) {
			return $this->cache_instances;
		}

		$url = $this->normalize_url( $url );

		if ( isset( $this->cache_instances[ $url ] ) ) {
			return $this->cache_instances[ $url ];
		}

		return false;
	}

	public function get_default_cache_class() {
		return $this->default_cache_class;
	}

	public function init() {
		if ( is_null( $this->default_cache_class ) ) {
			throw new \Exception( 'No default cache class set.' );
		}

		if ( ! $this->get_cache_class( $this->default_cache_class ) ) {
			throw new \Exception( 'Default cache class does not exist.' );
		}

		$this->create_cache_instance( 'fake' );
	}

	public function normalize_url( $url ) {
		// Needs a lot of work...
		$parsed_url = parse_url( $url );

		if ( ! $url || ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
			throw new \Exception( 'Malformed URL.' );
		}

		if ( ! isset( $parsed_url['path'] ) ) {
			$parsed_url['path'] = '/';
		}

		return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
	}

	public function remove_cache_classes( $id ) {
		$r = false;

		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			unset( $this->registered_cache_classes[ $id ] );

			if ( $id === $this->default_cache_class ) {
				$this->default_cache_class = null;
			}

			$r = true;
		}

		return $r;
	}

	public function set_default_cache_class( $id ) {
		if ( isset( $this->registered_cache_classes[ $id ] ) ) {
			$this->default_cache_class = $id;

			return true;
		}

		return false;
	}

	protected function create_cache_instance( $url ) {
		if (
			! $this->get_cache_instances( $url ) &&
			! is_null( $this->default_cache_class ) &&
			$class = $this->get_cache_class( $this->default_cache_class )
		) {
			$url = $this->normalize_url( $url );
			$this->cache_instances[ $url ] = new $class( $url );

			return true;
		}

		return false;
	}
}
