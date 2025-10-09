<?php
namespace xorb\search\events;

use craft\base\Event;

class TackHitEvent extends Event
{
    public string $url;
    public bool $trackHit;
}
