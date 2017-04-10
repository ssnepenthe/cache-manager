<?php
/**
 * @package cache-manager
 */

namespace SSNepenthe\Cache_Manager;

use SSNepenthe\Cache_Manager\Toolbar;
use SSNepenthe\Cache_Manager\Multi_Cache;
use SSNepenthe\Cache_Manager\Nginx\Fast_CGI_HTTP;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Cache_Manager {
	protected $multi_cache;
	protected $path = null;

	public function __construct() {
		$this->multi_cache = new Multi_Cache;
	}

	/**
	 * @hook
	 *
	 * @priority 99
	 *
	 * @todo FastCGIFileSystem will throw an exception if the provided dir is
	 *       not writable. Instead of failing silently, add a dismissable admin
	 *       notice notifying the user that the fs cache is not active.
	 */
	public function init() {
		$this->multi_cache->add_provider( new Fast_CGI_HTTP );

		$path = $this->get_path();
		$icon = '';

		$toolbar = new Toolbar;

		$toolbar->add_nodes( [
			[
				'id' => 'cm-cache-menu',
				'display_cb' => [ $this, 'parent_display' ],
				'no_href' => true,
				'title' => sprintf( 'Cache%s', $icon ),
			],
			[
				'action_cb' => [ $this, 'create_action' ],
				'display_cb' => [ $this, 'create_display' ],
				'id' => 'cm-create-cache',
				'query_args' => [ 'path' => $path ],
				'title' => 'Create Cache',
			],
			[
				'action_cb' => [ $this, 'refresh_action' ],
				'display_cb' => [ $this, 'refresh_display' ],
				'id' => 'cm-refresh-cache',
				'query_args' => [ 'path' => $path ],
				'title' => 'Refresh Cache',
			],
		] );

		add_action( 'admin_bar_menu', [ $toolbar, 'admin_bar_menu' ], 999 );
		add_action( 'admin_init', [ $toolbar, 'admin_init' ], 999 );
	}

	public function get_path() {
		if ( ! is_null( $this->path ) ) {
			return $this->path;
		}

		if ( is_admin() ) {
			$this->path = $this->get_path_from_admin();

			return $this->path;
		}

		/**
		 * Don't use filter_input( INPUT_SERVER, ... ) due to fcgi bug.
		 * @link https://secure.php.net/manual/en/function.filter-input.php#77307
		 */
		$path = $_SERVER['REQUEST_URI'];
		$this->path = filter_var( $path, FILTER_SANITIZE_URL );

		return $this->path;
	}

	public function get_path_from_admin() {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return false;
		}

		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		$post = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $action || ! $post ) {
			return false;
		}

		if ( 'edit' !== $action ) {
			return false;
		}

		$post_object = get_post( absint( $post ) );

		if ( ! get_post_type_object( $post_object->post_type )->public ) {
			return false;
		}

		$path = parse_url( get_permalink( $post_object ), PHP_URL_PATH );
		$path = is_null( $path ) ? '/' : $path;

		return $path;
	}

	/**
	 * @hook
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $old_status && 'private' !== $old_status ) {
			return;
		}

		if ( ! $this->multi_cache->has_refreshable() ) {
			return;
		}

		$url = get_permalink( $post );

		$this->multi_cache->refresh( $url );
	}

	public function create_action() {
		if ( ! $this->multi_cache->has_creatable() ) {
			return;
		}

		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( ! $path ) {
			return;
		}

		$this->multi_cache->create( home_url( $path ) );
	}

	public function create_display() {
		if ( ! $this->multi_cache->has_creatable() || ! $this->get_path() ) {
			return false;
		}

		return true;
	}

	public function parent_display() {
		return (
			$this->create_display() ||
			$this->delete_display() ||
			$this->flush_display() ||
			$this->refresh_display()
		);
	}

	public function refresh_action() {
		if ( ! $this->multi_cache->has_refreshable() ) {
			return;
		}

		$path = filter_input( INPUT_GET, 'path', FILTER_SANITIZE_URL );

		if ( ! $path ) {
			return;
		}

		$this->multi_cache->refresh( home_url( $path ) );
	}

	public function refresh_display() {
		if ( ! $this->multi_cache->has_refreshable() || ! $this->get_path() ) {
			return false;
		}

		return true;
	}
}
