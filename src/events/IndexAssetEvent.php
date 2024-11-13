<?php
namespace xorb\search\events;

use craft\base\Event;
use craft\elements\Asset;

class IndexAssetEvent extends Event
{
    public Asset $asset;
    public ?string $mainData;
}
