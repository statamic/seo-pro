<?php

namespace Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->get();

        $expected = [
            'site_name' => 'Cool Runnings',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => 'Home',
            'description' => 'I see a bad-ass mother.',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Home | Cool Runnings',
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->with([
                'description' => '{{ favourite_colour | upper }}',
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('RED', $data['description']);
    }

    #[Test]
    public function it_hydrates_when_parsing_antlers()
    {
        config([
            'app.name' => 'My Site',
            'app.foo' => 'bar',
            'statamic.system.view_config_allowlist' => ['app.name'],
        ]);

        $entry = Entry::findByUri('/about')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->with(['title' => '[{{ config:app:name }}] [{{ config:app:foo }}] [{{ site }}]'])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('[My Site] [] [default]', $data['title']);
    }

    #[Test]
    public function it_returns_the_raw_value_when_a_value_contains_invalid_antlers()
    {
        $entry = Entry::findByUri('/about')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->with([
                'description' => '{{ collection from="locations" }}',
            ])
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('{{ collection from="locations" }}', $data['description']);
    }

    #[Test]
    public function it_returns_the_raw_schema_when_json_ld_schema_contains_invalid_antlers()
    {
        $data = (new Cascade)
            ->with([
                'title' => 'Home',
                'json_ld_schema' => '{{ collection from="locations" }}',
            ])
            ->get();

        $this->assertEquals(['{{ collection from="locations" }}'], $data['json_ld']->all());
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
        Collection::findByHandle('pages')->queryEntries()->get()->each->delete();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->get();

        $expected = [
            'site_name' => 'Cool Runnings',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => null,
            'description' => null,
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'compiled_title' => 'Cool Runnings',
            'og_title' => 'Cool Runnings',
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->with([
                'response_code' => 404,
            ])
            ->get();

        $this->assertEquals('404 Page Not Found', $data['title']);
        $this->assertEquals('404 Page Not Found | Cool Runnings', $data['compiled_title']);
    }

    #[Test]
    public function it_noindexes_error_pages()
    {
        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->with([
                'response_code' => 404,
            ])
            ->get();

        $this->assertContains('noindex', $data['robots']);
        $this->assertEquals('noindex', $data['robots_indexing']);
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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
            ->withSiteDefaults(SiteDefaults::in('default')->all())
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

    #[Test]
    public function it_generates_json_ld_data()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'site_name' => 'Cool Writings',
            'description' => 'Bob sled team',
            'json_ld_entity' => 'person',
            'json_ld_entity_name' => 'Derice Bannock',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->with([
                'title' => 'Home',
                'json_ld_schema' => '{"@context":"https://schema.org","@type":"WebPage","name":"{{ title }}"}',
            ])
            ->get();

        $this->assertEquals([
            '{"@context":"https://schema.org","@type":"Person","name":"Derice Bannock","@id":"http://cool-runnings.com#person","url":"http://cool-runnings.com"}',
            '{"@context":"https://schema.org","@type":"WebPage","name":"Home"}',
        ], $data['json_ld']->all());
    }

    #[Test]
    public function it_generates_json_ld_data_with_custom_schema_from_site_defaults()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'site_name' => 'Cool Writings',
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_schema' => '{"@context":"https://schema.org","@type":"LocalBusiness","name":"Cool Runnings Ltd"}',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->with([
                'title' => 'Home',
            ])
            ->get();

        $this->assertEquals([
            '{"@context":"https://schema.org","@type":"Organization","name":"Cool Runnings Ltd","@id":"http://cool-runnings.com#organization","url":"http://cool-runnings.com"}',
            '{"@context":"https://schema.org","@type":"LocalBusiness","name":"Cool Runnings Ltd"}',
        ], $data['json_ld']->all());
    }

    #[Test]
    public function it_only_outputs_organization_schema_on_the_homepage()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
        ]);

        $home = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $this->assertContains(
            '{"@context":"https://schema.org","@type":"Organization","name":"Cool Runnings Ltd","@id":"http://cool-runnings.com#organization","url":"http://cool-runnings.com"}',
            $home['json_ld']->all()
        );

        $about = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/about'))
            ->get();

        $this->assertEmpty($about['json_ld']->all());
    }

    #[Test]
    public function it_only_outputs_person_schema_on_the_homepage()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'person',
            'json_ld_entity_name' => 'Derice Bannock',
        ]);

        $home = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $this->assertContains(
            '{"@context":"https://schema.org","@type":"Person","name":"Derice Bannock","@id":"http://cool-runnings.com#person","url":"http://cool-runnings.com"}',
            $home['json_ld']->all()
        );

        $about = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/about'))
            ->get();

        $this->assertEmpty($about['json_ld']->all());
    }

    #[Test]
    public function it_parses_antlers_in_json_ld_schema_values()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_schema' => new Value('{"@context":"https://schema.org","@type":"WebPage","name":"{{ title }}"}'),
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $this->assertEquals('{"@context":"https://schema.org","@type":"WebPage","name":"Home"}', $data['json_ld']->last());
    }

    #[Test]
    public function glide_url_is_returned_for_json_ld_organization_logo()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'site_name' => 'Cool Writings',
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_entity_logo' => 'assets::img/stetson.jpg',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $organization = json_decode($data['json_ld'][0], true);

        $this->assertEquals('Organization', $organization['@type']);
        $this->assertStringContainsString('/img/asset/', $organization['logo']);
        $this->assertStringContainsString('w=512', $organization['logo']);
        $this->assertStringContainsString('h=512', $organization['logo']);
    }

    #[Test]
    public function permalink_is_returned_for_json_ld_organization_logo_when_config_is_false()
    {
        config(['statamic.seo-pro.json_ld.use_glide_for_logo' => false]);

        $siteDefaults = SiteDefaults::in('default')->set([
            'site_name' => 'Cool Writings',
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_entity_logo' => 'assets::img/stetson.jpg',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $organization = json_decode($data['json_ld'][0], true);

        $this->assertEquals('Organization', $organization['@type']);
        $this->assertEquals('http://cool-runnings.com/assets/img/stetson.jpg', $organization['logo']);
    }

    #[Test]
    public function it_defaults_to_organization_when_entity_type_is_missing()
    {
        // Legacy defaults that predate the entity type field won't have json_ld_entity set.
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity_name' => 'Cool Runnings Ltd',
        ]);

        $home = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($home['json_ld']->first(), true);

        $this->assertEquals('Organization', $schema['@type']);
        $this->assertEquals('Cool Runnings Ltd', $schema['name']);
    }

    #[Test]
    public function it_does_not_output_entity_schema_when_disabled()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'disabled',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
        ]);

        $home = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $this->assertEmpty($home['json_ld']->all());
    }

    #[Test]
    public function it_migrates_legacy_organization_handles_when_reading_site_defaults()
    {
        $this->setSeoInSiteDefaults([
            'json_ld_entity' => 'organization',
            'json_ld_organization_name' => 'Cool Runnings Ltd',
            'json_ld_organization_logo' => 'assets::img/stetson.jpg',
        ]);

        $values = SiteDefaults::in('default')->all();

        $this->assertEquals('Cool Runnings Ltd', $values['json_ld_entity_name']);
        $this->assertEquals('assets::img/stetson.jpg', $values['json_ld_entity_logo']);
        $this->assertArrayNotHasKey('json_ld_organization_name', $values);
        $this->assertArrayNotHasKey('json_ld_organization_logo', $values);
    }

    #[Test]
    public function it_migrates_legacy_person_name_when_reading_site_defaults()
    {
        $this->setSeoInSiteDefaults([
            'json_ld_entity' => 'person',
            'json_ld_person_name' => 'Derice Bannock',
        ]);

        $values = SiteDefaults::in('default')->all();

        $this->assertEquals('Derice Bannock', $values['json_ld_entity_name']);
        $this->assertArrayNotHasKey('json_ld_person_name', $values);
    }

    #[Test]
    public function it_includes_shared_entity_fields()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_entity_alternate_name' => 'Cool Runnings',
            'json_ld_entity_description' => 'Jamaican bobsled team',
            'json_ld_entity_url' => 'https://example.com',
            'json_ld_entity_telephone' => '+1234567890',
            'json_ld_entity_email' => 'hello@example.com',
            'json_ld_entity_same_as' => [
                'https://twitter.com/coolrunnings',
                'https://facebook.com/coolrunnings',
            ],
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertEquals('Cool Runnings', $schema['alternateName']);
        $this->assertEquals('Jamaican bobsled team', $schema['description']);
        $this->assertEquals('https://example.com', $schema['url']);
        $this->assertEquals('+1234567890', $schema['telephone']);
        $this->assertEquals('hello@example.com', $schema['email']);
        $this->assertEquals([
            'https://twitter.com/coolrunnings',
            'https://facebook.com/coolrunnings',
        ], $schema['sameAs']);
    }

    #[Test]
    public function it_falls_back_to_the_home_url_when_entity_url_is_blank()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertEquals('http://cool-runnings.com', $schema['url']);
    }

    #[Test]
    public function it_nests_postal_address_and_geo_coordinates()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_entity_street_address' => '1 Ice Track',
            'json_ld_entity_locality' => 'Calgary',
            'json_ld_entity_region' => 'Alberta',
            'json_ld_entity_postal_code' => 'T2P',
            'json_ld_entity_country' => 'CA',
            'json_ld_entity_latitude' => '51.0447',
            'json_ld_entity_longitude' => '-114.0719',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertEquals([
            '@type' => 'PostalAddress',
            'streetAddress' => '1 Ice Track',
            'addressLocality' => 'Calgary',
            'addressRegion' => 'Alberta',
            'postalCode' => 'T2P',
            'addressCountry' => 'CA',
        ], $schema['address']);

        $this->assertEquals([
            '@type' => 'GeoCoordinates',
            'latitude' => '51.0447',
            'longitude' => '-114.0719',
        ], $schema['geo']);
    }

    #[Test]
    public function it_omits_geo_coordinates_when_only_one_value_is_set()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
            'json_ld_entity_latitude' => '51.0447',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertArrayNotHasKey('geo', $schema);
    }

    #[Test]
    public function it_generates_local_business_schema()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'local_business',
            'json_ld_entity_name' => 'Cool Runnings Bobsled',
            'json_ld_entity_price_range' => '$$',
            'json_ld_entity_opening_hours' => [
                'monday' => ['opening' => '09:00', 'closing' => '17:00'],
                'sunday' => ['opening' => null, 'closing' => null],
            ],
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertEquals('LocalBusiness', $schema['@type']);
        $this->assertEquals('http://cool-runnings.com#localbusiness', $schema['@id']);
        $this->assertEquals('$$', $schema['priceRange']);
        $this->assertEquals([[
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'https://schema.org/Monday',
            'opens' => '09:00',
            'closes' => '17:00',
        ]], $schema['openingHoursSpecification']);
    }

    #[Test]
    public function it_generates_corporation_schema()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_entity' => 'corporation',
            'json_ld_entity_name' => 'Cool Runnings Inc',
            'json_ld_entity_ticker_symbol' => 'NASDAQ:COOL',
        ]);

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/'))
            ->get();

        $schema = json_decode($data['json_ld']->first(), true);

        $this->assertEquals('Corporation', $schema['@type']);
        $this->assertEquals('http://cool-runnings.com#corporation', $schema['@id']);
        $this->assertEquals('NASDAQ:COOL', $schema['tickerSymbol']);
    }

    #[Test]
    public function it_generates_json_ld_breadcrumbs()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'site_name' => 'Cool Writings',
            'description' => 'Bob sled team',
            'json_ld_entity' => 'person',
            'json_ld_entity_name' => 'Derice Bannock',
            'json_ld_breadcrumbs' => true,
        ]);

        $this->get('/dance');

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $this->assertEquals([
            '{"@context":"https://schema.org","@type":"Person","name":"Derice Bannock","@id":"http://cool-runnings.com#person","url":"http://cool-runnings.com"}',
            '{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"http://cool-runnings.com"},{"@type":"ListItem","position":2,"name":"\'Dance Like No One is Watching\' Is Bad Advice","item":"http://cool-runnings.com/dance"}]}',
        ], $data['json_ld']->all());
    }

    #[Test]
    public function it_generates_json_ld_breadcrumbs_for_entry()
    {
        Collection::findByHandle('articles')->routes('articles/{slug}')->save();

        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_breadcrumbs' => true,
        ]);

        $this->get('/articles/dance');

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $breadcrumbs = collect($data['json_ld'])->first(fn ($snippet) => str_contains($snippet, 'BreadcrumbList'));
        $breadcrumbs = json_decode($breadcrumbs, true);

        $this->assertEquals('BreadcrumbList', $breadcrumbs['@type']);
        $this->assertCount(3, $breadcrumbs['itemListElement']);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'http://cool-runnings.com',
        ], $breadcrumbs['itemListElement'][0]);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Articles',
            'item' => 'http://cool-runnings.com/articles',
        ], $breadcrumbs['itemListElement'][1]);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 3,
            'name' => "'Dance Like No One is Watching' Is Bad Advice",
            'item' => 'http://cool-runnings.com/articles/dance',
        ], $breadcrumbs['itemListElement'][2]);
    }

    #[Test]
    public function it_generates_json_ld_breadcrumbs_for_taxonomy_term()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_breadcrumbs' => true,
        ]);

        $this->files->makeDirectory(resource_path('views/topics'), force: true);
        $this->files->put(resource_path('views/topics/index.antlers.html'), '');

        $this->get('/topics/sneakers');

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $breadcrumbs = collect($data['json_ld'])->first(fn ($snippet) => str_contains($snippet, 'BreadcrumbList'));
        $breadcrumbs = json_decode($breadcrumbs, true);

        $this->assertEquals('BreadcrumbList', $breadcrumbs['@type']);
        $this->assertCount(3, $breadcrumbs['itemListElement']);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'http://cool-runnings.com',
        ], $breadcrumbs['itemListElement'][0]);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Topics',
            'item' => 'http://cool-runnings.com/topics',
        ], $breadcrumbs['itemListElement'][1]);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 3,
            'name' => 'Sneakers',
            'item' => 'http://cool-runnings.com/topics/sneakers',
        ], $breadcrumbs['itemListElement'][2]);

        $this->files->deleteDirectory(resource_path('views/topics'));
    }

    #[Test]
    public function it_generates_json_ld_breadcrumbs_for_taxonomy_term_when_taxonomy_has_no_template()
    {
        $siteDefaults = SiteDefaults::in('default')->set([
            'json_ld_breadcrumbs' => true,
        ]);

        // Remove the page that shadows the /topics URL so it resolves to the taxonomy itself.
        // The taxonomy has no template, so the breadcrumbs tag drops it from the trail.
        $this->files->deleteDirectory(resource_path('views/topics'));
        Entry::findByUri('/topics')->delete();

        $this->get('/topics/sneakers');

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $breadcrumbs = collect($data['json_ld'])->first(fn ($snippet) => str_contains($snippet, 'BreadcrumbList'));

        // The taxonomy crumb is omitted (it has no template), so itemListElement must
        // still be a sequential JSON array with contiguous positions - not a keyed object with gaps.
        $this->assertStringContainsString('"itemListElement":[{', $breadcrumbs);

        $breadcrumbs = json_decode($breadcrumbs, true);

        $this->assertEquals('BreadcrumbList', $breadcrumbs['@type']);
        $this->assertCount(2, $breadcrumbs['itemListElement']);
        $this->assertSame([0, 1], array_keys($breadcrumbs['itemListElement']));

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'http://cool-runnings.com',
        ], $breadcrumbs['itemListElement'][0]);

        $this->assertEquals([
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Sneakers',
            'item' => 'http://cool-runnings.com/topics/sneakers',
        ], $breadcrumbs['itemListElement'][1]);
    }
}
