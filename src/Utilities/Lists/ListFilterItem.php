<?php

/**
 *  List filter item
 */

namespace Unified\Utilities\Lists;

class ListFilterItem
{
    public string $key = "";
    public string $value = "";

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
