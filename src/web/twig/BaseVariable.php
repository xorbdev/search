<?php
namespace xorb\search\web\twig;

use Craft;
use xorb\search\elements\db\ResultQuery;
use xorb\search\elements\Result as ResultElement;
use xorb\search\Plugin;

class BaseVariable
{
    public function name(): string
    {
        $plugin = Plugin::getInstance();
        return Plugin::t($plugin->name);
    }

    public function isPro(): bool
    {
        return Plugin::getInstance()->isPro();
    }

    public function isLite(): bool
    {
        return Plugin::getInstance()->isLite();
    }
}
