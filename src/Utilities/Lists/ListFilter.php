<?php

/**
 *  Filter class, used by lists in Unified
 */

namespace Unified\Utilities\Lists;

class ListFilter
{
    // Standard filters
    public const FILTER_SEARCH = 'search';

    // Sorting
    public const SORTING_FIELD = 'sort_field';
    public const SORTING_INVERSE = 'sort_inverse';

    // Filters
    public array $filters = [];

    // Count and offset
    public int $count = 50;
    public int $offset = 0;

    /**
     *  Set filter - supports string and arrays as value
     */
    public function setFilter($name, $value)
    {
        if (is_null($value)) {
            unset($this->filters[$name]);
            return false;
        }

        // Sanitize and escape for SQL
        if (is_array($value)) {
            if (empty($value)) {
                return false;
            }
            foreach ($value as &$data) {
                $data = sanitize_text_field($data);
                $data = esc_sql($data);
            }
        } else {
            if (in_array($name, [self::SORTING_FIELD, self::SORTING_INVERSE])) {
                $value = sanitize_key($value); // more restrictive
            } else {
                $value = sanitize_text_field($value);
            }

            $value = esc_sql($value);
            if (strlen($value) == 0) {
                return false;
            }
        }

        // Set filter
        $this->filters[$name] = $value;
        return true;
    }

    /**
     *  Get filter
     */
    public function getFilter($name, $if_empty_return_type = 'array')
    {
        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        } else {
            if ($if_empty_return_type == 'array') {
                return [];
            } else {
                return "";
            }
        }
    }

    /**
     *  Check if we have any filters to apply
     */
    public function hasFilters()
    {
        if (count($this->filters) > 0) {
            return true;
        }
        return false;
    }
}
