<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults;

class CascadeTest extends TestCase
{
    /** @test */
    public function it_generates_seo_cascade_from_site_defaults_and_home_entry()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
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
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => [
                'handle' => 'default',
                'name' => 'English',
                'locale' => 'en_US',
                'short_locale' => 'en',
                'url' => '/',
            ],
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    /** @test */
    public function it_overwrites_data_in_cascade()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
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
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => [
                'handle' => 'default',
                'name' => 'English',
                'locale' => 'en_US',
                'short_locale' => 'en',
                'url' => '/',
            ],
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    /** @test */
    public function it_generates_compiled_title_from_cascaded_parts()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
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

    /** @test */
    public function it_parses_field_references()
    {
        $entry = Entry::findByUri('/about')->entry();

        $entry->data(['favourite_colour' => 'Red'])->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'description' => '@seo:favourite_colour',
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('Red', $data['description']);
    }

    /** @test */
    public function it_generates_seo_cascade_without_exception_when_no_home_entry_exists()
    {
        Entry::findByUri('/')->delete();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
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
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => [
                'handle' => 'default',
                'name' => 'English',
                'locale' => 'en_US',
                'short_locale' => 'en',
                'url' => '/',
            ],
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }
}
