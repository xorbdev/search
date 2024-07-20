<?php
namespace xorb\search\helpers;

use const PREG_OFFSET_CAPTURE;

class UrlComparer
{
    private function __construct()
    {}

    public static function matchUrl(
        string $url,
        string $matchUrl,
        string $matchComparator,
        ?array &$matches = null,
    ): bool
    {
        $parts = parse_url($url);

        $path = $parts['path'] ?? '/';

        $query = [];
        if ($parts['query'] ?? null) {
            parse_str($parts['query'], $query);
        }

        $matchHandled = false;
        $matchPath = '/';
        $matchQuery = [];

        if ($matchComparator === 'regex' || $matchComparator === 'notRegex') {
            $pos = strpos($matchUrl, '://');
            if ($pos !== false) {
                $pos = strpos($matchUrl, '/', $pos + 3);
                if ($pos === false) {
                    $pos = strpos($matchUrl, '?', $pos + 3);
                    if ($pos === false) {
                        $matchUrl = '';
                    } else {
                        $matchUrl = substr($matchUrl, $pos);
                    }
                } else {
                    $matchUrl = substr($matchUrl, $pos);
                }
            }

            // Check if valid
		    if (@preg_match($matchUrl, '') === false) {
                // Find ? outside of ( and ) and assume start of query
                if (preg_match_all(
                    '/\?+(?![^(]*\))/',
                    $matchUrl,
                    $urlMatches,
                    PREG_OFFSET_CAPTURE
                )) {
                    $matchPath = substr($matchUrl, 0, $urlMatches[0][0][1]);

                    $matchQuery = substr($matchUrl, $urlMatches[0][0][1] + 1);
                    parse_str($matchQuery, $matchQuery);

                    $matchHandled = true;
                }
            }
        }

        if (!$matchHandled) {
            $parts = parse_url($matchUrl);

            $matchPath = $parts['path'] ?? '/';

            if ($parts['query'] ?? null) {
                parse_str($parts['query'], $matchQuery);
            }
        }

        if (!static::matchPath($path, $matchPath, $matchComparator, $matches)) {
            return false;
        }

        if (!static::matchQuery($query, $matchQuery)) {
            return false;
        }

        return true;

    }

    public static function matchPath(
        string $path,
        string $matchPath,
        string $matchComparator,
        ?array &$matches = null,
    ): bool
    {
        if ($matchComparator === 'regex' || $matchComparator === 'notRegex') {
		    if (@preg_match($matchPath, '') === false) {
                $path = '/' . trim($path, '/') . '/';
                $matchPath = '/' . trim($matchPath, '/') . '/';

                $matchPath = static::cleanRegexPath($matchPath);
            } else {
                $path = '/' . trim($path, '/');
            }
        } else {
            $path = '/' . trim($path, '/') . '/';
            $matchPath = '/' . trim($matchPath, '/') . '/';
        }

        return self::matchValue($path, $matchPath, $matchComparator);
    }

    public static function matchQuery(
        string|array $query,
        string|array $matchQuery,
    ): bool
    {
        if (is_string($query)) {
            parse_str($query, $query);
        }

        if (is_string($matchQuery)) {
            parse_str($matchQuery, $matchQuery);
        }

        foreach ($matchQuery as $key => $value) {
            if ($value === '') {
                if (array_key_exists($key, $query) &&
                    $query[$key] !== ''
                ) {
                    return false;
                }

                continue;
            }

            if (!array_key_exists($key, $query) ||
                $query[$key] !== $value
            ) {
                return false;
            }
        }

        return true;
    }

    public static function matchQueryParam(
        string $value,
        string $matchValue,
        string $matchComparator,
        ?array &$matches = null,
    ): bool
    {
        return self::matchValue(
            $value,
            $matchValue,
            $matchComparator,
            $matches
        );
    }

    private static function matchValue(
        string $value,
        string $matchValue,
        string $matchComparator,
        ?array &$matches = null,
    ): bool
    {
        if ($matchComparator === 'regex' || $matchComparator === 'notRegex') {
		    if (@preg_match($matchValue, '') === false) {
                $matchValue = static::cleanRegexPath($matchValue);
            }
        }

        switch ($matchComparator) {
            case 'exact':
                return ($value === $matchValue);
            case 'contains':
                return str_contains($value, $matchValue);
            case 'notContains':
                return !str_contains($value, $matchValue);
            case 'startsWith':
                return str_starts_with($value, $matchValue);
            case 'notStartsWith':
                return !str_starts_with($value, $matchValue);
            case 'endsWith':
                return str_ends_with($value, $matchValue);
            case 'notEndsWith':
                return !str_ends_with($value, $matchValue);
            case 'regex':
                // Handle both 0 and false
                $result = !!@preg_match(
                    $matchValue,
                    $value,
                    $matches,
                    PREG_UNMATCHED_AS_NULL,
                );

                if ($matches !== null) {
                    unset($matches[0]);
                    $matches = array_values($matches);
                }

                return $result;
            case 'notRegex':
                $result = !@preg_match(
                    $matchValue,
                    $value,
                    $matches,
                    PREG_UNMATCHED_AS_NULL,
                );

                if ($matches !== null) {
                    unset($matches[0]);
                    $matches = array_values($matches);
                }

                return $result;
        }

        return false;
    }

    public static function cleanRegexPath(string $path): string
    {
        // Escape any non escaped paths
        $count = preg_match_all(
            '/\/+(?![^(]*\))/',
            $path,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        while ($count--) {
            $pos = $matches[0][$count][1];
            $path = substr_replace($path, '\/', $pos, 1);
        }

		$path = '/\A^' . $path . '\z/i';

        return $path;
    }
}
