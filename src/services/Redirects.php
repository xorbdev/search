<?php
namespace xorb\search\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use xorb\search\db\Table;
use xorb\search\helpers\UrlComparer;
use xorb\search\models\Redirect as RedirectModel;
use xorb\search\Plugin;
use xorb\search\records\Redirect as RedirectRecord;
use yii\base\Component;

use const SORT_ASC;
use const SORT_DESC;

class Redirects extends Component
{
    public const PROJECT_CONFIG_PATH = 'xorb.search.redirects';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new RedirectModel($result);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }
    private function createItemsQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'fromUrl',
                'toUrl',
                'type',
                'regex',
                'ignoreQueryParams',
                'priority',
                'uid',
            ])
            ->from([Table::REDIRECTS])
            ->orderBy([
                'priority' => SORT_DESC,
                'fromUrl' => SORT_ASC,
                'toUrl' => SORT_ASC,
            ]);

        return $query;
    }

    public function hasRedirects(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllRedirects(): array
    {
        return $this->items()->all();
    }

    public function getRedirectById(int $id): ?RedirectModel
    {
        return $this->items()->firstWhere('id', $id);
    }

    public function getExistingRedirect(RedirectModel $model): ?RedirectModel
    {
        return $this->items()
            ->where('siteId', $model->siteId)
            ->where('fromUrl', $model->fromUrl)
            ->where('toUrl', $model->toUrl)
            ->where('regex', $model->regex)
            ->firstWhere('ignoreQueryParams', $model->ignoreQueryParams);
    }

    public function deleteRedirectById(int $id): bool
    {
        $model = $this->getRedirectById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteRedirect($model);

    }
    public function deleteRedirect(RedirectModel $model): bool
    {
        $plugin = Plugin::getInstance();

        if ($plugin->getSettings()->enableRedirects) {
            $this->deleteRecord($model->uid);
        } else {
            Craft::$app->getProjectConfig()->remove(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid
            );
        }

        return true;
    }

    public function saveRedirect(RedirectModel $model, bool $runValidation = true): bool
    {
        $plugin = Plugin::getInstance();

        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Redirect not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingRedirect = $this->getExistingRedirect($model);
        if ($existingRedirect && $existingRedirect->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching redirect already exists.'));
            Craft::info('Redirect not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(Table::REDIRECTS, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(Table::REDIRECTS, $model->id);
        }

        if ($plugin->getSettings()->enableRedirects) {
            $this->saveRecord($model->uid, $model->getConfig());
        } else {
            Craft::$app->getProjectConfig()->set(
                self::PROJECT_CONFIG_PATH . '.' . $model->uid,
                $model->getConfig()
            );
        }

        if ($isNew) {
            $model->id = Db::idByUid(Table::REDIRECTS, $model->uid);
        }

        return true;
    }

    public function handleChanged(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $this->saveRecord($uid, $data);

        // Clear caches
        $this->items = null;
    }
    private function saveRecord(string $uid, array $data): void
    {
        $data = $this->typecastData($data);

        $record = $this->getRecord($uid);

        $record->siteId = $data['siteId'];
        $record->fromUrl = $data['fromUrl'];
        $record->toUrl = $data['toUrl'];
        $record->type = $data['type'];
        $record->regex = $data['regex'];
        $record->ignoreQueryParams = $data['ignoreQueryParams'];
        $record->priority = $data['priority'];
        $record->uid = $uid;

        $record->save(false);
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $this->deleteRecord($uid);

        $this->items = null;
    }
    private function deleteRecord(string $uid): void
    {
        $record = $this->getRecord($uid);

        if ($record->getIsNewRecord()) {
            return;
        }

        $record->delete();
    }

    private function getRecord(int|string $criteria): RedirectRecord
    {
        $query = RedirectRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @var ?RedirectRecord **/
        $record = $query->one();

        return $record ?? new RedirectRecord();
    }

    public function typecastData(array $data): array
    {
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ?: null);

        $data['fromUrl'] = $data['fromUrl'] ?? '';
        $data['toUrl'] = (($data['toUrl'] ?? '') !== '' ? $data['toUrl'] : null);
        $data['type'] = (($data['type'] ?? '') !== '' ? $data['type'] : 301);
        $data['regex'] = (($data['regex'] ?? false) ? true : false);
        $data['ignoreQueryParams'] = (($data['ignoreQueryParams'] ?? false) ? true : false);
        $data['priority'] = intval($data['priority'] ?? 0);

        if ($data['type'] === '410') {
            $data['toUrl'] = null;
        }

        if ($data['siteId'] !== null) {
            $site = Craft::$app->getSites()->getSiteById($data['siteId'], true);
            if (!$site) {
                $data['siteId'] = null;
            }
        }

        return $data;
    }

    public function getRedirect(): array
    {
        $request = Craft::$app->getRequest();

		$path = $request->getFullPath();
		$query = $request->getQueryStringWithoutPath();
        if ($query !== '') {
            $query = '?' . $query;
        }

        $toUrl = null;
        $type = null;
        $priority = 0;

        $siteId = Craft::$app->getSites()->getCurrentSite()->id;

        foreach ($this->items() as $redirect) {
            if ($redirect->siteId !== null &&
                $redirect->siteId !== $siteId
            ) {
                continue;
            }

            if ($redirect->priority < $priority) {
                continue;
            }

            if ($redirect->priority === $priority &&
                ($toUrl !== null || $type !== null)
            ) {
                continue;
            }

            $url = $path;
            if (!$redirect->ignoreQueryParams) {
                $url .= $query;
            }

            $fromUrl = $redirect->fromUrl;
            $matches = [];

            if (UrlComparer::matchUrl(
                $url,
                $redirect->fromUrl,
                ($redirect->regex ? 'regex' : 'exact'),
                $matches
            )) {
                $toUrl = $redirect->toUrl;
                $type = $redirect->type;

                if ($matches) {
                    // so $10 comes before $1
                    $matches = array_reverse($matches);
                    foreach ($matches as $key => $value) {
                        $toUrl = str_replace('$' . ($key + 1), $value, $toUrl);
                    }
                }
            }
        }

        if ($toUrl !== null && !str_contains($toUrl, '://')) {
            $toUrl = UrlHelper::siteUrl($toUrl);
        }

        return [$toUrl, $type];
    }
}
