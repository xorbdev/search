<?php
namespace xorb\search\tasks;

use DateTime;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Db;
use xorb\search\db\Table;
use xorb\search\helpers\UrlCleaner;
use xorb\search\Plugin;
use xorb\search\tasks\BaseTask;

class UpdatePageScoreTask extends BaseTask
{
    public function __construct(?int $siteId = null)
    {
        parent::__construct('updatePageScore', $siteId);
    }

    public function performSite(int $siteId): bool
    {
        $plugin = Plugin::getInstance();

        $scores = [];
        $weighted = $plugin->getSettings()->weightedHitScore;
        $period = $this->getHitScorePeriodSeconds();

        if ($period === null) {
            $where = [
                'and',
                ['<=', 'dateHit', Db::prepareDateForDb($this->now)],
                ['siteId' => $siteId],
            ];
        } else {
            $time = $this->now->getTimestamp() - $period;
            $dateStart = new DateTime('@' . $time);

            $where = [
                'and',
                ['>', 'dateHit', Db::prepareDateForDb($dateStart)],
                ['<=', 'dateHit', Db::prepareDateForDb($this->now)],
                ['siteId' => $siteId],
            ];
        }

        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'url',
                'dateHit',
            ])
            ->from(Table::HITS)
            ->where($where);

        $batch = $query->batch(500);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
                $url = UrlCleaner::clean($row['siteId'], $row['url']);

                if (!array_key_exists($url, $scores)) {
                    $scores[$url] = 0.0;
                }

                if ($weighted) {
                    $hitTime = strtotime($row['dateHit']);
                    $timeDifference = $this->now->getTimestamp() - $hitTime;

                    // Adjust score weight based on time difference
                    $scores[$url] += max(0, 1 - ($timeDifference / $period));
                } else {
                    ++$scores[$url];
                }
            }
        }

        $query = (new Query())
            ->select([
                Table::RESULTS . '.id',
                Table::RESULTS . '.resultUrl',
            ])
            ->from(Table::RESULTS)
            ->innerJoin(
                CraftTable::ELEMENTS_SITES,
                Table::RESULTS . '.id = ' . CraftTable::ELEMENTS_SITES . '.elementId'
            )
            ->where([CraftTable::ELEMENTS_SITES . '.siteId' => $siteId])
            ->andWhere([Table::RESULTS. '.resultType' => 'page']);

        $batch = $query->batch(500);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
                // Multiply by 100 for higher precision
                $score = intval(($scores[$row['resultUrl']] ?? 0) * 100);

                $query->createCommand()
                    ->update(
                        Table::RESULTS,
                        ['score' => $score],
                        ['id' => $row['id']]
                    )
                    ->execute();
            }
        }

        return true;
    }

    private function getHitScorePeriodSeconds(): ?int
    {
        $plugin = Plugin::getInstance();

        $hitScorePeriod = $plugin->getSettings()->hitScorePeriod;

        return match ($hitScorePeriod) {
            'day' => 86400,     // 1 day
            'week' => 604800,   // 7 days
            'month' => 2592000, // 30 days (approximate)
            'three-months' => 7776000, // 90 days (approximate)
            'six-months' => 15552000, // 180 days (approximate)
            'year' => 31536000, // 365 days (approximate)
            default => null
        };
    }
}
