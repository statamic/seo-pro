<?php

namespace Tests;

use Illuminate\Support\Facades\Log;
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
        ];
    }

    public static function obfuscatedPhpInAntlersProvider()
    {
        return [
            [
                "{{ _php_used = (['' => ''] | json); _open = (_php_used | at(0)); _close = (_php_used | at(6)); _antlers_modified = _open + _open + '? echo \"php used\" ?' + _close + _close; _antlers_modified | antlers /}}",
                'PHP Node evaluated in user content: {{? echo "php used" ?}}',
                ' echo "php used" ',
            ],
        ];
    }

    private function getAntlersPhpData($antlers)
    {
        $entry = Entry::findByUri('/about')->entry();

        return (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->with([
                'description' => $antlers,
            ])
            ->withCurrent($entry)
            ->get();
    }

    /**
     * @test
     *
     * @dataProvider phpInAntlersProvider
     */
    public function it_doesnt_parse_php_in_antlers($antlers, $output)
    {
        $data = $this->getAntlersPhpData($antlers);

        $this->assertNotEquals('php used', $data['description']);
        $this->assertEquals($output, $data['description']);
    }

    /**
     * @test
     *
     * @dataProvider obfuscatedPhpInAntlersProvider
     */
    public function it_doesnt_parse_obfuscated_php_in_antlers($antlers, $logMessage, $phpContent)
    {
        Log::shouldReceive('warning')->once()->with($logMessage, [
            'file' => null,
            'trace' => [],
            'content' => $phpContent,
        ]);

        $data = $this->getAntlersPhpData($antlers);

        $this->assertSame('', $data['description']);
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
}
