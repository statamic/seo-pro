<?php

namespace Tests;

use Statamic\Facades\Collection;
use Statamic\Facades\Data;

class GraphQLTest extends TestCase
{
    protected function getEnvironmentSetup($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.graphql.enabled', true);
        $app['config']->set('statamic.graphql.resources.collections', true);
        $app['config']->set('statamic.graphql.resources.taxonomies', true);
        $app['config']->set('statamic.seo-pro.assets.container', 'assets');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->setSeoInSiteDefaults([
                'site_name' => 'Cool Runnings',
                'priority' => 0.7,
            ])
            ->setSeoOnCollection(Collection::find('articles'), [
                'site_name_position' => 'before',
                'site_name_separator' => '>>>',
            ])
            ->setSeoOnEntry(Data::findByUri('/nectar'), [
                'image' => 'img/stetson.jpg',
            ]);
    }

    /** @test */
    public function it_queries_for_entry_seo_meta_html()
    {
        $query = <<<'GQL'
{
    entry(slug: "nectar") {
        title
        seo {
            html
        }
    }
}
GQL;

        $expectedHtml = collect([
            '<title>Cool Runnings &gt;&gt;&gt; Nectar of the Gods</title>',
            '<meta name="description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<meta property="og:type" content="website" />',
            '<meta property="og:title" content="Nectar of the Gods" />',
            '<meta property="og:description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<meta property="og:url" content="http://cool-runnings.com/nectar" />',
            '<meta property="og:site_name" content="Cool Runnings" />',
            '<meta property="og:locale" content="en_US" />',
            '<meta name="twitter:card" content="summary_large_image" />',
            '<meta name="twitter:title" content="Nectar of the Gods" />',
            '<meta name="twitter:description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<meta property="og:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />',
            '<meta property="og:image:width" content="900" />',
            '<meta property="og:image:height" content="587" />',
            '<meta property="og:image:alt" content="" />',
            '<meta name="twitter:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />',
            '<meta name="twitter:image:alt" content="" />',
            '<link href="http://cool-runnings.com" rel="home" />',
            '<link href="http://cool-runnings.com/nectar" rel="canonical" />',
            '<link type="text/plain" rel="author" href="http://cool-runnings.com/humans.txt" />',
        ])->implode('');

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'entry' => [
                    'title' => 'Nectar of the Gods',
                    'seo' => [
                        'html' => $expectedHtml,
                    ],
                ],
            ]]);
    }

    /** @test */
    public function it_queries_for_entry_seo_cascade_so_user_can_render_custom_meta()
    {
        $query = <<<'GQL'
{
    entry(slug: "nectar") {
        title
        seo {
            site_name
            site_name_position
            site_name_separator
            title
            compiled_title
            description
            priority
            change_frequency
            og_title
            canonical_url
            alternate_locales {
                url
            }
            prev_url
            next_url
            home_url
            humans_txt
            twitter_card
            twitter_handle
            twitter_title
            twitter_description
            image {
                url
                permalink
            }
            last_modified(format: "Y-m-d")
        }
    }
}
GQL;

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'entry' => [
                    'title' => 'Nectar of the Gods',
                    'seo' => [
                        'site_name' => 'Cool Runnings',
                        'site_name_position' => 'before',
                        'site_name_separator' => '>>>',
                        'title' => 'Nectar of the Gods',
                        'compiled_title' => 'Cool Runnings >>> Nectar of the Gods',
                        'description' => "The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko's Modern Life. That would have to wait until 5:30pm for that, and I am impatient.\nIn truth, the day wou...",
                        'priority' => 0.7,
                        'change_frequency' => 'monthly',
                        'og_title' => 'Nectar of the Gods',
                        'canonical_url' => 'http://cool-runnings.com/nectar',
                        'alternate_locales' => [],
                        'prev_url' => null,
                        'next_url' => null,
                        'home_url' => 'http://cool-runnings.com',
                        'humans_txt' => 'http://cool-runnings.com/humans.txt',
                        'twitter_card' => 'summary_large_image',
                        'twitter_handle' => null,
                        'twitter_title' => 'Nectar of the Gods',
                        'twitter_description' => "The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko's Modern Life. That would have to wait until 5:30pm for that, and I am impatient.\nIn truth, the day wou...",
                        'image' => [
                            'url' => '/assets/img/stetson.jpg',
                            'permalink' => 'http://cool-runnings.com/assets/img/stetson.jpg',
                        ],
                        'last_modified' => Data::findByUri('/nectar')->lastModified()->format('Y-m-d'),
                    ],
                ],
            ]]);
    }

    /** @test */
    public function it_queries_for_term_seo_meta_html()
    {
        $query = <<<'GQL'
{
    term(id: "topics::dance") {
        title
        seo {
            html
        }
    }
}
GQL;

        $expectedHtml = collect([
            '<title>Dance | Cool Runnings</title>',
            '<meta property="og:type" content="website" />',
            '<meta property="og:title" content="Dance" />',
            '<meta property="og:url" content="http://cool-runnings.com/topics/dance" />',
            '<meta property="og:site_name" content="Cool Runnings" />',
            '<meta property="og:locale" content="en_US" />',
            '<meta name="twitter:card" content="summary_large_image" />',
            '<meta name="twitter:title" content="Dance" />',
            '<link href="http://cool-runnings.com" rel="home" />',
            '<link href="http://cool-runnings.com/topics/dance" rel="canonical" />',
            '<link type="text/plain" rel="author" href="http://cool-runnings.com/humans.txt" />',
        ])->implode('');

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'term' => [
                    'title' => 'Dance',
                    'seo' => [
                        'html' => $expectedHtml,
                    ],
                ],
            ]]);
    }

    /** @test */
    public function it_queries_for_term_seo_cascade_so_user_can_render_custom_meta()
    {
        $query = <<<'GQL'
{
    term(id: "topics::dance") {
        title
        seo {
            site_name
            site_name_position
            site_name_separator
            title
            compiled_title
            description
            priority
            change_frequency
            og_title
            canonical_url
            alternate_locales {
                url
            }
            prev_url
            next_url
            home_url
            humans_txt
            twitter_card
            twitter_handle
            image {
                url
                permalink
            }
            last_modified(format: "Y-m-d")
        }
    }
}
GQL;

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'term' => [
                    'title' => 'Dance',
                    'seo' => [
                        'site_name' => 'Cool Runnings',
                        'site_name_position' => 'after',
                        'site_name_separator' => '|',
                        'title' => 'Dance',
                        'compiled_title' => 'Dance | Cool Runnings',
                        'description' => null,
                        'priority' => 0.7,
                        'change_frequency' => 'monthly',
                        'og_title' => 'Dance',
                        'canonical_url' => 'http://cool-runnings.com/topics/dance',
                        'alternate_locales' => [],
                        'prev_url' => null,
                        'next_url' => null,
                        'home_url' => 'http://cool-runnings.com',
                        'humans_txt' => 'http://cool-runnings.com/humans.txt',
                        'twitter_card' => 'summary_large_image',
                        'twitter_handle' => null,
                        'image' => null,
                        'last_modified' => Data::findByUri('/topics/dance')->lastModified()->format('Y-m-d'),
                    ],
                ],
            ]]);
    }

    /** @test */
    public function it_gracefully_outputs_null_image_when_not_set()
    {
        $query = <<<'GQL'
{
    entry(slug: "dance") {
        seo {
            title
            image {
                url
                permalink
            }
        }
    }
}
GQL;

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'entry' => [
                    'seo' => [
                        'title' => "'Dance Like No One is Watching' Is Bad Advice",
                        'image' => null,
                    ],
                ],
            ]]);
    }
}
