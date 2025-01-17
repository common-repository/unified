<?php

/**
 * Handle plugins dir
 */

namespace Unified\Utilities;

class PluginDirs
{
    use SingletonTrait;

    const OPTION_CACHE_KEY = 'unified_cache_key';

    // Subdir constants
    const SUBDIR_TMP = 'tmp';
    const SUBDIR_EMAIL_ATTACHMENTS = 'email-attachments';

    private string $unified_base_uploads_dir;
    private string $unified_base_uploads_url;

    public function __construct()
    {
        // Random site specific dir key, to better hide data
        $random_site_key = get_option(self::OPTION_CACHE_KEY);
        if ($random_site_key === false) {
            $random_site_key = uniqid();
            update_option(self::OPTION_CACHE_KEY, $random_site_key, true);
        }

        $path_after_base = '/unified-' . $random_site_key . '/';
        $upload_dir = wp_upload_dir();
        $this->unified_base_uploads_dir = $upload_dir['basedir'] . $path_after_base;
        $this->unified_base_uploads_url = $upload_dir['baseurl'] . $path_after_base;
    }

    /**
     * Get a specific subdir under unified main dir in uploads
     * @param string $subdir Subdirectory name without trailing slash wanted
     * @return string Full path to dir, with trailing slash
     */
    public function getUnifiedUploadsFilePath(string $subdir = '', bool $with_htaccess = true, bool $with_index_php = true): string
    {
        $dir_wanted = $this->unified_base_uploads_dir . $subdir . '/';

        if (!file_exists($dir_wanted)) {
            // Create dir
            mkdir($dir_wanted, 0755, true);

            if ($with_htaccess) {
                // Create .htaccess to block access
                $htaccess_file = $dir_wanted . ".htaccess";
                if (!file_exists($htaccess_file)) {
                    $htaccess_content = "order deny,allow" . PHP_EOL . "deny from all";
                    file_put_contents($htaccess_file, $htaccess_content);
                }
            }

            if ($with_index_php) {
                // Create empty index.php file
                $index_php_file = $dir_wanted . "index.php";
                if (!file_exists($index_php_file)) {
                    $index_php_content = "<?php " . PHP_EOL . "// silence is golden";
                    file_put_contents($index_php_file, $index_php_content);
                }
            }
        }

        // Set proper permissions
        @chmod($dir_wanted, 0755);

        return $dir_wanted;
    }

    /**
     * Get a URL for a subdir
     */
    public function getUnifiedUploadsFileURL(string $sub_dir = '')
    {
        return trailingslashit($this->unified_base_uploads_url . $sub_dir);
    }
}
