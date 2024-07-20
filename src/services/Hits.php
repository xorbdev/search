<?php
namespace xorb\search\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use xorb\search\helpers\PluginHelper;
use xorb\search\records\Hit as HitRecord;
use yii\base\Component;

class Hits extends Component
{
    public function trackHit(): bool
    {
        if (PluginHelper::trackHit()) {
            $record = new HitRecord();
            $record->siteId = Craft::$app->getSites()->getCurrentSite()->id;
            $record->url = Craft::$app->getRequest()->getUrl();
            $record->dateHit = Db::prepareDateForDb(DateTimeHelper::currentUTCDateTime());
            $record->save(false);

            return true;
        }

        return false;
    }
}
