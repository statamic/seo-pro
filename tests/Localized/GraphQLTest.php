<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class GraphQLTest extends LocalizedTestCase
{
    protected function getEnvironmentSetup($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.graphql.enabled', true);
        $app['config']->set('statamic.graphql.resources.collections', true);
    }

    #[Test]
    public function it_queries_multisite_for_canonical_url_and_alternate_locales_in_html_meta()
    {
        $query = <<<'GQL'
{
    entry(id: "d9848738-ca48-433e-b85f-8e9571780e0c") {
        seo {
            html
        }
    }
}
GQL;

        $expectedHtml = collect([
            '<title>Les Nectar of the Gods | Site Name</title>',
            '<meta name="description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<meta property="og:type" content="website" />',
            '<meta property="og:title" content="Les Nectar of the Gods" />',
            '<meta property="og:description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<meta property="og:url" content="http://cool-runnings.com/fr/nectar" />',
            '<meta property="og:site_name" content="Site Name" />',
            '<meta property="og:locale" content="fr_FR" />',
            '<meta property="og:locale:alternate" content="en_US" />',
            '<meta name="twitter:card" content="summary_large_image" />',
            '<meta name="twitter:title" content="Les Nectar of the Gods" />',
            '<meta name="twitter:description" content="The day started just like any other. Wake up at 5:30am, brush my teeth, bathe in a tub of warm milk, and trim my toenails while quietly resenting the fact that Flipper was on Nickelodeon at this hour instead of Rocko&#039;s Modern Life. That would have to wait until 5:30pm for that, and I am impatient.In truth, the day wou..." />',
            '<link href="http://cool-runnings.com" rel="home" />',
            '<link href="http://cool-runnings.com/fr/nectar" rel="canonical" />',
            '<link rel="alternate" href="http://cool-runnings.com/fr/nectar" hreflang="fr" />',
            '<link rel="alternate" href="http://cool-runnings.com/nectar" hreflang="en" />',
            '<link type="text/plain" rel="author" href="http://cool-runnings.com/humans.txt" />',
        ])->implode('');

        $this
            ->withoutExceptionHandling()
            ->post('/graphql', ['query' => $query])
            ->assertGqlOk()
            ->assertExactJson(['data' => [
                'entry' => [
                    'seo' => [
                        'html' => $expectedHtml,
                    ],
                ],
            ]]);
    }

    #[Test]
    public function it_queries_multisite_for_canonical_url_and_alternate_locales_in_cascade_meta()
    {
        $query = <<<'GQL'
{
    entry(id: "d9848738-ca48-433e-b85f-8e9571780e0c") {
        seo {
            title
            canonical_url
            alternate_locales {
                site {
                    handle
                    locale
                }
                url
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
                        'title' => 'Les Nectar of the Gods',
                        'canonical_url' => 'http://cool-runnings.com/fr/nectar',
                        'alternate_locales' => [
                            [
                                'site' => [
                                    'handle' => 'default',
                                    'locale' => 'en_US',
                                ],
                                'url' => 'http://cool-runnings.com/nectar',
                            ],
                        ],
                    ],
                ],
            ]]);
    }

    #[Test]
    public function it_queries_localized_site_default_values()
    {
        SiteDefaults::in('default')->set('site_name', 'Cool Runnings')->save();
        SiteDefaults::in('french')->set('site_name', 'Corse Fantastiche')->save();

        $query = <<<'GQL'
{
    entry(id: "8e4b4e60-5dfb-47b0-a2d7-a904d64aeb80") {
        locale
        seo {
            title
            site_name
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
                    'locale' => 'default',
                    'seo' => [
                        'title' => 'Nectar of the Gods',
                        'site_name' => 'Cool Runnings',
                    ],
                ],
            ]]);

        $query = <<<'GQL'
{
    entry(id: "d9848738-ca48-433e-b85f-8e9571780e0c") {
        locale
        seo {
            title
            site_name
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
                    'locale' => 'french',
                    'seo' => [
                        'title' => 'Les Nectar of the Gods',
                        'site_name' => 'Corse Fantastiche',
                    ],
                ],
            ]]);
    }
}
