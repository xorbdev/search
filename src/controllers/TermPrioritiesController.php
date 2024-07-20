<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\models\TermPriority as TermPriorityModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TermPrioritiesController extends Controller
{
    public function init(): void
    {
        $plugin = Plugin::getInstance();

        parent::init();

        $this->requireCpRequest();

        if (!$plugin->getSettings()->enableTermPriorities) {
            $this->requirePermission(Plugin::PERMISSION_SETTINGS);
        }
        $this->requirePermission(Plugin::PERMISSION_VIEW_TERM_PRIORITIES);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/_terms/priorities/index',
            [
                'items' => $plugin->getTermPriorities()->getAllTermPriorities(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?TermPriorityModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getTermPriorities()->getTermPriorityById($id);

            if (!$item) {
                throw new NotFoundHttpException(Plugin::t('Search term priority not found.'));
            }
        } elseif ($item === null) {
            $item = new TermPriorityModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/_terms/priorities/item',
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
        $data = $plugin->getTermPriorities()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getTermPriorities()->getTermPriorityById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Search term priority not found.');
            }
        } else {
            $item = new TermPriorityModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getTermPriorities()->saveTermPriority($item)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save search term priority.'));

            return null;
        }

        $this->setSuccessFlash(Plugin::t('Search term priority saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getTermPriorities()->deleteTermPriorityById($id);

        return $this->asSuccess(Plugin::t('Search term priority deleted.'));
    }
}
