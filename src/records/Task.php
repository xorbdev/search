<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Task record.
 *
 * @property int $id
 * @property int $siteId
 * @property string $task
 * @property string|null $dateLast
 * @property string|null $dateStart
 * @property bool $running
 */
class Task extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::TASKS;
    }
}
