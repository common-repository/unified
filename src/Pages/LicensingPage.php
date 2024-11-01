<?php

/**
 * Licensing page
 */

namespace Unified\Pages;

use Unified\Utilities\Licensing\Licensing;

class LicensingPage extends UnifiedPage
{
    public function handleGET()
    {
        $licensing = new Licensing();
        $license_key = $licensing->getCurrentLicenseKey();
        $licensing_state = $licensing->getLicenseState();

        $is_license_key_defined_as_constant = false;
        if (defined('UNIFIED_LICENSE_KEY') && strlen(UNIFIED_LICENSE_KEY) > 0) {
            $is_license_key_defined_as_constant = true;
        }

        $diff_seconds = time() - $licensing_state->timestamp;
        $diff_hours = floor($diff_seconds / 3600);

        // Nonces
        $set_license_key_nonce = wp_create_nonce('unified_set_license_key');

        // Localize the script with data
        $js_data = [
            'nonce' => $set_license_key_nonce,
            'license_key' => $license_key,
            'license_state' => $licensing_state,
            'license_defined_as_constant' => $is_license_key_defined_as_constant,
            'hours_since_last_check' => $diff_hours,
        ];

        \wp_localize_script('unified_admin', 'unifiedLicense', $js_data);

        echo '<unified-licensing id="unified-licensing"></unified-licensing>';
    }

    public function handlePOST()
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'unified_set_license_key')) {
            wp_die(__('Security token is no longer valid - Go back and try again.', 'unified'));
        }

        // Save access key
        $license_key = sanitize_key(trim($_POST['unified_license_key'] ?? ''));
        $licensing = new Licensing();
        $licensing->setCurrentLicenseKey($license_key);
    }
}
