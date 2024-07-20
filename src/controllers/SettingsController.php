<?php
namespace xorb\search\controllers;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use xorb\search\jobs\UpdateProjectConfig;
use xorb\search\Plugin;
use yii\web\HttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();

        $this->requirePermission(Plugin::PERMISSION_SETTINGS);
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/index'
        );
    }

    public function actionItem(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/_general/item',
            ['item' => $plugin->getSettings()]
        );
    }

    public function actionSave(): ?Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();

        $data = Craft::$app->getRequest()->getParam('data');

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $data)) {
            $this->setFailFlash(Plugin::t('Couldn’t save settings.'));

            return null;
        }

        Queue::push(new UpdateProjectConfig());

        $this->setSuccessFlash(Plugin::t('Settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
