<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use xorb\search\models\Redirect as RedirectModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class RedirectsController extends Controller
{
    public function init(): void
    {
        $plugin = Plugin::getInstance();

        parent::init();

        $this->requireCpRequest();

        if (!$plugin->getSettings()->enableRedirects) {
            $this->requirePermission(Plugin::PERMISSION_SETTINGS);
        }
        $this->requirePermission(Plugin::PERMISSION_VIEW_REDIRECTS);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/_redirects/index',
            [
                'items' => $plugin->getRedirects()->getAllRedirects(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?RedirectModel $item = null): Response
    {
        $plugin = Plugin::getInstance();

        if ($id) {
            $item = $plugin->getRedirects()->getRedirectById($id);

            if (!$item) {
                throw new NotFoundHttpException(Plugin::t('Redirect not found.'));
            }
        } elseif ($item === null) {
            $item = new RedirectModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/_redirects/item',
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
        $data = $plugin->getRedirects()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getRedirects()->getRedirectById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Redirect not found.');
            }
        } else {
            $item = new RedirectModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getRedirects()->saveRedirect($item)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            $this->setFailFlash(Plugin::t('Couldn’t save redirect.'));

            return null;
        }

        $this->setSuccessFlash(Plugin::t('Redirect saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getRedirects()->deleteRedirectById($id);

        return $this->asSuccess(Plugin::t('Redirect deleted.'));
    }
}
