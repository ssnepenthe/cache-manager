<?php
/**
 * @package cache-manager
 */

namespace SSNepenthe\CacheManager\Nginx;

use SSNepenthe\CacheManager\Interfaces\CheckableCache;
use SSNepenthe\CacheManager\Interfaces\DeletableCache;
use SSNepenthe\CacheManager\Interfaces\FlushableCache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class FastCGIFileSystem implements CheckableCache, DeletableCache, FlushableCache {
	const METHOD = 'GET';

	protected $data = [
		'url' => [],
		'path' => [],
	];
	protected $directory;

	public function __construct( string $directory ) {
		// To suppress warnings about open_basdir restrictions.
		$directory = @realpath( $directory );

		if ( ! is_writable( $directory ) ) {
			throw new \RuntimeException( sprintf(
				'The %s directory is not writable.',
				$directory
			) );
		}

		$this->directory = $directory;
	}

	public function delete( string $url ) {
		$success = false;

		if ( $this->exists( $url ) && $this->writable( $url ) ) {
			$success = unlink( $this->cache_file_path( $url ) );
		}

		return $success;
	}

	public function exists( string $url ) {
		return file_exists( $this->cache_file_path( $url ) );
	}

	public function flush() {
		$success = false;
		$cache_files = glob(
			sprintf( '%s/**/**/*', $this->directory ),
			GLOB_NOSORT
		);

		foreach ( $cache_files as $file ) {
			if ( is_writable( $file ) ) {
				$success = unlink( $file ) || $success;
			}
		}

		return $success;
	}

	public function writable( string $url ) {
		return is_writable( $this->cache_file_path( $url ) );
	}

	protected function cache_file_path( string $url ) {
		if ( isset( $this->data['path'][ $url ] ) ) {
			return $this->data['path'][ $url ];
		}

		$parsed = $this->parse_url( $url );

		$key = sprintf( '%s%s%s%s',
			$parsed['scheme'],
			self::METHOD,
			$parsed['host'],
			$parsed['path']
		);

		$md5 = md5( $key );

		// We should be able to set number of levels to match nginx config.
		$level_one = substr( $md5, -1 );
		$level_two = substr( $md5, -3, 2 );

		$this->data['path'][ $url ] = sprintf(
			'%s/%s/%s/%s',
			$this->directory,
			$level_one,
			$level_two,
			$md5
		);

		return $this->data['path'][ $url ];
	}

	protected function parse_url( string $url ) {
		if ( isset( $this->data['url'][ $url ] ) ) {
			return $this->data['url'][ $url ];
		}

		$parsed = parse_url( $url );

		if ( ! isset( $parsed['scheme'] ) || ! isset( $parsed['host'] ) ) {
			throw new \RuntimeException( 'Malformed URL provided.' );
		}

		if ( ! isset( $parsed['path'] ) ) {
			$parsed['path'] = '/';
		}

		$this->data['url'][ $url ] = $parsed;

		return $this->data['url'][ $url ];
	}
}
