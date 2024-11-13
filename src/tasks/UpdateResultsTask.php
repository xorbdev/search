<?php
namespace xorb\search\tasks;

use craft\db\Query;
use craft\db\Table as CraftTable;
use xorb\search\db\Table;
use xorb\search\elements\Result as ResultElement;
use xorb\search\helpers\AssetResult;
use xorb\search\helpers\PageResult;
use xorb\search\tasks\BaseTask;

class UpdateResultsTask extends BaseTask
{
    protected $forceUpdatePages;
    protected $forceUpdateAssets;

    public function __construct(
        ?int $siteId = null,
        bool $forceUpdatePages = false,
        bool $forceUpdateAssets = false,
    )
    {
        parent::__construct('updateResults', $siteId);

        $this->forceUpdatePages = $forceUpdatePages;
        $this->forceUpdateAssets = $forceUpdateAssets;
    }

    public function performSite(int $siteId): bool
    {
        $query = (new Query())
            ->select([
                Table::RESULTS . '.[[id]]',
                Table::RESULTS . '.[[resultType]]',
            ])
            ->from(Table::RESULTS)
            ->innerJoin(
                CraftTable::ELEMENTS_SITES,
                Table::RESULTS . '.[[id]] = ' . CraftTable::ELEMENTS_SITES . '.[[elementId]]'
            )
            ->where([CraftTable::ELEMENTS_SITES . '.[[siteId]]' => $siteId]);

        $batch = $query->batch(500);

        foreach ($batch as $rows) {
            foreach ($rows as $row) {
                /** @var ResultElement **/
                $resultElement = ResultElement::find()
                    ->id($row['id'])
                    ->one();

                if ($resultElement->resultType === 'page') {
                    PageResult::update($resultElement, null, $this->forceUpdatePages);
                } else {
                    AssetResult::update($resultElement, null, $this->forceUpdateAssets);
                }
            }
        }

        return true;
    }
}
