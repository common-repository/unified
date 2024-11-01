<?php

/**
 *  List table column
 */

namespace Unified\Utilities\Lists;

class ListColumn
{
    public string $data_field = "";
    public string $label = "";
    public bool $is_sortable = true;
    public bool $links_to_user = false;
    public string $custom_presentation = "";
    public array $extra_data = [];

    /**
     *  Constructor
     */
    public function __construct(string $data_field, string $label, bool $is_sortable, bool $links_to_user, string $custom_presentation = "", $extra_data = [])
    {
        $this->data_field = $data_field;
        $this->label = $label;
        $this->is_sortable = $is_sortable;
        $this->links_to_user = $links_to_user;
        $this->custom_presentation = $custom_presentation;
        $this->extra_data = $extra_data;
    }
}
