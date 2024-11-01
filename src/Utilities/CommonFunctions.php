<?php

namespace Unified\Utilities;

use Unified\Utilities\Licensing\Licensing;

/**
 * Class for common functions
 */
class CommonFunctions
{
    use SingletonTrait;

    // Cached data
    private $is_logged_in = null;

    /**
     *  Get asset full url
     */
    public function getAssetUrl($asset)
    {
        static $manifest = null;
        static $plugin_url = null;
        if ($manifest === null) {
            $manifest = json_decode(file_get_contents(DAEV_UNIFIED_PLUGIN_DIR . 'dist/manifest.json'));
            $plugin_url = trailingslashit(DAEV_UNIFIED_PLUGIN_URL . "dist");
        }

        if (isset($manifest->$asset)) {
            return $plugin_url . $manifest->$asset;
        } else {
            return "";
        }
    }

    /**
     *   Check if a user is logged in, before is_user_logged_in is available
     *   We just check for that logged in cookie exist and do not validate anything, knowing well that we cant be certain it is valid
     */
    public function isUserLoggedIn(): bool
    {
        if ($this->is_logged_in !== null) {
            return $this->is_logged_in;
        }

        $this->is_logged_in = false;
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, 'wordpress_logged_in_') !== false) {
                $this->is_logged_in = true;
                break;
            }
        }
        return $this->is_logged_in;
    }

    /**
     * Recursively delete files in directory
     */
    public function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $response = $this->removeDirectory($dir . "/" . $object);
                        if ($response === false) {
                            return false;
                        }
                    } else {
                        @unlink($dir . "/" . $object);
                    }
                }
            }
            @rmdir($dir);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the best charset and collation for db tables on this site
     */
    public function getDBCharsetAndCollation()
    {
        global $wpdb;
        return $wpdb->determine_charset('utf8', 'utf8_general_ci');
    }

    /**
     * Check if premium version
     */
    public static function isPremiumVersion()
    {
        static $is_premium = null;

        if ($is_premium === null) {
            // Check if premium version
            if (file_exists(DAEV_UNIFIED_PLUGIN_DIR . '/.premium')) {
                $is_premium = true;
            } else {
                $is_premium = false;
            }
        }

        return $is_premium;
    }

    /**
     * Check if valid pro version
     */
    public static function isValidPremiumVersion()
    {
        if (self::isPremiumVersion()) {
            $licensing = new Licensing();
            return $licensing->verifyLicense();
        }
        return false;
    }
}
