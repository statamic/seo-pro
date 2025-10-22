<?php

namespace Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class CascadeTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::enforceTrailingSlashes(false);
        URL::clearUrlCache();

        parent::tearDown();
    }

    #[Test]
    public function it_generates_seo_cascade_from_site_defaults_and_home_entry()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->get();

        $expected = [
            'site_name' => 'Site Name',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'I see a bad-ass mother.',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Site Name',
            'og_title' => 'Home',
            'canonical_url' => 'http://cool-runnings.com',
            'prev_url' => null,
            'next_url' => null,
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Home',
            'twitter_description' => 'I see a bad-ass mother.',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_overwrites_data_in_cascade()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'site_name' => 'Cool Writings',
                'description' => 'Bob sled team',
            ])
            ->with([
                'description' => 'Best bob sled team!',
            ])
            ->get();

        $expected = [
            'site_name' => 'Cool Writings',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'Best bob sled team!',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Cool Writings',
            'og_title' => 'Home',
            'canonical_url' => 'http://cool-runnings.com',
            'prev_url' => null,
            'next_url' => null,
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'current_hreflang' => 'en',
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_generates_compiled_title_from_cascaded_parts()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'site_name' => 'Cool Writings',
                'site_name_position' => 'after',
            ])
            ->with([
                'site_name_position' => 'before',
                'site_name_separator' => '>>>',
                'title' => 'Jamaica',
            ])
            ->get();

        $this->assertEquals('Cool Writings >>> Jamaica', $data['compiled_title']);
    }

    #[Test]
    public function it_parses_antlers()
    {
        $entry = Entry::findByUri('/about')->entry();

        $entry->data(['favourite_colour' => 'Red'])->save();

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'description' => '{{ favourite_colour | upper }}',
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('RED', $data['description']);
    }

    public static function phpInAntlersProvider()
    {
        return [
            [
                '{{? echo "php used" ?}}',
                '{{? echo "php used" ?}}',
            ],
            [
                '{{$ "php used" $}}',
                '{{$ "php used" $}}',
            ],
            [
                "{{ _php_used = '@{@{' + '? echo \"php used\" ?' + '@}}'; _php_used | antlers /}}",
                "{{ _php_used = '@{@{' + '? echo \"php used\" ?' + '@}}'; _php_used | antlers /}}",
            ],
            [
                "{{ _php_used = (['' => ''] | json); _open = (_php_used | at(0)); _close = (_php_used | at(6)); _antlers_modified = _open + _open + '? echo \"php used\" ?' + _close + _close; _antlers_modified | antlers /}}",
                "{{ _php_used = (['' => ''] | json); _open = (_php_used | at(0)); _close = (_php_used | at(6)); _antlers_modified = _open + _open + '? echo \"php used\" ?' + _close + _close; _antlers_modified | antlers /}}",
            ],
        ];
    }

    #[Test]
    #[DataProvider('phpInAntlersProvider')]
    public function it_doesnt_parse_php_in_antlers($antlers, $output)
    {
        $entry = Entry::findByUri('/about')->entry();

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'description' => $antlers,
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertNotEquals('php used', $data['description']);
        $this->assertEquals($output, $data['description']);
    }

    #[Test]
    public function it_parses_field_references()
    {
        $entry = Entry::findByUri('/about')->entry();

        $entry->data(['favourite_colour' => 'Red'])->save();

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'description' => '@seo:favourite_colour',
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('Red', $data['description']);
    }

    #[Test]
    public function it_generates_seo_cascade_without_exception_when_no_home_entry_exists()
    {
        Entry::findByUri('/')->delete();

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->get();

        $expected = [
            'site_name' => 'Site Name',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => null,
            'description' => null,
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Site Name',
            'og_title' => 'Site Name',
            'canonical_url' => 'http://cool-runnings.com',
            'prev_url' => null,
            'next_url' => null,
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_generates_404_title_with_404_in_response_code_in_context()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'response_code' => 404,
            ])
            ->get();

        $this->assertEquals('404 Page Not Found', $data['title']);
        $this->assertEquals('404 Page Not Found | Site Name', $data['compiled_title']);
    }

    #[Test]
    public function it_generates_next_and_prev_urls_off_tag_paginator_from_cms_core()
    {
        $entry = Entry::findByUri($uri = '/about')->entry();

        Blink::put('tag-paginator', (new LengthAwarePaginator(
            items: [],
            total: 15,
            perPage: 3,
            currentPage: 3,
        ))->setPath($uri));

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->withCurrent($entry)
            ->get();

        $expected = [
            'canonical_url' => 'http://cool-runnings.com/about?page=3',
            'prev_url' => 'http://cool-runnings.com/about?page=2',
            'next_url' => 'http://cool-runnings.com/about?page=4',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_generates_urls_with_trailing_slashes_when_configured()
    {
        $entry = Entry::findByUri($uri = '/about')->entry();

        Blink::put('tag-paginator', (new LengthAwarePaginator(
            items: [],
            total: 15,
            perPage: 3,
            currentPage: 3,
        ))->setPath($uri));

        URL::enforceTrailingSlashes();

        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->withCurrent($entry)
            ->get();

        $expected = [
            'canonical_url' => 'http://cool-runnings.com/about/?page=3',
            'prev_url' => 'http://cool-runnings.com/about/?page=2',
            'next_url' => 'http://cool-runnings.com/about/?page=4',
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_overwrites_og_title()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'site_name' => 'Cool Writings',
                'description' => 'Bob sled team',
            ])
            ->with([
                'og_title' => 'John Candy',
            ])
            ->get();

        $expected = [
            'site_name' => 'Cool Writings',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'Bob sled team',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Cool Writings',
            'og_title' => 'John Candy',
            'canonical_url' => 'http://cool-runnings.com',
            'prev_url' => null,
            'next_url' => null,
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'current_hreflang' => 'en',
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    #[Test]
    public function it_overwrites_twitter_title_and_description()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::in('default')->all())
            ->with([
                'site_name' => 'Cool Writings',
                'description' => 'Bob sled team',
            ])
            ->with([
                'twitter_title' => 'John Candy',
                'twitter_description' => 'Best bob sled team!',
            ])
            ->get();

        $expected = [
            'site_name' => 'Cool Writings',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'Bob sled team',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Cool Writings',
            'og_title' => 'Home',
            'canonical_url' => 'http://cool-runnings.com',
            'prev_url' => null,
            'next_url' => null,
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'current_hreflang' => 'en',
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'John Candy',
            'twitter_description' => 'Best bob sled team!',
        ];

        $this->assertArraySubset($expected, $data);
    }
}
