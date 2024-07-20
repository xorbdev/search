<?php
namespace xorb\search\tests;

use PHPUnit\Framework\TestCase;
use xorb\search\helpers\UrlComparer;

class UrlComparerHelperTest extends TestCase
{
    public function testClass(): void
    {
        $this->assertTrue(UrlComparer::matchUrl(
            url: '/test?foo=bar&bar=foo',
            matchUrl: '/test/?bar=foo&foo=bar',
            matchComparator: 'exact',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/test/url?foo=bar&bar=foo',
            matchUrl: '/test/?bar=foo&foo=bar',
            matchComparator: 'contains',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/test/url?foo=bar&bar=foo',
            matchUrl: '/url/?bar=foo&foo=bar',
            matchComparator: 'contains',
        ));

        $this->assertFalse(UrlComparer::matchUrl(
            url: 'testurl?foo=bar&bar=foo',
            matchUrl: 'url?bar=foo&foo=bar',
            matchComparator: 'contains',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '2024-04',
            matchUrl: '[0-9]{4}-[0-9]{2}',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '2024-04',
            matchUrl: '/[0-9]{4}-[0-9]{2}',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/2024-04/',
            matchUrl: '/[0-9]{4}-[0-9]{2}',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/2024-04/',
            matchUrl: '/[0-9]{4}-[0-9]{2}/',
            matchComparator: 'regex',
        ));

        $this->assertFalse(UrlComparer::matchUrl(
            url: 'test/2024-04',
            matchUrl: '[0-9]{4}-[0-9]{2}',
            matchComparator: 'regex',
        ));

        $this->assertFalse(UrlComparer::matchUrl(
            url: 'test/2024-04/events',
            matchUrl: '/[0-9]{4}-[0-9]{2}/events/',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: 'test/2024-04/events',
            matchUrl: '/[0-9]{4}-[0-9]{2}\/events/',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/calendar/2024-04',
            matchUrl: '/calendar/[0-9]{4}-[0-9]{2}',
            matchComparator: 'regex',
        ));

        $this->assertTrue(UrlComparer::matchUrl(
            url: '/calendar/2024-04?foo=bar',
            matchUrl: '/calendar/[0-9]{4}-[0-9]{2}?foo=bar',
            matchComparator: 'regex',
        ));
    }
}
