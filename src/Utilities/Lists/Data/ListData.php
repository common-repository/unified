<?php

/**
 *   Declare a common interface for list data classes
 */

namespace Unified\Utilities\Lists\Data;

use Unified\Utilities\Lists\ListFilter;

interface ListData
{
    public function getData(ListFilter $list_filter);
}
