<?php

/**
 * Email module
 */

namespace Unified\Modules\Email;

use Unified\Utilities\Configuration;

class EmailModule
{
    /**
     *  Initialize email module
     *  @since 1.0
     */
    public function init()
    {
        $configuration = Configuration::getInstance();
        if ($configuration->get('email_use_custom_smtp')) {
            (new SMTP())->init();
        }

        // Email queue
        if ($configuration->get('email_use_email_queue')) {
            (new EmailQueue())->init();
        }

        // Email log
        if ($configuration->get('email_log_list')) {
            (new EmailLog())->init();
        }

        // Email from name
        $mail_from_name = $configuration->get('email_smtp_send_from_name');
        if (strlen($mail_from_name) > 0) {
            \add_filter('wp_mail_from_name', function () use ($mail_from_name) {
                return $mail_from_name;
            });
        }

        // Email from email
        $mail_from_email = $configuration->get('email_smtp_send_from_email');
        if (strlen($mail_from_email) > 0) {
            \add_filter('wp_mail_from', function () use ($mail_from_email) {
                return $mail_from_email;
            });
        }
    }
}
