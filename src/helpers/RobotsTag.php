<?php
namespace xorb\search\helpers;

use Craft;
use DateTime;
use xorb\search\helpers\MetaTags;

/**
 * https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag
 */
class RobotsTag
{
    private bool $noIndex = false;
    private ?DateTime $unavailableAfterDate = null;

    public function __construct(
        string $html,
        array $headers,
        protected bool $parseMetaTag = true,
        protected bool $parseHeaderTag = true
    ) {
        $success = false;

        if (!$this->parseHeaderTag($headers)) {
            $this->parseMetaTag($html);
        }
    }

    private function parseHeaderTag(array $headers): bool
    {
        if (!$this->parseHeaderTag) {
            return false;
        }

        $tags = [];

        foreach ($headers as $header => $value) {
            $header = strtolower($header);

            if ($header === 'x-robots-tag') {
                if (is_array($value)) {
                    $tags = array_merge($tags, $value);
                } else {
                    $tags[] = $value;
                }
            }
        }

        if (!$tags) {
            return false;
        }

        foreach ($tags as $tag) {
            $this->parseRobots($tag);
        }

        return true;
    }

    private function parseMetaTag(string $html): bool
    {
        if (!$this->parseMetaTag) {
            return false;
        }

        $metaTags = new MetaTags($html);
        $robots = $metaTags->getMetaTags('robots');

        if ($robots === null) {
            return false;
        }

        foreach ($robots as $tag) {
            $this->parseRobots($tag);
        }

        return true;
    }

    private function parseRobots(string $robots): void
    {
        $robots = strtolower($robots);

        $pos = strpos($robots, 'unavailable_after');

        if ($pos !== false) {
            // Extract the content after 'unavailable_after:'
            $date = substr($robots, $pos + 18);
            $date = explode(',', $date)[0];
            $date = strtotime($date);

            if ($date !== false) {
                $this->unavailableAfterDate = new DateTime('@' . $date);
            }
        }

        $pos = strpos($robots, 'noindex');
        if ($pos !== false) {
            $this->noIndex = true;
        }

        $pos = strpos($robots, 'none');
        if ($pos !== false) {
            $this->noIndex = true;
        }
    }

    public function getNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function getUnavailableAfterDate(): ?DateTIme
    {
        return $this->unavailableAfterDate;
    }
}
