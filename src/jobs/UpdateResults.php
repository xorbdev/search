<?php
namespace xorb\search\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\i18n\Translation;
use xorb\search\Plugin;
use xorb\search\tasks\AddAssetsTask;
use xorb\search\tasks\AddKnownPagesTask;
use xorb\search\tasks\AddNewPagesTask;
use xorb\search\tasks\UpdatePageScoreTask;
use xorb\search\tasks\UpdateResultsTask;
use xorb\search\tasks\UpdateTermPrioritiesIndexTask;

class UpdateResults extends BaseJob
{
    public ?int $siteId = null;
    public bool $forceUpdatePages = false;
    public bool $forceUpdateAssets = false;

    public function execute($queue): void
    {
        $this->setProgress(
            $queue,
            0,
            Plugin::t('Adding known URIs to the results index.')
        );

        $task = new AddKnownPagesTask($this->siteId);
        $task->perform();

        $this->setProgress(
            $queue,
            0.1,
            Plugin::t('Adding found URIs to the results index.')
        );

        $task = new AddNewPagesTask($this->siteId);
        $task->perform();

        $this->setProgress(
            $queue,
            0.2,
            Plugin::t('Adding searchable assets.')
        );

        $task = new AddAssetsTask($this->siteId);
        $task->perform();

        $this->setProgress(
            $queue,
            0.4,
            Plugin::t('Reindexing existing results.')
        );

        $task = new UpdateResultsTask(
            $this->siteId,
            $this->forceUpdatePages,
            $this->forceUpdateAssets
        );
        $task->perform();

        $this->setProgress(
            $queue,
            0.6,
            Plugin::t('Updating the search score of each result.')
        );

        $task = new UpdatePageScoreTask($this->siteId);
        $task->perform();

        $this->setProgress(
            $queue,
            0.8,
            Plugin::t('Updating search term priorities index.')
        );

        $task = new UpdateTermPrioritiesIndexTask($this->siteId);
        $task->perform();

        $this->setProgress(
            $queue,
            1,
        );
    }

    protected function defaultDescription(): ?string
    {
        return Translation::prep(Plugin::HANDLE, 'Updating search results index.');
    }
}
