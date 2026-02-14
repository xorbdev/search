<?php
namespace xorb\search\tasks;

use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\Db;
use xorb\search\helpers\AssetResult;
use xorb\search\helpers\UrlCleaner;
use xorb\search\tasks\BaseTask;

class AddAssetsTask extends BaseTask
{
    protected $assetIds = [];

    public function __construct(?int $siteId = null)
    {
        parent::__construct('addAssets', $siteId);
    }

    protected function performSite(int $siteId): bool
    {
        $this->assetIds = [];

        /** @var AssetQuery **/
        $assetQuery = Asset::instance()::find();
        $assetQuery->siteId = $siteId;
        $assetQuery->orderBy(['id' => SORT_ASC]);

        $totalCount = $assetQuery->count();

        if (!$totalCount) {
            return false;
        }

        $this->addAssets($siteId, $assetQuery, $totalCount);

        return true;
    }

    protected function addAssets(
        int $siteId,
        AssetQuery $assetQuery,
        int $totalCount,
    ): void
    {
        foreach ($assetQuery->batch(100) as $batch) {
            /** @var Asset **/
            foreach ($batch as $item) {
                $url = $item->getUrl();

                if ($url === null) {
                    continue;
                }

                $this->addAsset($siteId, $item);
            }
        }
    }

    protected function addAsset(
        int $siteId,
        Asset $asset,
    ): void
    {
        if (in_array($asset->id, $this->assetIds)) {
            return;
        }

        $this->assetIds[] = $asset->id;

        AssetResult::addAsset($siteId, $asset);
    }
}
