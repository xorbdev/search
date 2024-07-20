<?php
namespace xorb\search\services;

use Craft;
use xorb\search\Plugin;
use yii\base\Component;

class Features extends Component
{
    public function isCpResults(): bool
    {
        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_RESULTS)) {
            return false;
        }

        return true;
    }

    public function isCpQueries(): bool
    {
        $plugin = Plugin::getInstance();

        if (!$plugin->getSettings()->trackQueries) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_QUERIES)) {
            return false;
        }

        return true;
    }

    public function isCpTerms(): bool
    {
        return ($this->isCpTermMaps() && $this->isCpTermPriorities());
    }

    public function isCpTermMaps(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if (!$plugin->getSettings()->enableTermMaps) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_TERM_MAPS)) {
            return false;
        }

        return true;
    }

    public function isCpTermPriorities(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if (!$plugin->getSettings()->enableTermPriorities) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_TERM_PRIORITIES)) {
            return false;
        }

        return true;
    }

    public function isCpRules(): bool
    {
        return ($this->isCpIgnoreRules() && $this->isCpQueryParamRules());
    }

    public function isCpIgnoreRules(): bool
    {
        $plugin = Plugin::getInstance();

        if (!$plugin->getSettings()->enableIgnoreRules) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_IGNORE_RULES)) {
            return false;
        }

        return true;
    }

    public function isCpQueryParamRules(): bool
    {
        $plugin = Plugin::getInstance();

        if (!$plugin->getSettings()->enableQueryParamRules) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_QUERY_PARAM_RULES)) {
            return false;
        }

        return true;
    }

    public function isCpRedirects(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if (!$plugin->getSettings()->enableRedirects) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_REDIRECTS)) {
            return false;
        }

        return true;
    }

    public function isSettingsTerms(): bool
    {
        return (
            $this->isSettingsTermMaps() &&
            $this->isSettingsTermPriorities()
        );
    }
    public function isSettingsTermMaps(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if ($plugin->getSettings()->enableTermMaps) {
            return false;
        }

        if (!$this->showCpSettingsNavItem()) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_TERM_MAPS)) {
            return false;
        }

        return true;
    }

    public function isSettingsTermPriorities(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if ($plugin->getSettings()->enableTermPriorities) {
            return false;
        }

        if (!$this->showCpSettingsNavItem()) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_TERM_PRIORITIES)) {
            return false;
        }

        return true;
    }

    public function isSettingsRules(): bool
    {
        return (
            $this->isSettingsIgnoreRules() &&
            $this->isSettingsQueryParamRules()
        );
    }

    public function isSettingsIgnoreRules(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableIgnoreRules) {
            return false;
        }

        if (!$this->showCpSettingsNavItem()) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_IGNORE_RULES)) {
            return false;
        }

        return true;
    }

    public function isSettingsQueryParamRules(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableQueryParamRules) {
            return false;
        }

        if (!$this->showCpSettingsNavItem()) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_QUERY_PARAM_RULES)) {
            return false;
        }

        return true;
    }

    public function isSettingsRedirects(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if ($plugin->getSettings()->enableRedirects) {
            return false;
        }

        if (!$this->showCpSettingsNavItem()) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_REDIRECTS)) {
            return false;
        }

        return true;
    }

    public function showCpSettingsNavItem(): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return false;
        }

        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_SETTINGS)) {
            return false;
        }

        return true;
    }

    public function showSitemap(): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->isLite()) {
            return false;
        }

        if (!$plugin->getSettings()->enableSitemap) {
            return false;
        }

        return true;
    }
}
