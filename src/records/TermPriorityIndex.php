<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Hit record.
 *
 * @property int $id
 * @property int $siteId
 * @property int $termPriorityId
 * @property int $resultId
 */
class TermPriorityIndex extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::TERM_PRIORITIES_INDEX;
    }
}
