<?php
namespace xorb\search\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use xorb\search\elements\Result as ResultElement;
use xorb\search\Plugin;

class SetStatus extends ElementAction
{
    public const SEARCH_ENABLED = 'search-enabled';
    public const SEARCH_DISABLED = 'search-disabled';

    public const SITEMAP_ENABLED = 'sitemap-enabled';
    public const SITEMAP_DISABLED = 'sitemap-disabled';

    public const RULES_ENABLED = 'rules-enabled';
    public const RULES_DISABLED = 'rules-disabled';

    public ?string $status = null;

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['status'], 'required'];
        $rules[] = [['status'], 'in', 'range' => [
            self::SEARCH_ENABLED,
            self::SEARCH_DISABLED,
            self::SITEMAP_ENABLED,
            self::SITEMAP_DISABLED,
            self::RULES_ENABLED,
            self::RULES_DISABLED
        ]];

        return $rules;
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: (selectedItems) => {
            const element = selectedItems.find('.element');
            return (
                Garnish.hasAttr(element, 'data-savable') &&
                !Garnish.hasAttr(element, 'data-disallow-status')
            );
        },
    });
})();
JS, [static::class]);

        return Craft::$app->getView()->renderTemplate('search/_components/elementactions/set-status.twig');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var ElementInterface $elementType */
        $elementType = $this->elementType;
        $elementsService = Craft::$app->getElements();

        $elements = $query->all();
        $failCount = 0;

        $statusType = match ($this->status) {
            self::SEARCH_ENABLED => 'Search',
            self::SEARCH_DISABLED => 'Search',
            self::SITEMAP_ENABLED => 'Sitemap',
            self::SITEMAP_DISABLED => 'Sitemap',
            self::RULES_ENABLED => 'Rules',
            self::RULES_DISABLED => 'Rules',
            default => null,
        };

        if ($statusType === null) {
            return false;
        }

        /** @var ResultElement $element **/
        foreach ($elements as $element) {
            switch ($this->status) {
                case self::SEARCH_ENABLED:
                    // Skip if there's nothing to change
                    if (!$element->searchIgnore) {
                        continue 2;
                    }

                    $element->searchIgnore = false;
                    break;
                case self::SEARCH_DISABLED:
                    // Skip if there's nothing to change
                    if ($element->searchIgnore) {
                        continue 2;
                    }

                    $element->searchIgnore = true;
                    break;
                case self::SITEMAP_ENABLED:
                    // Skip if there's nothing to change
                    if (!$element->sitemapIgnore) {
                        continue 2;
                    }

                    $element->sitemapIgnore = false;
                    break;
                case self::SITEMAP_DISABLED:
                    // Skip if there's nothing to change
                    if ($element->sitemapIgnore) {
                        continue 2;
                    }

                    $element->sitemapIgnore = true;
                    break;
                case self::RULES_ENABLED:
                    // Skip if there's nothing to change
                    if (!$element->rulesIgnore) {
                        continue 2;
                    }

                    $element->rulesIgnore = false;
                    break;
                case self::RULES_DISABLED:
                    // Skip if there's nothing to change
                    if ($element->rulesIgnore) {
                        continue 2;
                    }

                    $element->rulesIgnore = true;
                    break;
            }

            if ($elementsService->saveElement($element) === false) {
                ++$failCount;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            $statusType = strtolower($statusType);

            if (count($elements) === 1) {
                $this->setMessage(Plugin::t('Could not update ' . $statusType . ' status due to a validation error.'));
            } else {
                $this->setMessage(Plugin::t('Could not update ' . $statusType . ' statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Plugin::t($statusType . ' status updated, with some failures due to validation errors.'));
        } else {
            if (count($elements) === 1) {
                $this->setMessage(Plugin::t($statusType . ' status updated.'));
            } else {
                $this->setMessage(Plugin::t($statusType . ' statuses updated.'));
            }
        }

        return true;
    }
}
