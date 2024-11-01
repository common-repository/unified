<?php

namespace Unified\Modules\Security;

use Unified\Utilities\Configuration;
use Unified\Utilities\OutputModification;

/**
 * Security headers
 * @since 1.0
 */
class Headers
{
    /**
     *  Initialize security headers
     *  @since 1.0
     */
    public function init()
    {
        $configuration = Configuration::getInstance();
        $output_modification = OutputModification::getInstance();
        add_filter('wp_headers', [$this, 'modifyHTTPHeader']);

        // Removing headers
        if ($configuration->get('security_header_x_pingback')) {
            $output_modification->removeHeader('X-Pingback');
        }
        if ($configuration->get('security_header_x_powered_by')) {
            header_remove('x-powered-by');
            $output_modification->removeHeader('X-Powered-By');
        }
        // Adding headers
        if ($configuration->get('security_header_x_frame_options')) {
            $output_modification->addHeader('X-Frame-Options', 'SAMEORIGIN');
        }
        if ($configuration->get('security_header_x_content_type_options')) {
            $output_modification->addHeader('X-Content-Type-Options', 'nosniff');
        }
        if ($configuration->get('security_header_referrer_policy')) {
            $output_modification->addHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if ($configuration->get('security_header_strict_transport_security')) {
            $output_modification->addHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    /**
     *  Modify headers of HTTP response from wp_headers hook
     *  @since 1.0
     */
    public function modifyHTTPHeader($headers)
    {
        $output_modification = OutputModification::getInstance();
        $headers_to_add = $output_modification->getHeadersToBeAdd();
        $headers_to_remove = $output_modification->getHeadersToBeRemoved();

        // Remove headers
        foreach ($headers_to_remove as $header_to_remove) {
            foreach ($headers as $header_key => $header_value) {
                if (stripos($header_key, $header_to_remove) !== false) {
                    unset($headers[$header_key]);
                }
            }
        }

        // Add headers
        foreach ($headers_to_add as $header_key => $header_value) {
            $headers[$header_key] = $header_value;
        }

        return $headers;
    }
}
