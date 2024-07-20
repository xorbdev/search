<?php
namespace xorb\search\search;

use Craft;
use craft\helpers\Search as SearchHelper;
use xorb\search\helpers\PluginHelper;
use yii\base\InvalidArgumentException;

readonly class SearchQueryTerm
{
    public function __construct(
        protected string $term,
        protected bool $exclude = false,
        protected bool $phrase = false,
        protected bool $group = false,
        protected int $minKeywordLength = 4,
    ) {
        if ($phrase && $group) {
            throw new InvalidArgumentException('Search term cannot be both a phrase and a group.');
        }
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getExclude(): bool
    {
        return $this->exclude;
    }

    public function getPhrase(): bool
    {
        return $this->phrase;
    }

    public function getGroup(): bool
    {
        return $this->group;
    }

    public function getKeywords(): array
    {
        $terms = explode(' ', $this->getTerm());

        foreach ($terms as $key => $value) {
            if (mb_strlen($value) < $this->minKeywordLength) {
                unset($terms[$key]);
            }
        }

        $terms = array_values($terms);

        return $terms;
    }

    public function getPermutations(): array
    {
        $terms = explode(' ', $this->getTerm());

        $permutations = $this->getPermutationsRecursive($terms);
        $permutations = $this->sortPermutations($permutations, $terms);
        $permutations = $this->mergePermutations($permutations, $terms);

        return $this->cleanPermutations($permutations);
    }

    private function getPermutationsRecursive(
        array $terms,
        int $start = 0,
        array $current = []
    ): array
    {
        $result = [];

        for ($i = $start; $i < count($terms); ++$i) {
            $current[] = $terms[$i];

            // Add the current combination to the result.
            $result[] = $current;

            // Recursively generate permutations for the rest of the words.
            $result = array_merge(
                $result,
                $this->getPermutationsRecursive($terms, $i + 1, $current)
            );

            // Remove the last word to backtrack and try the next combination.
            array_pop($current);
        }

        return $result;
    }

    private function sortPermutations(
        array $permutations,
        array $terms
    ): array
    {
        usort($permutations, function(array $a, array $b) use($terms) {
            $test = (count($a) <=> count($b));

            // Multipe words first
            if ($test !== 0) {
                return -$test;
            }

            $sequenceCountA = 0;
            $sequenceCountB = 0;

            // Prioritize term order first
            $previous = -1;
            foreach ($a as $key => $value) {
                $index = array_search($value, $terms);

                if ($previous === -1) {
                    $previous = $index;
                    continue;
                }

                if ($index === $previous + 1) {
                    ++$sequenceCountA;
                }
            }

            $previous = -1;
            foreach ($b as $key => $value) {
                $index = array_search($value, $terms);

                if ($previous === -1) {
                    $previous = $index;
                    continue;
                }

                if ($index === $previous + 1) {
                    ++$sequenceCountB;
                }
            }

            return -($sequenceCountA <=> $sequenceCountB);
        });

        return $permutations;
    }

    private function mergePermutations(
        array $permutations,
        array $terms
    ): array
    {
        $result = [];

        foreach ($permutations as $permutation) {
            if (count($permutation) === 1) {
                continue;
            }

            $merged = [implode(' ', $permutation)];

            foreach ($terms as $value) {
                if (!in_array($value, $permutation)) {
                    $merged[] = $value;
                }
            }

            $result[] = $merged;
        }

        $result[] = $terms;

        return $result;
    }

    private function cleanPermutations(array $permutations): array
    {
        foreach ($permutations as $key => $value) {
            foreach ($value as $key2 => $value2) {
                if (str_contains($value2, ' ')) {
                    continue;
                }

                if (mb_strlen($value2) < $this->minKeywordLength) {
                    unset($value[$key2]);
                }
            }

            $permutations[$key] = $value;
        }

        return $permutations;
    }

    public function getNormalizedTerm(?int $siteId = null): string
    {
        $language = PluginHelper::getSiteLanguage($siteId);

        return SearchHelper::normalizeKeywords(
            str: $this->term,
            ignore: [],
            processCharMap: ($language !== null),
            language: $language
        );
    }

    public function getNormalizedTerms(?int $siteId = null): array
    {
        $language = PluginHelper::getSiteLanguage($siteId);

        $normalizedTerms = [];

        foreach ($this->getKeywords() as $value) {
            $normalizedTerms[] = SearchHelper::normalizeKeywords(
                str: $value,
                ignore: [],
                processCharMap: ($language !== null),
                language: $language
            );
        }

        return $normalizedTerms;
    }

    public function getNormalizedPermutations(?int $siteId = null): array
    {
        $language = PluginHelper::getSiteLanguage($siteId);

        $normalizedPermutations = [];

        foreach ($this->getPermutations() as $value) {
            foreach ($value as $key => $value2) {
                $value[$key] = SearchHelper::normalizeKeywords(
                    str: $value2,
                    ignore: [],
                    processCharMap: ($language !== null),
                    language: $language
                );
            }

            $normalizedPermutations[] = $value;
        }

        return $normalizedPermutations;
    }
}
