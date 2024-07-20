<?php
namespace xorb\search\jobs;

use Craft;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use xorb\search\Plugin;
use xorb\search\services\IgnoreRules;
use xorb\search\services\TermMaps;
use xorb\search\services\TermPriorities;
use xorb\search\services\QueryParamRules;
use xorb\search\services\Redirects;
use yii\base\Component;

class UpdateProjectConfig extends BaseJob
{
    public function execute($queue): void
    {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        // Ignore rules
        if ($settings->enableIgnoreRules) {
            Craft::$app->projectConfig->remove(IgnoreRules::PROJECT_CONFIG_PATH);
        } else {
            $this->saveProjectConfig(
                IgnoreRules::class,
                $plugin->getIgnoreRules()->getAllIgnoreRules()
            );
        }

        // Search term maps
        if ($settings->enableTermMaps) {
            Craft::$app->projectConfig->remove(TermMaps::PROJECT_CONFIG_PATH);
        } else {
            $this->saveProjectConfig(
                TermMaps::class,
                $plugin->getTermMaps()->getAllTermMaps()
            );
        }

        // Search term priorities
        if ($settings->enableTermPriorities) {
            Craft::$app->projectConfig->remove(TermPriorities::PROJECT_CONFIG_PATH);
        } else {
            $this->saveProjectConfig(
                TermPriorities::class,
                $plugin->getTermPriorities()->getAllTermPriorities()
            );
        }

        // Query param rules
        if ($settings->enableQueryParamRules) {
            Craft::$app->projectConfig->remove(QueryParamRules::PROJECT_CONFIG_PATH);
        } else {
            $this->saveProjectConfig(
                QueryParamRules::class,
                $plugin->getQueryParamRules()->getAllQueryParamRules()
            );
        }

        // Redirects
        if ($settings->enableRedirects) {
            Craft::$app->projectConfig->remove(Redirects::PROJECT_CONFIG_PATH);
        } else {
            $this->saveProjectConfig(
                Redirects::class,
                $plugin->getRedirects()->getAllRedirects()
            );
        }
    }

    private function saveProjectConfig(string $class, array $models): void
    {
        $projectConfigName = constant($class . '::PROJECT_CONFIG_PATH');

        foreach ($models as $model) {
            Craft::$app->getProjectConfig()->set(
                $projectConfigName . '.' . $model->uid,
                $model->getConfig()
            );
        }
    }

    protected function defaultDescription(): ?string
    {
        return Translation::prep(Plugin::HANDLE, 'Updating search project config.');
    }
}
