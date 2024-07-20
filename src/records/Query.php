<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Term priority record.
 *
 * @property int $id
 * @property int $siteId
 * @property string $query
 * @property string $dateQuery
 */
class Query extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::QUERIES;
    }
}
