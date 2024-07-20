<?php
namespace xorb\search\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\Queue;
use yii\console\ExitCode;
use xorb\search\jobs\UpdateResults;
use xorb\search\tasks\AddAssetsTask;
use xorb\search\tasks\AddKnownPagesTask;
use xorb\search\tasks\AddNewPagesTask;
use xorb\search\tasks\UpdatePageScoreTask;
use xorb\search\tasks\UpdateResultsTask;
use xorb\search\tasks\UpdateTermPrioritiesIndexTask;

class ResultsController extends Controller
{
    /**
     * @var int|null The site id to update.
     */
    public ?int $siteId = null;

    public $defaultAction = 'update';

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        $options[] = 'siteId';

        return $options;
    }

    /**
     * Updates all aspects of the search results index.
     */
    public function actionUpdate(): int
    {
        $exitCode = $this->actionAddKnownPages();
        if ($exitCode) {
            return $exitCode;
        }

        $exitCode = $this->actionAddNewPages();
        if ($exitCode) {
            return $exitCode;
        }

        $exitCode = $this->actionAddAssets();
        if ($exitCode) {
            return $exitCode;
        }

        $exitCode = $this->actionUpdateResults();
        if ($exitCode) {
            return $exitCode;
        }

        $exitCode = $this->actionUpdatePageScore();
        if ($exitCode) {
            return $exitCode;
        }

        $exitCode = $this->actionUpdateTermPrioritiesIndex();
        if ($exitCode) {
            return $exitCode;
        }

        return ExitCode::OK;
    }

    /**
     * Adds an update job to the job queue.
     */
    public function actionQueueUpdate(): int
    {
        Queue::push(new UpdateResults([
            'siteId' => $this->siteId,
        ]));

        return ExitCode::OK;
    }

    /**
     * Adds new results found from hits tracking to the index.
     */
    public function actionAddKnownPages(): int
    {
        $task = new AddKnownPagesTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Adds new results found from hits tracking to the index.
     */
    public function actionAddNewPages(): int
    {
        $task = new AddNewPagesTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Adds assets marked as searchable to the index.
     */
    public function actionAddAssets(): int
    {
        $task = new AddAssetsTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Updates the page score of each result in hte index based on the
     * current hit tracking state.
     */
    public function actionUpdatePageScore(): int
    {
        $task = new UpdatePageScoreTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Scans each page in the index for changes and updates it accordingly.
     */
    public function actionUpdateResults(): int
    {
        $task = new UpdateResultsTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Updates the term priority index.
     */
    public function actionUpdateTermPrioritiesIndex(): int
    {
        $task = new UpdateTermPrioritiesIndexTask($this->siteId);

        if (!$task->perform()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
