<?php
namespace Akqa\CmsApi\Model;

use Akqa\CmsApi\Api\CmsPageInterface;
use Magento\Cms\Model\PageFactory;

class CmsPage implements CmsPageInterface
{
    protected $pageFactory;

    public function __construct(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    /**
     * Get CMS page JSON
     *
     * POST body:
     * { "identifier": "home", "content_type": "slider" }
     *
     * @return array
     */
    public function getPage()
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        $identifier  = $data['identifier'] ?? null;
        $contentType = $data['content_type'] ?? null;

        if (!$identifier) {
            return ['error' => true, 'message' => 'Identifier is required'];
        }

        $page = $this->pageFactory->create()->load($identifier, 'identifier');
        if (!$page || !$page->getId()) {
            return ['error' => true, 'message' => 'CMS Page not found'];
        }

        $html = $page->getContent();

        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $bodyNode = $doc->getElementsByTagName('body')->item(0);

        if (!$bodyNode) {
            $content = [];
        } elseif ($contentType) {
            // Return only nodes matching data-content-type
            $content = $this->getNodesByContentType($bodyNode, $contentType);
        } else {
            // Return full page content
            $content = $this->domNodeToArray($bodyNode);
        }

        return [
            'content' => $content
        ];
    }

    /**
     * Recursively convert a DOM node to associative array
     */
    protected function domNodeToArray($node)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->nodeValue);
            if ($text === '') {
                return null;
            }

            // Detect Magento widget placeholder
            if (preg_match('/\{\{widget\s+([^\}]+)\}\}/', $text, $matches)) {
                $widgetString = $matches[1];
                $widgetArray = $this->parseWidgetString($widgetString);
                return ['widget' => $widgetArray];
            }

            return ['text' => $text];
        }

        $output = ['tag' => $node->nodeName];

        if ($node->hasAttributes()) {
            $attrs = [];
            foreach ($node->attributes as $attr) {
                $attrs[$attr->nodeName] = $attr->nodeValue;
            }
            if (!empty($attrs)) {
                $output['attributes'] = $attrs;
            }
        }

        if ($node->hasChildNodes()) {
            $children = [];
            foreach ($node->childNodes as $child) {
                $childArray = $this->domNodeToArray($child);
                if ($childArray !== null) {
                    $children[] = $childArray;
                }
            }
            if (!empty($children)) {
                $output['content'] = $children;
            }
        }

        return $output;
    }


    /**
     * Find all nodes with matching data-content-type
     *
     * Returns array of nodes, each with full subtree
     */
    protected function getNodesByContentType($node, $contentType)
    {
        $matches = [];

        if ($node->nodeType === XML_ELEMENT_NODE) {
            if ($node->hasAttributes()
                && $node->attributes->getNamedItem('data-content-type')
                && $node->attributes->getNamedItem('data-content-type')->nodeValue === $contentType
            ) {
                $matches[] = $this->domNodeToArray($node);
            } else {
                foreach ($node->childNodes as $child) {
                    $matches = array_merge($matches, $this->getNodesByContentType($child, $contentType));
                }
            }
        }

        return $matches;
    }

    protected function parseWidgetString($widgetString)
    {
        $widget = [];

        // Split key=value pairs
        preg_match_all('/(\w+)="([^"]*)"/', $widgetString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2];

            // Convert numeric values and booleans
            if (is_numeric($value)) {
                $value = (int)$value;
            } elseif ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }

            $widget[$key] = $value;
        }

        return $widget;
    }
}