<?php
namespace xorb\search\services;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\FieldLayout;
use xorb\search\db\Table;
use xorb\search\elements\Result as ResultElement;
use yii\base\Component;

class Results extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.results';

    public function getResultById(int $resultId): ?ResultElement
    {
        /** @var ?ResultElement **/
        $element = ResultElement::find()
            ->id($resultId)
            ->one();

        return $element;
    }

    public function deleteResultById(int $resultId): bool
    {
        $resultElement = $this->getResultById($resultId);

        if ($resultElement === null) {
            return false;
        }

        return $this->deleteResult($resultElement);
    }
    public function deleteResult(ResultElement $resultElement): bool
    {
        return Craft::$app->getElements()->deleteElement($resultElement, true);
    }

    public function saveResult(ResultElement $resultElement, bool $runValidation = true): bool
    {
        return Craft::$app->getElements()->saveElement($resultElement, $runValidation);
    }

    public function getResultCount(): int
    {
        return (new Query())
            ->from(Table::RESULTS)
            ->count();
    }

    public function handleChangedFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllFieldsProcessed();
        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty(reset($data))) {
            // Delete the field layout
            $fieldsService->deleteLayoutsByType(ResultElement::class);
            return;
        }

        // Save the field layout
        $layout = FieldLayout::createFromConfig(reset($data));

        $layout->id = $fieldsService->getLayoutByType(ResultElement::class)->id;
        $layout->type = ResultElement::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout, false);
    }

    public function handleDeletedFieldLayout(): void
    {
        Craft::$app->getFields()->deleteLayoutsByType(ResultElement::class);
    }
}
