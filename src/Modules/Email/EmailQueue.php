<?php

/**
 * Email Queue
 */

namespace Unified\Modules\Email;

use Unified\Modules\Email\Model\Email;
use Unified\Utilities\CommonFunctions;
use Unified\Utilities\PluginDirs;

class EmailQueue
{
    public string $table_name = '';
    public string $default_email_state_on_insert = '';

    // Cron
    const CRON_MAX_RUN_TIME = 15;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'unified_email_log';
        $this->default_email_state_on_insert = Email::STATUS_NEW;
    }

    /**
     *  Initialize email queue
     */
    public function init()
    {
        // Fetch it before it tries to send it
        add_filter('pre_wp_mail', [$this, 'queueEmail'], \PHP_INT_MIN, 2);

        // Add email queue processing to cron
        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display' => 'Every minute'
            ];
            return $schedules;
        });
        if (!wp_next_scheduled('unified_cron_email_queue_processing')) {
            wp_schedule_event(time(), 'every_minute', 'unified_cron_email_queue_processing');
        }
        add_action('unified_cron_email_queue_processing', function () {
            $email_queue = new EmailQueue();
            $email_queue->processQueue();
        });
    }

    /**
     * Handle when wp_mail is called, so we put it in queue
     */
    public function queueEmail($value, array $atts)
    {
        $email = new Email();

        $to = '';
        if (isset($atts['to'])) {
            $to = $atts['to'];
        }
        if (!is_array($to)) {
            $to = explode(',', $to);
        }
        $email->to = $to;

        if (isset($atts['subject'])) {
            $email->subject = $atts['subject'];
        }

        if (isset($atts['message'])) {
            $email->content = $atts['message'];
        }

        $headers = [];
        if (isset($atts['headers'])) {
            $headers = $atts['headers'];
        }
        if (!is_array($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }
        // Check if our no queue header is present, and if so, no requeue
        if (in_array('UNIFIED_NO_QUEUE', $headers)) {
            return $value;
        }
        $email->headers = $headers;


        $attachments = [];
        if (isset($atts['attachments'])) {
            $attachments = $atts['attachments'];
        }
        if (!is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }
        $this->handleAttachments($attachments, $email);

        $this->addEmailToQueue($email);

        // If email should be sent, we return null, as that will continue the normal flow of wp_mail
        if ($this->default_email_state_on_insert == Email::STATUS_SENT) {
            return null;
        }
        // Returning non-null, stops the normal email flow, allowing cron to handle it
        return true;
    }

    /**
     * Process queue from cron
     */
    public function processQueue()
    {
        global $wpdb;
        $time_start = microtime(true);

        // Figure out how much time we want to use
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time == 0) {
            $max_execution_time = self::CRON_MAX_RUN_TIME;
        }
        if ($max_execution_time > self::CRON_MAX_RUN_TIME) {
            $max_execution_time = self::CRON_MAX_RUN_TIME;
        }

        $time_hard_end = $time_start + $max_execution_time;

        // Process
        $last_email_id = -1;
        $emails_sent = 0;

        while (true) {
            if (microtime(true) > $time_hard_end) {
                break;
            }

            // Fetch an email to send
            $email = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM `{$this->table_name}` WHERE `id` > %d and `status` != %s ORDER BY `id` ASC LIMIT 1", $last_email_id, Email::STATUS_SENT)
            );
            if (is_null($email)) {
                // No more work
                break;
            }
            $email = Email::mapFromDB($email);
            $last_email_id = $email->id;

            // Send now
            $mail_result = $this->sendEmailNow($email);

            if ($mail_result) {
                $emails_sent++;
            }
        }

        return $emails_sent;
    }

    /**
     * Send the specific email now
     */
    public function sendEmailNow(Email $email)
    {
        // Get the paths we are using
        $plugin_dirs = PluginDirs::getInstance();
        $email_attachment_path = $plugin_dirs->getUnifiedUploadsFilePath(PluginDirs::SUBDIR_EMAIL_ATTACHMENTS, false, true);
        $temp_path = $plugin_dirs->getUnifiedUploadsFilePath(PluginDirs::SUBDIR_TMP);

        // Get attachments
        $original_attachments = $this->getTemporaryAttachmentsWithOriginalfilenames($email, $email_attachment_path, $temp_path);

        // Header (add our own header, that prevents it being requeued)
        $headers = $email->headers;
        $headers[] = 'UNIFIED_NO_QUEUE';

        // Call the wp_mail function to get the email sent - We could consider sending it ourselves via phpmailer
        $result = wp_mail($email->to, $email->subject, $email->content, $headers, $original_attachments);

        // Update in DB
        global $wpdb;
        if ($email->id > 0) {
            if ($result) {
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => Email::STATUS_SENT,
                        'sent' => time()
                    ],
                    ['id' => $email->id],
                    [
                        '%s',
                        '%d'
                    ],
                    ['%d']
                );
            } else {
                $wpdb->update(
                    $this->table_name,
                    [
                        'status' => Email::STATUS_RETRY,
                        'sent' => time()
                    ],
                    ['id' => $email->id],
                    [
                        '%s',
                        '%d'
                    ],
                    ['%d']
                );
            }
        }

        return $result;
    }

    /**
     * Save attachments to a safe place, as they might be temporary files
     * Also, we want to keep their original name
     */
    public function handleAttachments(array $attachments, Email $email)
    {
        $attachment_dir = $this->createAttachmentDir();

        foreach ($attachments as $attachment) {
            $new_filename = sha1(\uniqid());
            // Move it to our attachment dir
            $original_filename = sanitize_file_name(basename($attachment));
            copy($attachment, $attachment_dir . $new_filename);
            // Add both old filename and new one to email
            $email->addAttachment($original_filename, $new_filename);
        }
    }

    /**
     * Get attachment with original filenames for sending
     */
    public function getTemporaryAttachmentsWithOriginalfilenames(Email $email, string $source_path, string $temporary_path): array
    {
        $attachment_list = [];
        foreach ($email->attachments as $attachment) {
            if (file_exists($source_path . $attachment->file_location)) {
                copy($source_path . $attachment->file_location, $temporary_path . $attachment->orig_filename);
                $attachment_list[] = $temporary_path . $attachment->orig_filename;
            }
        }
        return $attachment_list;
    }

    /**
     * Add email to queue
     */
    public function addEmailToQueue(Email $email)
    {
        global $wpdb;

        $fields = [
            'created' => time(),
            'status' => $this->default_email_state_on_insert,
            'mail_to' => implode(',', $email->to),
            'mail_subject' => $email->subject,
            'mail_content' => $email->content,
            'mail_headers' => json_encode($email->headers),
            'mail_attachments' => json_encode($email->attachments),
        ];

        $fields_type = [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ];

        if ($this->default_email_state_on_insert === Email::STATUS_SENT) {
            $fields['sent'] = time();
            $fields_type[] = '%d';
        }

        $wpdb->insert(
            $this->table_name,
            $fields,
            $fields_type
        );
        $email->id = $wpdb->insert_id;
    }

    /**
     * Search for email in queue
     * @return Email[] List of emails found or empty array if none found
     */
    public function searchEmail(string $search): array
    {
        global $wpdb;
        $search = '%' . $wpdb->esc_like($search) . '%';
        $sql = $wpdb->prepare('SELECT * FROM `' . $this->table_name . '` WHERE
        `mail_to` LIKE %s OR
        `mail_subject` LIKE %s OR
        `mail_content` LIKE %s
        ORDER BY `ID` DESC', $search, $search, $search);
        $emails_found = $wpdb->get_results($sql);

        $email_objects = [];
        foreach ($emails_found as $email) {
            $email_objects[] = Email::mapFromDB($email);
        }
        return $email_objects;
    }

    /**
     * Create database table for email queue
     */
    public function createEmailQueueTable()
    {
        $charset_and_collation = CommonFunctions::getInstance()->getDBCharsetAndCollation();

        $table_definition = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `created` int NOT NULL,
            `sent` int NULL DEFAULT NULL,
            `status` varchar(10) NOT NULL,
            `mail_to` longtext NOT NULL,
            `mail_subject` varchar(1000) NOT NULL,
            `mail_content` longtext NOT NULL,
            `mail_headers` text NOT NULL,
            `mail_attachments` mediumtext NOT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            FULLTEXT KEY `mail_to` (`mail_to`),
            FULLTEXT KEY `mail_subject` (`mail_subject`),
            FULLTEXT KEY `mail_content` (`mail_content`)
          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=" . $charset_and_collation['charset'] . " COLLATE=" . $charset_and_collation['collate'] . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($table_definition);
    }

    /**
     * Create location where we can keep attachments
     * @return string Full path to attachment dir
     */
    public function createAttachmentDir(): string
    {
        $plugin_dirs = PluginDirs::getInstance();
        $plugin_dirs->getUnifiedUploadsFilePath(PluginDirs::SUBDIR_TMP);
        return $plugin_dirs->getUnifiedUploadsFilePath(PluginDirs::SUBDIR_EMAIL_ATTACHMENTS, false, true);
    }

    /**
     * Get email content for single email
     * @return string|null Mail content raw as string or null if email doesnt exist
     */
    public function getEmailContent(int $email_id): string
    {
        $sql = 'SELECT `mail_content` FROM `' . $this->table_name . '` WHERE `id`=%d';
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare($sql, $email_id));
    }

    /**
     * Truncate the email log
     */
    public function truncateDatabase()
    {
        $sql = 'DELETE FROM `' . $this->table_name . '`';
        global $wpdb;
        $wpdb->query($sql);
    }

    /**
     * Send test email to a user, to check that everything is good
     */
    public function sendTestEmail(string $to_email): bool
    {
        $email = new Email();
        $email->to[] = $to_email;
        $email->subject = sprintf(__('Test email sent from %s by Unified plugin', 'unified'), get_home_url());
        $email->content = __('Test content for email', 'unified');

        return $this->sendEmailNow($email);
    }
}
