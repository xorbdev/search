<?php
namespace xorb\search\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Html;
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
        $sites = Craft::$app->getSites()->getAllSites(true);
        $siteOptions = [];

        foreach ($sites as $site) {
            $siteOptions[] = [
                'label' => $site->getUiLabel(),
                'value' => $site->id,
            ];
        }

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        $volumeOptions = [];

        foreach ($volumes as $volume) {
            $volumeOptions[] = [
                'label' => Html::encode($volume->name),
                'value' => $volume->id,
            ];
        }

        $fileKindOptions = [];

        foreach (AssetsHelper::getAllowedFileKinds() as $value => $kind) {
            $fileKindOptions[] = [
                'label' => $kind['label'],
                'value' => $value,
            ];
        }

        $view = Craft::$app->getView();

        return $view->renderTemplate(Plugin::HANDLE . '/_utilities/index',[
            'siteOptions' => $siteOptions,
            'volumeOptions' => $volumeOptions,
            'fileKindOptions' => $fileKindOptions,
        ]);
    }
}
