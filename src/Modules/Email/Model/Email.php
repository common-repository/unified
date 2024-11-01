<?php

namespace Unified\Modules\Email\Model;

class Email
{
    public int $id = 0;
    public int $created = 0;
    public string $created_formatted = '';
    public int $sent = 0;
    public string $sent_formatted = '';
    public string $status = '';
    public string $status_formatted = '';
    public array $to = [];
    public string $subject = '';
    public string $content = '';
    public array $headers = [];
    public array $attachments = [];

    // States
    const STATUS_NEW = 'new';
    const STATUS_RETRY = 'retry';
    const STATUS_SENT = 'sent';

    public function addAttachment(string $original_filename, string $new_file_location)
    {
        $this->attachments[] =  (object) [
            'orig_filename' => $original_filename,
            'file_location' => $new_file_location,
        ];
    }

    public function decorateFormatted()
    {
        $format = get_option('date_format') . ' ' . get_option('time_format');

        $this->created_formatted = wp_date($format, $this->created);
        if ($this->sent > 0) {
            $this->sent_formatted = wp_date($format, $this->sent);
        } else {
            $this->sent_formatted = __('N/A', 'unified');
        }
        $this->status_formatted =  ucfirst($this->status);
    }

    public static function mapFromDB($db_object): Email
    {
        $email = new self();
        $email->id = $db_object->id ?? 0;
        $email->created = $db_object->created ?? 0;
        $email->sent = $db_object->sent ?? 0;
        $email->status = $db_object->status ?? '';
        $email->to = explode(',', $db_object->mail_to ?? '');
        $email->subject = $db_object->mail_subject ?? '';
        $email->content = $db_object->mail_content ?? '';
        $email->headers = json_decode($db_object->mail_headers ?? '') ?? [];
        if (!is_array($email->headers)) {
            $email->headers = [];
        }
        $email->attachments = json_decode($db_object->mail_attachments ?? '') ?? [];
        if (!is_array($email->attachments)) {
            $email->attachments = [];
        }

        return $email;
    }
}
