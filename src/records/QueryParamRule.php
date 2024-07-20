<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Query param rule record.
 *
 * @property int $id
 * @property ?int $siteId
 * @property string $name
 * @property string $resultUrlValue
 * @property string $resultUrlComparator
 * @property ?string $queryParamKey
 * @property ?string $queryParamValue
 * @property ?string $queryParamComparator
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class QueryParamRule extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::QUERY_PARAM_RULES;
    }
}
