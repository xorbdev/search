<?php
namespace xorb\search\tasks;

use Craft;
use craft\db\Query;
use craft\db\Table as CraftTable;
use xorb\search\db\Table;
use xorb\search\elements\Result as ResultElement;
use xorb\search\helpers\UrlComparer;
use xorb\search\records\TermPriorityIndex as TermPriorityIndexRecord;
use xorb\search\tasks\BaseTask;

class UpdateTermPrioritiesIndexTask extends BaseTask
{
    public function __construct(?int $siteId = null)
    {
        parent::__construct('updateTermPrioritiesIndex', $siteId);
    }

    public function performSite(int $siteId): bool
    {
        // Delete existing index items for this site
        Craft::$app->getDb()
            ->createCommand()
            ->delete(Table::TERM_PRIORITIES, ['siteId' => $siteId])
            ->execute();

        $where = [
            'or',
            ['siteId' => null],
            ['siteId' => $siteId],
        ];

        $query = (new Query())
            ->select([
                'id',
                'term',
                'resultUrlValue',
                'resultUrlComparator',
                'searchPriority',
            ])
            ->from(Table::TERM_PRIORITIES)
            ->where($where);

        $batch = $query->batch(500);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
               $this->generatePriorityIndex($siteId, $row);
            }
        }

        return true;
    }

    private function generatePriorityIndex(int $siteId, array $priority): void
    {
        $batch = ResultElement::find()
            ->siteId($siteId)
            ->resultType('page')
            ->searchQuery($priority['term'])
            ->batch(100);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
                if (!UrlComparer::matchUrl(
                    $row->resultUrl,
                    $priority['resultUrlValue'],
                    $priority['resultUrlComparator']
                )) {
                    continue;
                }

                $record = new TermPriorityIndexRecord();
                $record->siteId = $siteId;
                $record->termPriorityId = $priority['id'];
                $record->resultId = $row['id'];
                $record->save(false);
            }
        }
    }
}
