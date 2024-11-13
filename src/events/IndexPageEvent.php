<?php
namespace xorb\search\events;

use craft\base\ElementInterface;
use craft\base\Event;

class IndexPageEvent extends Event
{
    public ?ElementInterface $element;
    public string $url;
    public ?string $mainData;
}
