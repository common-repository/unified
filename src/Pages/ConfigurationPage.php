<?php

/**
 * Show configuration of plugin
 */

namespace Unified\Pages;

use Unified\Utilities\AsyncRequest\AsyncRequestHandler;
use Unified\Utilities\CommonFunctions;
use Unified\Utilities\Configuration;

class ConfigurationPage extends UnifiedPage
{
    public function handleGET()
    {
        // Get current configuration
        $configuration = Configuration::getInstance();
        $current_configuration = $configuration->getArray();
        $recommended_values = $configuration->getRecommendedValues();
        $default_values = $configuration->getDefaultValues();
        $commonfunctions = CommonFunctions::getInstance();

        // Localize the script with data
        $js_data = [
            'nonce' => wp_create_nonce('unified-configuration-ajax'),
            'configuration' => $current_configuration,
            'recommended_values' => $recommended_values,
            'default_values' => $default_values,
            'logo_url' => $commonfunctions->getAssetUrl('logo-no-background.svg'),
        ];

        \wp_localize_script('unified_admin', 'unifiedConfiguration', $js_data);
        echo '<unified-configuration id="unified-configuration"></unified-configuration>';
    }

    public function handlePOST()
    {
        $async_request_handler = new AsyncRequestHandler();
        $async_request_handler->checkAuthentication('unified-configuration-ajax');

        $configuration = Configuration::getInstance();

        $return_content = [
            'errors' => [],
            'configuration' => $configuration->getArray(),
        ];

        // Get the configuration array - Data will be sanitize and validated inside setWithArray function, depending on type of data
        $post_data = $_POST['configuration'] ?? null;
        if ($post_data !== null) {
            $errors_found = $configuration->setWithArray($post_data);
            if (count($errors_found) > 0) {
                $return_content['configuration'] = $configuration->getArray();
                $return_content['errors'] = $errors_found;
                wp_send_json_error($return_content);
            }
        } else {
            wp_send_json_error($return_content);
        }

        // Save the valid data
        $configuration->save();
        $return_content['configuration'] = $configuration->getArray();
        wp_send_json_success($return_content);
    }
}
