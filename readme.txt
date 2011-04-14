=== Plugin Name ===
Contributors: jeffstieler, filosofo
Tags: json rpc
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 0.9.1

This plugin provides a JSON version of the XML-RPC WordPress API.

== Description ==
This plugin provides a JSON version of the XML-RPC WordPress API.

This is a slightly modified version of [filosofo](http://profiles.wordpress.org/users/filosofo/)'s [WP JSON RPC API Plugin](http://wordpress.org/extend/plugins/wp-json-rpc-api/)

I removed the javascript includes, and stripped down the JSON RPC server to simply wrap WordPress' own XML RPC server.

== Installation ==

1. Upload  the `wp-json-rpc` directory to your `/wp-content/plugins/` directory, or include `wp-json-rpc/wp-json-rpc-api.php` in one of your own plugins.
1. Activate it through the 'Plugins' menu in WordPress
