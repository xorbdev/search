<?php
namespace xorb\search\web\twig;

use Craft;
use xorb\search\Plugin;

class CpVariable extends BaseVariable
{
    public function hasIgnoreRules(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getIgnoreRules()->hasIgnoreRules();
    }

    public function hasQueryParamRules(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getQueryParamRules()->hasQueryParamRules();
    }

    public function hasTermMaps(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getTermMaps()->hasTermMaps();
    }

    public function hasTermPriorities(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getTermPriorities()->hasTermPriorities();
    }

    public function hasRedirects(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getRedirects()->hasRedirects();
    }

    public function comparatorOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            'exact' => Plugin::t('Exact Match'),
            'contains' => Plugin::t('Contains'),
            'startsWith' => Plugin::t('Starts With'),
            'endsWith' => Plugin::t('Ends With'),
            'regex' => Plugin::t('Matches Regex Pattern'),
            'notContains' => Plugin::t('Doesn\'t Contain'),
            'notStartsWith' => Plugin::t('Doesn\'t Start With'),
            'notEndsWith' => Plugin::t('Doesn\'t End With'),
            'notRegex' => Plugin::t('Doesn\'t Match Regex Pattern'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function hitScorePeriodOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            'day' => Plugin::t('Day'),
            'week' => Plugin::t('Week'),
            'month' => Plugin::t('Month'),
            'three-months' => Plugin::t('Three Months'),
            'six-months' => Plugin::t('Six Months'),
            'year' => Plugin::t('Year'),
            'all' => Plugin::t('All Time'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function redirectTypeOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            '301' => Plugin::t('301 Permanant'),
            '302' => Plugin::t('302 Temporary'),
            '410' => Plugin::t('410 Gone'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function sitemapChangefreqOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            'always' => Plugin::t('Always'),
            'hourly' => Plugin::t('Hourly'),
            'daily' => Plugin::t('Daily'),
            'weekly' => Plugin::t('Weekly'),
            'monthly' => Plugin::t('Monthly'),
            'yearly' => Plugin::t('Yearly'),
            'never' => Plugin::t('Never'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function sitemapPriorityOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            '0' => Plugin::t('0.0 (Low)'),
            '10' => Plugin::t('0.1'),
            '20' => Plugin::t('0.2'),
            '30' => Plugin::t('0.3'),
            '40' => Plugin::t('0.4'),
            '50' => Plugin::t('0.5'),
            '60' => Plugin::t('0.6'),
            '70' => Plugin::t('0.7'),
            '80' => Plugin::t('0.8'),
            '90' => Plugin::t('0.9'),
            '100' => Plugin::t('1.0 (High)'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function siteOptions($emptyOption = null): array
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = strval($emptyOption);
        }

        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            $options[$site->id] = $site->name;
        }

        return $options;
    }

    public function settingsNavItems(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        $showRulesHeader = $features->isSettingsRules();
        $showTermsHeader = $features->isSettingsTerms();

        $items = [
            'general' => ['title' => Plugin::t('General Settings')],
            'results' => ['title' => Plugin::t('Result Fields')],
        ];

        if (!$showRulesHeader) {
            if ($features->isSettingsIgnoreRules()) {
                $items['ignore-rules'] = ['title' => Plugin::t('Ignore Rules')];
            } elseif ($features->isSettingsQueryParamRules()) {
                $items['query-param-rules'] = ['title' => Plugin::t('Query Param Rules')];
            }
        }

        if (!$showTermsHeader) {
            if ($features->isSettingsTermMaps()) {
                $items['term-maps'] = ['title' => Plugin::t('Search Term Maps')];
            } elseif ($features->isSettingsTermPriorities()) {
                $items['term-priorities'] = ['title' => Plugin::t('Search Term Priorities')];
            }
        }

        if ($features->isSettingsRedirects()) {
            $items['redirects'] = ['title' => Plugin::t('Redirects')];
        }

        if ($showRulesHeader) {
            $items['rules-heading'] = ['heading' => Plugin::t('Rules')];

            if ($features->isSettingsIgnoreRules()) {
                $items['ignore-rules'] = ['title' => Plugin::t('Ignore Rules')];
            }

            if ($features->isSettingsQueryParamRules()) {
                $items['query-param-rules'] = ['title' => Plugin::t('Query Param Rules')];
            }
        }

        if ($showTermsHeader) {
            $items['terms-heading'] = ['heading' => Plugin::t('Search Terms')];

            if ($features->isSettingsTermMaps()) {
                $items['term-maps'] = ['title' => Plugin::t('Maps')];
            }
            if ($features->isSettingsTermPriorities()) {
                $items['term-priorities'] = ['title' => Plugin::t('Priorities')];
            }
        }

        return $items;
    }

    public function rulesNavItems(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        $items = [];

        if ($features->isCpIgnoreRules()) {
            $items['ignore-rules'] = ['title' => Plugin::t('Ignore Rules')];
        }

        if ($features->isCpQueryParamRules()) {
            $items['query-param-rules'] = ['title' => Plugin::t('Query Param Rules')];
        }

        return $items;
    }

    public function ignoreRulesMeta(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        if ($features->isCpRules()) {
            return [
                'layout' => 'search/_layouts/rules',
                'url' => 'search/rules/ignore-rules',
            ];
        } elseif ($features->isCpIgnoreRules()) {
            return [
                'layout' => 'search/_layouts/rules',
                'url' => 'search/ignore-rules',
            ];
        } else {
            return [
                'layout' => 'search/_layouts/settings',
                'url' => 'search/settings/ignore-rules',
            ];
        }
    }

    public function queryParamRulesMeta(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        if ($features->isCpRules()) {
            return [
                'layout' => 'search/_layouts/rules',
                'url' => 'search/rules/query-param-rules',
            ];
        } elseif ($features->isCpQueryParamRules()) {
            return [
                'layout' => 'search/_layouts/rules',
                'url' => 'search/query-param-rules',
            ];
        } else {
            return [
                'layout' => 'search/_layouts/settings',
                'url' => 'search/settings/query-param-rules',
            ];
        }
    }

    public function termsNavItems(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        $items = [];

        if ($features->isCpTermMaps()) {
            $items['maps'] = ['title' => Plugin::t('Search Term Maps')];
        }

        if ($features->isCpTermPriorities()) {
            $items['priorities'] = ['title' => Plugin::t('Search Term Priorities')];
        }

        return $items;
    }

    public function termMapsMeta(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        if ($features->isCpTerms()) {
            return [
                'layout' => 'search/_layouts/terms',
                'url' => 'search/terms/maps',
            ];
        } elseif ($features->isCpTermMaps()) {
            return [
                'layout' => 'search/_layouts/terms',
                'url' => 'search/term-maps',
            ];
        } else {
            return [
                'layout' => 'search/_layouts/settings',
                'url' => 'search/settings/term-maps',
            ];
        }
    }

    public function termPrioritiesMeta(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        if ($features->isCpTerms()) {
            return [
                'layout' => 'search/_layouts/terms',
                'url' => 'search/terms/priorities',
            ];
        } elseif ($features->isCpTermPriorities()) {
            return [
                'layout' => 'search/_layouts/terms',
                'url' => 'search/term-priorities',
            ];
        } else {
            return [
                'layout' => 'search/_layouts/settings',
                'url' => 'search/settings/term-priorities',
            ];
        }
    }

    public function redirectsMeta(): array
    {
        $features = Plugin::getInstance()->getFeatures();

        if ($features->isCpRedirects()) {
            return [
                'layout' => 'search/_layouts/redirects',
                'url' => 'search/redirects',
            ];
        } else {
            return [
                'layout' => 'search/_layouts/settings',
                'url' => 'search/settings/redirects',
            ];
        }
    }

    public function showSitemap(): bool
    {
        $features = Plugin::getInstance()->getFeatures();

        return $features->showSitemap();
    }
}
