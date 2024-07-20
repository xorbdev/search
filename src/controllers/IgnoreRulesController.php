<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\models\IgnoreRule as IgnoreRuleModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class IgnoreRulesController extends Controller
{
    public function init(): void
    {
        $plugin = Plugin::getInstance();

        parent::init();

        $this->requireCpRequest();

        if (!$plugin->getSettings()->enableIgnoreRules) {
            $this->requirePermission(Plugin::PERMISSION_SETTINGS);
        }
        $this->requirePermission(Plugin::PERMISSION_VIEW_IGNORE_RULES);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/_rules/ignore-rules/index',
            [
                'items' => $plugin->getIgnoreRules()->getAllIgnoreRules(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?IgnoreRuleModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getIgnoreRules()->getIgnoreRuleById($id);

            if (!$item) {
                throw new NotFoundHttpException(Plugin::t('Ignore rule not found.'));
            }
        } elseif ($item === null) {
            $item = new IgnoreRuleModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/_rules/ignore-rules/item',
            [
                'id' => $id,
                'item' => $item,
            ]
        );
    }

    public function actionSave(): ?Response
    {
        $plugin = Plugin::getInstance();

    	$this->requirePostRequest();

        $data = Craft::$app->getRequest()->getParam('data');
        $data = $plugin->getIgnoreRules()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getIgnoreRules()->getIgnoreRuleById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Ignore rule not found.');
            }
        } else {
            $item = new IgnoreRuleModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getIgnoreRules()->saveIgnoreRule($item)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save ignore rule.'));

            return null;
        }

        $this->setSuccessFlash(Plugin::t('Ignore rule saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getIgnoreRules()->deleteIgnoreRuleById($id);

        return $this->asSuccess(Plugin::t('Ignore rule deleted.'));
    }
}
