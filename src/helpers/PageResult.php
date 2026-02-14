<?php
namespace xorb\search\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\UrlHelper;
use DateTime;
use xorb\search\elements\Result as ResultElement;
use xorb\search\events\IndexPageEvent;
use xorb\search\helpers\PluginHelper;
use xorb\search\helpers\HtmlPage;
use xorb\search\helpers\UrlCleaner;
use xorb\search\helpers\UrlIgnorer;
use xorb\search\Plugin;
use yii\base\Event;
use yii\base\InvalidArgumentException;

class PageResult
{
    protected static $updatedPages = [];

    public const EVENT_UPDATE_MAIN_DATA = 'eventUpdatePageMainData';

    private function __construct()
    {}

    /**
     * @param int $siteId
     * @param ?Element $element
     * @param ?string $url
     * @return bool
     */
    public static function addPage(
        int $siteId,
        ?ElementInterface $element,
        ?string $url = null,
    ): bool
    {
        if ($element !== null) {
            $url = $element->getUrl();

            if ($url === null) {
                return false;
            }

            $url = UrlCleaner::clean($siteId, $url);
        } elseif ($url !== null) {
            $url = UrlHelper::rootRelativeUrl($url);
            $url = UrlCleaner::clean($siteId, $url);
        } else {
            throw new InvalidArgumentException('Either element or url is required.');
        }

        $resultElement = self::getResultElement($siteId, $element, $url);

        // If null then an existing page.
        if ($resultElement === null) {
            return true;
        }

        return static::update($resultElement, $element, true);
    }

    /**
     * @param int $siteId
     * @param ?Element $element
     * @param string $url
     * @return ResultElement
     */
    private static function getResultElement(
        int $siteId,
        ?ElementInterface $element,
        string $url
    ): ?ResultElement
    {
        $plugin = Plugin::getInstance();

        // Existing
        /** @var ?ResultElement **/
        $resultElement = ResultElement::find()
            ->siteId($siteId)
            ->resultType('page')
            ->resultUrl($url)
            ->one();

        if ($resultElement !== null) {
            $resultId = ($element ? $element->id : null);

            if ($resultElement->resultId === $resultId) {
                return null;
            }

            $resultElement->resultId = $resultId;

            return $resultElement;
        }

        // Trashed
        /** @var ?ResultElement **/
        $resultElement = ResultElement::find()
            ->siteId($siteId)
            ->resultType('page')
            ->resultUrl($url)
            ->trashed(true)
            ->one();

        if ($resultElement !== null) {
            Craft::$app->getElements()->restoreElement($resultElement);

            $resultElement->resultId = ($element ? $element->id : null);
            $resultElement->rulesIgnore = false;

            return $resultElement;
        }

        // New
        $resultElement = new ResultElement();
        $resultElement->siteId = $siteId;
        $resultElement->resultType = 'page';
        $resultElement->resultId = ($element ? $element->id : null);
        $resultElement->resultUrl = $url;

        return $resultElement;
    }

    public static function update(
        ResultElement $resultElement,
        ?ElementInterface $element = null,
        bool $forceUpdate = false
    ): bool
    {
        $plugin = Plugin::getInstance();

        if ($resultElement->resultType !== 'page') {
            throw new InvalidArgumentException('Result element is not a page.');
        }

        $key = $resultElement->siteId . ':' . $resultElement->resultUrl;

        if (array_key_exists($key, static::$updatedPages)) {
            return static::$updatedPages[$key];
        }

        // Get absolute url
        $site = Craft::$app->getSites()->getSiteById(
            siteId: $resultElement->siteId,
            withDisabled: true,
        );

        $url = rtrim($site->getBaseUrl(), '/') . $resultElement->resultUrl;

        if (self::invalidResult($resultElement, $url)) {
            if ($resultElement->id !== null) {
                Craft::$app->getElements()->deleteElement(
                    $resultElement,
                    hardDelete: true
                );
            }

            // Don't add new result for invalid sites.
            static::$updatedPages[$key] = false;
            return static::$updatedPages[$key];
        }

        $dateTime = new DateTime();

        if (!$resultElement->rulesIgnore) {
            $resultElement->searchIgnore = false;
            $resultElement->dateUnavailableAfter = null;

            if ($plugin->isPro() &&
                $plugin->getSettings()->sitemapIgnoreRules
            ) {
                $resultElement->sitemapIgnore = false;
            }
        }

        if ($plugin->isPro() && $resultElement->id === null) {
            $resultElement->sitemapPriority = $plugin->getSettings()->sitemapDefaultPriority;
            $resultElement->sitemapChangefreq = $plugin->getSettings()->sitemapDefaultChangefreq;
            $resultElement->sitemapIgnore = $plugin->getSettings()->sitemapIgnoreNewPageUrls;
        }

        // Determine if search result should be soft ignored.
        if (!$resultElement->rulesIgnore &&
            UrlIgnorer::ignore(
                $resultElement->siteId,
                $url
            )
        ) {
            $resultElement->searchIgnore = true;

            if ($plugin->isPro() &&
                $plugin->getSettings()->sitemapIgnoreRules
            ) {
                $resultElement->sitemapIgnore = true;
            }
        }

        [$html, $headers, $statusCode] = static::getResponse($url);

        // Don't add new result if the page doesn't exists.
        if ($statusCode === 404 &&
            $resultElement->id === null &&
            !$plugin->getSettings()->track404s
        ) {
            static::$updatedPages[$key] = false;
            return static::$updatedPages[$key];
        }

        if ($statusCode !== 200 || $html === null) {
            $resultElement->error = true;
            $resultElement->errorCode = $statusCode;
            $resultElement->dateError = $dateTime;

            // Do not add new pages that are redirects or gone.
            if ($resultElement->id === null &&
                in_array($statusCode, [301, 302, 410])
            ) {
                static::$updatedPages[$key] = false;
                return static::$updatedPages[$key];
            }

            Craft::$app->getElements()->saveElement(
                $resultElement,
            );

            static::$updatedPages[$key] = false;
            return static::$updatedPages[$key];
        }

        if (!$resultElement->rulesIgnore) {
            $robotsMetaTag = new RobotsTag(
                $html,
                $headers,
                $plugin->getSettings()->robotsHttpHeader,
                $plugin->getSettings()->robotsMetaTag,
            );

            if ($robotsMetaTag->getNoIndex()) {
                $resultElement->searchIgnore = true;

                if ($plugin->isPro() &&
                    $plugin->getSettings()->sitemapIgnoreRules
                ) {
                    $resultElement->sitemapIgnore = true;
                }
            }

            $resultElement->dateUnavailableAfter = $robotsMetaTag->getUnavailableAfterDate();
        }

        $resultHash = md5($html);

        // Same content so nothing to change
        if (!$forceUpdate && $resultElement->resultHash === $resultHash) {
            static::$updatedPages[$key] = Craft::$app->getElements()->saveElement(
                $resultElement,
            );

            return static::$updatedPages[$key];
        }

        $resultElement->error = false;
        $resultElement->errorCode = null;
        $resultElement->dateError = null;

        $resultElement->resultHash = $resultHash;
        $resultElement->dateResultModified = $dateTime;

        $htmlPage = new HtmlPage(
            $resultElement->siteId,
            $html,
        );

        $resultElement->resultTitle = $htmlPage->getMetaTitle() ??
            $htmlPage->getTitle() ??
            Plugin::t('Untitled');

        $resultElement->resultDescription = $htmlPage->getMetaDescription() ??
            $htmlPage->getDescription();

        $mainData = $htmlPage->getMain();

        if (Event::hasHandlers(static::class, self::EVENT_UPDATE_MAIN_DATA)) {
            if ($element === null && $resultElement->resultId) {
                /** @var ElementInterface|null $element */
                $element = Craft::$app->elements->getElementById($resultElement->resultId);
            }

            $event = new IndexPageEvent([
                'element' => $element,
                'url' => $url,
                'mainData' => $mainData,
            ]);

            Event::trigger(static::class, self::EVENT_UPDATE_MAIN_DATA, $event);

            $mainData = trim($event->mainData ?? '');
            if ($mainData === '') {
                $mainData = null;
            }
        }

        if ($mainData === null) {
            $resultElement->mainHash = null;
            $resultElement->mainData = null;
            $resultElement->error = true;
            $resultElement->errorCode = null;
            $resultElement->dateError = $dateTime;

            Craft::warning(
                'Main element not found. (' . $url . ')',
                Plugin::HANDLE
            );
        } else {
            $mainHash = md5($mainData);

            if ($resultElement->mainHash !== $mainHash) {
                $resultElement->mainHash = $mainHash;
                $resultElement->mainData = $mainData;
                $resultElement->dateMainModified = $dateTime;
            }
        }

        static::$updatedPages[$key] = Craft::$app->getElements()->saveElement(
            $resultElement,
        );

        return static::$updatedPages[$key];
    }

    private static function invalidResult(ResultElement $resultElement, string $url): bool
    {
        // If url is ignored absolutely, delete element if it exists
        // and don't add a new one.
        if (!$resultElement->rulesIgnore &&
            UrlIgnorer::ignore(
                $resultElement->siteId,
                $url,
                absolute: true,
            )
        ) {
            return true;
        }

        if (!PluginHelper::isValidSite($resultElement->siteId)) {
            return true;
        }

        return false;
    }

    protected static function getResponse(string $url): array
    {
        $plugin = Plugin::getInstance();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: ' . $plugin->getSettings()->uaString
        ]);

        $headers = [];
        $statusCode = null;

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function (
            $ch,
            $hdata,
        ) use (
            &$headers,
            &$statusCode,
        ) {
            $data = trim($hdata);

            if ($data !== '') {
                if (str_starts_with(strtolower($data), 'http/')) {
                    $parts = explode(' ', $data);
                    $statusCode = intval($parts[1] ?? 0);
                } else {
                    $parts = explode(':', $data, 2);
                    $parts = array_map(trim(...), $parts);

                    $headers[$parts[0]][] = $parts[1] ?? '';
                }
            }

            return strlen($hdata);
        });

        $html = curl_exec($ch);

        curl_close($ch);

        if ($html === false) {
            $html = null;
        }

        return [$html, $headers, $statusCode];
    }
}
