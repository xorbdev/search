<?php
namespace xorb\search\utilities;

use Craft;
use craft\base\Utility;
use xorb\search\Plugin;

class SearchIndex extends Utility
{
    public static function displayName(): string
    {
        return Plugin::t('Search');
    }

    public static function id(): string
    {
        return Plugin::HANDLE . '-index';
    }

    public static function icon(): ?string
    {
        return 'magnifying-glass';
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(Plugin::HANDLE . '/_utilities/index');
    }
}
