<?php
namespace xorb\search\tests;

use PHPUnit\Framework\TestCase;
use xorb\search\helpers\RobotsTag;

class RobotsTagHelperTest extends TestCase
{
    public function testClass(): void
    {
        $html = <<<'HTML'
<html>
<head>
    <meta name="robots" content="all">
</head>
<body></body>
</html>
HTML;
        $headers = [
            'x-robots-tag' => 'noindex',
        ];

        $robotsTag = new RobotsTag(
            $html,
            $headers,
            true,
            true,
        );

        $this->assertTrue($robotsTag->getNoIndex());
        $this->assertNull($robotsTag->getUnavailableAfterDate());

        $robotsTag = new RobotsTag(
            $html,
            $headers,
            true,
            false,
        );

        $this->assertFalse($robotsTag->getNoIndex());
        $this->assertNull($robotsTag->getUnavailableAfterDate());

        $robotsTag = new RobotsTag(
            $html,
            $headers,
            false,
            true,
        );

        $this->assertTrue($robotsTag->getNoIndex());
        $this->assertNull($robotsTag->getUnavailableAfterDate());

        $robotsTag = new RobotsTag(
            $html,
            $headers,
            false,
            false,
        );

        $this->assertFalse($robotsTag->getNoIndex());
        $this->assertNull($robotsTag->getUnavailableAfterDate());

        $html = <<<'HTML'
<html>
<head>
    <meta name="robots" content="all">
</head>
<body></body>
</html>
HTML;
        $headers = [
            'x-robots-tag' => 'noindex',
            'X-Robots-Tag' => 'unavailable_after: 25 Jun 2010 15:00:00 PST',
        ];

        $robotsTag = new RobotsTag(
            $html,
            $headers,
            true,
            true,
        );

        $this->assertTrue($robotsTag->getNoIndex());
        $this->assertNotNull($robotsTag->getUnavailableAfterDate());

        $html = <<<'HTML'
<html>
<head>
    <meta name="robots" content="all,unavailable_after: 25 Jun 2010 15:00:00 PST">
</head>
<body></body>
</html>
HTML;

        $robotsTag = new RobotsTag(
            $html,
            [],
            true,
            true,
        );

        $this->assertFalse($robotsTag->getNoIndex());
        $this->assertNotNull($robotsTag->getUnavailableAfterDate());

        $html = <<<'HTML'
<html>
<head>
    <meta name="robots" content="noindex">
    <meta name="robots" content="unavailable_after: 25 Jun 2010 15:00:00 PST">
</head>
<body></body>
</html>
HTML;

        $robotsTag = new RobotsTag(
            $html,
            [],
            true,
            true,
        );

        $this->assertTrue($robotsTag->getNoIndex());
        $this->assertNotNull($robotsTag->getUnavailableAfterDate());
    }
}
