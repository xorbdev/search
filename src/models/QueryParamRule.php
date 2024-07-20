<?php
namespace xorb\search\models;

use Craft;
use craft\base\Model;
use xorb\search\Plugin;
use xorb\search\validators\ComparatorValidator;

class QueryParamRule extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $name = null;
    public ?string $resultUrlValue = null;
    public ?string $resultUrlComparator = null;
    public ?string $queryParamKey = null;
    public ?string $queryParamValue = null;
    public ?string $queryParamComparator = null;
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
                'name',
                'resultUrlValue',
                'resultUrlComparator',
                'queryParamKey',
                'queryParamValue',
                'queryParamComparator',
            ],
            'required'
        ];

        $rules[] = [
            [
                'name',
                'resultUrlValue',
                'queryParamKey',
                'queryParamValue',
            ],
            'string',
            'max' => 250
        ];

        $rules[] = [
            [
                'resultUrlComparator',
                'queryParamComparator',
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
            'name' => Plugin::t('Name'),
            'resultUrlValue' => Plugin::t('Result URI Value'),
            'resultUrlComparator' => Plugin::t('Result URI Comparator'),
            'queryParamKey' => Plugin::t('Query Param Key'),
            'queryParamValue' => Plugin::t('Query Param Value'),
            'queryParamComparator' => Plugin::t('Query Param Comparator'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'siteId' => $this->siteId,
            'name' => $this->name,
            'resultUrlValue' => $this->resultUrlValue,
            'resultUrlComparator' => $this->resultUrlComparator,
            'queryParamKey' => $this->queryParamKey,
            'queryParamValue' => $this->queryParamValue,
            'queryParamComparator' => $this->queryParamComparator,
        ];
    }
}
