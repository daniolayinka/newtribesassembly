<?php
/** Plugin Name: NTA Live Cache Bypass
 * Keeps the Meetings screen and live-status routes dynamic behind Breeze/Varnish.
 */
$nta_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$nta_rest = isset($_GET['rest_route']) && is_string($_GET['rest_route']) ? $_GET['rest_route'] : '';
if (preg_match('~^/meetings/?$|^/wp-json/nta/v1/stream(?:/|$)~', $nta_path) || preg_match('~^/nta/v1/stream(?:/|$)~', $nta_rest)) {
    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
    add_action('init', function () {
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }, 0);
}
add_filter('breeze_filter_js_exclude', function ($scripts) { $scripts[] = 'assets/streams.js'; return $scripts; });
