<?php
namespace xorb\search\web\twig;

use Craft;
use DateTime;
use xorb\search\Plugin;
use xorb\search\elements\Result as ResultElement;
use xorb\search\elements\db\ResultQuery;

class SiteVariable extends BaseVariable
{
    public function results(string|array $criteria = []): ResultQuery
    {
        $query = ResultElement::find()
            ->searchMode(true)
            ->siteId(Craft::$app->getSites()->getCurrentSite()->id);

        if ($criteria) {
            if (is_string($criteria)) {
                $criteria = [
                    'searchQuery' => $criteria,
                ];
            }

            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
