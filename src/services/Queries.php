<?php
namespace xorb\search\services;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use xorb\search\db\Table;
use xorb\search\records\Query as QueryRecord;
use xorb\search\Plugin;
use yii\base\Component;

class Queries extends Component
{
    public function getQueries(?int $siteId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'query',
                'dateQuery',
            ])
            ->from([Table::QUERIES])
            ->offset($offset)
            ->limit($limit)
            ->orderBy([
                'dateQuery' => SORT_DESC,
            ]);

        if ($siteId !== null) {
            $query->where(['siteId' => $siteId]);
        }

        $data = $query->all();

        $total = (new Query())
            ->from([Table::QUERIES])
            ->count();

        return [$data, $total];
    }

    public function deleteQueryById(int $id): bool
    {
        $record = QueryRecord::find()
            ->where(['id' => $id])
            ->one();

        if (!$record) {
            return false;
        }

        $deletedRows = $record->delete();

        if ($deletedRows === false) {
            return false;
        }

        return ($deletedRows > 0);
    }

    public function deleteQueriesBySiteId(int $siteId): bool
    {
        $deletedRows = Db::delete(Table::QUERIES, ['siteId' => $siteId]);

        return ($deletedRows > 0);
    }

    public function trackQuery(string $searchQuery): bool
    {
        $plugin = Plugin::getInstance();

        // Search query too long
        if (mb_strlen($searchQuery) > 250) {
            return false;
        }

        if ($plugin->getSettings()->trackQueries) {
            $record = new QueryRecord();
            $record->siteId = Craft::$app->getSites()->getCurrentSite()->id;
            $record->query = $searchQuery;
            $record->dateQuery = Db::prepareDateForDb(DateTimeHelper::currentUTCDateTime());
            $record->save(false);

            return true;
        }

        return false;
    }
}
