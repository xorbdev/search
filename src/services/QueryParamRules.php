<?php
namespace xorb\search\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use xorb\search\db\Table;
use xorb\search\Plugin;
use xorb\search\models\QueryParamRule as QueryParamRuleModel;
use xorb\search\records\QueryParamRule as QueryParamRuleRecord;
use yii\base\Component;

use const SORT_ASC;

class QueryParamRules extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.queryParamRules';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new QueryParamRuleModel($result);
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
                'name',
                'siteId',
                'resultUrlValue',
                'resultUrlComparator',
                'queryParamKey',
                'queryParamValue',
                'queryParamComparator',
                'uid',
            ])
            ->from([Table::QUERY_PARAM_RULES])
            ->orderBy(['name' => SORT_ASC]);

        return $query;
    }

    public function hasQueryParamRules(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllQueryParamRules(): array
    {
        return $this->items()->all();
    }

    public function getQueryParamRuleById(int $id): ?QueryParamRuleModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getExistingQueryParamRule(QueryParamRuleModel $model): ?QueryParamRuleModel
    {
        return $this->items()
            ->where('siteId', $model->siteId)
            ->where('resultUrlValue', $model->resultUrlValue)
            ->where('resultUrlComparator', $model->resultUrlComparator)
            ->where('queryParamKey', $model->queryParamKey)
            ->where('queryParamValue', $model->queryParamValue)
            ->firstWhere('queryParamComparator', $model->queryParamComparator);
    }

    public function deleteQueryParamRuleById(int $id): bool
    {
        $model = $this->getQueryParamRuleById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteQueryParamRule($model);

    }
    public function deleteQueryParamRule(QueryParamRuleModel $model): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableQueryParamRules) {
            $this->deleteRecord($model->uid);
        } else {
            Craft::$app->getProjectConfig()->remove(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid
            );
        }

        return true;
    }

    public function saveQueryParamRule(QueryParamRuleModel $model, bool $runValidation = true): bool
    {
        $plugin = Plugin::getInstance();

        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Query param rule not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingQueryParamRule = $this->getExistingQueryParamRule($model);
        if ($existingQueryParamRule && $existingQueryParamRule->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching query param rule already exists.'));
            Craft::info('Query param rule not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(Table::QUERY_PARAM_RULES, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(Table::QUERY_PARAM_RULES, $model->id);
        }

        if ($plugin->getSettings()->enableQueryParamRules) {
            $this->saveRecord($model->uid, $model->getConfig());
        } else {
            Craft::$app->getProjectConfig()->set(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid,
                $model->getConfig()
            );
        }

        if ($isNew) {
            $model->id = Db::idByUid(Table::QUERY_PARAM_RULES, $model->uid);
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

        $record->name = $data['name'];
        $record->siteId = $data['siteId'];
        $record->resultUrlValue = $data['resultUrlValue'];
        $record->resultUrlComparator = $data['resultUrlComparator'];
        $record->queryParamKey = $data['queryParamKey'];
        $record->queryParamValue = $data['queryParamValue'];
        $record->queryParamComparator = $data['queryParamComparator'];
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

    private function getRecord(int|string $criteria): QueryParamRuleRecord
    {
        $query = QueryParamRuleRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @var ?QueryParamRuleRecord **/
        $record = $query->one();

        return $record ?? new QueryParamRuleRecord();
    }

    public function typecastData(array $data): array
    {
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ?: null);

        $data['name'] = trim($data['name'] ?? '');

        $data['resultUrlValue'] = trim($data['resultUrlValue'] ?? '');
        $data['resultUrlComparator'] = trim($data['resultUrlComparator'] ?? '');

        $data['queryParamKey'] = trim($data['queryParamKey'] ?? '');
        $data['queryParamKey'] = ($data['queryParamKey'] !== '' ? $data['queryParamKey'] : null);

        $data['queryParamValue'] = trim($data['queryParamValue'] ?? '');
        $data['queryParamValue'] = ($data['queryParamValue'] !== '' ? $data['queryParamValue'] : null);

        $data['queryParamComparator'] = trim($data['queryParamComparator'] ?? '');
        $data['queryParamComparator'] = ($data['queryParamComparator'] !== '' ? $data['queryParamComparator'] : null);

        /* if ($data['queryParamKey'] === null || $data['queryParamValue'] === null) {
            $data['queryParamKey'] = null;
            $data['queryParamValue'] = null;
            $data['queryParamComparator'] = null;
        } */

        if ($data['siteId'] !== null) {
            $site = Craft::$app->getSites()->getSiteById($data['siteId'], true);
            if (!$site) {
                $data['siteId'] = null;
            }
        }

        return $data;
    }
}
