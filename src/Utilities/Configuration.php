<?php

namespace Unified\Utilities;

use Unified\Modules\Email\EmailQueue;

/**
 * Configuration of plugin
 */
class Configuration
{
    use SingletonTrait;

    const CONFIGURATION_VERSION = 1;
    const CONFIGURATION_OPTION_NAME = 'unified_configuration';

    private $configuration = null;
    private $base_configuration = null;

    /**
     *  Initialize base configuration
     */
    public function initBaseConfiguration()
    {
        if ($this->base_configuration !== null) {
            return;
        }

        $this->base_configuration = [
            /**
             *  Global
             */
            'global_enabled' => [
                'default' => false,
                'on_change' => function ($new_value) {
                    // Clean all data when this settings changes
                    $clear_data = new ClearData();
                    $clear_data->clearAllModuleData();
                },
                'recommended' => true,
            ],
            /**
             *  Caching
             */
            'cache_pages' => [
                'default' => false,
                'recommended' => true,
            ],
            /**
             *  Email
             */
            'email_use_email_queue' => [
                'default' => false,
                'on_change' => function ($new_value) {
                    // Make sure email queue/log table is created
                    $email_queue = new EmailQueue();
                    $email_queue->createEmailQueueTable();
                    $email_queue->createAttachmentDir();
                },
                'recommended' => true,
            ],
            'email_log_list' => [
                'default' => false,
                'recommended' => true,
                'on_change' => function ($new_value) {
                    // Make sure email queue/log table is created
                    $email_queue = new EmailQueue();
                    $email_queue->createEmailQueueTable();
                    $email_queue->createAttachmentDir();
                },
            ],
            'email_use_custom_smtp' => [
                'default' => false,
            ],
            'email_smtp_host' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return strip_tags($value);
                },
                'validate' => function ($new_value) {
                    if ($this->configuration['email_use_custom_smtp']) {
                        if (!filter_var(gethostbyname($new_value), FILTER_VALIDATE_IP)) {
                            return __('Email SMTP host name is not valid or could not look up the DNS IP address for it', 'unified');
                        }
                    }
                },
            ],
            'email_smtp_port' => [
                'default' => 25,
                'validate' => function ($new_value) {
                    if ($new_value < 1 || $new_value > 65535) {
                        return __('Email SMTP port needs to a number between 1 and 65535', 'unified');
                    }
                },
            ],
            'email_userpass_authentication' => [
                'default' => false,
            ],
            'email_smtp_username' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return sanitize_user($value);
                },
            ],
            'email_smtp_password' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return strip_tags($value);
                },
            ],
            'email_smtp_secure' => [
                'default' => '',
                'validate' => function ($new_value) {
                    $valid_values = ['tls', 'ssl', ''];
                    if (!in_array($new_value, $valid_values)) {
                        return __('Email SMTP secure can only be tls, ssl or empty', 'unified');
                    }
                },
            ],
            'email_smtp_send_from_name' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return sanitize_text_field($value);
                },
            ],
            'email_smtp_send_from_email' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return sanitize_email($value);
                },
            ],
            'email_test_to_email' => [
                'default' => '',
                'sanitize' => function ($value) {
                    return sanitize_email($value);
                },
            ],
            /**
             *  Clean up
             */
            'clean_wp_output' => [
                'default' => false,
                'recommended' => true,
            ],
            'clean_disable_emoji' => [
                'default' => false,
                'recommended' => true,
            ],
            'clean_disable_feeds' => [
                'default' => false,
                'recommended' => true,
            ],
            'clean_disable_author_pages' => [
                'default' => false,
                'recommended' => true,
            ],
            'clean_disable_admin_bar' => [
                'default' => false,
                'recommended' => true,
            ],
            'clean_disable_default_rest_routes' => [
                'default' => false,
            ],
            /**
             *  Security headers
             */
            'security_header_x_pingback' => [
                'default' => false,
                'recommended' => true,
            ],
            'security_header_x_powered_by' => [
                'default' => false,
                'recommended' => true,
            ],
            'security_header_x_frame_options' => [ // X-Frame-Options
                'default' => false,
                'recommended' => true,
            ],
            'security_header_x_content_type_options' => [ // X-Content-Type-Options
                'default' => false,
                'recommended' => true,
            ],
            'security_header_referrer_policy' => [ // Referrer-Policy
                'default' => false,
                'recommended' => true,
            ],
            'security_header_strict_transport_security' => [ // strict-transport-security
                'default' => false,
            ],
            // Not yet implemented
            'security_header_permissions_policy' => [ // Permissions-Policy
                'default' => false,
            ],
            // Not yet implemented
            'security_header_content_security_policy' => [ // Content-Security-Policy
                'default' => false,
            ],
        ];

        // Init any missing parts of base configuration
        foreach ($this->base_configuration as $key => &$configuration) {
            if (!isset($configuration['sanitize'])) {
                $configuration['sanitize'] = null;
            }
            if (!isset($configuration['validate'])) {
                $configuration['validate'] = null;
            }
            if (!isset($configuration['on_change'])) {
                $configuration['on_change'] = null;
            }
            if (!isset($configuration['recommended'])) {
                $configuration['recommended'] = false;
            }
        }
    }

    /**
     *  Initialize configuration
     */
    public function init()
    {
        if (is_array($this->configuration)) {
            return;
        }

        $this->initBaseConfiguration();

        $configuration = get_option(self::CONFIGURATION_OPTION_NAME);
        if (is_array($configuration)) {
            $this->configuration = $configuration;
        } else {
            // No configuration was set yet, so set default
            $this->configuration = [];
        }

        // Make sure config has all fields
        $should_save = false;
        foreach ($this->base_configuration as $key => $content) {
            if (!isset($this->configuration[$key])) {
                $this->configuration[$key] = $content['default'];
                $should_save = true;
            }
        }

        if ($should_save) {
            $this->save();
        }
    }

    /**
     *  Save configuration
     */
    public function save()
    {
        if (update_option(self::CONFIGURATION_OPTION_NAME, $this->configuration, true)) {
            // If update was good - Clear any intermediate data, like clear page cache etc
            $clear_data = new ClearData();
            $clear_data->clearAllModuleData();
        }
    }

    /**
     *  Get specific configuration parameter
     */
    public function get(string $configuration_id)
    {
        if (isset($this->configuration[$configuration_id])) {
            return $this->configuration[$configuration_id];
        }
        return null;
    }

    /**
     *  Get configuration array
     *  @since 1.0
     */
    public function getArray()
    {
        return $this->configuration;
    }

    /**
     *  Set from array, such as $_POST
     *  @since 1.0
     */
    public function setWithArray(array $values): array
    {
        $this->initBaseConfiguration();
        $errors_found = [];
        foreach ($values as $key => $value) {
            if (isset($this->base_configuration[$key])) {
                // Cast value to proper type
                if (is_bool($this->base_configuration[$key]['default'])) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if (is_int($this->base_configuration[$key]['default'])) {
                    $value = intval($value);
                }
                if (is_string($this->base_configuration[$key]['default'])) {
                    $value = sanitize_text_field($value);
                }
                // Sanitize
                if (\is_callable($this->base_configuration[$key]['sanitize'])) {
                    $value = $this->base_configuration[$key]['sanitize']($value);
                }
                // Do validate
                if (\is_callable($this->base_configuration[$key]['validate'])) {
                    $error_msg = $this->base_configuration[$key]['validate']($value);
                    if (is_string($error_msg)) {
                        $errors_found[] = $error_msg;
                        continue;
                    }
                }
                if ($this->configuration[$key] !== $value) {
                    if (\is_callable($this->base_configuration[$key]['on_change'])) {
                        $this->base_configuration[$key]['on_change']($value);
                    }
                    $this->configuration[$key] = $value;
                }
            }
        }
        return $errors_found;
    }

    /**
     *  Get recommended values
     */
    public function getRecommendedValues(): array
    {
        $recommended_values = [];
        $this->initBaseConfiguration();

        foreach ($this->base_configuration as $configuration_key => $configuration) {
            if ($configuration['recommended'] === true) {
                $recommended_values[$configuration_key] = true;
            }
        }

        return $recommended_values;
    }

    /**
     *  Get default values
     */
    public function getDefaultValues(): array
    {
        $default_values = [];
        $this->initBaseConfiguration();

        foreach ($this->base_configuration as $configuration_key => $configuration) {
            $default_values[$configuration_key] = $configuration['default'];
        }

        return $default_values;
    }
}
