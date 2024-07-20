<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\models\QueryParamRule as QueryParamRuleModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class QueryParamRulesController extends Controller
{
    public function init(): void
    {
        $plugin = Plugin::getInstance();

        parent::init();

        $this->requireCpRequest();

        if (!$plugin->getSettings()->enableQueryParamRules) {
            $this->requirePermission(Plugin::PERMISSION_SETTINGS);
        }
        $this->requirePermission(Plugin::PERMISSION_VIEW_QUERY_PARAM_RULES);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/_rules/query-param-rules/index',
            [
                'items' => $plugin->getQueryParamRules()->getAllQueryParamRules(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?QueryParamRuleModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getQueryParamRules()->getQueryParamRuleById($id);

            if (!$item) {
                throw new NotFoundHttpException(Plugin::t('Query param rule not found.'));
            }
        } elseif ($item === null) {
            $item = new QueryParamRuleModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/_rules/query-param-rules/item',
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
        $data = $plugin->getQueryParamRules()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getQueryParamRules()->getQueryParamRuleById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Query param rule not found.');
            }
        } else {
            $item = new QueryParamRuleModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getQueryParamRules()->saveQueryParamRule($item)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save query param rule.'));

            return null;
        }

        $this->setSuccessFlash(Plugin::t('Query param rule saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getQueryParamRules()->deleteQueryParamRuleById($id);

        return $this->asSuccess(Plugin::t('Query param rule deleted.'));
    }
}
