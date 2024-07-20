<?php
namespace xorb\search\search;

use Craft;
use craft\db\Query;
use xorb\search\db\Table;
use xorb\search\helpers\PluginHelper;
use xorb\search\search\SearchQueryTerm;

class SearchQueryPriorities
{
    protected array $priorities = [];
    protected array $where;

    public function __construct(
        protected int $siteId,
    ) {
        $languageSite = PluginHelper::getSiteLanguage($this->siteId);
        $languageAll = PluginHelper::getSiteLanguage(null);

        if ($languageSite === $languageAll) {
            $this->where = [
                [
                    'or',
                    ['siteId' => $this->siteId],
                    ['siteId' => null],
                ]
            ];
        } else {
            $this->where = [
                ['siteId' => $this->siteId],
                ['siteId' => null],
            ];
        }
    }

    public function getPriorities(): array
    {
        return $this->priorities;
    }

    public function addTerm(SearchQueryTerm $term): void
    {
        foreach ($this->where as $where) {
            $this->addPriorityIds($term, $this->siteId, $where);
        }
    }

    protected function addPriorityIds(
        SearchQueryTerm $term,
        ?int $siteId,
        array $where
    ): void
    {
        $permutations = $term->getPermutations();

        $handled = [];

        foreach ($permutations as $permutation) {
            foreach ($permutation as $value) {
                if (in_array($value, $handled)) {
                    continue;
                }

                $handled[] = $value;

                $term = new SearchQueryTerm($value);
                $mappedTerm = $term->getNormalizedTerm($siteId);

                $priorities = (new Query())
                    ->select([
                        'id',
                        'uid',
                        'searchPriority',
                    ])
                    ->from([Table::TERM_PRIORITIES])
                    ->where([
                        'and',
                        ['normalizedTerm' => $mappedTerm],
                        $where,
                    ])
                    ->all();

                foreach ($priorities as $priority) {
                    $this->priorities[$priority['id']] = $priority['searchPriority'];
                }
            }
        }
    }
}
