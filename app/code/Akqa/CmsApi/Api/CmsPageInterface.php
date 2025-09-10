<?php
namespace Akqa\CmsApi\Api;

interface CmsPageInterface
{
    /**
     * Get CMS page by identifier
     *
     * @return array
     */
    public function getPage();
}