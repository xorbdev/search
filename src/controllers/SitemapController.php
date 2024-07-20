<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use xorb\search\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SitemapController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireSiteRequest();
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

		$response = Craft::$app->getResponse();
		$response->format = Response::FORMAT_XML;
        $response->content = $plugin->getSitemap()->index();

        return $response;
    }

    public function actionPage(int $page): Response
    {
        $plugin = Plugin::getInstance();

        $sitemap = Plugin::getInstance()->getSitemap();
        $pageCount = $sitemap->getPageCount();

        if ($pageCount === 1 || $page > $pageCount) {
            throw new NotFoundHttpException(Plugin::t('Sitemap page not found.'));
        }

		$response = Craft::$app->getResponse();
		$response->format = Response::FORMAT_XML;
        $response->content = $plugin->getSitemap()->page($page);

        return $response;
    }
}
