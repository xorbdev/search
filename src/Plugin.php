<?php
namespace xorb\search;

use Craft;
use craft\base\Model;
use craft\events\ExceptionEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\services\Dashboard;
use craft\services\Utilities;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use craft\web\View;
use Twig\Error\RuntimeError;
use xorb\search\controllers\IgnoreRulesController;
use xorb\search\controllers\TermsController;
use xorb\search\controllers\TermMapsController;
use xorb\search\controllers\TermPrioritiesController;
use xorb\search\controllers\QueriesController;
use xorb\search\controllers\QueryParamRulesController;
use xorb\search\controllers\RedirectsController;
use xorb\search\controllers\ResultsController;
use xorb\search\controllers\SettingsController;
use xorb\search\controllers\UtilitiesController;
use xorb\search\db\Table;
use xorb\search\elements\Result as ResultElement;
use xorb\search\fields\SearchableField;
use xorb\search\models\Settings as SettingsModel;
use xorb\search\services\Features;
use xorb\search\services\Hits;
use xorb\search\services\IgnoreRules;
use xorb\search\services\TermMaps;
use xorb\search\services\TermPriorities;
use xorb\search\services\Queries;
use xorb\search\services\QueryParamRules;
use xorb\search\services\Redirects;
use xorb\search\services\Results;
use xorb\search\services\Settings;
use xorb\search\services\Sitemap;
use xorb\search\utilities\SearchIndex as SearchIndexUtility;
use xorb\search\web\twig\CpVariable;
use xorb\search\web\twig\SiteVariable;
use xorb\search\widgets\RecentSearches as RecentSearchesWidget;
use yii\base\Event;
use yii\web\HttpException;

/**
 * @method SettingsModel getSettings()
 */
class Plugin extends \craft\base\Plugin
{
    public const HANDLE = 'search';

    public const PROJECT_CONFIG_PATH = 'xorb.' . self::HANDLE;

    public const PERMISSION_UPDATE_RESULTS = self::HANDLE . '-updateResults';
    public const PERMISSION_VIEW_RESULTS = self::HANDLE . '-viewResults';
    public const PERMISSION_CREATE_RESULTS = self::HANDLE . '-createResults';
    public const PERMISSION_SAVE_RESULTS = self::HANDLE . '-saveResults';
    public const PERMISSION_DELETE_RESULTS = self::HANDLE . '-deleteResults';
    public const PERMISSION_IGNORE_RESULTS = self::HANDLE . '-ignoreResults';

    public const PERMISSION_VIEW_TERM_MAPS = self::HANDLE . '-viewTermMaps';
    public const PERMISSION_CREATE_TERM_MAPS = self::HANDLE . '-createTermMaps';
    public const PERMISSION_SAVE_TERM_MAPS = self::HANDLE . '-saveTermMaps';
    public const PERMISSION_DELETE_TERM_MAPS = self::HANDLE . '-deleteTermMaps';

    public const PERMISSION_VIEW_TERM_PRIORITIES = self::HANDLE . '-viewTermPriorities';
    public const PERMISSION_CREATE_TERM_PRIORITIES = self::HANDLE . '-createTermPriorites';
    public const PERMISSION_SAVE_TERM_PRIORITIES = self::HANDLE . '-saveTermPriorites';
    public const PERMISSION_DELETE_TERM_PRIORITIES = self::HANDLE . '-deleteTermPriorites';

    public const PERMISSION_VIEW_IGNORE_RULES = self::HANDLE . '-viewIgnoreRules';
    public const PERMISSION_CREATE_IGNORE_RULES = self::HANDLE . '-createIgnoreRules';
    public const PERMISSION_SAVE_IGNORE_RULES = self::HANDLE . '-saveIgnoreRules';
    public const PERMISSION_DELETE_IGNORE_RULES = self::HANDLE . '-deleteIgnoreRules';

    public const PERMISSION_VIEW_QUERY_PARAM_RULES = self::HANDLE . '-viewQueryParamRules';
    public const PERMISSION_CREATE_QUERY_PARAM_RULES = self::HANDLE . '-createQueryParamRules';
    public const PERMISSION_SAVE_QUERY_PARAM_RULES = self::HANDLE . '-saveQueryParamRules';
    public const PERMISSION_DELETE_QUERY_PARAM_RULES = self::HANDLE . '-deleteQueryParamRules';

    public const PERMISSION_VIEW_REDIRECTS = self::HANDLE . '-viewRedirects';
    public const PERMISSION_CREATE_REDIRECTS = self::HANDLE . '-createRedirects';
    public const PERMISSION_SAVE_REDIRECTS = self::HANDLE . '-saveRedirects';
    public const PERMISSION_DELETE_REDIRECTS = self::HANDLE . '-deleteRedirects';

    public const PERMISSION_VIEW_QUERIES = self::HANDLE . '-viewQueries';
    public const PERMISSION_SETTINGS = self::HANDLE . '-settings';

    public const EDITION_LITE = 'lite';
    public const EDITION_PRO = 'pro';

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    public function init()
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->initCp();
        } elseif (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->initSite();
        } elseif (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->initConsole();
        }

        $this->initServices();

        $this->hasCpSection = $this->hasCpNavItem();

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->getHits()->trackHit();
        }

        $pluginName = $this->getSettings()->pluginName;
        if (strval($pluginName) !== '') {
            $this->name = $pluginName;
        }

        $this->registerPermissions();
    }

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public function isPro(): bool
    {
        return $this->edition === self::EDITION_PRO;
    }

    public function isLite(): bool
    {
        return !$this->isPro();
    }

    private function initCp()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Results
                if ($this->getFeatures()->isCpResults()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/results' => self::HANDLE . '/results/index',
                        // self::HANDLE . '/results/new' => self::HANDLE . '/results/item',
                        self::HANDLE . '/results/<elementId:\d+>' => self::HANDLE . '/results/edit',
                    ]);
                }

                // Queries
                if ($this->getFeatures()->isCpQueries()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/queries' => self::HANDLE . '/queries/index',
                    ]);
                }

                // Settings
                if ($this->getFeatures()->showCpSettingsNavItem()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/settings' => self::HANDLE . '/settings/index',
                        self::HANDLE . '/settings/general' => self::HANDLE . '/settings/item',
                        self::HANDLE . '/settings/results' => self::HANDLE . '/result-settings/item',
                    ]);
                }

                // Terms
                if ($this->getFeatures()->isCpTerms()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/terms' => self::HANDLE . '/terms/index',

                        self::HANDLE . '/terms/maps' => self::HANDLE . '/term-maps/index',
                        self::HANDLE . '/terms/maps/new' => self::HANDLE . '/term-maps/item',
                        self::HANDLE . '/terms/maps/<id:\d+>' => self::HANDLE . '/term-maps/item',

                        self::HANDLE . '/terms/priorities' => self::HANDLE . '/term-priorities/index',
                        self::HANDLE . '/terms/priorities/new' => self::HANDLE . '/term-priorities/item',
                        self::HANDLE . '/terms/priorities/<id:\d+>' => self::HANDLE . '/term-priorities/item',
                    ]);
                } elseif ($this->getFeatures()->isCpTermMaps()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/term-maps' => self::HANDLE . '/term-maps/index',
                        self::HANDLE . '/term-maps/new' => self::HANDLE . '/term-maps/item',
                        self::HANDLE . '/term-maps/<id:\d+>' => self::HANDLE . '/term-maps/item',
                    ]);

                    if ($this->getFeatures()->isSettingsTermPriorities()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/term-priorities' => self::HANDLE . '/term-priorities/index',
                            self::HANDLE . '/settings/term-priorities/new' => self::HANDLE . '/term-priorities/item',
                            self::HANDLE . '/settings/term-priorities/<id:\d+>' => self::HANDLE . '/term-priorities/item',
                        ]);
                    }
                } elseif ($this->getFeatures()->isCpTermPriorities()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/term-priorities' => self::HANDLE . '/term-priorities/index',
                        self::HANDLE . '/term-priorities/new' => self::HANDLE . '/term-priorities/item',
                        self::HANDLE . '/term-priorities/<id:\d+>' => self::HANDLE . '/term-priorities/item',
                    ]);

                    if ($this->getFeatures()->isSettingsTermMaps()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/term-maps' => self::HANDLE . '/term-maps/index',
                            self::HANDLE . '/settings/term-maps/new' => self::HANDLE . '/term-maps/item',
                            self::HANDLE . '/settings/term-maps/<id:\d+>' => self::HANDLE . '/term-maps/item',
                        ]);
                    }
                } else {
                    if ($this->getFeatures()->isSettingsTermMaps()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/term-maps' => self::HANDLE . '/term-maps/index',
                            self::HANDLE . '/settings/term-maps/new' => self::HANDLE . '/term-maps/item',
                            self::HANDLE . '/settings/term-maps/<id:\d+>' => self::HANDLE . '/term-maps/item',
                        ]);
                    }

                    if ($this->getFeatures()->isSettingsTermPriorities()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/term-priorities' => self::HANDLE . '/term-priorities/index',
                            self::HANDLE . '/settings/term-priorities/new' => self::HANDLE . '/term-priorities/item',
                            self::HANDLE . '/settings/term-priorities/<id:\d+>' => self::HANDLE . '/term-priorities/item',
                        ]);
                    }
                }

                // Rules
                if ($this->getFeatures()->isCpRules()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/rules' => self::HANDLE . '/rules/index',

                        self::HANDLE . '/rules/ignore-rules' => self::HANDLE . '/ignore-rules/index',
                        self::HANDLE . '/rules/ignore-rules/new' => self::HANDLE . '/ignore-rules/item',
                        self::HANDLE . '/rules/ignore-rules/<id:\d+>' => self::HANDLE . '/ignore-rules/item',

                        self::HANDLE . '/rules/query-param-rules' => self::HANDLE . '/query-param-rules/index',
                        self::HANDLE . '/rules/query-param-rules/new' => self::HANDLE . '/query-param-rules/item',
                        self::HANDLE . '/rules/query-param-rules/<id:\d+>' => self::HANDLE . '/query-param-rules/item',
                    ]);
                } elseif ($this->getFeatures()->isCpIgnoreRules()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/ignore-rules' => self::HANDLE . '/ignore-rules/index',
                        self::HANDLE . '/ignore-rules/new' => self::HANDLE . '/ignore-rules/item',
                        self::HANDLE . '/ignore-rules/<id:\d+>' => self::HANDLE . '/ignore-rules/item',
                    ]);

                    if ($this->getFeatures()->isSettingsQueryParamRules()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/query-param-rules' => self::HANDLE . '/query-param-rules/index',
                            self::HANDLE . '/settings/query-param-rules/new' => self::HANDLE . '/query-param-rules/item',
                            self::HANDLE . '/settings/query-param-rules/<id:\d+>' => self::HANDLE . '/query-param-rules/item',
                        ]);
                    }
                } elseif ($this->getFeatures()->isCpQueryParamRules()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/query-param-rules' => self::HANDLE . '/query-param-rules/index',
                        self::HANDLE . '/query-param-rules/new' => self::HANDLE . '/query-param-rules/item',
                        self::HANDLE . '/query-param-rules/<id:\d+>' => self::HANDLE . '/query-param-rules/item',
                    ]);

                    if ($this->getFeatures()->isSettingsIgnoreRules()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/ignore-rules' => self::HANDLE . '/ignore-rules/index',
                            self::HANDLE . '/settings/ignore-rules/new' => self::HANDLE . '/ignore-rules/item',
                            self::HANDLE . '/settings/ignore-rules/<id:\d+>' => self::HANDLE . '/ignore-rules/item',

                        ]);
                    }
                } else {
                    if ($this->getFeatures()->isSettingsIgnoreRules()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/ignore-rules' => self::HANDLE . '/ignore-rules/index',
                            self::HANDLE . '/settings/ignore-rules/new' => self::HANDLE . '/ignore-rules/item',
                            self::HANDLE . '/settings/ignore-rules/<id:\d+>' => self::HANDLE . '/ignore-rules/item',
                        ]);
                    }

                    if ($this->getFeatures()->isSettingsQueryParamRules()) {
                        $event->rules = array_merge($event->rules, [
                            self::HANDLE . '/settings/query-param-rules' => self::HANDLE . '/query-param-rules/index',
                            self::HANDLE . '/settings/query-param-rules/new' => self::HANDLE . '/query-param-rules/item',
                            self::HANDLE . '/settings/query-param-rules/<id:\d+>' => self::HANDLE . '/query-param-rules/item',
                        ]);
                    }
                }

                if ($this->getFeatures()->isCpRedirects()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/redirects' => self::HANDLE . '/redirects/index',
                        self::HANDLE . '/redirects/new' => self::HANDLE . '/redirects/item',
                        self::HANDLE . '/redirects/<id:\d+>' => self::HANDLE . '/redirects/item',
                    ]);
                } elseif ($this->getFeatures()->isSettingsRedirects()) {
                    $event->rules = array_merge($event->rules, [
                        self::HANDLE . '/settings/redirects' => self::HANDLE . '/redirects/index',
                        self::HANDLE . '/settings/redirects/new' => self::HANDLE . '/redirects/item',
                        self::HANDLE . '/settings/redirects/<id:\d+>' => self::HANDLE . '/redirects/item',
                    ]);
                }
            }
        );

        if ($this->isPro()) {
            Event::on(
                Fields::class,
                Fields::EVENT_REGISTER_FIELD_TYPES,
                function(RegisterComponentTypesEvent $event) {
                    $event->types[] = SearchableField::class;
                }
            );
        }

        $this->controllerMap = [
            'ignore-rules' => IgnoreRulesController::class,
            'terms' => TermsController::class,
            'term-maps' => TermMapsController::class,
            'term-priorities' => TermPrioritiesController::class,
            'query-param-rules' => QueryParamRulesController::class,
            'redirects' => RedirectsController::class,
            'results' => ResultsController::class,
            'settings' => SettingsController::class,
            'utilities' => UtilitiesController::class,
        ];

        // Add cp twig variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $handle = StringHelper::toCamelCase(self::HANDLE);
                $event->sender->set($handle, CpVariable::class);
            }
        );

        // Register elements
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = ResultElement::class;
            }
        );

        // Elements garbage collection
        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function (Event $event) {
                Craft::$app->getGc()->deletePartialElements(
                    ResultElement::class,
                    Table::RESULTS,
                    'id'
                );
            }
        );

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function(DefineFieldLayoutFieldsEvent $event) {
                $fieldLayout = $event->sender;

                if ($fieldLayout->type !== ResultElement::class) {
                    return;
                }

                $event->fields[] = [
                    'label' => self::t('Result Title'),
                    'class' => \craft\fieldlayoutelements\TextField::class,
                    'attribute' => 'resultTitle',
                    'mandatory' => true,
                    'readonly' => true,
                ];

                $event->fields[] = [
                    'label' => self::t('Result URI'),
                    'class' => \craft\fieldlayoutelements\TextField::class,
                    'attribute' => 'resultUrl',
                    'mandatory' => true,
                    'readonly' => true,
                ];

                $event->fields[] = [
                    'label' => self::t('Result Description'),
                    'class' => \craft\fieldlayoutelements\TextareaField::class,
                    'attribute' => 'resultDescription',
                    'mandatory' => true,
                    'readonly' => true,
                ];
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITIES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = SearchIndexUtility::class;
            }
        );

        if ($this->isPro() && $this->getSettings()->trackQueries) {
            Event::on(
                Dashboard::class,
                Dashboard::EVENT_REGISTER_WIDGET_TYPES,
                function(RegisterComponentTypesEvent $event) {
                    $event->types[] = RecentSearchesWidget::class;
                }
            );
        }
    }
    private function initConsole()
    {
        $this->controllerNamespace = 'xorb\\search\\console\\controllers';
    }
    private function initSite()
    {
        // $this->view->registerAssetBundle(\xorb\search\web\assets\site\SiteAsset::class);

        // Add site twig variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $handle = StringHelper::toCamelCase(self::HANDLE);
                $event->sender->set($handle, SiteVariable::class);
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                if ($this->getSettings()->enableSitemap) {
                    $sitemapName = $this->getSettings()->sitemapName;

                    $event->rules = array_merge($event->rules, [
                        $sitemapName . '.xml' => self::HANDLE . '/sitemap/index',
                        $sitemapName . '-<page:\d*>.xml' => self::HANDLE . '/sitemap/page',
                    ]);
                }
            }
        );

        // Redirect handling
        if (!Craft::$app->getRequest()->getIsLivePreview()) {
            Event::on(
                ErrorHandler::class,
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function (ExceptionEvent $event) {
                    $exception = $event->exception;

                    // Only redirect if a 404
                    if (!($exception instanceof HttpException) ||
                        $exception->statusCode !== 404
                    ) {
                        // Handle {% exit 404 %} in twig templates
                        if (!($exception instanceof RuntimeError)) {
                            return;
                        }

                        $prev = $exception->getPrevious();

                        if (!($prev instanceof HttpException) ||
                            $prev->statusCode !== 404
                        ) {
                            return;
                        }
                    }

                    list ($to, $type) = $this->getRedirects()->getRedirect();

                    if ($type === 410) {
			            $event->handled = true;
                        Craft::$app->getResponse()->setStatusCode(410);
                    } elseif ($to !== null) {
			            $event->handled = true;
                        Craft::$app->getResponse()->redirect($to, $type)->send();
                        Craft::$app->end();
                    }
                }
            );
        }
    }
    private function initServices()
    {
        $this->setComponents([
            'features' => Features::class,
            'hits' => Hits::class,
            'ignoreRules' => IgnoreRules::class,
            'termMaps' => TermMaps::class,
            'termPriorities' => TermPriorities::class,
            'queries' => Queries::class,
            'queryParamRules' => QueryParamRules::class,
            'redirects' => Redirects::class,
            'results' => Results::class,
            'sitemap' => Sitemap::class,
        ]);

        $projectConfig = Craft::$app->getProjectConfig();

        $service = $this->getResults();
        $projectConfig->onAdd(Results::PROJECT_CONFIG_PATH . '.fieldLayout', [$service, 'handleChangedFieldLayout'])
            ->onUpdate(Results::PROJECT_CONFIG_PATH . '.fieldLayout', [$service, 'handleChangedFieldLayout'])
            ->onRemove(Results::PROJECT_CONFIG_PATH . '.fieldLayout', [$service, 'handleDeletedFieldLayout']);

        if (!$this->getSettings()->enableIgnoreRules) {
            $service = $this->getIgnoreRules();
            $projectConfig
                ->onAdd(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onUpdate(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onRemove(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
        }

        if (!$this->getSettings()->enableTermMaps) {
            $service = $this->getTermMaps();
            $projectConfig
                ->onAdd(TermMaps::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onUpdate(TermMaps::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onRemove(TermMaps::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
        }

        if (!$this->getSettings()->enableTermPriorities) {
            $service = $this->getTermPriorities();
            $projectConfig
                ->onAdd(TermPriorities::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onUpdate(TermPriorities::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onRemove(TermPriorities::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
        }

        if (!$this->getSettings()->enableQueryParamRules) {
            $service = $this->getQueryParamRules();
            $projectConfig
                ->onAdd(QueryParamRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onUpdate(QueryParamRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onRemove(QueryParamRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
        }

        if (!$this->getSettings()->enableRedirects) {
            $service = $this->getRedirects();
            $projectConfig
                ->onAdd(Redirects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onUpdate(Redirects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
                ->onRemove(Redirects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
        }
    }
    private function registerPermissions()
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return;
        }

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => $this->name,
                    'permissions' => [
                        self::PERMISSION_UPDATE_RESULTS => [
                            'label' => self::t('Update Results'),
                        ],

                        self::PERMISSION_VIEW_QUERIES => [
                            'label' => self::t('View Queries'),
                        ],

                        self::PERMISSION_VIEW_RESULTS => [
                            'label' => self::t('View Results'),
                            'nested' => [
                                self::PERMISSION_CREATE_RESULTS => [
                                    'label' => self::t('Add Results'),
                                ],
                                self::PERMISSION_SAVE_RESULTS => [
                                    'label' => self::t('Edit Results'),
                                ],
                                self::PERMISSION_DELETE_RESULTS => [
                                    'label' => self::t('Delete Results'),
                                ],
                                self::PERMISSION_IGNORE_RESULTS => [
                                    'label' => self::t('Ignore Results'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_TERM_MAPS => [
                            'label' => self::t('View Search Term Maps'),
                            'nested' => [
                                self::PERMISSION_CREATE_TERM_MAPS => [
                                    'label' => self::t('Add Search Term Maps'),
                                ],
                                self::PERMISSION_SAVE_TERM_MAPS => [
                                    'label' => self::t('Edit Search Term Maps'),
                                ],
                                self::PERMISSION_DELETE_TERM_MAPS => [
                                    'label' => self::t('Delete Search Term Maps'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_TERM_PRIORITIES => [
                            'label' => self::t('View Search Term Priorities'),
                            'nested' => [
                                self::PERMISSION_CREATE_TERM_PRIORITIES => [
                                    'label' => self::t('Add Search Term Priorities'),
                                ],
                                self::PERMISSION_SAVE_TERM_PRIORITIES => [
                                    'label' => self::t('Edit Search Term Priorities'),
                                ],
                                self::PERMISSION_DELETE_TERM_PRIORITIES => [
                                    'label' => self::t('Delete Search Term Priorities'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_IGNORE_RULES => [
                            'label' => self::t('View Ignore Rules'),
                            'nested' => [
                                self::PERMISSION_CREATE_IGNORE_RULES => [
                                    'label' => self::t('Add Ignore Rules'),
                                ],
                                self::PERMISSION_SAVE_IGNORE_RULES => [
                                    'label' => self::t('Edit Ignore Rules'),
                                ],
                                self::PERMISSION_DELETE_IGNORE_RULES => [
                                    'label' => self::t('Delete Ignore Rules'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_QUERY_PARAM_RULES => [
                            'label' => self::t('View Query Param Rules'),
                            'nested' => [
                                self::PERMISSION_CREATE_QUERY_PARAM_RULES => [
                                    'label' => self::t('Add Query Param Rules'),
                                ],
                                self::PERMISSION_SAVE_QUERY_PARAM_RULES => [
                                    'label' => self::t('Edit Query Param Rules'),
                                ],
                                self::PERMISSION_DELETE_QUERY_PARAM_RULES => [
                                    'label' => self::t('Delete Query Param Rules'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_REDIRECTS => [
                            'label' => self::t('View Redirects'),
                            'nested' => [
                                self::PERMISSION_CREATE_REDIRECTS => [
                                    'label' => self::t('Add Redirects'),
                                ],
                                self::PERMISSION_SAVE_REDIRECTS => [
                                    'label' => self::t('Edit Redirects'),
                                ],
                                self::PERMISSION_DELETE_REDIRECTS => [
                                    'label' => self::t('Delete Redirects'),
                                ],
                            ]
                        ],

                        self::PERMISSION_SETTINGS => [
                            'label' => self::t('Access Settings'),
                        ],
                    ]
                ];
            }
        );
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = self::t($this->name);

        $item['subnav'] = [];

        if ($this->getFeatures()->isCpResults()) {
            $item['subnav']['results'] = [
                'label' => self::t('Results'),
                'url' => self::HANDLE . '/results',
            ];
        }

        if ($this->getFeatures()->isCpQueries()) {
            $item['subnav']['queries'] = [
                'label' => self::t('Queries'),
                'url' => self::HANDLE . '/queries',
            ];
        }

        if ($this->getFeatures()->isCpTerms()) {
            $item['subnav']['terms'] = [
                'label' => self::t('Search Terms'),
                'url' => self::HANDLE . '/terms/maps',
            ];
        } elseif ($this->getFeatures()->isCpTermMaps()) {
            $item['subnav']['term-maps'] = [
                'label' => self::t('Search Term Maps'),
                'url' => self::HANDLE . '/term-maps',
            ];
        } elseif ($this->getFeatures()->isCpTermPriorities()) {
            $item['subnav']['term-priorities'] = [
                'label' => self::t('Search Term Priorities'),
                'url' => self::HANDLE . '/term-priorities',
            ];
        }

        if ($this->getFeatures()->isCpRules()) {
            $item['subnav']['rules'] = [
                'label' => self::t('Rules'),
                'url' => self::HANDLE . '/rules/ignore-rules',
            ];
        } elseif ($this->getFeatures()->isCpIgnoreRules()) {
            $item['subnav']['ignore-rules'] = [
                'label' => self::t('Ignore Rules'),
                'url' => self::HANDLE . '/ignore-rules',
            ];
        } elseif ($this->getFeatures()->isCpQueryParamRules()) {
            $item['subnav']['query-param-rules'] = [
                'label' => self::t('Query Param Rules'),
                'url' => self::HANDLE . '/query-param-rules',
            ];
        }

        if ($this->getFeatures()->isCpRedirects()) {
            $item['subnav']['redirects'] = [
                'label' => self::t('Redirects'),
                'url' => self::HANDLE . '/redirects',
            ];
        }

        if ($this->getFeatures()->showCpSettingsNavItem()) {
            $item['subnav']['settings'] = [
                'label' => self::t('Settings'),
                'url' => self::HANDLE . '/settings/general',
            ];
        }

        return $item;
    }
    private function hasCpNavItem()
    {
        if ($this->getFeatures()->isCpResults()) {
            return true;
        }

        if ($this->getFeatures()->isCpQueries()) {
            return true;
        }

        if ($this->getFeatures()->isCpTerms()) {
            return true;
        } elseif ($this->getFeatures()->isCpTermMaps()) {
            return true;
        } elseif ($this->getFeatures()->isCpTermPriorities()) {
            return true;
        }

        if ($this->getFeatures()->isCpRules()) {
            return true;
        } elseif ($this->getFeatures()->isCpIgnoreRules()) {
            return true;
        } elseif ($this->getFeatures()->isCpQueryParamRules()) {
            return true;
        }

        if ($this->getFeatures()->isCpRedirects()) {
            return true;
        }

        if ($this->getFeatures()->showCpSettingsNavItem()) {
            return true;
        }

        return false;
    }

    public static function t(string $message, array $params = [], string $language = null): string
    {
        return Craft::t(self::HANDLE, $message, $params, $language);
    }

    protected function createSettingsModel(): ?Model
    {
        return new SettingsModel();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            self::HANDLE . '/settings',
            ['settings' => $this->getSettings()]
        );
    }

    public function getPluginName(): ?string
    {
        $pluginName = $this->getSettings()->pluginName;

        if ($pluginName !== null) {
            return $pluginName;
        }

        return $this->name;
    }

    public function getHits(): Hits
    {
        return $this->get('hits');
    }

    public function getIgnoreRules(): IgnoreRules
    {
        return $this->get('ignoreRules');
    }

    public function getTermMaps(): TermMaps
    {
        return $this->get('termMaps');
    }

    public function getTermPriorities(): TermPriorities
    {
        return $this->get('termPriorities');
    }

    public function getQueries(): Queries
    {
        return $this->get('queries');
    }

    public function getQueryParamRules(): QueryParamRules
    {
        return $this->get('queryParamRules');
    }

    public function getRedirects(): Redirects
    {
        return $this->get('redirects');
    }

    public function getResults(): Results
    {
        return $this->get('results');
    }

    public function getFeatures(): Features
    {
        return $this->get('features');
    }
    public function getSitemap(): Sitemap
    {
        return $this->get('sitemap');
    }
}
