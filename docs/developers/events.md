# Events

!!!info If your use case requires overriding default functionality in ways that are not currently supported, please feel free to send a [support request](https://xorb.dev/plugins/search/support) and we will see if adding an event makes sense. !!!

## Indexing Events

These events can be used to override the data indexed for each page and asset.

### Update Page Main Data

This event is triggered when a page is being added or updated to the index. You can change `$event->mainData` to override the indexed data.

```php
use xorb\search\events\IndexPageEvent;
use xorb\search\helpers\PageResult;
use yii\base\Event;

Event::on(
    PageResult::class,
    PageResult::EVENT_UPDATE_MAIN_DATA,
    function(IndexPageEvent $event) {
        // $event->element
        // $event->url
        // $event->mainData
    }
);
```

### Update Asset Main Data

This event is triggered when an asset is being added or updated to the index. You can change `$event->mainData` to override the indexed data.

```php
use xorb\search\events\IndexAssetEvent;
use xorb\search\helpers\AssetResult;
use yii\base\Event;

Event::on(
    AssetResult::class,
    AssetResult::EVENT_UPDATE_MAIN_DATA,
    function(IndexAssetEvent $event) {
        // $event->asset
        // $event->mainData

        if ($event->asset->kind === 'pdf') {
            $event->mainData = $this->myCustomOcrImplementation(
                $event->asset
            );
        }
    }
);
```
