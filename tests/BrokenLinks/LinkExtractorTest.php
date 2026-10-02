<?php

namespace Tests\BrokenLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\SeoPro\BrokenLinks\LinkExtractor;
use Tests\TestCase;

class LinkExtractorTest extends TestCase
{
    #[Test]
    public function it_finds_external_links_in_flat_field_values()
    {
        $found = (new LinkExtractor)->extract([
            'title' => 'Hello world',
            'body' => 'Check out https://example.com/page for more.',
            'link' => 'https://another-example.org',
        ]);

        $this->assertEquals([
            'https://example.com/page',
            'https://another-example.org',
        ], $found->all());
    }

    #[Test]
    public function it_finds_links_nested_inside_arrays_like_bard_grid_values()
    {
        $found = (new LinkExtractor)->extract([
            'content' => [
                ['type' => 'paragraph', 'content' => [
                    ['type' => 'text', 'text' => 'link', 'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://nested.example.com']],
                    ]],
                ]],
            ],
        ]);

        $this->assertEquals(['https://nested.example.com'], $found->all());
    }

    #[Test]
    public function it_only_looks_in_fields_on_the_blueprint_when_given_one()
    {
        $blueprint = Blueprint::make()->setContents([
            'fields' => [
                ['handle' => 'body', 'field' => ['type' => 'textarea']],
            ],
        ]);

        $found = (new LinkExtractor)->extract([
            'body' => 'https://example.com/page',
            'internal_notes' => 'https://example.com/elsewhere',
        ], $blueprint);

        $this->assertEquals(['https://example.com/page'], $found->all());
    }

    #[Test]
    public function it_ignores_non_http_schemes_and_relative_paths()
    {
        $found = (new LinkExtractor)->extract([
            'body' => 'mailto:test@example.com tel:+123456 javascript:alert(1) /relative/path #anchor',
        ]);

        $this->assertTrue($found->isEmpty());
    }

    #[Test]
    public function it_deduplicates_the_same_url_found_twice_in_the_same_field()
    {
        $found = (new LinkExtractor)->extract([
            'body' => 'https://example.com and again https://example.com',
        ]);

        $this->assertCount(1, $found);
    }

    #[Test]
    public function it_trims_trailing_sentence_punctuation_off_matched_urls()
    {
        $found = (new LinkExtractor)->extract([
            'body' => 'See https://example.com/page.',
        ]);

        $this->assertEquals('https://example.com/page', $found->first());
    }

    #[Test]
    public function it_keeps_parentheses_that_are_part_of_the_url()
    {
        $found = (new LinkExtractor)->extract([
            'body' => 'See https://en.wikipedia.org/wiki/Foo_(bar) and [this](https://example.com/page) (or https://example.com/other).',
        ]);

        $this->assertEquals([
            'https://en.wikipedia.org/wiki/Foo_(bar)',
            'https://example.com/page',
            'https://example.com/other',
        ], $found->all());
    }

    #[Test]
    public function it_excludes_links_to_private_and_internal_hosts()
    {
        $found = (new LinkExtractor)->extract([
            'body' => implode(' ', [
                'http://127.0.0.1/admin',
                'http://169.254.169.254/latest/meta-data',
                'http://10.0.0.5:8080',
                'http://[::1]/',
                'http://localhost:3000',
                'http://redis:6379',
                'http://printer.local',
                'http://app.internal',
                'https://example.com/page',
            ]),
        ]);

        $this->assertEquals(['https://example.com/page'], $found->all());
    }

    #[Test]
    public function it_excludes_links_to_the_sites_own_host_when_the_site_url_is_relative()
    {
        $found = (new LinkExtractor)->extract([
            'body' => 'See http://cool-runnings.com/about and https://example.com/page.',
        ]);

        $this->assertEquals(['https://example.com/page'], $found->all());
    }

    #[Test]
    public function it_excludes_hosts_listed_in_the_config_including_their_subdomains()
    {
        config()->set('statamic.seo-pro.broken_links.excluded_hosts', ['blocked.com']);

        $found = (new LinkExtractor)->extract([
            'body' => 'https://sub.blocked.com/path https://blocked.com https://example.com',
        ]);

        $this->assertEquals(['https://example.com'], $found->all());
    }
}
