<?php
namespace xorb\search\tasks;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use xorb\search\db\Table;
use xorb\search\elements\Result as ResultElement;
use xorb\search\helpers\PageResult;
use xorb\search\helpers\UrlCleaner;
use xorb\search\Plugin;
use xorb\search\tasks\BaseTask;

class AddNewPagesTask extends BaseTask
{
    public function __construct(?int $siteId = null)
    {
        parent::__construct('addNewPages', $siteId);
    }

    protected function performSite(int $siteId): bool
    {
        $urls = [];
        $ignoreUrls = [];
        $deleteIds = [];

        $dateLast = $this->getDateLast($siteId);

        if ($dateLast === null) {
            $where = [
                'and',
                ['<=', 'dateHit', Db::prepareDateForDb($this->now)],
                ['siteId' => $siteId],
            ];
        } else {
            $where = [
                'and',
                ['>', 'dateHit', Db::prepareDateForDb($dateLast)],
                ['<=', 'dateHit', Db::prepareDateForDb($this->now)],
                ['siteId' => $siteId],
            ];
        }

        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'url',
            ])
            ->from(Table::HITS)
            ->where($where)
            ->orderBy(['id' => SORT_ASC]);

        $batch = $query->batch(500);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
                $url = UrlCleaner::clean($row['siteId'], $row['url']);

                if (in_array($url, $urls)) {
                    continue;
                }

                if (in_array($url, $ignoreUrls)) {
                    $deleteIds[] = $row['id'];
                    continue;
                }

                $urls[] = $url;

                if (!PageResult::addPage($row['siteId'], null, $url)) {
                    $deleteIds[] = $row['id'];
                    $ignoreUrls[] = $url;
                }
            }
        }

        $command = Craft::$app->getDb()->createCommand();

        // Delete ignored urls
        foreach ($deleteIds as $deleteId) {
            $command->delete(
                Table::HITS,
                ['id' => $deleteId]
            )->execute();
        }

        return true;
    }
}
