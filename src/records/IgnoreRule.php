<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Ignore rule record.
 *
 * @property int $id
 * @property ?int $siteId
 * @property string $name
 * @property string $resultUrlValue
 * @property string $resultUrlComparator
 * @property bool $absolute
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class IgnoreRule extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::IGNORE_RULES;
    }
}
