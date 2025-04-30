<?php
namespace xorb\search\helpers;

use Craft;
use craft\helpers\Search as SearchHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use xorb\search\helpers\MetaTags;

use const PREG_OFFSET_CAPTURE;

class HtmlPage
{
    protected ?Site $site;
    protected MetaTags $metaTags;
    protected ?string $main;

    public function __construct(
        protected int $siteId,
        protected string $html,
    ) {
        $this->site = Craft::$app->getSites()->getSiteById(
            siteId: $this->siteId,
            withDisabled: true,
        );

        $this->metaTags = new MetaTags($html);

        $this->main = static::parseMain($html);
    }

    public function getTitle(): ?string
    {
        $title = static::parseElement($this->html, 'title');

        if ($title === null) {
            return null;
        }

        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

        if ($this->site === null) {
            return $title;
        }

        $name = $this->site->getName();

        if (StringHelper::endsWith(
            string: $title,
            with: $name,
            caseSensitive: false,
        )) {
            $newTitle = substr($title, 0, -strlen($name));

            // Remove common trailing separators.
            $newTitle = rtrim($newTitle, ' -|/');
            if ($newTitle !== '') {
                $title = $newTitle;
            }
        }

        return $title;

    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTags->getMetaTag('title');
    }

    public function getDescription(): ?string
    {
        if ($this->main === null) {
            return null;
        }

        $paragraph = static::parseElement($this->main, 'p');

        if ($paragraph !== null) {
            $paragraph = $this->cleanData($paragraph);
        }

        if ($paragraph === null) {
            return null;
        }

        if (mb_strlen($paragraph) <= 150) {
            return $paragraph;
        }

        $paragraph = mb_substr($paragraph, 0, 150);

        preg_match_all(
            '/\p{P}/u',
            $paragraph,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        // If matches are found
        if (!isset($matches[0]) || !empty($matches[0])) {
            preg_match_all(
                '/\p{P}\p{S}\p{C}/u',
                $paragraph,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            if (!isset($matches[0]) || !empty($matches[0])) {
                $lastPunctuation = '…';
                $lastPosition = strlen($paragraph);
            } else {
                // Get the last match
                $lastMatch = end($matches[0]);
                $lastPunctuation = $lastMatch[0];
                $lastPosition = $lastMatch[1];
            }
        } else {
            // Get the last match
            $lastMatch = end($matches[0]);
            $lastPunctuation = $lastMatch[0];
            $lastPosition = $lastMatch[1];
        }

        $paragraph = substr($paragraph, 0, $lastPosition) . $lastPunctuation;

        return $paragraph;

    }

    public function getMetaDescription(): ?string
    {
        return $this->metaTags->getMetaTag('description');
    }

    public function getMain(): ?string
    {
        if ($this->main === null) {
            return null;
        }

        $main = $this->cleanData($this->main);

        // Remove invalid utf8 multibyte sequences since
        // StringHelper::replaceMb4 will error out if encountered.
        $main = iconv('UTF-8', 'UTF-8//IGNORE', $main);
        if ($main === false) {
            $main = '';
        }

        return SearchHelper::normalizeKeywords(
            str: $main,
            ignore: [],
            processCharMap: true,
            language: $this->site?->language
        );
    }

    protected function cleanData(string $html): string
    {
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        $html = str_replace(
            ["\n", "\r", "\t"],
            ' ',
            $html
        );

        $html = explode(' ', $html);

        foreach ($html as $key => $value) {
            if ($value === '') {
                unset($html[$key]);
            }
        }

        return implode(' ', $html);
    }

    protected static function parseElement(string $html, string $element): ?string
    {
        $pos = strpos($html, '<' . $element . '>');
        if ($pos === false) {
            $pos = strpos($html, '<' . $element . ' ');

            if ($pos === false) {
                return null;
            }
        }

        $pos = strpos($html, '>', $pos);
        if ($pos === false) {
            return null;
        }
        ++$pos;

        $pos2 = strpos($html, '</' . $element . '>');
        if ($pos2 === false) {
            return null;
        }

        return substr($html, $pos, $pos2 - $pos);
    }

    protected static function parseMain(string $html): ?string
    {
        $main = static::parseElement($html, 'main');

        if ($main !== null) {
            // Remove elements with content that shouldn't be searched
            $main = static::parseOutElement($main, 'script');
            $main = static::parseOutElement($main, 'noscript');
            $main = static::parseOutElement($main, 'style');
            $main = static::parseOutElement($main, 'iframe');
            $main = static::parseOutElement($main, 'select');
            $main = static::parseOutElement($main, 'textarea');
            $main = static::parseOutElement($main, 'label');
            $main = static::parseOutElement($main, 'nav');
        }

        return $main;
    }

    protected static function parseOutElement(string $html, string $element): string
    {
        $len = strlen($element) + 3; // </$element>
        $offset = 0;
        $newData = '';

        while (true) {
            $pos = strpos($html, '<' . $element . ' ', $offset);
            if ($pos === false) {
                $pos = strpos($html, '<' . $element . '>', $offset);
                if ($pos === false) {
                    break;
                }
            }

            $pos2 = strpos($html, '</' . $element . '>', $pos);
            if ($pos2 === false) {
                break;
            }

            // Add content before element
            $newData .= substr($html, $offset, $pos - $offset);

            $offset = $pos2 + $len;
        }

        $newData .= substr($html, $offset);

        return $newData;
    }
}
