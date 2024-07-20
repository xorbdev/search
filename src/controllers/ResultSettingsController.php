<?php
namespace xorb\search\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\elements\Result as ResultElement;
use xorb\search\services\Results;
use yii\web\HttpException;
use yii\web\Response;

class ResultSettingsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();

        $this->requirePermission(Plugin::PERMISSION_SETTINGS);
    }

    public function actionItem(?FieldLayout $fieldLayout = null): Response
    {
        if ($fieldLayout === null) {
            $fieldLayout = Craft::$app->getFields()->getLayoutByType(ResultElement::class);

            // Override default name of field layout tabs not set up yet.
            $tabs = $fieldLayout->getTabs();
            if (count($tabs) === 1 && $tabs[0]->id === null) {
                $tabs[0]->name = Plugin::t('Details');
            }
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/_results/item',
            ['fieldLayout' => $fieldLayout]
        );
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        /* $fieldLayout->reservedFieldHandles = [
            '',
        ]; */

        if (!$fieldLayout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);

            Craft::$app->getUrlManager()->setRouteParams([
                'fieldLayout' => $fieldLayout,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save result fields.'));

            return null;
        }

        $currentFieldLayout = Craft::$app->getProjectConfig()->get(
            Results::PROJECT_CONFIG_PATH . '.fieldLayout'
        );

        if ($currentFieldLayout) {
            $uid = ArrayHelper::firstKey($currentFieldLayout);
        } else {
            $uid = StringHelper::UUID();
        }

        Craft::$app->getProjectConfig()->set(
            Results::PROJECT_CONFIG_PATH . '.fieldLayout',
            [$uid => $fieldLayout->getConfig()]
        );

        $this->setSuccessFlash(Plugin::t('Result fields saved.'));

        return $this->redirectToPostedUrl();
    }
}
