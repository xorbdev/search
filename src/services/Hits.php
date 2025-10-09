<?php
namespace xorb\search\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use xorb\search\events\TrackHitEvent;
use xorb\search\helpers\PluginHelper;
use xorb\search\records\Hit as HitRecord;
use yii\base\Component;
use yii\base\Event;

class Hits extends Component
{
    public const EVENT_TRACK_HIT = 'eventTrackHit';

    public function trackHit(): bool
    {
        $url = Craft::$app->getRequest()->getUrl();
        $trackHit = PluginHelper::trackHit();

        if (Event::hasHandlers(static::class, self::EVENT_TRACK_HIT)) {
            $event = new TrackHitEvent([
                'url' => $url,
                'trackHit' => $trackHit,
            ]);

            Event::trigger(static::class, self::EVENT_TRACK_HIT, $event);

            $url = $event->url;
            $trackHit = $event->trackHit;
        }

        if ($trackHit) {
            $record = new HitRecord();
            $record->siteId = Craft::$app->getSites()->getCurrentSite()->id;
            $record->url = $url;
            $record->dateHit = Db::prepareDateForDb(DateTimeHelper::currentUTCDateTime());
            $record->save(false);

            return true;
        }

        return false;
    }
}
