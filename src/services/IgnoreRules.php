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
use xorb\search\models\IgnoreRule as IgnoreRuleModel;
use xorb\search\records\IgnoreRule as IgnoreRuleRecord;
use yii\base\Component;

use const SORT_ASC;

class IgnoreRules extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.ignoreRules';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new IgnoreRuleModel($result);
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
                'absolute',
                'uid',
            ])
            ->from([Table::IGNORE_RULES])
            ->orderBy(['name' => SORT_ASC]);

        return $query;
    }

    public function hasIgnoreRules(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllIgnoreRules(): array
    {
        return $this->items()->all();
    }

    public function getIgnoreRuleById(int $id): ?IgnoreRuleModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getExistingIgnoreRule(IgnoreRuleModel $model): ?IgnoreRuleModel
    {
        return $this->items()
            ->where('siteId', $model->siteId)
            ->where('resultUrlValue', $model->resultUrlValue)
            ->Where('resultUrlComparator', $model->resultUrlComparator)
            ->firstWhere('absolute', $model->absolute);
    }

    public function deleteIgnoreRuleById(int $id): bool
    {
        $model = $this->getIgnoreRuleById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteIgnoreRule($model);

    }
    public function deleteIgnoreRule(IgnoreRuleModel $model): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableIgnoreRules) {
            $this->deleteRecord($model->uid);
        } else {
            Craft::$app->getProjectConfig()->remove(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid
            );
        }

        return true;
    }

    public function saveIgnoreRule(IgnoreRuleModel $model, bool $runValidation = true): bool
    {
        $plugin = Plugin::getInstance();

        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Ignore rule not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingIgnoreRule = $this->getExistingIgnoreRule($model);
        if ($existingIgnoreRule && $existingIgnoreRule->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching ignore rule already exists.'));
            Craft::info('Ignore rule not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(Table::IGNORE_RULES, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(Table::IGNORE_RULES, $model->id);
        }

        if ($plugin->getSettings()->enableIgnoreRules) {
            $this->saveRecord($model->uid, $model->getConfig());
        } else {
            Craft::$app->getProjectConfig()->set(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid,
                $model->getConfig()
            );
        }

        if ($isNew) {
            $model->id = Db::idByUid(Table::IGNORE_RULES, $model->uid);
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
        $record->absolute = $data['absolute'];
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

    private function getRecord(int|string $criteria): IgnoreRuleRecord
    {
        $query = IgnoreRuleRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @var ?IgnoreRuleRecord **/
        $record = $query->one();

        return $record ?? new IgnoreRuleRecord();
    }

    public function typecastData(array $data): array
    {
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ?: null);

        $data['name'] = $data['name'] ?? '';

        $data['resultUrlValue'] = $data['resultUrlValue'] ?? '';
        $data['resultUrlComparator'] = $data['resultUrlComparator'] ?? '';

        $data['absolute'] = (($data['absolute'] ?? false) ? true : false);

        if ($data['siteId'] !== null) {
            $site = Craft::$app->getSites()->getSiteById($data['siteId'], true);
            if (!$site) {
                $data['siteId'] = null;
            }
        }

        return $data;
    }
}
