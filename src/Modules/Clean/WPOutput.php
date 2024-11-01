<?php

namespace Unified\Modules\Clean;

/**
 * WP output cleaning
 * @since 1.0
 */
class WPOutput
{
    /**
     *  Disable base WP
     */
    public function disableBaseOutput()
    {
        // REST links output
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('template_redirect', 'wp_shortlink_header', 11);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // Remove stuff from header
        remove_action('wp_head', 'feed_links_extra', 3); // Display the links to the extra feeds such as category feeds
        remove_action('wp_head', 'feed_links', 2); // Display the links to the general feeds: Post and Comment Feed
        remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint
        remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
        remove_action('wp_head', 'index_rel_link'); // index link
        remove_action('wp_head', 'parent_post_rel_link', 10, 0); // prev link
        remove_action('wp_head', 'start_post_rel_link', 10, 0); // start link
        remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts
        remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook
        remove_action('wp_head', 'wp_shortlink_wp_head');

        // DNS Prefetch
        remove_action('wp_head', 'wp_resource_hints', 2);
    }

    /**
     *  Disable emoji
     */
    public function disableEmojiOutput()
    {
        // Remove Wp Emoji script
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }

    /**
     *  Disable feeds on all pages
     */
    public function disableFeeds()
    {
        // Remove feeds and just show HTTP 404 on these
        $feed_die_function = function () {
            status_header(404);
            exit;
        };
        add_action('do_feed', $feed_die_function, 1);
        add_action('do_feed_rdf', $feed_die_function, 1);
        add_action('do_feed_rss', $feed_die_function, 1);
        add_action('do_feed_rss2', $feed_die_function, 1);
        add_action('do_feed_atom', $feed_die_function, 1);
        add_action('do_feed_rss2_comments', $feed_die_function, 1);
        add_action('do_feed_atom_comments', $feed_die_function, 1);
    }

    /**
     *  Disable author pages
     */
    public function disableAuthorPages()
    {
        // Remove author pages
        add_action('template_redirect', function () {
            if (is_author()) {
                $target = get_option('siteurl');
                $status = '301';
                wp_redirect($target, 301);
                die();
            }
        }, 1);
    }

    /**
     *  Disable admin bar on frontend
     */
    public function disableAdminBar()
    {
        add_filter('show_admin_bar', '__return_false');
    }

    /**
     *  Disable REST default routes
     */
    public function disableRESTDefaultRoutes()
    {
        // Remove inital REST endpoints
        remove_action('rest_api_init', 'create_initial_rest_routes', 99);
        remove_action('rest_api_init', 'wp_oembed_register_route');
    }
}
