<?php

/**
 * Class for providing data for page headers
 * @since 1.6.0
 */

namespace Unified\Utilities\JSData;

use Unified\Utilities\CommonFunctions;

class PageHeaderData
{
    /**
     *  Load the JS data for page headers
     */
    public function load()
    {
        $common_functions = CommonFunctions::getInstance();

        $jsdata = [
            "isPro" => CommonFunctions::isPremiumVersion(),
            "isProValid" => CommonFunctions::isValidPremiumVersion(),
            "pageTitleImg" => $common_functions->getAssetUrl("logo-icon-color.svg"),
            "version" => DAEV_UNIFIED_VERSION,
        ];
        wp_localize_script('unified_admin', 'UnifiedPageHeader', $jsdata);
    }
}
