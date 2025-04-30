<?php
namespace xorb\search\fields;

use Craft;
use craft\base\ElementInterface;
use craft\fields\BaseOptionsField;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use xorb\search\Plugin;

class SearchableField extends BaseOptionsField
{
    public bool $default = false;
    public ?string $onLabel = null;
    public ?string $offLabel = null;
    protected bool $multiSite = false;
    protected static bool $multi = true;

    public function __construct($config = [])
    {
        $sites = Craft::$app->getSites()->getAllSites(true);

        $this->multiSite = (count($sites) > 1);

        $config['options'] = [];

        foreach ($sites as $site) {
            $config['options'][] = [
                'label' => $site->getName(true),
                'value' => strval($site->id),
            ];
        }

        parent::__construct($config);
    }

	public static function displayName(): string
	{
		return Plugin::t('Searchable');
	}

    public static function icon(): string
    {
        return 'magnifying-glass';
    }

    public static function isRequirable(): bool
    {
        return false;
    }

    public static function phpType(): string
    {
        return sprintf('\\%s', MultiOptionsFieldData::class);
    }

    protected function optionsSettingLabel(): string
    {
        // We don't allow customizing options
        return 'N/A';
    }

    public function getSettingsHtml(): ?string
    {
        return
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'OFF Label'),
                'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s disabled state.'),
                'id' => 'off-label',
                'name' => 'offLabel',
                'value' => $this->offLabel,
            ]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'ON Label'),
                'instructions' => Craft::t('app', 'The label text to display beside the lightswitch’s enabled state.'),
                'id' => 'on-label',
                'name' => 'onLabel',
                'value' => $this->onLabel,
            ]);
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        if ($this->multiSite) {
            /** @var MultiOptionsFieldData $value */
            if (ArrayHelper::contains($value, 'valid', false, true)) {
                Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
            }

            return Cp::selectizeHtml([
                'id' => $this->getInputId(),
                'describedBy' => $this->describedBy,
                'class' => 'selectize',
                'name' => $this->handle,
                'values' => $this->encodeValue($value),
                'options' => $this->translatedOptions(true, $value, $element),
                'multi' => true,
            ]);
        } else {
            return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                'id' => $this->getInputId(),
                'labelId' => $this->getLabelId(),
                'describedBy' => $this->describedBy,
                'name' => $this->handle,
                'on' => (count($value) > 0),
                'onLabel' => Craft::t('site', $this->onLabel),
                'offLabel' => Craft::t('site', $this->offLabel),
                'disabled' => false,
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element = null): string
    {
        if ($this->multiSite) {
            return Cp::selectizeHtml([
                'id' => $this->getInputId(),
                'describedBy' => $this->describedBy,
                'class' => 'selectize',
                'name' => $this->handle,
                'values' => $this->encodeValue($value),
                'options' => $this->translatedOptions(true, $value, $element),
                'multi' => true,
                'disabled' => true,
            ]);
        } else {
            return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                'id' => $this->getInputId(),
                'labelId' => $this->getLabelId(),
                'describedBy' => $this->describedBy,
                'name' => $this->handle,
                'on' => (count($value) > 0),
                'onLabel' => Craft::t('site', $this->onLabel),
                'offLabel' => Craft::t('site', $this->offLabel),
                'disabled' => true,
            ]);
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!$this->multiSite && $value !== '' && $value !== [] && $value !== null) {
            $value = [strval($this->options[0]['value'])];
        }

        return parent::normalizeValue($value, $element);
    }
}
