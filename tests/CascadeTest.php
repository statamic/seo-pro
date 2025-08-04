<?php

namespace Tests;

use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults;

class CascadeTest extends TestCase
{
    protected function tearDown(): void
    {
        if ($this->files->exists($path = base_path('custom_seo.yaml'))) {
            $this->files->delete($path);
        }

        parent::tearDown();
    }

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
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
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
    public function it_parses_antlers()
    {
        $entry = Entry::findByUri('/about')->entry();

        $entry->data(['favourite_colour' => 'Red'])->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
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

    /**
     * @test
     *
     * @dataProvider phpInAntlersProvider
     */
    public function it_doesnt_parse_php_in_antlers($antlers, $output)
    {
        $entry = Entry::findByUri('/about')->entry();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'description' => $antlers,
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertNotEquals('php used', $data['description']);
        $this->assertEquals($output, $data['description']);
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
            'home_url' => 'http://cool-runnings.com',
            'humans_txt' => 'http://cool-runnings.com/humans.txt',
            'site' => Site::get('default'),
            'alternate_locales' => [],
            'last_modified' => null,
            'twitter_card' => 'summary_large_image',
        ];

        $this->assertArraySubset($expected, $data);
    }

    /** @test */
    public function it_generates_404_title_with_404_in_response_code_in_context()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'response_code' => 404,
            ])
            ->get();

        $this->assertEquals('404 Page Not Found', $data['title']);
        $this->assertEquals('404 Page Not Found | Site Name', $data['compiled_title']);
    }

    /** @test */
    public function it_generates_seo_cascade_from_custom_site_defaults_path()
    {
        $this->files->put(base_path('custom_seo.yaml'), <<<'EOT'
site_name: Custom Site Name
site_name_position: after
site_name_separator: '|'
title: '@seo:title'
description: '@seo:content'
canonical_url: '@seo:permalink'
priority: 0.8
change_frequency: monthly
EOT
        );

        Config::set('statamic.seo-pro.site_defaults.path', base_path('custom_seo.yaml'));

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->get();

        $expected = [
            'site_name' => 'Custom Site Name',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'I see a bad-ass mother.',
            'priority' => 0.8,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Custom Site Name',
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
        ];

        $this->assertArraySubset($expected, $data);
    }

    /** @test */
    public function it_generates_published_date_from_entry_date()
    {
        // Use an article which has date enabled
        $entry = Entry::make()
            ->collection('articles')
            ->slug('test-article')
            ->date('2023-05-15 10:00:00')
            ->data(['title' => 'Test Article'])
            ->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('2023-05-15T10:00:00+00:00', $data['published_date']);
    }

    /** @test */
    public function it_uses_custom_og_title_when_provided()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'title' => 'Regular Title',
                'og_title' => 'Custom OG Title'
            ])
            ->get();

        $this->assertEquals('Custom OG Title', $data['og_title']);
        $this->assertEquals('Regular Title', $data['title']);
    }

    /** @test */
    public function it_uses_custom_og_description_when_provided()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'description' => 'Regular Description',
                'og_description' => 'Custom OG Description'
            ])
            ->get();

        $this->assertEquals('Custom OG Description', $data['og_description']);
        $this->assertEquals('Regular Description', $data['description']);
    }

    /** @test */
    public function it_uses_custom_twitter_title_when_provided()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'title' => 'Regular Title',
                'twitter_title' => 'Custom Twitter Title'
            ])
            ->get();

        $this->assertEquals('Custom Twitter Title', $data['twitter_title']);
        $this->assertEquals('Regular Title', $data['title']);
    }

    /** @test */
    public function it_uses_custom_twitter_description_when_provided()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'description' => 'Regular Description',
                'twitter_description' => 'Custom Twitter Description'
            ])
            ->get();

        $this->assertEquals('Custom Twitter Description', $data['twitter_description']);
        $this->assertEquals('Regular Description', $data['description']);
    }

    /** @test */
    public function it_extracts_author_name_from_entry()
    {
        $entry = Entry::make()
            ->collection('articles')
            ->slug('test-article')
            ->data([
                'title' => 'Test Article',
                'author' => ['test-user']
            ])
            ->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('Test User', $data['author']);
    }

    /** @test */
    public function it_generates_og_type_for_articles()
    {
        $entry = Entry::make()
            ->collection('articles')
            ->slug('test-article')
            ->data(['title' => 'Test Article'])
            ->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('article', $data['og_type']);
    }

    /** @test */
    public function it_generates_twitter_image_from_entry()
    {
        $entry = Entry::make()
            ->collection('pages')
            ->slug('test-page')
            ->data([
                'title' => 'Test Page',
                'twitter_image' => 'twitter-image.jpg'
            ])
            ->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('twitter-image.jpg', $data['twitter_image']);
    }

    /** @test */
    public function it_generates_updated_date_from_last_modified()
    {
        $entry = Entry::findByUri('/about')->entry();
        
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        // The updated_date will have a value if the entry has been modified
        // In test environment, this depends on the entry's actual last modified time
        $this->assertNotNull($data['updated_date']);
    }

    /** @test */
    public function it_extracts_author_information()
    {
        $entry = Entry::findByUri('/about')->entry();
        $entry->data(['author' => 'John Doe'])->save();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('John Doe', $data['author']);
    }

    /** @test */
    public function it_provides_default_og_type()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->get();

        $this->assertEquals('website', $data['og_type']);
    }

    /** @test */
    public function it_uses_configured_og_type()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['og_type' => 'article'])
            ->get();

        $this->assertEquals('article', $data['og_type']);
    }

    /** @test */
    public function it_uses_og_image_when_configured()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['og_image' => 'og-image.jpg'])
            ->get();

        $this->assertEquals('og-image.jpg', $data['og_image']);
    }

    /** @test */
    public function it_falls_back_to_image_for_og_image()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['image' => 'default-image.jpg'])
            ->get();

        $this->assertEquals('default-image.jpg', $data['og_image']);
    }

    /** @test */
    public function it_uses_og_description_when_configured()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['og_description' => 'Custom OG description'])
            ->get();

        $this->assertEquals('Custom OG description', $data['og_description']);
    }

    /** @test */
    public function it_falls_back_to_description_for_og_description()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['description' => 'Default description'])
            ->get();

        $this->assertEquals('Default description', $data['og_description']);
    }

    /** @test */
    public function it_uses_twitter_title_when_configured()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['twitter_title' => 'Twitter Title'])
            ->get();

        $this->assertEquals('Twitter Title', $data['twitter_title']);
    }

    /** @test */
    public function it_falls_back_to_title_for_twitter_title()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['title' => 'Default Title'])
            ->get();

        $this->assertEquals('Default Title', $data['twitter_title']);
    }

    /** @test */
    public function it_uses_twitter_description_when_configured()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['twitter_description' => 'Twitter description'])
            ->get();

        $this->assertEquals('Twitter description', $data['twitter_description']);
    }

    /** @test */
    public function it_falls_back_to_description_for_twitter_description()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['description' => 'Default description'])
            ->get();

        $this->assertEquals('Default description', $data['twitter_description']);
    }

    /** @test */
    public function it_uses_twitter_image_when_configured()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with(['twitter_image' => 'twitter-image.jpg'])
            ->get();

        $this->assertEquals('twitter-image.jpg', $data['twitter_image']);
    }

    /** @test */
    public function it_falls_back_through_image_hierarchy_for_twitter_image()
    {
        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'og_image' => 'og-image.jpg',
            ])
            ->get();

        $this->assertEquals('og-image.jpg', $data['twitter_image']);

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'image' => 'default-image.jpg',
            ])
            ->get();

        $this->assertEquals('default-image.jpg', $data['twitter_image']);
    }
}
