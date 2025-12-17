<?php
namespace xorb\search\helpers;

use Craft;
use craft\elements\Asset;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\Search as SearchHelper;
use craft\helpers\UrlHelper;
use Exception;
use DateTime;
use Smalot\PdfParser\Parser as PdfParser;
use xorb\search\events\IndexAssetEvent;
use xorb\search\elements\Result as ResultElement;
use xorb\search\Plugin;
use yii\base\Event;
use yii\base\InvalidArgumentException;

class AssetResult
{
    protected static $updatedAssets = [];

    public const EVENT_UPDATE_MAIN_DATA = 'eventUpdateAssetMainData';

    private function __construct()
    {}

    public static function addAsset(
        int $siteId,
        Asset $asset,
    ): bool
    {
        $site = Craft::$app->getSites()->getSiteById(
            siteId: $siteId,
            withDisabled: true,
        );

        $url = $asset->getUrl();

        if ($url === null) {
            return false;
        }

        if (str_starts_with($url, $site->getBaseUrl())) {
            $url = UrlHelper::rootRelativeUrl($url);
        }

        $resultElement = self::getResultElement($siteId, $asset, $url);

        // If null then an existing asset.
        if ($resultElement === null) {
            return false;
        }

        return static::update($resultElement, $asset, true);
    }

    private static function getResultElement(
        int $siteId,
        Asset $asset,
        string $url
    ): ?ResultElement
    {
        $plugin = Plugin::getInstance();

        // Existing
        /** @var ?ResultElement **/
        $resultElement = ResultElement::find()
            ->siteId($siteId)
            ->resultType('asset')
            ->resultId($asset->id)
            ->one();

        if ($resultElement) {
            if ($resultElement->resultUrl === $url) {
                return null;
            }

            $resultElement->resultUrl = $url;
            return $resultElement;
        }

        // Trashed
        /** @var ?ResultElement **/
        $resultElement = ResultElement::find()
            ->siteId($siteId)
            ->resultType('asset')
            ->resultId($asset->id)
            ->trashed(true)
            ->one();

        if ($resultElement !== null) {
            Craft::$app->getElements()->restoreElement($resultElement);
            $resultElement->resultUrl = $url;

            return $resultElement;
        }

        // New
        $resultElement = new ResultElement();
        $resultElement->siteId = $siteId;
        $resultElement->resultType = 'asset';
        $resultElement->resultId = $asset->id;
        $resultElement->resultUrl = $url;

        return $resultElement;
    }

    public static function update(
        ResultElement $resultElement,
        ?Asset $asset = null,
        bool $forceUpdate = false,
    ): bool
    {
        $plugin = Plugin::getInstance();

        if ($resultElement->resultType !== 'asset') {
            throw new InvalidArgumentException('Result element is not an asset.');
        }

        $key = $resultElement->siteId . ':' . $resultElement->resultId;

        if (array_key_exists($key, static::$updatedAssets)) {
            return static::$updatedAssets[$key];
        }

        if ($asset === null) {
            /** @var Asset|null $asset */
            $asset = Asset::find()->id($resultElement->resultId)->one();
        }

        if (self::invalidResult($resultElement, $asset)) {
            if ($resultElement->id !== null) {
                Craft::$app->getElements()->deleteElement(
                    $resultElement,
                    hardDelete: true
                );
            }

            static::$updatedAssets[$key] = false;
            return static::$updatedAssets[$key];
        }

        $dateTime = new DateTime();

        if ($plugin->isPro() && $resultElement->id === null) {
            $resultElement->sitemapPriority = $plugin->getSettings()->sitemapDefaultPriority;
            $resultElement->sitemapChangefreq = $plugin->getSettings()->sitemapDefaultChangefreq;
            $resultElement->sitemapIgnore = $plugin->getSettings()->sitemapIgnoreNewAssetUrls;
        }

        $resultElement->resultTitle = $asset->title;
        $resultElement->resultDescription = $asset->alt ?? null;

        $resultElement->error = false;
        $resultElement->errorCode = null;
        $resultElement->dateError = null;

        try {
            $resultHash = hash_file('md5', $asset->getUrl());
        } catch(Exception $e) {
            $resultHash =  null;
        }

        if ($forceUpdate ||
            $resultElement->id === null ||
            $resultElement->resultHash !== $resultHash
        ) {
            $resultElement->resultHash = $resultHash;
            $resultElement->dateResultModified = $dateTime;

            $mainData = $asset->title;

            if ($asset->alt ?? null) {
                $mainData .= ' ' . $asset->alt;
            }

            if ($asset->kind === 'pdf') {
                try {
                    $pdf = new PdfParser();
                    $pdf = $pdf->parseFile($asset->getUrl());

                    $mainData .= ' ' . $pdf->getText();
                } catch(Exception $e) {
                    // Do nothing
                }
            }

            $mainData = static::cleanMainData(
                $resultElement->siteId,
                $mainData
            );

            if ($mainData === '') {
                $mainData = null;
            }

            if (Event::hasHandlers(static::class, self::EVENT_UPDATE_MAIN_DATA)) {
                $event = new IndexAssetEvent([
                    'asset' => $asset,
                    'mainData' => $mainData,
                ]);

                Event::trigger(static::class, self::EVENT_UPDATE_MAIN_DATA, $event);

                $mainData = trim($event->mainData ?? '');
                if ($mainData === '') {
                    $mainData = null;
                }
            }

            $mainHash = md5($mainData ?? '');

            if ($resultElement->mainHash !== $mainHash ||
                ($resultElement->mainData === null and $mainData !== null)
            ) {
                $resultElement->mainHash = $mainHash;
                $resultElement->mainData = $mainData;
                $resultElement->dateMainModified = $dateTime;
            }
        }

        static::$updatedAssets[$key] = Craft::$app->getElements()->saveElement(
            $resultElement,
        );

        return static::$updatedAssets[$key];
    }

    private static function invalidResult(ResultElement $resultElement, ?Asset $asset): bool
    {
        if ($asset === null) {
            return true;
        }

        if (!static::isSearchable($resultElement->siteId, $asset)) {
            return true;
        }

        return false;
    }

    public static function isSearchable(int $siteId, Asset $asset): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        $settings = $plugin->getSettings();

        if ($settings->searchableAssetFieldHandle === '') {
            return false;
        }

        $fieldLayout = $asset->getFieldLayout();

        if ($fieldLayout === null ||
            $fieldLayout->getFieldByHandle($settings->searchableAssetFieldHandle) === null
        ) {
            return false;
        }

        $value = $asset->getFieldValue($settings->searchableAssetFieldHandle);

        if ($value instanceof MultiOptionsFieldData) {
            return $value->contains($siteId);
        }

        if (is_array($value)) {
            return in_array($siteId, $value);
        }

        if ($value) {
            return true;
        }

        return false;
    }

    protected static function cleanMainData(int $siteId, string $text): string
    {
        $site = Craft::$app->getSites()->getSiteById($siteId);

        $text = str_replace("\t", ' ', $text);

        // Remove invalid utf8 multibyte sequences since
        // StringHelper::replaceMb4 will error out if encountered.
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($text === false) {
            $text = '';
        }

        return SearchHelper::normalizeKeywords(
            str: $text,
            ignore: [],
            processCharMap: true,
            language: $site?->language
        );
    }
}
