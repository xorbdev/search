<?php
namespace xorb\search\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\helpers\Queue;
use craft\elements\db\ElementQueryInterface;
use xorb\search\jobs\UpdateResult as UpdateResultJob;
use xorb\search\Plugin;

class UpdateResult extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Plugin::t('Update result');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type
    });
})();
JS, [static::class]);

        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $plugin = Plugin::getInstance();

        $elements = $query->all();

        /** @var Element $element **/
        foreach ($elements as $element) {
            Queue::push(new UpdateResultJob([
                'resultId' => $element->id,
            ]));
        }

        if (count($elements) === 1) {
            $this->setMessage(Plugin::t('Result has been queued for update.'));
        } else {
            $this->setMessage(Plugin::t('Results have been queued for updates.'));
        }

        return true;
    }
}
