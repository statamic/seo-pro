<?php

namespace Tests\BrokenLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\LinkExtractor;
use Tests\TestCase;

class LinkExtractorTest extends TestCase
{
    #[Test]
    public function it_finds_external_links_in_flat_field_values()
    {
        $found = LinkExtractor::extract([
            'title' => 'Hello world',
            'body' => 'Check out https://example.com/page for more.',
            'link' => 'https://another-example.org',
        ], null);

        $this->assertEquals([
            'https://example.com/page',
            'https://another-example.org',
        ], $found->pluck('url')->all());
    }

    #[Test]
    public function it_finds_links_nested_inside_arrays_like_bard_grid_values()
    {
        $found = LinkExtractor::extract([
            'content' => [
                ['type' => 'paragraph', 'content' => [
                    ['type' => 'text', 'text' => 'link', 'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://nested.example.com']],
                    ]],
                ]],
            ],
        ], null);

        $this->assertEquals(['https://nested.example.com'], $found->pluck('url')->all());
    }

    #[Test]
    public function it_ignores_non_http_schemes_and_relative_paths()
    {
        $found = LinkExtractor::extract([
            'body' => 'mailto:test@example.com tel:+123456 javascript:alert(1) /relative/path #anchor',
        ], null);

        $this->assertTrue($found->isEmpty());
    }

    #[Test]
    public function it_deduplicates_the_same_url_found_twice_in_the_same_field()
    {
        $found = LinkExtractor::extract([
            'body' => 'https://example.com and again https://example.com',
        ], null);

        $this->assertCount(1, $found);
    }

    #[Test]
    public function it_trims_trailing_sentence_punctuation_off_matched_urls()
    {
        $found = LinkExtractor::extract([
            'body' => 'See https://example.com/page.',
        ], null);

        $this->assertEquals('https://example.com/page', $found->first()['url']);
    }

    #[Test]
    public function it_keeps_parentheses_that_are_part_of_the_url()
    {
        $found = LinkExtractor::extract([
            'body' => 'See https://en.wikipedia.org/wiki/Foo_(bar) and [this](https://example.com/page) (or https://example.com/other).',
        ], null);

        $this->assertEquals([
            'https://en.wikipedia.org/wiki/Foo_(bar)',
            'https://example.com/page',
            'https://example.com/other',
        ], $found->pluck('url')->all());
    }

    #[Test]
    public function it_excludes_links_to_the_sites_own_host_when_the_site_url_is_relative()
    {
        $found = LinkExtractor::extract([
            'body' => 'See http://cool-runnings.com/about and https://example.com/page.',
        ], null);

        $this->assertEquals(['https://example.com/page'], $found->pluck('url')->all());
    }

    #[Test]
    public function it_excludes_hosts_passed_as_excluded_including_subdomains()
    {
        $this->assertFalse(LinkExtractor::isExternal('https://sub.blocked.com/path', ['blocked.com']));
        $this->assertFalse(LinkExtractor::isExternal('https://blocked.com', ['blocked.com']));
        $this->assertTrue(LinkExtractor::isExternal('https://example.com', ['blocked.com']));
    }
}
