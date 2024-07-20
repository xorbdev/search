<?php
namespace xorb\search\helpers;

use Craft;

/**
 * https://developers.google.com/search/docs/crawling-indexing/robots/create-robots-txt
 */
class RobotsTxt
{
    protected array $globalAllowed = [];
    protected array $globalDisallowed = [];
    protected array $userAgentAllowed = [];
    protected array $userAgentDisallowed = [];
    protected array $sitemaps = [];

    public function __construct(
        protected string $robotsTxtUrl,
        protected string $userAgent = '',
    ) {
        $this->parseRobotsTxt($this->robotsTxtUrl, $this->userAgent);
    }

    protected function parseRobotsTxt(
        string $robotsTxtUrl,
        string $userAgent = ''
    ): void
    {
        $userAgents = ['*'];

        if ($userAgent !== '' && $userAgent !== '*') {
            $userAgents[] = $userAgent;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $robotsTxtUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        $result = curl_exec($ch);

        curl_close($ch);

        if ($result === false) {
            Craft::warning(
                'Robots.txt could not be read. (' . $this->robotsTxtUrl . ')',
                __METHOD__
            );
            return;
        }

        $line = strtok($result, "\r\n");

        $currentUserAgent = null; // Track if under a matching user agent
        $prevIsUserAgent = false; // Handle multiple user-agents after each other

        while ($line !== false) {
            $line = trim($line);

            if ($line === '') {
                $line = strtok("\r\n");
                continue;
            }

            if (preg_match('/^Sitemap:(.*)/i', $line, $match)) {
                $this->sitemaps[] = trim($match[1]);

                $line = strtok("\r\n");
                continue;
            }

            if (preg_match('/^User-agent:(.*)/i', $line, $match)) {
                if (!$prevIsUserAgent || $currentUserAgent === null) {
                    $prevIsUserAgent = true;

                    foreach ($userAgents as $value) {
                        if (strcasecmp($value, $match[1]) === 0) {
                            $currentUserAgent = $match[1];
                            break;
                        }
                    }
                }

                $line = strtok("\r\n");
                continue;
            }

            $prevIsUserAgent = false;

            if ($currentUserAgent !== null) {
                if (preg_match('/^Allow:(.*)/i', $line, $match)) {
                    if ($currentUserAgent === '*') {
                        $this->globalAllowed[] = $this->regexifyRule($match[1]);
                    } else {
                        $this->userAgentAllowed[] = $this->regexifyRule($match[1]);
                    }
                } elseif (preg_match('/^Disallow:(.*)/i', $line, $match)) {
                    if ($currentUserAgent === '*') {
                        $this->globalDisallowed[] = $this->regexifyRule($match[1]);
                    } else {
                        $this->userAgentDisallowed[] = $this->regexifyRule($match[1]);
                    }
                }
            }

            $line = strtok("\r\n");
        }
    }

    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    public function isAllowed(string $path): bool
    {
        foreach ($this->userAgentAllowed as $rule) {
            if (preg_match($rule, $path)) {
                return true;
            }
        }

        foreach ($this->userAgentDisallowed as $rule) {
            if (preg_match($rule, $path)) {
                return false;
            }
        }

        foreach ($this->globalAllowed as $rule) {
            if (preg_match($rule, $path)) {
                return true;
            }
        }

        foreach ($this->globalDisallowed as $rule) {
            if (preg_match($rule, $path)) {
                return false;
            }
        }

        return true;
    }

    public static function regexifyRule(string $rule): string
    {
        $rule = trim($rule);

        $suffix = '';
        if (str_ends_with($rule, '$')) {
            $rule = substr($rule, 0, -1);
            $suffix = '$';
        }

        $rule = explode('*', $rule);
        $rule = array_map(fn($rule) => preg_quote($rule, '/'), $rule);
        $rule = implode('(.*)', $rule) . $suffix;

        return '/^' . $rule . '/';
    }

    public static function matchRule(string $rule, string $matchPath): bool
    {
        $rule = static::regexifyRule($rule);

        if (preg_match($rule, $matchPath)) {
            return true;
        }

        return false;
    }
}
