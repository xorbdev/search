<?php
namespace xorb\search\tests;

use PHPUnit\Framework\TestCase;
use xorb\search\helpers\MetaTags;

class MetaTagsHelperTest extends TestCase
{
    public function testClass(): void
    {
        $html = <<<'HTML'
<html>
<head>
    <meta name="robots" content="all">
    <meta content="XORB" property="og:site_name">
</head>
<body></body>
</html>
HTML;

        $metaTags = new MetaTags($html);

        $this->assertEquals($metaTags->getMetaTag('robots'), 'all');
        $this->assertEquals($metaTags->getMetaTag('og:site_name'), 'XORB');
    }
}
