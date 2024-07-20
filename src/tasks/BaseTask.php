<?php
namespace xorb\search\tasks;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTime;
use UnexpectedValueException;
use xorb\search\records\Task as TaskRecord;
use xorb\search\helpers\PluginHelper;

abstract class BaseTask
{
    protected string $name;
    protected ?int $siteId = null;
    protected DateTime $now;
    protected ?TaskRecord $activeTask = null;

    public function __construct(string $name, ?int $siteId = null)
    {
        $this->name = $name;
        $this->siteId = $siteId;
        $this->now = DateTimeHelper::currentUTCDateTime();
    }

    public function perform(): bool
    {
        if ($this->siteId !== null) {
            if (!PluginHelper::isValidSite($this->siteId)) {
                return false;
            }

            if (!$this->start($this->siteId)) {
                return false;
            }

            if (!$this->performSite($this->siteId)) {
                $this->stop($this->siteId);
                return false;
            }

            if (!$this->stop($this->siteId)) {
                return false;
            }

            return true;
        }

        $success = true;

        $hasValidSite = false;

        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            if (!PluginHelper::isValidSite($site->id)) {
                continue;
            }

            $hasValidSite = true;

            if (!$this->start($site->id)) {
                $success = false;
                continue;
            }

            if (!$this->performSite($site->id)) {
                $success = false;
            }

            if (!$this->stop($site->id)) {
                $success = false;
            }
        }

        if (!$hasValidSite) {
            return false;
        }

        return $success;
    }

    protected abstract function performSite(int $siteId): bool;

    protected function getDateLast(?int $siteId = null): ?DateTime
    {
        if ($this->activeTask === null) {
            throw new UnexpectedValueException('Task not started.');
        }

        if ($this->activeTask->dateLast === null) {
            return null;
        }

        $dateTime = DateTimeHelper::toDateTime($this->activeTask->dateLast);

        return $dateTime ?: null;
    }

    protected function start(?int $siteId = null): bool
    {
        if ($this->activeTask !== null) {
            throw new UnexpectedValueException('Task already started.');
        }

        /** @var ?TaskRecord **/
        $task = TaskRecord::find()
            ->where([
                'task' => $this->name,
                'siteId' => $siteId,
            ])
            ->one();

        if ($task === null) {
            $this->activeTask = new TaskRecord();
            $this->activeTask->task = $this->name;
            $this->activeTask->siteId = $siteId;
        } else {
            $this->activeTask = $task;

            if ($this->activeTask->running) {
                $dateReset = DateTimeHelper::currentUTCDateTime();
                $dateReset->modify('-1 day');

                $dateStart = DateTimeHelper::toDateTime($this->activeTask->dateStart);

                // If it's been a day, assume task failed
                if ($dateReset < $dateStart) {
                    return false;
                }
            }
        }

        $this->activeTask->dateStart = Db::prepareDateForDb($this->now);
        $this->activeTask->running = true;

        $this->activeTask->save(false);

        return true;
    }

    protected function stop(int $siteId): bool
    {
        if ($this->activeTask === null) {
            throw new UnexpectedValueException('Task not started.');
        }

        $this->activeTask->running = false;
        $this->activeTask->dateLast = Db::prepareDateForDb($this->now);
        $this->activeTask->dateStart = null;

        $this->activeTask->save(false);

        $this->activeTask = null;

        return true;
    }
}
