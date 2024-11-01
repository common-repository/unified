<?php

namespace Unified\Modules\Caching;

use Unified\Utilities\CommonFunctions;
use Unified\Utilities\Configuration;
use Unified\Utilities\OutputModification;

/**
 * Page cache - Only loaded when not in admin
 * @since 1.0
 */
class PageCache
{
    // Caches
    private $cleared_pages = [];
    private $cache_fully_cleared = false;

    /**
     *  Initialize page cache
     *  @since 1.0
     */
    public function init()
    {
        // Only do cache when user is not logged in, to prevent caching pages with user specific stuff
        $common_functions = CommonFunctions::getInstance();
        if (!$common_functions->isUserLoggedIn()) {
            // If not in cache, handle output buffering, to get a cached version
            $this->handleOutputBuffering();
        }
    }

    /**
     *  Check if current request is cached already - called by MU plugin
     *  @since 1.0
     */
    public function checkCache()
    {
        $common_functions = CommonFunctions::getInstance();
        if (defined('WP_CLI') || php_sapi_name() == 'cli') {
            return false;
        }
        if ($common_functions->isUserLoggedIn()) {
            return false;
        }
        if (!$this->checkHTTPMethod()) {
            return false;
        }

        $configuration = Configuration::getInstance();
        $cache_store = CacheStore::getInstance();
        $cached_content = $cache_store->get($this->getCurrentUrl());

        if ($cached_content !== false) {
            // Extract headers
            $parts = explode('<!--endheader-->', $cached_content, 2);
            $headers = json_decode($parts[0]);

            if (!\headers_sent()) {
                if ($configuration->get('security_header_x_powered_by')) {
                    header_remove('x-powered-by');
                }
            }

            // Set the headers
            foreach ($headers as $header) {
                header($header);
            }
            // Set the cache-control header, to prevent client to cache our cache
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('X-Unified-Cache: HIT');

            echo $parts[1] ?? '';
            exit;
        }
    }

    /**
     *  Handle output buffering the page
     *  @since 1.0
     */
    public function handleOutputBuffering()
    {
        ob_start();
        add_action('shutdown', [$this, 'outputBufferingShutdown'], 0);
    }

    /**
     *  Check for exceptions - Test if this page should not be cached
     *  @since 1.0
     */
    public function shouldCurrentPageBeCached()
    {
        $request_url = esc_url_raw($_SERVER['REQUEST_URI']);

        // DONOTCACHEPAGE constant
        if (defined('DONOTCACHEPAGE')) {
            return false;
        }
        // WP REST
        if (strpos($request_url, 'wp-json') !== false) {
            return false;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        // XML RPC
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return false;
        }
        // WP CLI
        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }
        // PHP commandline
        if (PHP_SAPI == 'cli') {
            return false;
        }
        // Remove preview pages
        if (isset($_REQUEST['preview'])) {
            return false;
        }
        // Not in maintenance mode
        if (\wp_is_maintenance_mode()) {
            return false;
        }
        // Check HTTP status
        if (!$this->checkHTTPStatus()) {
            return false;
        }
        // Check HTTP method
        if (!$this->checkHTTPMethod()) {
            return false;
        }
        // WooCommerce exceptions
        if (function_exists('is_cart')) {
            if (\is_cart()) {
                return false;
            }
        }
        if (function_exists('is_checkout')) {
            if (\is_checkout()) {
                return false;
            }
        }
        if (function_exists('is_account_page')) {
            if (\is_account_page()) {
                return false;
            }
        }
        if (strpos($request_url, 'wp-login.php') !== false) {
            return false;
        }

        return true;
    }

    /**
     *  Check http status, as we only cache some http statuses
     *  @since 1.0
     */
    public function checkHTTPStatus(): bool
    {
        $response_code = \http_response_code();
        $cacheable = [200, 203, 204, 206, 300, 301, 404, 405, 410, 414, 501];
        if (in_array($response_code, $cacheable)) {
            return true;
        }
        return false;
    }

    /**
     *  Check http method, as we only cache some
     *  @since 1.0
     */
    public function checkHTTPMethod(): bool
    {
        $request_method = strtolower(sanitize_text_field($_SERVER['REQUEST_METHOD']));
        $cacheable = ['get', 'head'];
        if (in_array($request_method, $cacheable)) {
            return true;
        }
        return false;
    }

    /**
     *  Handle output buffer shutdown
     *  @since 1.0
     */
    public function outputBufferingShutdown()
    {
        $output = '';

        $levels = ob_get_level();
        for ($i = 0; $i < $levels; $i++) {
            $output .= ob_get_clean();
        }

        if ($this->shouldCurrentPageBeCached()) {
            // Get any relevant output modification to headers or content
            $output_modification = OutputModification::getInstance();
            $search_replaces = $output_modification->getContentSearchReplace();

            // Do any relevant search replaces
            foreach ($search_replaces as $search => $replace) {
                $output = str_replace($search, $replace, $output);
            }

            // Get the current headers
            $headers = $this->getHeaders();
            $headers_json = json_encode($headers);

            // Save output to cache store
            $cache_store = CacheStore::getInstance();
            $cache_store->save($this->getCurrentUrl(), $headers_json . '<!--endheader-->' . $output);
        }

        // Output it for the current request
        echo $output;
    }

    /**
     *  Get headers to cache before writing cache
     *  @since 1.0
     */
    public function getHeaders()
    {
        flush();
        $headers = headers_list();
        $http = $this->getHTTPProtocol() . ' ' . \http_response_code();
        \array_unshift($headers, $http);
        return $headers;
    }

    /**
     * Get http protocol to add to headers
     */
    public function getHTTPProtocol()
    {
        $http_protocol = '';
        if (!empty($_SERVER['SERVER_PROTOCOL'])) {
            $server_http_protocol = sanitize_text_field($_SERVER['SERVER_PROTOCOL']);
            if (is_string($server_http_protocol)) {
                $http_protocol = strtoupper($server_http_protocol);
            }
        }
        if (!$http_protocol || stripos($http_protocol, 'HTTP/') !== 0) {
            $http_protocol = 'HTTP/1.1';
        }
        return $http_protocol;
    }

    /**
     *  Handle output buffer shutdown
     *  @since 1.0
     */
    public function getCurrentUrl()
    {
        $current_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return sanitize_url($current_url);
    }

    /**
     *  Clear page cache
     *  @since 1.0
     */
    public function clearCache()
    {
        if ($this->cache_fully_cleared) {
            return;
        }
        $cache_store = CacheStore::getInstance();
        $cache_store->clean();
        $this->cache_fully_cleared = true;
    }

    /**
     *  Handle updated post
     *  @since 1.0
     */
    public function clearPostCache(int $post_id)
    {
        // Make sure we only run it once per instance
        if (isset($this->cleared_pages[$post_id])) {
            return;
        }

        $link = get_permalink($post_id);
        $cache_store = CacheStore::getInstance();
        $cache_store->clean($link);

        $this->cleared_pages[$post_id] = 1;
    }

    /**
     *  Handle updated comment
     *  @since 1.0
     */
    public function clearPostCacheForComment(int $comment_id)
    {
        $comment = get_comment($comment_id);
        if (is_object($comment) && isset($comment->comment_post_ID)) {
            $this->clearPostCache($comment->comment_post_ID);
        }
    }

    /**
     *  Handle updated comment transition
     *  @since 1.0
     */
    public function clearPostCacheForCommentTransition($new_status, $old_status, $comment)
    {
        if (is_object($comment) && is_int($comment->comment_post_ID)) {
            $this->clearPostCache($comment->comment_post_ID);
        }
    }
}
