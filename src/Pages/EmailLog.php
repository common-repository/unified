<?php

/**
 * Email log
 */

namespace Unified\Pages;

use Unified\Modules\Email\EmailQueue;
use Unified\Modules\Email\Lists\EmailLogData;
use Unified\Modules\Email\Model\Email;
use Unified\Utilities\AsyncRequest\AsyncRequestHandler;
use Unified\Utilities\CommonFunctions;
use Unified\Utilities\Lists\ListColumn;
use Unified\Utilities\Lists\ListColumnConfiguration;
use Unified\Utilities\Lists\ListFilter;
use Unified\Utilities\Lists\ListFilterItem;
use Unified\Utilities\Lists\ListTableController;
use Unified\Utilities\Lists\ListUIFilter;
use Unified\Utilities\PluginDirs;

class EmailLog extends UnifiedPage
{
    public function handleGET()
    {
        $list_table_controller = new ListTableController();
        $list_table_controller->setHeadline(__('Email log', 'unified'));
        $list_table_controller->setSubHeadline(__('See the emails sent from this site.', 'unified'));
        //$list_table_controller->setDataLimit(50);

        // Columns
        $column_configuration = new ListColumnConfiguration();
        $column_configuration->addColumn(new ListColumn('created_formatted', __('Created', 'unified'), false, false));
        $column_configuration->addColumn(new ListColumn('sent_formatted', __('Sent', 'unified'), false, false));
        $column_configuration->addColumn(new ListColumn('status_formatted', __('Status', 'unified'), false, false));
        $column_configuration->addColumn(new ListColumn('to', __('Recipient(s)', 'unified'), false, false, 'array-to-string'));
        $column_configuration->addColumn(new ListColumn('subject', __('Subject', 'unified'), false, false));
        $column_configuration->addColumn(new ListColumn('attachments', __('Attachments', 'unified'), false, false, 'email-log-attachments'));
        $column_configuration->addColumn(new ListColumn('actions', __('Actions', 'unified'), false, false, 'email-log-actions'));

        // Status UI filter
        $list_table_controller->addUIFilter(new ListUIFilter('status', __('Status', 'unified'), 0, function () {
            $statuses = [];
            $statuses[] = new ListFilterItem(Email::STATUS_NEW, __('New', 'unified'));
            $statuses[] = new ListFilterItem(Email::STATUS_RETRY, __('Retry', 'unified'));
            $statuses[] = new ListFilterItem(Email::STATUS_SENT, __('Sent', 'unified'));
            return $statuses;
        }));

        $list_table_controller->setColumns($column_configuration);

        $list_table_controller->setAjaxAction('unified_email_log');

        // Get the attachment dirs
        $plugin_dirs = PluginDirs::getInstance();
        $list_table_controller->addExtraJSData('email_attachment_url', $plugin_dirs->getUnifiedUploadsFileURL(PluginDirs::SUBDIR_EMAIL_ATTACHMENTS));

        $list_table_controller->setDataToJS();

        echo '<unified-email-log id="unified-email-log"></unified-email-log>';
    }

    public function handlePOST()
    {
        $async_request_handler = new AsyncRequestHandler();
        $async_request_handler->checkAuthentication('unified_email_log');

        // Figure out the action we want
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the data for the list
            $list_filter = new ListFilter();
            $query = $_POST['query'] ?? [];
            $list_filter->setFilter(ListFilter::FILTER_SEARCH, $query['search'] ?? '');
            $list_filter->setFilter(ListFilter::SORTING_FIELD, $query['sort'] ?? '');
            $list_filter->setFilter(ListFilter::SORTING_INVERSE, $query['sortinv'] ?? '');
            $list_filter->setFilter('status', $query['status'] ?? []);
            $list_filter->offset = $query['offset'] ?? 0;

            // Fetch some data
            $data = (new EmailLogData())->getData($list_filter);

            // Send data
            wp_send_json_success($data);
        }

        // If GET, we just return a single email
        $email_queue = new EmailQueue();
        $mail_id = intval($_GET['mid'] ?? 0);
        if ($mail_id > 0) {
            $mail_content = $email_queue->getEmailContent($mail_id);
            echo $mail_content;
            exit;
        }
        wp_send_json_error([__('Email could not be found', 'unified')]);
    }
}
