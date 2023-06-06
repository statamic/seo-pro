<?php

namespace Tests;

use Statamic\Facades\Collection;

class GraphQLTest extends TestCase
{
    protected function getEnvironmentSetup($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.graphql.enabled', true);
        $app['config']->set('statamic.graphql.resources.collections', true);
        $app['config']->set('statamic.seo-pro.assets.container', 'assets');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->setSeoInSiteDefaults([
                'priority' => 0.7,
            ])
            ->setSeoOnCollection(Collection::find('articles'), [
                'site_name' => 'Cool Runnings',
                'site_name_position' => 'before',
                'site_name_separator' => '>>>',
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
            '<link href="http://cool-runnings.com/" rel="home" />',
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
            image {
                url
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
                        'home_url' => 'http://cool-runnings.com/',
                        'humans_txt' => 'http://cool-runnings.com/humans.txt',
                        'twitter_card' => 'summary_large_image',
                        'twitter_handle' => null,
                        'image' => null,
                        'last_modified' => '2023-06-06',
                    ],
                ],
            ]]);
    }
}
