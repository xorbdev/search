<?php
namespace xorb\search\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\controllers\ElementsController;
use xorb\search\Plugin;
use yii\web\Response;

class ResultsController extends ElementsController
{
    public function actionIndex(): Response
    {
        $this->requireCpRequest();

        $this->requirePermission(Plugin::PERMISSION_VIEW_RESULTS);

        return $this->renderTemplate(Plugin::HANDLE . '/_results/index');
    }

    public function actionEdit(?ElementInterface $element, ?int $elementId = null): Response
    {
        $response = parent::actionEdit($element, $elementId);

        return $response
            ->selectedSubnavItem('results');
    }
}
