<?php
namespace xorb\search\controllers;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\jobs\UpdateResults;
use xorb\search\jobs\AddUrl;
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
        Queue::push(new UpdateResults());

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
}
