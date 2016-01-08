# cache-manager
WordPress mu-plugin for managing the Nginx fastcgi cache. Easily extended to manage other caches.

## Loading the plugin
This is a WordPress mu-plugin. By default, the `composer/installers` package will install it to `wp-content/mu-plugins/cache-manager`. Unfortunately, since the main plugin file is in a subdirectory of the mu-plugin directory, it will not be loaded automatically.

One option is to create a proxy plugin file which `require`s `wp-content/mu-plugins/cache-manager/cache-manager.php`.

Alternatively, run `composer require ssnepenthe/horme` and follow the instructions found in that packages readme file to autoload composer-managed mu-plugins.

## Usage
This plugin assumes that you are running Nginx with fastcgi caching, but not the ngx_cache_purge module.

It uses the `X-Nginx-Cache-Purge` header to notify the server to regenerate an individual cache item. For an example server configuration see [How I built “Have Baby. Need Stuff!”](https://markjaquith.wordpress.com/2012/05/15/how-i-built-have-baby-need-stuff/) or the Nginx templates under `provision/templates/nginx` in the [wp-chaos repo](https://github.com/ssnepenthe/wp-chaos/tree/master/provision/templates/nginx).

It also assumes that you have configured `fastcgi_cache_path` to `/var/cache/nginx` (although this is filterable at `SSNepenthe\CacheManager\cache_dir`) with `levels=1:2`.

If these conditions are met, you can use the new `cache` menu item (when logged in as an admin or super admin) to create, delete or regenerate individual cache items or to flush the entire cache.

## Todo
Add info about customizing the plugin when conditions are not met.
