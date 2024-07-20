<?php
namespace xorb\search\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\Search as SearchHelper;
use craft\helpers\StringHelper;
use xorb\search\db\Table;
use xorb\search\helpers\PluginHelper;
use xorb\search\models\TermPriority as TermPriorityModel;
use xorb\search\Plugin;
use xorb\search\records\TermPriority as TermPriorityRecord;
use yii\base\Component;

use const SORT_ASC;

class TermPriorities extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.termPriorities';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new TermPriorityModel($result);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }
    private function createItemsQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'term',
                'normalizedTerm',
                'resultUrlValue',
                'resultUrlComparator',
                'searchPriority',
                'uid',
            ])
            ->from([Table::TERM_PRIORITIES])
            ->orderBy(['term' => SORT_ASC]);

        return $query;
    }

    public function hasTermPriorities(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllTermPriorities(): array
    {
        return $this->items()->all();
    }

    public function getTermPriorityById(int $id): ?TermPriorityModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getExistingTermPriority(TermPriorityModel $model): ?TermPriorityModel
    {
        return $this->items()
            ->where('siteId', $model->siteId)
            ->where('term', $model->resultUrlValue)
            ->where('resultUrlValue', $model->resultUrlValue)
            ->firstWhere('resultUrlComparator', $model->resultUrlComparator);
    }

    public function deleteTermPriorityById(int $id): bool
    {
        $model = $this->getTermPriorityById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteTermPriority($model);

    }
    public function deleteTermPriority(TermPriorityModel $model): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableTermPriorities) {
            $this->deleteRecord($model->uid);
        } else {
            Craft::$app->getProjectConfig()->remove(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid
            );
        }

        return true;
    }

    public function saveTermPriority(TermPriorityModel $model, bool $runValidation = true): bool
    {
        $plugin = Plugin::getInstance();

        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Search term priority not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingTermPriority = $this->getExistingTermPriority($model);
        if ($existingTermPriority && $existingTermPriority->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching search term priority already exists.'));
            Craft::info('Search term priority not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(Table::TERM_PRIORITIES, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(Table::TERM_PRIORITIES, $model->id);
        }

        if ($plugin->getSettings()->enableTermPriorities) {
            $this->saveRecord($model->uid, $model->getConfig());
        } else {
            Craft::$app->getProjectConfig()->set(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid,
                $model->getConfig()
            );
        }

        if ($isNew) {
            $model->id = Db::idByUid(Table::TERM_PRIORITIES, $model->uid);
        }

        return true;
    }

    public function handleChanged(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $this->saveRecord($uid, $data);

        // Clear caches
        $this->items = null;
    }

    private function saveRecord(string $uid, array $data): void
    {
        $data = $this->typecastData($data);

        $record = $this->getRecord($uid);

        $record->siteId = $data['siteId'];
        $record->term = $data['term'];
        $record->normalizedTerm = $data['normalizedTerm'];
        $record->resultUrlValue = $data['resultUrlValue'];
        $record->resultUrlComparator = $data['resultUrlComparator'];
        $record->searchPriority = $data['searchPriority'];
        $record->uid = $uid;

        $record->save(false);
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $this->deleteRecord($uid);

        $this->items = null;
    }
    private function deleteRecord(string $uid): void
    {
        $record = $this->getRecord($uid);

        if ($record->getIsNewRecord()) {
            return;
        }

        $record->delete();
    }

    private function getRecord(int|string $criteria): TermPriorityRecord
    {
        $query = TermPriorityRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @var ?TermPriorityRecord **/
        $record = $query->one();

        return $record ?? new TermPriorityRecord();
    }

    public function typecastData(array $data): array
    {
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ?: null);

        $data['term'] = $data['term'] ?? '';

        $data['resultUrlValue'] = $data['resultUrlValue'] ?? '';
        $data['resultUrlComparator'] = $data['resultUrlComparator'] ?? '';

        $language = PluginHelper::getSiteLanguage($data['siteId']);

        $data['normalizedTerm'] = SearchHelper::normalizeKeywords(
            str: $data['term'],
            ignore: [],
            processCharMap: ($language !== null),
            language: $language
        );

        return $data;
    }
}
