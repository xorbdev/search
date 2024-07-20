<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;

/**
 * Redirect record.
 *
 * @property int $id
 * @property ?int $siteId
 * @property string $fromUrl
 * @property ?string $toUrl
 * @property string $type
 * @property bool $regex
 * @property bool $ignoreQueryParams
 * @property int $priority
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class Redirect extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::REDIRECTS;
    }
}
