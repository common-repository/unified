<?php

/**
 *  List table column configuration
 */

namespace Unified\Utilities\Lists;

class ListColumnConfiguration
{
    private array $columns = [];

    /**
     *  Constructor
     */
    public function __construct()
    {
    }

    /**
     *  Add column
     */
    public function addColumn(ListColumn $list_column)
    {
        $this->columns[] = $list_column;
    }

    /**
     *  Get columns
     */
    public function getColumns()
    {
        return $this->columns;
    }
}
