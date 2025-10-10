<?php
namespace xorb\search\tasks;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as CommercePlugin;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use xorb\search\helpers\PageResult;
use xorb\search\helpers\UrlCleaner;
use xorb\search\tasks\BaseTask;
use xorb\search\elements\Result as ResultElement;

class AddKnownPagesTask extends BaseTask
{
    protected $elementIds = [];

    public function __construct(?int $siteId = null)
    {
        parent::__construct('addKnownPages', $siteId);
    }

    protected function performSite(int $siteId): bool
    {
        $this->elementIds = [];

        $this->addSectionUrls($siteId);
        $this->addCategoryUrls($siteId);
        $this->addProductUrls($siteId);

        return true;
    }

    protected function addSectionUrls(int $siteId): void
    {
        $items = Craft::$app->getEntries()->getAllSections();
        $items = array_filter($items, $this->filterHasUrls(...));

        foreach ($items as $item) {
            $elementQuery = Entry::find();
            $elementQuery->sectionId = $item['id'];
            $elementQuery->siteId = $siteId;

            $totalCount = $elementQuery->count();

            if (!$totalCount) {
                continue;
            }

            $this->addPages($siteId, $elementQuery, $totalCount);
        }
    }

    protected function addCategoryUrls(int $siteId): void
    {
        $items = Craft::$app->getCategories()->getAllGroups();
        $items = array_filter($items, $this->filterHasUrls(...));

        foreach ($items as $item) {
            $elementQuery = Category::find();
            $elementQuery->groupId = $item['id'];
            $elementQuery->siteId = $siteId;

            $totalCount = $elementQuery->count();

            if (!$totalCount) {
                continue;
            }

            $this->addPages($siteId, $elementQuery, $totalCount);
        }
    }

    protected function addProductUrls(int $siteId): void
    {
        if (Craft::$app->plugins->getPlugin('commerce') === null) {
            return;
        }

        $plugin = CommercePlugin::getInstance();

        $items = $plugin->getProductTypes()->getAllProductTypes();
        $items = array_filter($items, $this->filterHasUrls(...));

        foreach ($items as $item) {
            $elementQuery = Product::find();
            $elementQuery->typeId = $item['id'];
            $elementQuery->siteId = $siteId;

            $totalCount = $elementQuery->count();

            if (!$totalCount) {
                continue;
            }

            $this->addPages($siteId, $elementQuery, $totalCount);
        }
    }

    private function filterHasUrls(mixed $item): bool
    {
        foreach ($item->getSiteSettings() as $siteSettings) {
            if ($siteSettings->hasUrls) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $siteId
     * @param ElementQuery $elementQuery
     * @param int $totalCount
     */
    protected function addPages(
        int $siteId,
        ElementQueryInterface $elementQuery,
        int $totalCount,
    ): void
    {
        $limit = 250;
        $offset = 0;

        while (true) {
            $elementQuery->limit = $limit;
            $elementQuery->offset = $offset * $limit;

            /** @var Element $item **/
		    foreach ($elementQuery->all() as $item) {
                $url = $item->getUrl();

                if ($url === null) {
                    continue;
                }

                $this->addPage($siteId, $item);
            }

            $totalCount -= $limit;
            if ($totalCount <= 0) {
                break;
            }

            ++$offset;
        }
    }

    /**
     * @param int $siteId
     * @param Element $element
     */
    protected function addPage(
        int $siteId,
        ElementInterface $element,
    ): void
    {
        if (in_array($element->id, $this->elementIds)) {
            return;
        }

        $this->elementIds[] = $element->id;

        PageResult::addPage($siteId, $element);
    }
}
