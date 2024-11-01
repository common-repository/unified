<?php

/**
 * Email Log
 */

namespace Unified\Modules\Email;

use Unified\Modules\Email\Model\Email;
use Unified\Utilities\Configuration;

class EmailLog
{
    /**
     *  Initialize email log
     */
    public function init()
    {
        $configuration = Configuration::getInstance();

        // Email logging here, only intercept email if it is not done via email queue functionality
        if (!$configuration->get('email_use_email_queue')) {
            $email_queue = new EmailQueue();
            $email_queue->default_email_state_on_insert = Email::STATUS_SENT;
            add_filter('pre_wp_mail', [$email_queue, 'queueEmail'], \PHP_INT_MIN, 2);
        }
    }
}
