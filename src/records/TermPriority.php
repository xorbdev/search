<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Term priority record.
 *
 * @property int $id
 * @property ?int $siteId
 * @property string $term
 * @property string $normalizedTerm
 * @property string $resultUrlValue
 * @property string $resultUrlComparator
 * @property int $searchPriority
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class TermPriority extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::TERM_PRIORITIES;
    }
}
