<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\models\TermMap as TermMapModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TermMapsController extends Controller
{
    public function init(): void
    {
        $plugin = Plugin::getInstance();

        parent::init();

        $this->requireCpRequest();

        if (!$plugin->getSettings()->enableTermMaps) {
            $this->requirePermission(Plugin::PERMISSION_SETTINGS);
        }
        $this->requirePermission(Plugin::PERMISSION_VIEW_TERM_MAPS);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/_terms/maps/index',
            [
                'items' => $plugin->getTermMaps()->getAllTermMaps(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?TermMapModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getTermMaps()->getTermMapById($id);

            if (!$item) {
                throw new NotFoundHttpException(Plugin::t('Search term map not found.'));
            }
        } elseif ($item === null) {
            $item = new TermMapModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/_terms/maps/item',
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
        $data = $plugin->getTermMaps()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getTermMaps()->getTermMapById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Search term map not found.');
            }
        } else {
            $item = new TermMapModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getTermMaps()->saveTermMap($item)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save search term map.'));

            return null;
        }

        $this->setSuccessFlash(Plugin::t('Search term map saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getTermMaps()->deleteTermMapById($id);

        return $this->asSuccess(Plugin::t('Search term map deleted.'));
    }
}
