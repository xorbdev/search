<?php
namespace xorb\search\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;
use DateTime;
use xorb\search\elements\Result as ResultElement;
use xorb\search\db\Table;
use xorb\search\search\SearchQuery;
use xorb\search\search\SearchQueryMaps;
use xorb\search\search\SearchQueryPriorities;
use xorb\search\search\SearchQueryTerm;
use xorb\search\Plugin;
use yii\db\Expression;

class ResultQuery extends ElementQuery
{
    public ?string $resultType = null;
    public ?int $resultId = null;
    public ?string $resultTitle = null;
    public ?string $resultUrl = null;
    public ?string $resultDescription = null;
    public ?string $resultHash = null;
    public ?string $mainHash = null;
    public ?string $mainData = null;
    public ?int $score = null;
    public ?int $searchPriority = null;
    public ?bool $searchIgnore = null;
    public ?int $sitemapPriority = null;
    public ?string $sitemapChangefreq = null;
    public ?bool $sitemapIgnore = null;
    public ?bool $rulesIgnore = null;
    public ?bool $error = null;
    public ?int $errorCode = null;
    public ?DateTime $dateResultModified = null;
    public ?DateTime $dateMainModified = null;
    public ?DateTime $dateUnavailableAfter = null;
    public ?DateTime $dateError = null;

    public ?string $searchQuery = null;
    protected bool $searchMode = false;
    protected bool $sitemapMode = false;

    public function resultType(?string $value): static
    {
        $this->resultType = $value;
        return $this;
    }

    public function resultId(?int $value): static
    {
        $this->resultId = $value;
        return $this;
    }

    public function resultTitle(?string $value): static
    {
        $this->resultTitle = $value;
        return $this;
    }

    public function resultUrl(?string $value): static
    {
        $this->resultUrl = $value;
        return $this;
    }

    public function resultDescription(?string $value): static
    {
        $this->resultDescription = $value;
        return $this;
    }

    public function resultHash(?string $value): static
    {
        $this->resultHash = $value;
        return $this;
    }

    public function mainHash(?string $value): static
    {
        $this->mainHash = $value;
        return $this;
    }

    public function mainData(?string $value): static
    {
        $this->mainData = $value;
        return $this;
    }

    public function score(?int $value): static
    {
        $this->score = $value;
        return $this;
    }

    public function searchPriority(?int $value): static
    {
        $this->searchPriority = $value;
        return $this;
    }

    public function searchIgnore(?bool $value): static
    {
        $this->searchIgnore = $value;
        return $this;
    }

    public function sitemapPriority(?int $value): static
    {
        $this->sitemapPriority = $value;
        return $this;
    }

    public function sitemapChangefreq(?string $value): static
    {
        $this->sitemapChangefreq = $value;
        return $this;
    }

    public function sitemapIgnore(?bool $value): static
    {
        $this->sitemapIgnore = $value;
        return $this;
    }

    public function rulesIgnore(?bool $value): static
    {
        $this->rulesIgnore = $value;
        return $this;
    }

    public function error(?bool $value): static
    {
        $this->error = $value;
        return $this;
    }

    public function errorCode(?int $value): static
    {
        $this->errorCode = $value;
        return $this;
    }

    public function dateResultModified(mixed $value): static
    {
        $this->dateResultModified = $value;
        return $this;
    }

    public function dateMainModified(mixed $value): static
    {
        $this->dateMainModified = $value;
        return $this;
    }

    public function dateUnavailableAfter(mixed $value): static
    {
        $this->dateUnavailableAfter = $value;
        return $this;
    }

    public function dateError(mixed $value): static
    {
        $this->dateError = $value;
        return $this;
    }

    public function searchQuery(?string $value): static
    {
        $this->searchQuery = $value;

        return $this;
    }

    public function searchMode(bool $value): static
    {
        $this->searchMode = $value;

        if ($this->searchMode) {
            $this->sitemapMode = false;
        }

        return $this;
    }

    public function sitemapMode(bool $value): static
    {
        $this->sitemapMode = $value;

        if ($this->sitemapMode) {
            $this->searchMode = false;
        }

        return $this;
    }

    public function populate($rows): array
    {
        // Track search query if set and first page
        if ($this->searchQuery !== null && $this->offset === 0) {
            $plugin = Plugin::getInstance();
            if ($plugin->isPro()) {
                $plugin->getQueries()->trackQuery($this->searchQuery);
            }
        }

        return parent::populate($rows);
    }

    protected function beforePrepare(): bool
    {
        $plugin = Plugin::getInstance();

        $this->joinElementTable(Table::RESULTS);

        $this->query->select([
            Table::RESULTS . '.resultType',
            Table::RESULTS . '.resultId',
            Table::RESULTS . '.resultTitle',
            Table::RESULTS . '.resultUrl',
            Table::RESULTS . '.resultDescription',
            Table::RESULTS . '.resultHash',
            Table::RESULTS . '.mainHash',
            Table::RESULTS . '.score',
            Table::RESULTS . '.searchPriority',
            Table::RESULTS . '.searchIgnore',
            Table::RESULTS . '.rulesIgnore',
            Table::RESULTS . '.error',
            Table::RESULTS . '.errorCode',
            Table::RESULTS . '.dateResultModified',
            Table::RESULTS . '.dateMainModified',
            Table::RESULTS . '.dateUnavailableAfter',
            Table::RESULTS . '.dateError',
        ]);

        // Avoid returning large data if searching
        if ($this->searchQuery === null) {
            $this->query->addSelect([
                Table::RESULTS . '.mainData',
            ]);
        }

        if ($plugin->isPro()) {
            $this->query->addSelect([
                Table::RESULTS . '.sitemapIgnore',
                Table::RESULTS . '.sitemapChangefreq',
                Table::RESULTS . '.sitemapPriority',
            ]);
        }

        if ($this->resultType !== null) {
            $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.resultType', $this->resultType));
        }

        if ($this->resultId !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.resultId' => $this->resultId]);
        }

        if ($this->resultTitle !== null) {
            $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.resultTitle', $this->resultTitle));
        }

        if ($this->resultUrl !== null) {
            $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.resultUrl', $this->resultUrl));
        }

        if ($this->resultDescription !== null) {
            $this->subQuery->andWhere(['like', Table::RESULTS . '.resultDescription', $this->resultDescription]);
        }

        if ($this->resultHash !== null) {
            $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.resultHash', $this->resultHash));
        }

        if ($this->mainHash !== null) {
            $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.mainHash', $this->mainHash));
        }

        if ($this->score !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.score' => $this->score]);
        }

        if ($this->searchPriority !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.searchPriority' => $this->searchPriority]);
        }

        if ($this->searchIgnore !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.searchIgnore' => $this->searchIgnore]);
        }

        if ($plugin->isPro()) {
            if ($this->sitemapPriority !== null) {
                $this->subQuery->andWhere([Table::RESULTS . '.sitemapPriority' => $this->sitemapPriority]);
            }

            if ($this->sitemapChangefreq !== null) {
                $this->subQuery->andWhere(Db::parseParam(Table::RESULTS . '.sitemapChangefreq', $this->sitemapChangefreq));
            }

            if ($this->sitemapIgnore !== null) {
                $this->subQuery->andWhere([Table::RESULTS . '.sitemapIgnore' => $this->sitemapIgnore]);
            }
        }

        if ($this->rulesIgnore !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.rulesIgnore' => $this->rulesIgnore]);
        }

        if ($this->error !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.error' => $this->error]);
        }

        if ($this->errorCode !== null) {
            $this->subQuery->andWhere([Table::RESULTS . '.errorCode' => $this->errorCode]);
        }

        if ($this->dateResultModified !== null) {
            $this->subQuery->andWhere(Db::parseDateParam(Table::RESULTS . '.dateResultModified', $this->dateResultModified));
        }

        if ($this->dateMainModified !== null) {
            $this->subQuery->andWhere(Db::parseDateParam(Table::RESULTS . '.dateMainModified', $this->dateMainModified));
        }

        if ($this->dateUnavailableAfter !== null) {
            $this->subQuery->andWhere(Db::parseDateParam(Table::RESULTS . '.dateUnavailableAfter', $this->dateUnavailableAfter));
        }

        if ($this->dateError !== null) {
            $this->subQuery->andWhere(Db::parseDateParam(Table::RESULTS . '.dateError', $this->dateError));
        }

        if ($this->searchMode) {
            $this->subQuery->andWhere([Table::RESULTS . '.searchIgnore' => false]);
            $this->subQuery->andWhere([Table::RESULTS . '.error' => false]);

            $this->subQuery->andWhere([
                'or',
                [Table::RESULTS . '.dateUnavailableAfter' => null],
                Db::parseDateParam(Table::RESULTS . '.dateUnavailableAfter', DateTimeHelper::currentUTCDateTime(), '>='),
            ]);
        } elseif ($this->sitemapMode) {
            $this->subQuery->andWhere([Table::RESULTS . '.sitemapIgnore' => false]);
            $this->subQuery->andWhere([Table::RESULTS . '.error' => false]);

            if ($plugin->getSettings()->sitemapIgnoreRules) {
                $this->subQuery->andWhere([
                    'or',
                    [Table::RESULTS . '.dateUnavailableAfter' => null],
                    Db::parseDateParam(Table::RESULTS . '.dateUnavailableAfter', DateTimeHelper::currentUTCDateTime(), '>='),
                ]);
            }
        }

        if ($this->searchQuery !== null) {
            if (!$this->applySearchQuery($this->searchQuery)) {
                return false;
            }
        }

        return parent::beforePrepare();
    }

    protected function applySearchQuery(string $searchQuery): bool
    {
        $searchQuery = new SearchQuery($searchQuery);

        $isMysql = Craft::$app->getDb()->getIsMysql();

        $where = [];
        $expressions = [];
        $terms = [];
        $orderByPriority = (
            (
                $this->orderBy === null ||
                $this->orderBy === '' ||
                $this->orderBy === []
            ) &&
            !$this->groupBy
        );

        if ($orderByPriority) {
            $priorities = new SearchQueryPriorities($this->siteId);
        } else {
            $priorities = null;
        }

        $hasTerms = false;

        foreach ($searchQuery->getTerms() as $term) {
            if ($term->getPhrase()) {
                $normalizedTerm = $term->getNormalizedTerm($this->siteId);

                $sql = $this->phraseMatch($normalizedTerm);

                if ($term->getExclude()) {
                    $sql = 'NOT (' . $sql . ')';
                }

                $where[] = $sql;

                $hasTerms = true;
                continue;
            }

            $map = new SearchQueryMaps($term, $this->siteId);
            $permutations = $map->getNormalizedPermutations();
            if (!$permutations) {
                continue;
            }

            $hasTerms = true;

            if ($priorities !== null) {
                $terms = [...$terms, ...$map->getTerms()];
            }

            if (count($permutations) === 1) {
                $expressions[] = $this->fullTextExpression(
                    $permutations[0][0],
                    $term->getExclude(),
                );

                // $terms = [...$terms, ...$permutations[0][0]];
            } else {
                $orGroup = [];

                foreach ($permutations as $value) {
                    $andGroup = [];

                    foreach ($value as $value2) {
                        $andGroup[] = $this->fullTextExpression(
                            $value2,
                            $term->getExclude(),
                        );
                    }

                    $orGroup[] = $this->fullText($andGroup);
                }

                // If any permutations
                $where[] = '(' . implode(' OR ', $orGroup) . ')';
            }

            if ($priorities !== null) {
                $priorities->addTerm($term);
            }
        }

        if (!$hasTerms) {
           return false;
        }

        // Add mapped terms to priorites
        if ($priorities !== null) {
            $terms = array_unique($terms);
            foreach ($terms as $term) {
                $term = new SearchQueryTerm($term);

                $priorities->addTerm($term);
            }

            $priorities = $priorities->getPriorities();
        }

        if ($expressions) {
            $where[] = $this->fullText($expressions);
        }

        $where = implode(' AND ', $where);

        $this->subQuery->andWhere(new Expression($where));

        if ($orderByPriority) {
            if ($priorities) {
                $termPriorityIds = array_keys($priorities);
                $termPriorityIds = implode(',', $termPriorityIds);

                // We only want to gorup and join on the subquery and then
                // just use the resulting value to order on the main query.

                $this->subQuery->leftJoin(
                    Table::TERM_PRIORITIES_INDEX,
                    [
                        'and',
                        Table::TERM_PRIORITIES_INDEX . '.siteId= ' . $this->siteId,
                        Table::TERM_PRIORITIES_INDEX . '.resultId = ' . Table::RESULTS . '.id',
                        Table::TERM_PRIORITIES_INDEX . '.termPriorityId IN (' . $termPriorityIds . ')'
                    ]
                );

                $this->subQuery->leftJoin(
                    Table::TERM_PRIORITIES,
                    Table::TERM_PRIORITIES . '.id = ' . Table::TERM_PRIORITIES_INDEX . '.termPriorityId',
                );

                $this->subQuery->addSelect(new Expression(
                    'GREATEST(' .
                        'MAX(' .
                            'COALESCE(' .
                                Table::TERM_PRIORITIES . '.`searchPriority`,0'.
                            ')' .
                        '),' .
                        Table::RESULTS . '.`searchPriority`' .
                    ') AS `priorityOrder`'
                ));

                $this->subQuery->orderBy([
                    '`priorityOrder`' => SORT_DESC,
                    Table::RESULTS . '.score' => SORT_DESC,
                ]);

                $this->subQuery->groupBy(Table::RESULTS . '.id');

                $this->query->orderBy([
                    '`subquery`.`priorityOrder`' => SORT_DESC,
                    Table::RESULTS . '.score' => SORT_DESC,
                ]);
            } else {
                $this->orderBy([
                    Table::RESULTS . '.searchPriority' => SORT_DESC,
                    Table::RESULTS . '.score' => SORT_DESC,
                ]);
            }
        }

        return true;
    }
    private function phraseMatch(string $term): string
    {
        $db = Craft::$app->getDb();

        if ($db->getIsMysql()) {
            return sprintf(
                "MATCH(%s) AGAINST('%s' IN NATURAL LANGUAGE MODE)",
                $db->quoteColumnName('mainData'),
                $term,
            );
        }

        $ftTerm = explode(' ', $term);
        $ftTerm = implode(' & ', $ftTerm);
        $likeTerm = '%' . $term . '%';

        return sprintf(
            "%s @@ '%s'::tsquery AND %s LIKE '%s'",
            $db->quoteColumnName('mainData_vector'),
            $ftTerm,
            $db->quoteColumnName('mainData_vector'),
            $likeTerm
        );
    }

    private function fullText(array $expressions): string
    {
        $db = Craft::$app->getDb();

        if ($db->getIsMysql()) {
            return sprintf(
                "MATCH(%s) AGAINST('%s' IN BOOLEAN MODE)",
                $db->quoteColumnName('mainData'),
                implode(' AND ', $expressions),
            );
        }

        return sprintf(
            "%s @@ '%s'::tsquery",
            $db->quoteColumnName('mainData_vector'),
            implode(' & ', $expressions),
        );
    }
    private function fullTextExpression(
        array $terms,
        bool $exclude = false,
    ): string
    {
        $isMysql = Craft::$app->getDb()->getIsMysql();

        if ($isMysql) {
            $terms = array_map(
                function($value) {
                    if (str_contains($value, ' ')) {
                        $value = '"' . $value . '"';
                    }

                    return $value;
                },
                $terms
            );

            if (count($terms) > 1) {
                $terms = '(' . implode(' ', $terms) .')';
            } else {
                $terms = $terms[0];
            }

            if ($exclude) {
                $terms = '-' . $terms;
            } else {
                $terms = '+' . $terms;
            }

            return $terms;
        }

        $terms = array_map(
            function($value) {
                if (str_contains($value, ' ')) {
                    $value = "''" . $value . "''";
                }

                return $value;
            },
            $terms
        );

        if (count($terms) > 1) {
            $terms = '(' . implode(' | ', $terms) .')';
        } else {
            $terms = $terms[0];
        }

        if ($exclude) {
            $terms = '!' . $terms;
        }

        return $terms;
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            ResultElement::STATUS_ERROR => [
                Table::RESULTS . '.error' => true
            ],
            ResultElement::STATUS_SEARCH_ENABLED => [
                Table::RESULTS . '.error' => false,
                Table::RESULTS . '.searchIgnore' => false,
            ],
            ResultElement::STATUS_SEARCH_DISABLED => [
                Table::RESULTS . '.error' => false,
                Table::RESULTS . '.searchIgnore' => true,
            ],
            ResultElement::STATUS_SITEMAP_ENABLED => [
                Table::RESULTS . '.error' => false,
                Table::RESULTS . '.sitemapIgnore' => false,
            ],
            ResultElement::STATUS_SITEMAP_DISABLED => [
                Table::RESULTS . '.error' => false,
                Table::RESULTS . '.sitemapIgnore' => true,
            ],
            ResultElement::STATUS_RULES_ENABLED => [
                Table::RESULTS . '.rulesIgnore' => false,
            ],
            ResultElement::STATUS_RULES_DISABLED => [
                Table::RESULTS . '.rulesIgnore' => true,
            ],
            default => parent::statusCondition($status),
        };
    }
}
