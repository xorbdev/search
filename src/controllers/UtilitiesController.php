<?php
namespace xorb\search\controllers;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use craft\models\Site;
use xorb\search\Plugin;
use xorb\search\jobs\AddUrl;
use xorb\search\jobs\MakeAssetsSearchable;
use xorb\search\jobs\UpdateResults;
use yii\web\Response;

class UtilitiesController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requirePermission('utility:search-index');
    }

    public function actionUpdate(): ?Response
    {
        $forceUpdate = Craft::$app->getRequest()->getParam('forceUpdate');

        Queue::push(new UpdateResults([
            'forceUpdatePages' => !!$forceUpdate,
            'forceUpdateAssets' => !!$forceUpdate,
        ]));

        return $this->asSuccess('Update results queued.');
    }

    public function actionAddUrl(): ?Response
    {
        $url = Craft::$app->getRequest()->getParam('uri');
        if (!$url) {
            return $this->asFailure(Plugin::t('URI not specified.'));
        }

        Queue::push(new AddUrl([
            'url' => $url
        ]));

        return $this->asSuccess('URI queued for indexing.');
    }

    public function actionMakeAssetsSearchable(): ?Response
    {
        $siteIds = Craft::$app->getRequest()->getParam('sites');
        $volumeIds = Craft::$app->getRequest()->getParam('volumes');
        $fileKinds = Craft::$app->getRequest()->getParam('fileKinds');

        if ($siteIds === '*') {
            $siteIds = array_map(
                fn(Site $site) => $site->id,
                Craft::$app->getSites()->getAllSites(true),
            );
        }

        if ($volumeIds === '*') {
            $volumeIds = Craft::$app->getVolumes()->getAllVolumeIds();
        }

        Queue::push(new MakeAssetsSearchable([
            'siteIds' => $siteIds,
            'volumeIds' => $volumeIds,
            'fileKinds' => $fileKinds,
        ]));

        return $this->asSuccess('Make assets searchable queued.');
    }
}
