<?php

namespace SSNepenthe\CacheManager;

use \DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Cache {
	protected $cache_dir;
	protected $cache_path;
	protected $cache_file_age;

	public function __construct() {
		$this->cache_dir = '/var/cache/nginx';
		$this->cache_path = $this->calculate_cache_path();
		$this->cache_file_age = $this->calculate_cache_file_age();
	}

	public function cache_file_exists() {
		return file_exists( $this->cache_path );
	}

	public function get_cache_file_age() {
		return $this->cache_file_age;
	}

	protected function calculate_cache_path() {
		$scheme = is_ssl() ? 'https' : 'http';
		$method = 'GET';
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];

		$md5 = md5( $scheme . $method . $host . $uri );
		$level_one = substr( $md5, -1 );
		$level_two = substr( $md5, -3, 2 );

		return sprintf(
			'%1$s/%2$s/%3$s/%4$s',
			$this->cache_dir,
			$level_one,
			$level_two,
			$md5
		);
	}

	protected function calculate_cache_file_age() {
		if ( ! $this->cache_file_exists() ) {
			return '';
		}

		$mtime = new DateTime( '@' . filemtime( $this->cache_path ) );
		$now = new DateTime( 'now' );

		$age = $now->diff( $mtime );

		return $age->format( $this->get_date_time_format( $age ) );
	}

	public function cache_timestamp() {
		$time = current_time( 'Y-m-d H:i:s', true );

		echo sprintf(
			'<!-- Page generated on %1$s UTC. -->',
			esc_html( $time )
		);

		echo "\n";
	}

	protected function get_date_time_format( $date_time ) {
		$r = [];

		if ( $date_time->d || $date_time->m || $date_time->y ) {
			$r[] = '%a days';
		}

		if ( $date_time->h ) {
			$r[] = '%h hours';
		}

		if ( $date_time->i ) {
			$r[] = '%i minutes';
		}

		if ( $date_time->s ) {
			$r[] = '%s seconds';
		}

		return implode( ', ', $r );
	}
}
