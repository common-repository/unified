<?php

namespace Unified\Utilities;

/**
 * Output modifications
 * @since 1.0
 */
class OutputModification
{
    use SingletonTrait;

    private $content_search_replaces = [];
    private $headers_to_add = [];
    private $headers_to_remove = [];

    /**
     *  Add search/replace to content
     */
    public function addContentSearchReplace(string $search, string $replace)
    {
        $this->content_search_replaces[$search] = $replace;
    }

    /**
     *  Get search/replace to content
     */
    public function getContentSearchReplace()
    {
        return $this->content_search_replaces;
    }

    /**
     *  Add a header to remove from response
     */
    public function removeHeader(string $header)
    {
        $this->headers_to_remove[] = $header;
    }

    /**
     *  Get headers to remove
     */
    public function getHeadersToBeRemoved()
    {
        return $this->headers_to_remove;
    }

    /**
     *  Add a header to response
     */
    public function addHeader(string $header, string $value)
    {
        $this->headers_to_add[$header] =  $value;
    }

    /**
     *  Get headers to add
     */
    public function getHeadersToBeAdd()
    {
        return $this->headers_to_add;
    }
}
