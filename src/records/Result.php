<?php
namespace xorb\search\records;

use craft\db\ActiveRecord;
use xorb\search\db\Table;
/**
 * Result record.
 *
 * @property int $id
 * @property string $resultType
 * @property ?int $resultId
 * @property ?string $resultTitle
 * @property string $resultUrl
 * @property ?string $resultDescription
 * @property ?string $resultHash
 * @property ?string $mainHash
 * @property ?string $mainData
 * @property int $score
 * @property int $searchPriority
 * @property bool $searchIgnore
 * @property int $sitemapPriority
 * @property string $sitemapChangefreq
 * @property bool $sitemapIgnore
 * @property bool $rulesIgnore
 * @property bool $error
 * @property ?int $errorCode
 * @property ?string $dateResultModified
 * @property ?string $dateMainModified
 * @property ?string $dateUnavailableAfter
 * @property ?string $dateError
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class Result extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::RESULTS;
    }
}
