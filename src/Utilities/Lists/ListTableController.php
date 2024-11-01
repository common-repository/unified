<?php

/**
 *  Handle data for list tables
 */

namespace Unified\Utilities\Lists;

use Unified\Utilities\CommonFunctions;

class ListTableController
{
    private string $headline = "";
    private string $sub_headline = "";
    private int $data_limit = -1;
    // Columns shown and their configuration
    private ?ListColumnConfiguration $columns = null;
    // List of UI filters shown to the use
    private array $ui_filters = [];
    // Forced search filter term (such as lookup of a specific user)
    private string $forced_search_term = '';
    // Which field to show as the default sorted one, assumed ASC by default
    private string $default_sorting_field = "";
    // Set ajax action
    private string $ajax_action = '';
    // Extra JS data
    private array $extra_js_data = [];

    /**
     * Get data and set to JS
     */
    public function setDataToJS()
    {
        // Set data to JS
        $js_string = 'var unifiedListData=' . json_encode($this->createDataForJS()) . ';';
        wp_add_inline_script('unified_admin', $js_string, 'before');
    }

    /**
     * Create the data array for JS
     */
    public function createDataForJS()
    {
        // Data
        $data = [
            'api_token' => wp_create_nonce($this->ajax_action),
            'api_action' => $this->ajax_action,
            'is_pro' => CommonFunctions::isValidPremiumVersion() ? true : false,
            'forced_search_filter' => $this->forced_search_term,
            'columns' => $this->columns->getColumns(),
            'ui_filters' => $this->getUIFilters(),
            'default_sort_field' => $this->default_sorting_field,
            'headline' => $this->getHeadline(),
            'sub_headline' => $this->getSubHeadline(),
            'data_limit' => $this->data_limit,
        ];

        $data = array_merge($this->extra_js_data, $data);

        return $data;
    }

    /**
     * Set headline
     */
    public function setHeadline(string $headline)
    {
        $this->headline = $headline;
    }

    /**
     * Get headline
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set sub headline
     */
    public function setSubHeadline(string $sub_headline)
    {
        $this->sub_headline = $sub_headline;
    }

    /**
     * Get sub headline
     */
    public function getSubHeadline()
    {
        return $this->sub_headline;
    }

    /**
     * Set data limit
     */
    public function setDataLimit(int $data_limit)
    {
        $this->data_limit = $data_limit;
    }

    /**
     * Set columns
     */
    public function setColumns(ListColumnConfiguration $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Get headline
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set ajax action
     */
    public function setAjaxAction(string $ajax_action)
    {
        $this->ajax_action = $ajax_action;
    }

    /**
     * Get UI filters, popuplated and sorted by order
     */
    public function getUIFilters()
    {
        if (count($this->ui_filters) == 0) {
            return [];
        }

        // Get options on filters
        foreach ($this->ui_filters as $ui_filter) {
            $ui_filter->populateOptions();
        }

        // Sort it by order
        uasort($this->ui_filters, function ($a, $b) {
            if ($a->order == $b->order) {
                return 0;
            }
            return ($a->order < $b->order) ? -1 : 1;
        });

        return $this->ui_filters;
    }

    /**
     * Clear UI filters
     */
    public function clearUIFilters()
    {
        $this->ui_filters = [];
    }

    /**
     * Add UI filters
     */
    public function addUIFilter(ListUIFilter $list_ui_filter)
    {
        $this->ui_filters[$list_ui_filter->id] = $list_ui_filter;
    }

    /**
     * Get UI filters
     */
    public function getUIFilter($list_ui_filter_id): ?ListUIFilter
    {
        return $this->ui_filters[$list_ui_filter_id] ?? null;
    }

    /**
     * Set default sort field
     */
    public function setDefaultSortField(string $field)
    {
        $this->default_sorting_field = $field;
    }

    /**
     * Add forced search filter
     */
    public function addForcedSearchFilter(string $search)
    {
        $this->forced_search_term = $search;
    }

    /**
     * Add extra JS data
     */
    public function addExtraJSData(string $key, $value)
    {
        $this->extra_js_data[$key] = $value;
    }
}
