<?php
namespace xorb\search\helpers;

use xorb\search\Plugin;
use xorb\search\helpers\UrlComparer;
use xorb\search\services\QueryParamRules;

use const PHP_QUERY_RFC3986;

/**
 * Helper function to normalize URLs for matching and remove ignored
 * query params.
 */
class UrlCleaner
{
    private function __construct()
    {}

    public static function clean(int $siteId, string $url): string
    {
        $plugin = Plugin::getInstance();

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        $path = '/' . trim($path, '/');
        parse_str($parts['query'] ?? '', $query);

        $newQuery = [];

        $queryParamRules = $plugin->getQueryParamRules();

        foreach ($queryParamRules->getAllQueryParamRules() as $rule) {
            if ($rule->siteId !== null && $rule->siteId !== $siteId) {
                continue;
            }

            if (!UrlComparer::matchUrl(
                $url,
                $rule->resultUrlValue,
                $rule->resultUrlComparator
            )) {
                continue;
            }

            $value = self::getQueryParamValue(
                $rule->queryParamKey,
                $query,
            );

            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $newValues = [];

                foreach ($value as $singleValue) {
                    if (!UrlComparer::matchQueryParam(
                        $singleValue,
                        $rule->queryParamValue,
                        $rule->queryParamComparator
                    )) {
                        continue;
                    }

                    $newValues[] = $singleValue;
                }

                if (!$newValues) {
                    continue;
                }

                sort($newValues);

                $value = $newValues;
            } elseif (!UrlComparer::matchQueryParam(
                $value,
                $rule->queryParamValue,
                $rule->queryParamComparator
            )) {
                continue;
            }

            $newQuery = self::setQueryParamValue(
                $rule->queryParamKey,
                $newQuery,
                $value
            );
        }

        static::sortQuery($newQuery);

        $query = http_build_query($newQuery, '', null, PHP_QUERY_RFC3986);

        if ($query !== '') {
            $query = '?' . $query;
        }

        return $path . $query;
    }

    public static function sortQuery(&$query): void
    {
        ksort($query);

        foreach ($query as $key => $value) {
            if (is_array($value)) {
                static::sortQuery($query[$key]);
            }
        }
    }

    private static function getQueryParamValue(
        string $key,
        array $query
    ): null|string|array
    {
        $keyParts = explode('[', $key);

        if (!array_key_exists($keyParts[0], $query)) {
            return null;
        }

        // No more nested keys
        if (count($keyParts) === 1 || $keyParts[1] === ']') {
            return $query[$keyParts[0]];
        }

        $query = $query[$keyParts[0]];
        unset($keyParts[0]);
        // Remove trailing ']' from first key
        $keyParts[1] = substr($keyParts[1], 0, -1);

        $key = implode('[', $keyParts);

        return self::getQueryParamValue($key, $query);
    }

    private static function setQueryParamValue(
        string $key,
        array $query,
        string|array $value
    ): array
    {
        $keyParts = explode('[', $key);

        if (count($keyParts) === 1) {
            $query[$keyParts[0]] = $value;
            return $query;
        }

        if ($keyParts[1] === ']') {
            if (is_array($value)) {
                $query[$keyParts[0]] = $value;
            } elseif (array_key_exists($keyParts[0], $query) &&
                is_array($query[$keyParts[0]])
            ) {
                $query[$keyParts[0]][] = $value;
            } else {
                $query[$keyParts[0]] = [$value];
            }

            return $query;
        }

        $currentKey = $keyParts[0];

        unset($keyParts[0]);
        // Remove trailing ']' from first key
        $keyParts[1] = substr($keyParts[1], 0, -1);

        $key = implode('[', $keyParts);

        $query[$currentKey] = self::setQueryParamValue(
            $key,
            $query[$currentKey] ?? [],
            $value
        );

        return $query;
    }
}
