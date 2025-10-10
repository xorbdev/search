<?php
namespace xorb\search\events;

use craft\base\Event;

class TrackHitEvent extends Event
{
    public string $url;
    public bool $trackHit;
}
