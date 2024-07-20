<?php
namespace xorb\search\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Cp;
use xorb\search\Plugin;

class RecentSearches extends Widget
{
    public int $limit = 10;

    public static function displayName(): string
    {
        return Plugin::t('Recent Searches');
    }

    public static function icon(): string
    {
        return 'magnifying-glass';
    }

    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['limit'], 'number', 'integerOnly' => true];
        return $rules;
    }

    public function getSettingsHtml(): null|string
    {
        return Cp::textFieldHtml([
            'label' => Craft::t('app', 'Limit'),
            'id' => 'limit',
            'name' => 'limit',
            'value' => $this->limit,
            'size' => 2,
            'errors' => $this->getErrors('limit'),
        ]);
    }

    public function getBodyHtml(): ?string
    {
        $plugin = Plugin::getInstance();

        return Craft::$app->getView()->renderTemplate(
            Plugin::HANDLE . '/_widgets/index',
            [
                'items' => $plugin->getQueries()->getQueries(null, 1, $this->limit)[0]
            ]
        );
    }
}
