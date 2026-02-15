<?php
namespace xorb\search\jobs;

use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\models\Site;
use craft\queue\BaseJob;
use craft\i18n\Translation;
use UnexpectedValueException;
use xorb\search\helpers\PluginHelper;
use xorb\search\Plugin;

class MakeAssetsSearchable extends BaseJob
{
    public ?array $siteIds = null;
    public ?array $volumeIds = null;
    public ?array $fileKinds = null;

    public function execute($queue): void
    {
        /** @var AssetQuery **/
        $assetQuery = Asset::instance()::find();
        $assetQuery->orderBy(['id' => SORT_ASC]);

        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $siteIds = $this->siteIds ?? array_map(
            fn(Site $site) => $site->id,
            Craft::$app->getSites()->getAllSites(true),
        );

        $sites = [];

        foreach ($assetQuery->batch(100) as $batch) {
            /** @var Asset **/
            foreach ($batch as $asset) {
                if ($this->volumeIds !== null &&
                    !in_array($asset->volumeId, $this->volumeIds)
                ) {
                    continue;
                }

                if ($this->fileKinds !== null &&
                    !in_array($asset->kind, $this->fileKinds)
                ) {
                    continue;
                }

                $fieldLayout = $asset->getFieldLayout();
                if ($fieldLayout === null) {
                    continue;
                }

                $field = $fieldLayout->getFieldByHandle($settings->searchableAssetFieldHandle);
                if ($field === null) {
                    continue;
                }

                $asset->setFieldValue($settings->searchableAssetFieldHandle, $siteIds);
                Craft::$app->elements->saveElement($asset, false);
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Translation::prep(Plugin::HANDLE, 'Making assets searchable.');
    }
}
