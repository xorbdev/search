<?php
namespace xorb\search\helpers;

use Craft;
use craft\helpers\UrlHelper;
use xorb\search\Plugin;

class PluginHelper
{
    private function __construct()
    {}

    /**
     * Determines if the specified site id is valid.
     *
     * A valid site is one that exists and has a single absolute base url
     * associated with it.
     */
    public static function isValidSite(int $siteId): bool
    {
        $site = Craft::$app->getSites()->getSiteById(
            siteId: $siteId,
            withDisabled: true,
        );

        if ($site === null) {
            return false;
        }

        $baseUrl = $site->getBaseUrl(parse: false);

        if ($baseUrl === null ||
            $baseUrl === '' ||
            str_contains($baseUrl, '@web')
        ) {
            return false;
        }

        $baseUrl = $site->getBaseUrl(parse: true);

        if (!UrlHelper::isAbsoluteUrl($baseUrl)) {
            return false;
        }

        return true;
    }

    public static function trackHit(): bool
    {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        if (!$settings->trackHits) {
            return false;
        }

        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            return false;
        }

        if (Craft::$app->getRequest()->getIsLivePreview()) {
            return false;
        }

        if (Craft::$app->getRequest()->getIsActionRequest()) {
            return false;
        }

        if (Craft::$app->getRequest()->getUserAgent() === $settings->uaString) {
            return false;
        }

        $url = Craft::$app->getRequest()->getUrl();

        if ($url === '/robots.txt') {
            return false;
        }

        if ($plugin->isPro()) {
            if ($url === '/' . $settings->sitemapName . '.xml') {
                return false;
            }
        }

        return true;
    }

    public static function getSiteLanguage(?int $siteId): ?string
    {
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId, true);
            return $site->language;
        }

        $language = null;

        // If all sites use the same language, use it.
        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            if ($language === null) {
                $language = $site->language;
                continue;
            }

            if ($language !== $site->language) {
                $language = null;
                break;
            }
        }

        return $language;
    }
}
