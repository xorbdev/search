<?php
namespace xorb\search\controllers;

use craft\web\Controller;
use xorb\search\Plugin;
use yii\web\Response;

class TermsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();

        $this->requirePermission(Plugin::PERMISSION_VIEW_TERM_MAPS);
        $this->requirePermission(Plugin::PERMISSION_VIEW_TERM_PRIORITIES);
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            Plugin::HANDLE . '/_terms/index',
        );
    }
}
