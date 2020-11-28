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
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'locale' => 'default',
            'alternate_locales' => [],
            'last_modified' => null,
        ];

        $this->assertEquals($expected, $data);
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
            'home_url' => 'http://cool-runnings.com/',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'locale' => 'default',
            'alternate_locales' => [],
            'last_modified' => null,
        ];

        $this->assertEquals($expected, $data);
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
        $entry = Entry::findBySlug('about', 'pages');

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
}
