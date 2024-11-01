<?php

/**
 *  List UI Filter
 */

namespace Unified\Utilities\Lists;

class ListUIFilter
{
    public string $id = "";
    public string $placeholder = "";
    public int $order = 0;  // Smaller numbers get shown first
    public array $options = [];
    private $options_populate_callable = null;

    /**
     *  Constructor
     */
    public function __construct(string $id, string $placeholder, int $order, ?callable $options_populate_callable = null)
    {
        $this->id = $id;
        $this->placeholder = $placeholder;
        $this->order = $order;
        $this->options_populate_callable = $options_populate_callable;
    }

    /**
     *  Populate options
     */
    public function populateOptions()
    {
        if (\is_callable($this->options_populate_callable)) {
            $this->options = call_user_func($this->options_populate_callable);
        }
    }
}
