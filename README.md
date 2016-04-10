# cache-manager
WordPress mu-plugin for managing the Nginx fastcgi cache. Easily extended to manage other caches.

## Usage
This plugin assumes that you are running Nginx with fastcgi caching, but not the ngx_cache_purge module.

It uses the `X-Nginx-Cache-Purge` header to notify the server to regenerate an individual cache item. For an example server configuration see [How I built “Have Baby. Need Stuff!”](https://markjaquith.wordpress.com/2012/05/15/how-i-built-have-baby-need-stuff/).
