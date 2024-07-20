<?php
namespace xorb\search\services;

use Craft;
use craft\helpers\UrlHelper;
use DOMDocument;
use DOMElement;
use UnexpectedValueException;
use xorb\search\db\Table;
use xorb\search\Plugin;
use xorb\search\elements\Result as ResultElement;
use xorb\search\elements\db\ResultQuery;
use yii\base\Component;

use const SORT_DESC;

class Sitemap extends Component
{
    private ?ResultQuery $resultQuery = null;

    private function getResultQuery(): ResultQuery
    {
        if ($this->resultQuery === null) {
            $this->resultQuery = ResultElement::find()
                ->sitemapMode(true)
                ->siteId(Craft::$app->getSites()->getCurrentSite()->id);
        }

        return $this->resultQuery;
    }

    public function index(): ?string
    {
        $pageCount = $this->getPageCount();

        if ($pageCount === 1) {
            return $this->page(1);
        }

        $lastModifiedDate = $this->getLastModifiedDate();

        $document = $this->createDocument();
        $sitemapindex = $this->createElement($document, 'sitemapindex');

        for ($i = 1; $i <= $pageCount; ++$i) {
            $url = $this->getPageUrl($i);

            $sitemap = $this->createElement($sitemapindex, 'sitemap');

			$this->createElement(
                $sitemap,
				'loc',
                $url
			);

			$this->createElement(
                $sitemap,
				'lastmod',
                $lastModifiedDate
			);
        }

        $xml = $document->saveXML();

        if ($xml === false) {
            return null;
        }

        return $xml;
    }

    public function page(int $page): ?string
    {
        $plugin = Plugin::getInstance();

        $document = $this->createDocument();
        $urlset = $this->createElement($document, 'urlset');

        $query = $this->getResultQuery()->orderBy(['id' => SORT_DESC]);

        $pageLimit = $plugin->getSettings()->sitemapUrlLimit;

        $batchSize = 50;
        $offset = 0;

        while (true) {
            // We don't want to query more than the page limit
            if ($offset + $batchSize > $pageLimit) {
                $batchSize = $pageLimit - $offset;
            }

            $results = $query->limit($batchSize)
                ->offset($offset + (($page -1) * $pageLimit))
                ->all();

            /** @var ResultElement $result **/
            foreach ($results as $result) {
                $url = $this->createElement($urlset, 'url');

                $this->createElement(
                    $url,
                    'loc',
                    UrlHelper::siteUrl($result->resultUrl)
                );

                $this->createElement(
                    $url,
                    'lastmod',
                    $result->dateResultModified->format('c')
                );

                $this->createElement(
                    $url,
                    'changefreq',
                    $result->sitemapChangefreq
                );

                $this->createElement(
                    $url,
                    'priority',
                    strval($result->sitemapPriority)
                );
            }

            $offset += $batchSize;

            if ($offset >= $pageLimit) {
                break;
            }
        }

        $xml = $document->saveXML();

        if ($xml === false) {
            return null;
        }

        return $xml;
    }

    public function getPageCount(): int
    {
        $plugin = Plugin::getInstance();

        $limit = $plugin->getSettings()->sitemapUrlLimit;
        $count = $this->getResultQuery()->count();

        if ($limit === 0 || $limit > $count) {
            return 1;
        }

        return intval(ceil($count / $limit));
    }

    public function getLastModifiedDate(): string
    {
        /** @var ResultElement **/
        $item = $this->getResultQuery()
            ->orderBy([Table::RESULTS . '.dateResultModified' => SORT_DESC])
            ->one();

        // Reset orderby
        $this->getResultQuery()->orderBy([]);

        return $item->dateResultModified->format('c');
    }

    private function getPageUrl(int $page): string
    {
        $plugin = Plugin::getInstance();

        $sitemapName = $plugin->getSettings()->sitemapName;

		return UrlHelper::siteUrl(
			$sitemapName . '-' . $page . '.xml'
		);
    }

	private function createDocument(): DOMDocument
	{
		$document = new DOMDocument('1.0', 'UTF-8');

		// Don't compress when in devmode
		if (Craft::$app->config->general->devMode) {
			$document->formatOutput = true;
        }

	    return $document;
	}
    private function createElement(
        DOMDocument|DOMElement $node,
        string $name,
        string $value = ''
    ): DOMElement
    {
        if ($node instanceof DOMDocument) {
            $element = $node->createElement($name, $value);
        } else {
            $element = $node->ownerDocument->createElement($name, $value);
        }

        if ($node instanceof DOMDocument) {
            $element->setAttribute(
                'xmlns',
                'http://www.sitemaps.org/schemas/sitemap/0.9'
            );
        }

        $node->appendChild($element);

        return $element;
    }
}
