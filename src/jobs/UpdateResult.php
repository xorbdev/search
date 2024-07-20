<?php
namespace xorb\search\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\i18n\Translation;
use UnexpectedValueException;
use xorb\search\elements\Result as ResultElement;
use xorb\search\helpers\AssetResult;
use xorb\search\helpers\PageResult;
use xorb\search\Plugin;

class UpdateResult extends BaseJob
{
    public ?int $resultId = null;

    public function execute($queue): void
    {
        /** @var ResultElement|null */
        $resultElement = ResultElement::find()
            ->id($this->resultId)
            ->one();

        if (!$resultElement) {
            throw new UnexpectedValueException('Result not found.');
        }

        if ($resultElement->resultType === 'page') {
            PageResult::update($resultElement);
        } else {
            AssetResult::update($resultElement);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Translation::prep(Plugin::HANDLE, 'Updating search result.');
    }
}
