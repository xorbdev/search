<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Hit record.
 *
 * @property int $id
 * @property int $siteId
 * @property string $url
 * @property string $dateHit
 */
class Hit extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::HITS;
    }
}
