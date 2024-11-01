<?php

/**
 * Handle async requests setup
 */

namespace Unified\Utilities\AsyncRequest;

use Unified\Modules\Email\EmailQueue;
use Unified\Pages\ConfigurationPage;
use Unified\Pages\EmailLog;
use Unified\Utilities\Configuration;

class AsyncRequestHandler
{
    public function init()
    {
        $configuration = Configuration::getInstance();

        // Configuration save
        add_action('wp_ajax_unified_configuration', function () {
            $configuration_page = new ConfigurationPage();
            $configuration_page->handlePOST();
        });

        // Email log
        if ($configuration->get('email_log_list')) {
            // Fetch data for the email log list
            add_action('wp_ajax_unified_email_log', function () {
                $email_log = new EmailLog();
                $email_log->handlePOST();
            });
        }

        // Email test
        if (!empty($configuration->get('email_test_to_email'))) {
            add_action('wp_ajax_unified_email_test', function () {
                $this->checkAuthentication('unified-configuration-ajax');

                $configuration = Configuration::getInstance();
                $email_queue = new EmailQueue();
                $result = $email_queue->sendTestEmail($configuration->get('email_test_to_email'));
                if ($result) {
                    wp_send_json_success();
                }
                wp_send_json_error([__('Test email could not be send right now - Make sure configuration of SMTP is correct.', 'unified')]);
            });
        }
    }

    public function checkAuthentication(string $action)
    {
        // Check for nonce security
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_nonce'])), $action)) {
            wp_send_json_error([__('Authentication was not correct, so no changes was made - Refresh page and try again', 'unified')]);
        }

        // Make sure user has right access
        if (!current_user_can('manage_options')) {
            wp_send_json_error([__('You do not have access to do this.', 'unified')]);
        }
    }
}
