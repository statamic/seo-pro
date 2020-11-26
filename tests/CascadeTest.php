<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\Term;
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
    public function it_generates_home_url_on_sub_page()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent(Entry::findBySlug('about', 'pages'))
            ->get();

        $this->assertEquals('http://cool-runnings.com/', $data['home_url']);
    }

    /** @test */
    public function it_generates_canonical_url_for_entry()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent(Entry::findBySlug('about', 'pages'))
            ->get();

        $this->assertEquals('http://cool-runnings.com/about', $data['canonical_url']);
    }

    /** @test */
    public function it_generates_canonical_url_for_term()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent(Term::findBySlug('sneakers', 'topics'))
            ->get();

        $this->assertEquals('http://cool-runnings.com/topics/sneakers', $data['canonical_url']);
    }

    /** @test */
    public function it_generates_canonical_url_with_pagination()
    {
        $this->call('GET', '/', ['page' => 2]);

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent(Entry::findBySlug('about', 'pages'))
            ->get();

        $this->assertEquals('http://cool-runnings.com/about?page=2', $data['canonical_url']);
    }
}
