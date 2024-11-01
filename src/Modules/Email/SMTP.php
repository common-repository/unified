<?php

/**
 * Custom SMTP support
 */

namespace Unified\Modules\Email;

use Unified\Utilities\Configuration;

class SMTP
{
    public $configuration;

    /**
     *  Constructor
     */
    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration ?? Configuration::getInstance();
    }

    /**
     *  Initialize custom SMTP
     */
    public function init()
    {
        \add_filter('phpmailer_init', [$this, 'changePHPMailerConfiguration']);
    }

    /**
     *  Initialize custom SMTP
     */
    public function changePHPMailerConfiguration(\PHPMailer\PHPMailer\PHPMailer $phpmailer)
    {
        $host = $this->configuration->get('email_smtp_host');
        $port = $this->configuration->get('email_smtp_port');
        $using_auth = $this->configuration->get('email_userpass_authentication');
        $smtp_username = $this->configuration->get('email_smtp_username');
        $smtp_password = $this->configuration->get('email_smtp_password');
        $smtp_secure_mode = $this->configuration->get('email_smtp_secure');

        // Use the settings
        $phpmailer->isSMTP();
        $phpmailer->Timeout = 25;
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        $phpmailer->SMTPAuth = $using_auth;
        $phpmailer->Username = $smtp_username;
        $phpmailer->Password = $smtp_password;
        $phpmailer->SMTPSecure = $smtp_secure_mode;
    }
}
