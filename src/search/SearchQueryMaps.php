<?php
namespace xorb\search\search;

use Craft;
use craft\db\Query;
use xorb\search\db\Table;
use xorb\search\helpers\PluginHelper;
use xorb\search\search\SearchQueryTerm;

class SearchQueryMaps
{
    protected array $map = [];
    protected array $terms = [];

    public function __construct(
        protected SearchQueryTerm $term,
        protected int $siteId,
    ) {
        $languageSite = PluginHelper::getSiteLanguage($this->siteId);
        $languageAll = PluginHelper::getSiteLanguage(null);

        if ($languageSite === $languageAll) {
            $this->addTermMaps(
                $this->siteId,
                [
                    'or',
                    ['siteId' => $this->siteId],
                    ['siteId' => null],
                ]
            );
        } else {
            $this->addTermMaps(
                $this->siteId,
                ['siteId' => $this->siteId],
            );
            $this->addTermMaps(
                null,
                ['siteId' => null],
            );
        }

        $this->terms = array_unique($this->terms);
    }

    public function getNormalizedPermutations(): array
    {
        $permutations = $this->term->getNormalizedPermutations($this->siteId);

        foreach ($permutations as $key => $value) {
            if  (!$value) {
                unset($permutations[$key]);
                continue;
            }

            foreach ($value as $key2 => $value2) {
                $permutations[$key][$key2] = [$value2, ...$this->map[$value2]];
            }
        }

        return $permutations;
    }

    public function getTerms(): array
    {
        return $this->terms;
    }

    protected function addTermMaps(?int $siteId, array $where): void
    {
        $permutations = $this->term->getPermutations();

        $handled = [];

        foreach ($permutations as $permutation) {
            foreach ($permutation as $value) {
                if (in_array($value, $handled)) {
                    continue;
                }

                $handled[] = $value;

                $term = new SearchQueryTerm($value);
                $value = $term->getNormalizedTerm($this->siteId);
                $mappedTerm = $term->getNormalizedTerm($siteId);

                if (!array_key_exists($value, $this->map)) {
                    $this->map[$value] = [];
                }

                $terms = (new Query())
                    ->select([
                        'alternate',
                    ])
                    ->from([Table::TERM_MAPS])
                    ->where([
                        'and',
                        ['normalizedTerm' => $mappedTerm],
                        $where,
                    ])
                    ->all();

                foreach ($terms as $term) {
                    $this->terms[] = $term['alternate'];
                    $term = new SearchQueryTerm($term['alternate']);
                    $this->map[$value][] = $term->getNormalizedTerm($this->siteId);
                }

                $terms = (new Query())
                    ->select([
                        'term',
                    ])
                    ->from([Table::TERM_MAPS])
                    ->where([
                        'and',
                        ['normalizedAlternate' => $mappedTerm],
                        $where,
                    ])
                    ->all();

                foreach ($terms as $term) {
                    $this->terms[] = $term['term'];
                    $term = new SearchQueryTerm($term['term']);
                    $this->map[$value][] = $term->getNormalizedTerm($this->siteId);
                }
            }
        }
    }
}
