<?php
namespace xorb\search\helpers;

use Craft;
use craft\helpers\UrlHelper;
use xorb\search\Plugin;
use xorb\search\helpers\UrlComparer;
use xorb\search\helpers\PluginHelper;

class UrlIgnorer
{
    private static array $robotsTxt = [];

    private function __construct()
    {}

    public static function ignore(
        int $siteId,
        string $url,
        bool $absolute = false
    ): bool
    {
        $url = UrlHelper::rootRelativeUrl($url);

        if (static::matchIgnoreRules($siteId, $url, $absolute)) {
            return true;
        }

        if (static::matchRedirects($siteId, $url)) {
            return true;
        }

        if (!$absolute) {
            if (static::matchRobotsTxt($siteId, $url)) {
                return true;
            }
        }

        return false;
    }

    protected static function matchIgnoreRules(
        int $siteId,
        string $url,
        bool $absolute,
    ): bool
    {
        $plugin = Plugin::getInstance();

        $ignoreRules = $plugin->getIgnoreRules();

        foreach ($ignoreRules->getAllIgnoreRules() as $rule) {
            if ($rule->siteId !== null && $rule->siteId !== $siteId) {
                continue;
            }

            if ($absolute && !$rule->absolute) {
                continue;
            }

            if (UrlComparer::matchUrl(
                $url,
                $rule->resultUrlValue,
                $rule->resultUrlComparator
            )) {
                return true;
            }
        }

        return false;
    }

    protected static function matchRedirects(int $siteId, string $url): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        // Check if it matches redirect
        $urlNoQuery = explode('?', $url)[0];

        $redirects = $plugin->getRedirects();

        foreach ($redirects->getAllRedirects() as $redirect) {
            if ($redirect->siteId !== null && $redirect->siteId !== $siteId) {
                continue;
            }

            if (UrlComparer::matchUrl(
                ($redirect->ignoreQueryParams ? $urlNoQuery : $url),
                $redirect->fromUrl,
                ($redirect->regex ? 'regex' : 'exact')
            )) {
                return true;
            }
        }

        return false;
    }

    protected static function matchRobotsTxt(int $siteId, string $url): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        $robotsTxt = self::getRobotsTxt($siteId);

        if ($robotsTxt === null) {
            return false;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        $path = '/' . trim($path, '/');

        return !$robotsTxt->isAllowed($path);
    }

    private static function getRobotsTxt(int $siteId): ?RobotsTxt
    {
        if (array_key_exists($siteId, self::$robotsTxt)) {
            return self::$robotsTxt[$siteId];
        }

        $plugin = Plugin::getInstance();

        if (!$plugin->getSettings()->robotsTxt) {
            self::$robotsTxt[$siteId] = null;
            return self::$robotsTxt[$siteId];
        }

        if (!PluginHelper::isValidSite($siteId)) {
            self::$robotsTxt[$siteId] = null;
            return self::$robotsTxt[$siteId];
        }

        $site = Craft::$app->getSites()->getSiteById(
            siteId: $siteId,
            withDisabled: true,
        );

        if ($site === null) {
            return null;
        }

        self::$robotsTxt[$siteId] = new RobotsTxt(
            $site->getBaseUrl() . 'robots.txt',
            $plugin->getSettings()->robotsUaString,
        );

        return self::$robotsTxt[$siteId];
    }
}
