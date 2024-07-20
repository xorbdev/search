<?php
namespace xorb\search\models;

use Craft;
use craft\base\Model;
use xorb\search\Plugin;
use xorb\search\validators\ComparatorValidator;

class TermPriority extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $term = null;
    public ?string $normalizedTerm = null;
    public ?string $resultUrlValue = null;
    public ?string $resultUrlComparator = null;
    public ?int $searchPriority = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'term',
                'normalizedTerm',
                'resultUrlValue',
                'resultUrlComparator',
                'searchPriority',
            ],
            'required'
        ];

        $rules[] = [
            [
                'siteId',
                'searchPriority',
            ],
            'number',
            'integerOnly' => true,
            'min' => 0,
        ];

        $rules[] = [
            [
                'term',
                'normalizedTerm',
                'resultUrlValue',
            ],
            'string',
            'max' => 250
        ];

        $rules[] = [
            [
                'resultUrlComparator',
            ],
            ComparatorValidator::class
        ];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'siteId' => Craft::t('app', 'Site'),
            'term' => Plugin::t('Search Term'),
            'normalizedTerm' => Plugin::t('Normalized Search Term'),
            'resultUrlValue' => Plugin::t('Result URI Value'),
            'resultUrlComparator' => Plugin::t('Result URI Comparator'),
            'searchPriority' => Plugin::t('Search Priority'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'siteId' => $this->siteId,
            'term' => $this->term,
            'normalizedTerm' => $this->normalizedTerm,
            'resultUrlValue' => $this->resultUrlValue,
            'resultUrlComparator' => $this->resultUrlComparator,
            'searchPriority' => $this->searchPriority,
        ];
    }
}
