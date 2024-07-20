<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Term map record.
 *
 * @property int $id
 * @property ?int $siteId
 * @property string $term
 * @property string $alternate
 * @property string $normalizedTerm
 * @property string $normalizedAlternate
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class TermMap extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::TERM_MAPS;
    }
}
