<?php
namespace xorb\search\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\Search as SearchHelper;
use xorb\search\db\Table;
use xorb\search\helpers\PluginHelper;
use xorb\search\models\TermMap as TermMapModel;
use xorb\search\Plugin;
use xorb\search\records\TermMap as TermMapRecord;
use yii\base\Component;

use const SORT_ASC;

class TermMaps extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.termMaps';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new TermMapModel($result);
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
                'alternate',
                'normalizedTerm',
                'normalizedAlternate',
                'uid',
            ])
            ->from([Table::TERM_MAPS])
            ->orderBy([
                'term' => SORT_ASC,
                'alternate' => SORT_ASC,
            ]);

        return $query;
    }

    public function hasTermMaps(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllTermMaps(): array
    {
        return $this->items()->all();
    }

    public function getTermMapById(int $id): ?TermMapModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getExistingTermMap(TermMapModel $model): ?TermMapModel
    {
        return $this->items()
            ->where('siteId', $model->siteId)
            ->where('term', $model->term)
            ->firstWhere('alternate', $model->alternate);
    }

    public function deleteTermMapById(int $id): bool
    {
        $model = $this->getTermMapById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteTermMap($model);

    }
    public function deleteTermMap(TermMapModel $model): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableTermMaps) {
            $this->deleteRecord($model->uid);
        } else {
            Craft::$app->getProjectConfig()->remove(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid
            );
        }

        return true;
    }

    public function saveTermMap(TermMapModel $model, bool $runValidation = true): bool
    {
        $plugin = Plugin::getInstance();

        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Search term map not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingTermMap = $this->getExistingTermMap($model);
        if ($existingTermMap && $existingTermMap->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching search term map already exists.'));
            Craft::info('Search term map not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(Table::TERM_MAPS, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(Table::TERM_MAPS, $model->id);
        }

        if ($plugin->getSettings()->enableTermMaps) {
            $this->saveRecord($model->uid, $model->getConfig());
        } else {
            Craft::$app->getProjectConfig()->set(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid,
                $model->getConfig()
            );
        }

        if ($isNew) {
            $model->id = Db::idByUid(Table::TERM_MAPS, $model->uid);
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
        $record->alternate = $data['alternate'];
        $record->normalizedTerm = $data['normalizedTerm'];
        $record->normalizedAlternate = $data['normalizedAlternate'];
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

    private function getRecord(int|string $criteria): TermMapRecord
    {
        $query = TermMapRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @var ?TermMapRecord **/
        $record = $query->one();

        return $record ?? new TermMapRecord();
    }

    public function typecastData(array $data): array
    {
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ? $data['siteId'] : null);

        $data['term'] = trim($data['term'] ?? '');
        $data['alternate'] = trim($data['alternate'] ?? '');

        $normalized = false;

        $language = PluginHelper::getSiteLanguage($data['siteId']);

        $data['normalizedTerm'] = SearchHelper::normalizeKeywords(
            str: $data['term'],
            ignore: [],
            processCharMap: ($language !== null),
            language: $language
        );

        $data['normalizedAlternate'] = SearchHelper::normalizeKeywords(
            str: $data['alternate'],
            ignore: [],
            processCharMap: ($language !== null),
            language: $language
        );

        return $data;
    }
}
