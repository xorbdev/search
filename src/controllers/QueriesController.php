<?php
namespace xorb\search\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\AdminTable as AdminTableHelper;
use craft\i18n\Locale;
use xorb\search\Plugin;
use yii\web\Response;

class QueriesController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();

        $this->requirePermission(Plugin::PERMISSION_VIEW_QUERY_PARAM_RULES);
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            Plugin::HANDLE . '/_queries/index',
            []
        );
    }

    public function actionItems(int $page, int $per_page, ?string $site = null): Response
    {
        $plugin = Plugin::getInstance();

        if ($site === null) {
            $site = Craft::$app->getSites()->getPrimarySite();
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
        }

        [$data, $total] = $plugin->getQueries()->getQueries(
            $site->id,
            $page,
            $per_page
        );

        $pagination = AdminTableHelper::paginationLinks(
            $page, $total, $per_page
        );

        foreach ($data as $key => $value) {
            $value['title'] = $value['query'];

            $value['dateQuery'] = Craft::$app->formatter->asDatetime(
                $value['dateQuery'],
                'short'
            );

            unset($value['query']);

            $data[$key] = $value;
        }

        return $this->asJson([
            'pagination' => $pagination,
            'data' => $data
        ]);
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getQueries()->deleteQueryById($id);

        return $this->asSuccess(Plugin::t('Query deleted.'));
    }

    public function actionClear(?string $site = null): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();

        if ($site === null) {
            $site = Craft::$app->getSites()->getPrimarySite();
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($site);
        }

        $plugin->getQueries()->deleteQueriesBySiteId($site->id);

        return $this->asSuccess(Plugin::t('Queries deleted.'));
    }
}
