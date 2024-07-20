<?php
namespace xorb\search\models;

use Craft;
use craft\base\Model;
use DateTime;
use xorb\search\Plugin;
use xorb\search\validators\ComparatorValidator;

class IgnoreRule extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $name = null;
    public ?string $resultUrlValue = null;
    public ?string $resultUrlComparator = null;
    public bool $absolute = false;
    public ?string $uid = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

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
            ],
            'required'
        ];

        $rules[] = [
            [
                'name',
                'resultUrlValue',
            ],
            'string',
            'max' => 250
        ];

        $rules[] = [
            [
                'absolute',
            ],
            'boolean',
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
            'name' => Plugin::t('Name'),
            'resultUrlValue' => Plugin::t('Result URI Value'),
            'resultUrlComparator' => Plugin::t('Result URI Comparator'),
            'absolute' => Plugin::t('Ignore Absolutely'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'siteId' => $this->siteId,
            'name' => $this->name,
            'resultUrlValue' => $this->resultUrlValue,
            'resultUrlComparator' => $this->resultUrlComparator,
            'absolute' => $this->absolute,
        ];
    }
}
