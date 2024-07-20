<?php
namespace xorb\search\models;

use Craft;
use craft\base\Model;
use xorb\search\Plugin;
use xorb\search\validators\RedirectTypeValidator;

class Redirect extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $fromUrl = null;
    public ?string $toUrl = null;
    public string $type = '301';
    public bool $regex = false;
    public bool $ignoreQueryParams = false;
    public int $priority = 0;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'fromUrl',
                'type',
                'priority',
            ],
            'required'
        ];

        $rules[] = [
            [
                'siteId',
                'priority'
            ],
            'number',
            'integerOnly' => true,
            'min' => 0,
        ];

        $rules[] = [
            [
                'fromUrl',
                'toUrl',
            ],
            'string',
            'max' => 250
        ];

        $rules[] = [
            [
                'regex',
                'ignoreQueryParams',
            ],
            'boolean'
        ];

        $rules[] = [
            [
                'type',
            ],
            RedirectTypeValidator::class
        ];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'siteId' => Craft::t('app', 'Site'),
            'fromUrl' => Plugin::t('From URI'),
            'toUrl' => Plugin::t('To URI'),
            'type' => Plugin::t('Redirect Type'),
            'regex' => Plugin::t('Process As Regex'),
            'ignoreQueryParams' => Plugin::t('Ignore Query Params'),
            'priority' => Plugin::t('Priority'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'siteId' => $this->siteId,
            'fromUrl' => $this->fromUrl,
            'toUrl' => $this->toUrl,
            'type' => $this->type,
            'regex' => $this->regex,
            'ignoreQueryParams' => $this->ignoreQueryParams,
            'priority' => $this->priority,
        ];
    }
}
