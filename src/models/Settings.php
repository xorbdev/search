<?php
namespace xorb\search\models;

use craft\base\Model;
use xorb\search\Plugin;
use xorb\search\validators\HitScorePeriodValidator;
use xorb\search\validators\SitemapChangefreqValidator;

class Settings extends Model
{
    public ?string $pluginName = 'Search';
    public bool $trackHits = true;
    public string $hitScorePeriod = 'year';
    public bool $weightedHitScore = true;
    public bool $trackQueries = false;
    public bool $track404s = false;
    public string $uaString = 'Craft CMS Search';
    public string $searchableAssetFieldHandle = 'searchable';
    public bool $enableSitemap = false;
    public ?string $sitemapName = 'sitemap';
    public int $sitemapUrlLimit = 1000;
    public bool $sitemapIncludeAssets = false;
    public bool $sitemapIgnoreRules = false;
    public int $sitemapDefaultPriority = 50;
    public string $sitemapDefaultChangefreq = 'weekly';
    public bool $sitemapIgnoreNewPageUrls = false;
    public bool $sitemapIgnoreNewAssetUrls = false;
    public bool $enableRedirects = false;
    public bool $enableTermMaps = false;
    public bool $enableTermPriorities = false;
    public bool $enableIgnoreRules = false;
    public bool $enableQueryParamRules = false;
    public bool $robotsTxt = false;
    public ?string $robotsUaString = '*';
    public bool $robotsMetaTag = false;
    public bool $robotsHttpHeader = false;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'hitScorePeriod',
                'sitemapUrlLimit',
                'sitemapDefaultChangefreq',
                'uaString',
            ],
            'required',
        ];

        $rules[] = [
            ['pluginName'],
            'string',
            'max' => 52,
        ];

        $rules[] = [
            [
                'trackHits',
                'weightedHitScore',
                'trackQueries',
                'track404s',
                'enableSitemap',
                'sitemapIncludeAssets',
                'sitemapIgnoreRules',
                'sitemapIgnoreNewPageUrls',
                'sitemapIgnoreNewAssetUrls',
                'enableRedirects',
                'enableTermMaps',
                'enableTermPriorities',
                'enableIgnoreRules',
                'enableQueryParamRules',
                'robotsTxt',
                'robotsMetaTag',
                'robotsHttpHeader',
            ],
            'boolean',
        ];

        $rules[] = [
            [
                'uaString',
                'robotsUaString',
                'searchableAssetFieldHandle',
                'sitemapName',
            ],
            'string',
            'max' => 250,
        ];

        $rules[] = [
            [
                'sitemapUrlLimit',
            ],
            'number',
            'integerOnly' => true,
            'min' => 0,
        ];

        $rules[] = [
            [
                'sitemapDefaultPriority',
            ],
            'number',
            'integerOnly' => true,
            'min' => 0,
            'max' => 100,
        ];

        $rules[] = [
            [
                'sitemapDefaultChangefreq',
            ],
            SitemapChangefreqValidator::class,
        ];

        $rules[] = [
            [
                'hitScorePeriod',
            ],
            HitScorePeriodValidator::class,
        ];

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'pluginName' => Plugin::t('Custom Plugin Name'),
            'trackHits' => Plugin::t('Track Page Hits'),
            'hitScorePeriod' => Plugin::t('Hit Score Period'),
            'weightedHitScore' => Plugin::t('Weighted Hits'),
            'trackQueries' => Plugin::t('Track Search Queries'),
            'track404s' => Plugin::t('Track 404 Errors'),
            'uaString' => Plugin::t('User-Agent String'),
            'searchableAssetFieldHandle' => Plugin::t('Searchable Asset Field Handle'),
            'enableSitemap' => Plugin::t('Enable Sitemap'),
            'sitemapName' => Plugin::t('Sitemap Name'),
            'sitemapUrlLimit' => Plugin::t('Sitemap URL Set Limit'),
            'sitemapIncludeAssets' => Plugin::t('Sitemap Include Assets'),
            'sitemapIgnoreRules' => Plugin::t('Sitemap obey ignore rules'),
            'sitemapDefaultPriority' => Plugin::t('Sitemap Default Priority'),
            'sitemapDefaultChangefreq' => Plugin::t('Sitemap Default Change Frequency'),
            'sitemapIgnoreNewPageUrls' => Plugin::t('Sitemap Ignore New Pages'),
            'sitemapIgnoreNewAssetUrls' => Plugin::t('Sitemap Ignore New Assets'),
            'enableRedirects' => Plugin::t('Enable Redirects'),
            'enableTermMaps' => Plugin::t('Enable Search Term Maps'),
            'enableTermPriorities' => Plugin::t('Enable Search Term Priorities'),
            'enableIgnoreRules' => Plugin::t('Enable Ignore Rules'),
            'enableQueryParamRules' => Plugin::t('Enable Query Param Rules'),
            'robotsTxt' => Plugin::t('Obey Robots.txt'),
            'robotsUaString' => Plugin::t('Robots.txt User-Agent String'),
            'robotsMetaTag' => Plugin::t('Obey Robots Meta Tag'),
            'robotsHttpHeader' => Plugin::t('Obey X-Robots-Tag HTTP Header'),
        ];
    }

    public function hasGeneralErrors(): bool
    {
        if ($this->getErrors('pluginName') ||
            $this->getErrors('trackQueries') ||
            $this->getErrors('track404s') ||
            $this->getErrors('uaString') ||
            $this->getErrors('searchableAssetFieldHandle')
        ) {
            return true;
        }

        return false;
    }

    public function hasFeaturesErrors(): bool
    {
        if ($this->getErrors('enableRedirects') ||
            $this->getErrors('enableTermMaps') ||
            $this->getErrors('enableTermPriorities') ||
            $this->getErrors('enableIgnoreRules') ||
            $this->getErrors('enableQueryParamRules')
        ) {
            return true;
        }

        return false;
    }

    public function hasIndexingErrors(): bool
    {
        if ($this->getErrors('trackHits') ||
            $this->getErrors('hitScorePeriod') ||
            $this->getErrors('weightedHitScore') ||
            $this->getErrors('robotsTxt') ||
            $this->getErrors('robotsUaString') ||
            $this->getErrors('robotsMetaTag') ||
            $this->getErrors('robotsHttpHeader')
        ) {
            return true;
        }

        return false;
    }

    public function hasSitemapErrors(): bool
    {
        if ($this->getErrors('enableSitemap') ||
            $this->getErrors('sitemapName') ||
            $this->getErrors('sitemapUrlLimit') ||
            $this->getErrors('sitemapIncludeAssets') ||
            $this->getErrors('sitemapIgnoreRules') ||
            $this->getErrors('sitemapDefaultPriority') ||
            $this->getErrors('sitemapDefaultChangefreq') ||
            $this->getErrors('sitemapIgnoreNewPageUrls') ||
            $this->getErrors('sitemapIgnoreNewAssetUrls')
        ) {
            return true;
        }

        return false;
    }
}
