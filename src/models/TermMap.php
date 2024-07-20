<?php
namespace xorb\search\models;

use Craft;
use craft\base\Model;
use xorb\search\Plugin;

class TermMap extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $term = null;
    public ?string $alternate = null;
    public ?string $normalizedTerm = null;
    public ?string $normalizedAlternate = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'siteId',
            ],
            'number',
            'integerOnly' => true,
            'min' => 0,
        ];

        $rules[] = [
            [
                'term',
                'alternate',
                'normalizedTerm',
                'normalizedAlternate',
            ],
            'required'
        ];

        $rules[] = [
            [
                'term',
                'alternate',
                'normalizedTerm',
                'normalizedAlternate',
            ],
            'string',
            'max' => 250
        ];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'siteId' => Craft::t('app', 'Site'),
            'term' => Plugin::t('Search Term'),
            'alternate' => Plugin::t('Alternate'),
            'normalizedTerm' => Plugin::t('Normalized Search Term'),
            'normalizedAlternate' => Plugin::t('Normalized Alternate'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'siteId' => $this->siteId,
            'term' => $this->term,
            'alternate' => $this->alternate,
            'normalizedTerm' => $this->normalizedTerm,
            'normalizedAlternate' => $this->normalizedAlternate,
        ];
    }
}
