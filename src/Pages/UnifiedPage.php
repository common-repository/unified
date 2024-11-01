<?php

/**
 * Base class for pages in Unified
 */

namespace Unified\Pages;

abstract class UnifiedPage
{
    public function render()
    {
        // Check if user has access
        if (!$this->checkAccess()) {
            wp_die(__('You do not have access to this page', 'unified'));
        }

        // Handle POST before GET
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePOST();
        }
        // Handle GET
        $this->handleGET();
    }

    public function checkAccess(): bool
    {
        return current_user_can('manage_options');
    }

    abstract public function handleGET();
    abstract public function handlePOST();
}
