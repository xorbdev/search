<?php
namespace xorb\search\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Edit as EditAction;
use craft\elements\actions\Restore as RestoreAction;
use craft\elements\actions\Duplicate as DuplicateAction;
use craft\elements\User;
use craft\events\DefineHtmlEvent;
use craft\fieldlayoutelements\Html as HtmlFieldLayout;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\validators\UrlValidator;
use DateTime;
use xorb\search\db\Table;
use xorb\search\elements\db\ResultQuery;
use xorb\search\elements\actions\SetStatus as SetStatusAction;
use xorb\search\elements\actions\IgnoreResult as IgnoreResultAction;
use xorb\search\elements\actions\IgnoreRules as IgnoreRulesAction;
use xorb\search\elements\actions\IgnoreSitemap as IgnoreSitemapAction;
use xorb\search\elements\actions\UnignoreResult as UnignoreResultAction;
use xorb\search\elements\actions\UnignoreRules as UnignoreRulesAction;
use xorb\search\elements\actions\UnignoreSitemap as UnignoreSitemapAction;
use xorb\search\elements\actions\UpdateResult as UpdateResultAction;
use xorb\search\Plugin;
use xorb\search\records\Result as ResultRecord;
use yii\base\InvalidConfigException;

class Result extends Element
{
    public const STATUS_ERROR = 'error';
    public const STATUS_SEARCH_ENABLED = 'search-enabled';
    public const STATUS_SEARCH_DISABLED = 'search-disabed';
    public const STATUS_SITEMAP_ENABLED = 'sitemap-enabled';
    public const STATUS_SITEMAP_DISABLED = 'sitemap-disabed';
    public const STATUS_RULES_ENABLED = 'rules-enabled';
    public const STATUS_RULES_DISABLED = 'rules-disabed';

    public ?string $resultType = null;
    public ?int $resultId = null;
    public ?string $resultTitle = null;
    public ?string $resultDescription = null;
    public ?string $resultUrl = null;
    public ?string $resultHash = null;
    public ?string $mainHash = null;
    public ?string $mainData = null;
    public int $score = 0;
    public int $searchPriority = 0;
    public bool $searchIgnore = false;
    public int $sitemapPriority = 0;
    public string $sitemapChangefreq = 'weekly';
    public bool $sitemapIgnore = false;
    public bool $rulesIgnore = false;
    public bool $error = false;
    public ?int $errorCode = null;
    public ?DateTime $dateResultModified = null;
    public ?DateTime $dateMainModified = null;
    public ?DateTime $dateUnavailableAfter = null;
    public ?DateTime $dateError = null;

    protected ?string $linkUrl = null;

    public function init(): void
    {
        parent::init();

        $this->title = $this->resultTitle;
        $this->linkUrl = $this->resultUrl;
        // $this->setUiLabel(Plugin::t('Edit'));
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'resultType',
                'resultUrl',
            ],
            'required'
        ];

        $rules[] = [
            [
                'resultId',
            ],
            'number',
            'integerOnly' => true,
        ];

        $rules[] = [
            [
                'resultHash',
                'mainHash',
            ],
            'string',
            'max' => 32
        ];

        $rules[] = [
            [
                'resultTitle',
                'resultUrl',
            ], 'string',
            'max' => 250
        ];

        $rules[] = [
            [
                'resultDescription',
                'mainData',
            ],
            'string'
        ];

        $rules[] = [
            [
                'resultUrl'
            ],
            UrlValidator::class,
            'defaultScheme' => 'https'
        ];

        $rules[] = [
            [
                'score',
                'searchPriority',
                'sitemapPriority',
            ],
            'number',
            'integerOnly' => true,
            'min' => 0
        ];

        $rules[] = [
            [
                'searchIgnore',
                'sitemapIgnore',
                'rulesIgnore',
                'error',
            ],
            'boolean'
        ];

        return $rules;
    }

    public function attributeLabels(): array
    {
        return [
            'resultType' => Plugin::t('Result Type'),
            'resultId' => Plugin::t('Result ID'),
            'resultTitle' => Plugin::t('Result Title'),
            'resultUrl' => Plugin::t('Result URI'),
            'resultDescription' => Plugin::t('Result Description'),
            'resultHash' => Plugin::t('Result Hash'),
            'mainHash' => Plugin::t('Main Hash'),
            'mainData' => Plugin::t('Main Data'),
            'score' => Plugin::t('Score'),
            'searchPriority' => Plugin::t('Priority'),
            'searchIgnore' => Plugin::t('Ignore'),
            'sitemapPriority' => Plugin::t('Sitemap Priority'),
            'sitemapChangeFreq' => Plugin::t('Sitemap Change Frequency'),
            'sitemapIgnore' => Plugin::t('Sitemap Ignore'),
            'rulesIgnore' => Plugin::t('Ignore Rules'),
            'error' => Plugin::t('Error'),
            'errorCode' => Plugin::t('Error Code'),
            'dateResultModified' => Plugin::t('Result Modified Date'),
            'dateMainModified' => Plugin::t('Main Modified Date'),
            'dateUnavailableAfter' => Plugin::t('Unavailable After Date'),
            'dateError' => Plugin::t('Error Date'),
        ];
    }

    public static function displayName(): string
    {
        return Plugin::t('Result');
    }

    public static function lowerDisplayName(): string
    {
        return Plugin::t('result');
    }

    public static function pluralDisplayName(): string
    {
        return Plugin::t('Results');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Plugin::t('results');
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        $plugin = Plugin::getInstance();

        $statuses = [
            self::STATUS_SEARCH_ENABLED => ['label' => Plugin::t('Search Enabled'), 'color' => 'green'],
            self::STATUS_SITEMAP_ENABLED => ['label' => Plugin::t('Sitemap Enabled'), 'color' => 'green'],
            self::STATUS_RULES_ENABLED => ['label' => Plugin::t('Rules Enabled'), 'color' => 'green'],
            self::STATUS_SEARCH_DISABLED => ['label' => Plugin::t('Search Disabled')],
            self::STATUS_SITEMAP_DISABLED => ['label' => Plugin::t('Sitemap Disabled')],
            self::STATUS_RULES_DISABLED => ['label' => Plugin::t('Rules Disabled')],
            self::STATUS_ERROR => ['label' => Plugin::t('Error'), 'color' => 'red'],
        ];

        if (!$plugin->getFeatures()->showSitemap()) {
            unset(
                $statuses[self::STATUS_SITEMAP_ENABLED],
                $statuses[self::STATUS_SITEMAP_DISABLED],
            );
        }

        return $statuses;
    }

    public function getStatus(): ?string
    {
        if ($this->error) {
            return self::STATUS_ERROR;
        }

        if ($this->searchIgnore) {
            return self::STATUS_DISABLED;
        }

        return self::STATUS_ENABLED;
    }

    public static function find(): ResultQuery
    {
        return new ResultQuery(static::class);
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_SAVE_RESULTS);
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_VIEW_RESULTS);
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_DELETE_RESULTS);
    }

    public function canDeleteForSite(User $user): bool
    {
        return false;
    }

    public function canDuplicate(User $user): bool
    {
        return false;
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    public function crumbs(): array
    {
        return [
            [
                'label' => Plugin::t('Search'),
                'url' => 'search',
            ],
            [
                'label' => Plugin::t('Results'),
                'url' =>  'search/results',
            ],
        ];
    }

    public function getSidebarHtml(bool $static): string
    {
        $components = [];

        $metaFieldsHtml = $this->metaFieldsHtml($static);
        if ($metaFieldsHtml !== '') {
            $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta']) .
                Html::tag('h2', Craft::t('app', 'Metadata'), ['class' => 'visually-hidden']);
        }

        $searchFieldsHtml = $this->searchFieldsHtml($static);
        if ($searchFieldsHtml !== '') {
            $components[] = Html::beginTag('fieldset') .
                Html::tag('legend', Plugin::t('Search'), ['class' => 'visually-hidden']) .
                Html::tag('div', $searchFieldsHtml, ['class' => 'meta']) .
                Html::endTag('fieldset');
        }

        $sitemapFieldsHtml = $this->sitemapFieldsHtml($static);
        if ($sitemapFieldsHtml !== '') {
            $components[] = Html::beginTag('fieldset') .
                Html::tag('legend', Plugin::t('Sitemap'), ['class' => 'h6']) .
                Html::tag('div', $sitemapFieldsHtml, ['class' => 'meta']) .
                Html::endTag('fieldset');
        }

        // Fire a defineSidebarHtml event
        $event = new DefineHtmlEvent([
            'html' => implode("\n", $components),
        ]);
        $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);
        return $event->html;
    }

    protected function searchFieldsHtml(bool $static): string
    {
        $fields[] = Cp::lightswitchFieldHtml([
            'id' => 'searchEnabled',
            'label' => Plugin::t('Search Enabled'),
            'name' => 'searchEnabled',
            'on' => !$this->searchIgnore,
            'disabled' => $static,
        ]);

        $fields[] = Cp::textFieldHtml([
            'label' => Plugin::t('Priority'),
            'id' => 'searchPriority',
            'name' => 'searchPriority',
            'type' => 'number',
            'value' => $this->searchPriority,
            'errors' => $this->getErrors('searchPriority'),
            'disabled' => $static,
            'step' => 1,
            'min' => 0,
        ]);

        $fields[] = Cp::dateTimeFieldHtml([
            'label' => Plugin::t('Unavailable After'),
            'id' => 'dateUnavailableAfter',
            'name' => 'dateUnavailableAfter',
            'value' => $this->dateUnavailableAfter,
            'first' => true,
            'errors' => $this->getErrors('dateUnavailableAfter'),
            'disabled' => $static,
        ]);

        $fields[] = Cp::lightswitchFieldHtml([
            'id' => 'rulesEnabled',
            'label' => Plugin::t('Rules Enabled'),
            'name' => 'rulesEnabled',
            'on' => !$this->rulesIgnore,
            'disabled' => $static,
        ]);

        return implode("\n", $fields);
    }

    protected function sitemapFieldsHtml(bool $static): string
    {
        $plugin = Plugin::getInstance();

        if (!$plugin->isPro()) {
            return '';
        }

        $fields[] = $statusFields[] = Cp::lightswitchFieldHtml([
            'id' => 'sitemapEnabled',
            'label' => Plugin::t('Sitemap Enabled'),
            'name' => 'sitemapEnabled',
            'on' => !$this->sitemapIgnore,
            'disabled' => $static,
        ]);

        $fields[] = Cp::selectFieldHtml([
            'label' => Plugin::t('Priority'),
            'id' => 'sitemapPriority',
            'name' => 'sitemapPriority',
            'value' => $this->sitemapPriority,
            'options' => [
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
            ],
            'disabled' => $static,
            'errors' => $this->getErrors('typeId'),
        ]);

        $fields[] = Cp::selectFieldHtml([
            'label' => Plugin::t('Change Frequency'),
            'id' => 'sitemapChangefreq',
            'name' => 'sitemapChangefreq',
            'value' => $this->sitemapChangefreq,
            'options' => [
                'always' => Plugin::t('Always'),
                'hourly' => Plugin::t('Hourly'),
                'daily' => Plugin::t('Daily'),
                'weekly' => Plugin::t('Weekly'),
                'monthly' => Plugin::t('Monthly'),
                'yearly' => Plugin::t('Yearly'),
                'never' => Plugin::t('Never'),
            ],
            'disabled' => $static,
            'errors' => $this->getErrors('typeId'),
        ]);

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();

        if ($this->getStatus() !== self::STATUS_ERROR) {
            return [
                Plugin::t('Score') => $this->score,
                Plugin::t('Last Changed') => $this->dateMainModified
                    ? $formatter->asDatetime($this->dateMainModified, Formatter::FORMAT_WIDTH_SHORT)
                    : false,
            ];
        }

        return [
            Plugin::t('Error Code') => $this->errorCode,
            Plugin::t('Error Date') => $this->dateError
            ? $formatter->asDatetime($this->dateError, Formatter::FORMAT_WIDTH_SHORT)
            : false,
        ];
    }

    protected function statusFieldHtml(): string
    {
        return '';
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return parent::getFieldLayout() ?? Craft::$app->getFields()->getLayoutByType(self::class);
    }

    protected static function defineActions(string $source): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_RESULTS)) {
            /* $actions[] = $elementsService->createAction([
                'type' => ViewAction::class,
                'label' => Craft::t('app', 'View {type}', [
                    'type' => static::lowerDisplayName(),
                ]),
            ]); */
        }

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_SAVE_RESULTS)) {
            $actions[] = $elementsService->createAction([
                'type' => SetStatusAction::class,
            ]);
        }

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_UPDATE_RESULTS)) {
            $actions[] = $elementsService->createAction([
                'type' => UpdateResultAction::class,
            ]);
        }

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_DELETE_RESULTS)) {
            $actions[] = $elementsService->createAction([
                'type' => RestoreAction::class,
                'successMessage' => Plugin::t('Results restored.'),
                'partialSuccessMessage' => Plugin::t('Some results restored.'),
                'failMessage' => Plugin::t('Results not restored.'),
            ]);
        }

        return $actions;
    }
    public static function actions(string $source): array
    {
        $actions = parent::actions($source);

        // Remove unused options
        foreach ($actions as $key => $value) {
            if ($value === DuplicateAction::class) {
                unset($actions[$key]);
                $actions = array_values($actions);
                break;
            }
        }

        return $actions;
    }
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Title'),
                'orderBy' => Table::RESULTS . '.resultTitle',
                'attribute' => 'title',
                'defaultDir' => 'asc',
            ],
            [
                'label' => Plugin::t('Type'),
                'orderBy' => Table::RESULTS . '.resultType',
                'attribute' => 'type',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'URI'),
                'orderBy' => Table::RESULTS . '.resultUrl',
                'attribute' => 'uri',
                'defaultDir' => 'desc',
            ]
        ];
    }
    protected function defineSearchableAttrubutes(): array
    {
        return [
            'id',
            'resultTitle',
            'resultUrl',
            'resultDescription',
        ];
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('search/results');
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('search/results/' . $this->id);
    }

    public function setAttributesFromRequest(array $values): void
    {
        $plugin = Plugin::getInstance();

        $searchEnabled = ArrayHelper::remove($values, 'searchEnabled');
        $rulesEnabled = ArrayHelper::remove($values, 'rulesEnabled');
        $dateUnavailableAfter = ArrayHelper::remove($values, 'dateUnavailableAfter');

        if ($searchEnabled !== null) {
            $this->searchIgnore = ($searchEnabled ? false : true);
        }

        if ($rulesEnabled !== null) {
            $this->rulesIgnore = ($rulesEnabled ? false : true);
        }

        if ($dateUnavailableAfter !== null) {
            $this->dateUnavailableAfter = DateTimeHelper::toDateTime($dateUnavailableAfter) ?: null;
        }

        if ($plugin->isPro()) {
            $sitemapEnabled = ArrayHelper::remove($values, 'sitemapEnabled');

            if ($sitemapEnabled !== null) {
                $this->sitemapIgnore = ($sitemapEnabled ? false : true);
            }
        }

        parent::setAttributesFromRequest($values);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = ResultRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid result ID: ' . $this->id);
            }
        } else {
            $record = new ResultRecord();
            $record->id = intval($this->id);
        }

        $record->resultType = $this->resultType;
        $record->resultId = $this->resultId;
        $record->resultTitle = $this->resultTitle;
        $record->resultUrl = $this->resultUrl;
        $record->resultDescription = $this->resultDescription;
        $record->resultHash = $this->resultHash;
        $record->mainHash = $this->mainHash;
        $record->mainData = $this->mainData;
        if (Craft::$app->getDb()->getIsPgsql()) {
            $record->mainData_vector = $this->mainData;
        }
        $record->score = $this->score;
        $record->searchPriority = $this->searchPriority;
        $record->searchIgnore = $this->searchIgnore;
        $record->sitemapPriority = $this->sitemapPriority;
        $record->sitemapChangefreq = $this->sitemapChangefreq;
        $record->sitemapIgnore = $this->sitemapIgnore;
        $record->rulesIgnore = $this->rulesIgnore;
        $record->error = $this->error;
        $record->errorCode = $this->errorCode;
        $record->dateResultModified = Db::prepareDateForDb($this->dateResultModified);
        $record->dateMainModified = Db::prepareDateForDb($this->dateMainModified);
        $record->dateUnavailableAfter = Db::prepareDateForDb($this->dateUnavailableAfter);
        $record->dateError = Db::prepareDateForDb($this->dateError);

        $record->save(false);

        parent::afterSave($isNew);
    }

    public function getSupportedSites(): array
    {
        if ($this->siteId) {
            return [$this->siteId];
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    protected static function defineSources(string $context = null): array
    {
        $plugin = Plugin::getInstance();

        $sources = [
            [
                'key' => '*',
                'label' => Plugin::t('All Results'),
                'criteria' => []
            ],
        ];

        if ($plugin->isPro()) {
            $sources[] = [
                'key' => 'result:pages',
                'label' => Plugin::t('Page Results'),
                'criteria' => [
                    'resultType' => 'page'
                ]
            ];
            $sources[] = [
                'key' => 'result:assets',
                'label' => Plugin::t('Asset Results'),
                'criteria' => [
                    'resultType' => 'asset'
                ]
            ];
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'type' => ['label' => Plugin::t('Type')],
            // 'title' => ['label' => Craft::t('app', 'Title')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'errorCode' => ['label' => Plugin::t('Error')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'type',
            // 'title',
            'uri',
            'link',
            'errorCode',
        ];
    }

    protected function attributeHtml(string $attribute): string
    {
        // TODO Change word from visit webpage if an asset
        switch ($attribute) {
            case 'type':
                return ElementHelper::attributeHtml($this->resultType);
            /* case 'title':
                return ElementHelper::attributeHtml($this->resultTitle); */
            case 'link':
                $url = $this->linkUrl;

                if ($url !== null) {
                    return Html::a('', $url, [
                        'rel' => 'noopener',
                        'target' => '_blank',
                        'data-icon' => 'world',
                        'title' => Craft::t('app', 'Visit webpage'),
                        'aria-label' => Craft::t('app', 'View'),
                    ]);
                }

                return '';
            case 'uri':
                $sites = Craft::$app->getSites();

                return $this->linkAttributeHtml(
                    $this->linkUrl,
                    ($sites->getTotalSites() !== 1)
                );
        }

        return parent::attributeHtml($attribute);
    }

    protected function linkAttributeHtml(
        ?string $url,
        bool $relative = false
    ): string
    {
        if ($url === null) {
            return '';
        }

        $urlText = $url;

        if ($relative) {
            $parts = explode('://', $url, 2);
            if (count($parts) === 2) {
                if ($parts[1] === '') {
                    $urlText = '/';
                } else {
                    $parts = explode('/', $parts[1], 2);
                    $urlText = '/' . $parts[1];
                }
            }
        }

        $find = ['/'];
        $replace = ['/<wbr>'];

        $wordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

        if ($wordSeparator) {
            $find[] = $wordSeparator;
            $replace[] = $wordSeparator . '<wbr>';
        }

        if ($url === '/') {
            $urlText = Html::tag('span', '', [
                'data-icon' => 'home',
                'title' => Craft::t('app', 'Homepage'),
            ]);
        } else {
            $urlText = str_replace($find, $replace, $urlText);
        }

        return Html::a(Html::tag('span', $urlText, ['dir' => 'ltr']), $url, [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'class' => 'go',
            'title' => Craft::t('app', 'Visit webpage'),
        ]);
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['resultType', 'resultTitle', 'resultUrl', 'mainData'];
    }
}
