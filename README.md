# cache-manager
WordPress plugin for managing the Nginx fastcgi cache.

## Requirements
PHP 5.4 or greater and Composer.

## Installation
Install using Composer:

```
$ composer require ssnepenthe/cache-manager
```

## Usage
This plugin assumes that you are running Nginx with fastcgi caching, but not the ngx_cache_purge module.

By default it uses the `X-Nginx-Cache-Purge` header (filterable at `cache_manager_refresh_headers`) to notify the server to regenerate an individual cache item. For an example server configuration see [How I built “Have Baby. Need Stuff!”](https://markjaquith.wordpress.com/2012/05/15/how-i-built-have-baby-need-stuff/).
