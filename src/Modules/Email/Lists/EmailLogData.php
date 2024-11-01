<?php

/**
 *  Email log data
 */

namespace Unified\Modules\Email\Lists;

use Unified\Modules\Email\EmailQueue;
use Unified\Modules\Email\Model\Email;
use Unified\Utilities\CommonFunctions;
use Unified\Utilities\Licensing\Licensing;
use Unified\Utilities\Lists\Data\ListData;
use Unified\Utilities\Lists\ListFilter;

class EmailLogData implements ListData
{
    public function getData(ListFilter $list_filter)
    {
        global $wpdb;
        $email_queue = new EmailQueue();

        $sql = 'SELECT `id`, `created`, `sent`, `status`, `mail_to`, `mail_subject`, `mail_attachments` FROM `' . $email_queue->table_name . '` WHERE 1=1 ';

        // Search filter
        $search_filter = $list_filter->getFilter(ListFilter::FILTER_SEARCH, '');
        if (!empty($search_filter)) {
            $search_like = '%' . $wpdb->esc_like($search_filter) . '%';
            $sql .= $wpdb->prepare('AND (`mail_to` LIKE %s OR `mail_subject` LIKE %s OR `mail_content` LIKE %s)', $search_like, $search_like, $search_like);
        }
        // Status filter
        $status_filter = $list_filter->getFilter('status', []);
        if (!empty($status_filter)) {
            // Add to the SQL, filter data is already sanitized and escaped in ListFilter
            $sql .= ' AND `status` IN ("' . implode('","', $status_filter) . '")';
        }

        $offset = $list_filter->offset;
        $count = $list_filter->count;

        $should_have_data_limit = false;
        /* Limit currently commented out
        if (CommonFunctions::isPremiumVersion()) {
            $licensing = new Licensing();
            if (!$licensing->verifyLicense()) {
                $should_have_data_limit = true;
            }
        } else {
            $should_have_data_limit = true;
        }
        */

        if ($should_have_data_limit) {
            $data_limit = 50;
            if ($offset + $count > $data_limit) {
                $remaining =  $data_limit - $offset;

                if ($remaining > 0) {
                    $count = $remaining;
                } else {
                    return [];
                }
            }
        }

        $sql .= ' ORDER BY `id` DESC LIMIT ' . $offset . ', ' . $count;

        $sql_result = $wpdb->get_results($sql);

        $emails = [];
        foreach ($sql_result as $res) {
            $email = Email::mapFromDB($res);
            $email->decorateFormatted();
            $emails[] = $email;
        }

        return $emails;
    }
}
