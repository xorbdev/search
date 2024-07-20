<?php
namespace xorb\search\helpers;

use Craft;
use craft\helpers\Search as SearchHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use DOMDocument;
use DOMXPath;

use const PREG_OFFSET_CAPTURE;

class MetaTags
{
    protected array $metaTags = [];

    public function __construct(string $html)
    {
        $doc = new DOMDocument();

        $useErrors = libxml_use_internal_errors(true);

        $doc->loadHTML($html);

        libxml_use_internal_errors($useErrors);

        $xpath = new DOMXPath($doc);

        $nodes = $xpath->query('//head/meta');

        foreach($nodes as $node) {
            $name = trim($node->getAttribute('name'));
            if ($name === '') {
                $name = trim($node->getAttribute('property'));
            }

            if ($name === '') {
                continue;
            }

            $content = trim($node->getAttribute('content'));
            if ($content === '') {
                continue;
            }

            $this->metaTags[$name][] = $content;
        }
    }

    public function getMetaTag(string $name): ?string
    {
        return $this->metaTags[$name][0] ?? null;
    }

    public function getMetaTags(string $name): ?array
    {
        return $this->metaTags[$name] ?? null;
    }
}
