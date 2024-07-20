<?php
namespace xorb\search\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\helpers\UrlHelper;
use craft\i18n\Translation;
use UnexpectedValueException;
use xorb\search\helpers\PageResult;
use xorb\search\helpers\PluginHelper;
use xorb\search\Plugin;

class AddUrl extends BaseJob
{
    public ?string $url = null;

    public function execute($queue): void
    {
        if ($this->url === null) {
            throw new UnexpectedValueException('URI not specified.');
        }

        if (UrlHelper::isAbsoluteUrl($this->url)) {
            $found = false;

            foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
                if (!PluginHelper::isValidSite($site->id)) {
                    continue;
                }

                $baseUrl = $site->getBaseUrl(parse: true);

                // Ensure same schema
                $baseUrl = str_replace('http://', 'https://', $baseUrl);
                $baseUrl = rtrim($baseUrl, '/');

                $url = str_replace('http://', 'https://', $this->url);
                $url = rtrim($url, '/');

                if ($url === $baseUrl || str_starts_with($url, $baseUrl . '/')) {
                    PageResult::addPage($site->id, null, $url);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                Craft::warning('The specified URI does not belong to any sites. (' . $this->url . ')', __METHOD__);
            }

            return;
        }

        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            if (!PluginHelper::isValidSite($site->id)) {
                continue;
            }

            PageResult::addPage($site->id, null, $this->url);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Translation::prep(Plugin::HANDLE, 'Adding URI to search results index.');
    }
}
