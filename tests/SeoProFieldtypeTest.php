<?php

namespace Tests;

use Illuminate\Support\Facades\Request;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\SeoPro\Fieldtypes\SeoProFieldtype;

class SeoProFieldtypeTest extends TestCase
{
    #[Test]
    public function it_augments()
    {
        $field = (new SeoProFieldtype)->setField(new Field('test', ['type' => 'seo_pro']));

        $augment = $field->augment([
            'title' => 'Foo',
            'description' => 'Bar',
        ]);

        $this->assertCount(2, $augment);

        $this->assertInstanceOf(Value::class, $augment['title']);
        $this->assertEquals('Foo', $augment['title']->value());

        $this->assertInstanceOf(Value::class, $augment['description']);
        $this->assertEquals('Bar', $augment['description']->value());
    }

    #[Test]
    public function it_augments_for_api_routes()
    {
        config()->set('statamic.editions.pro', true);
        config()->set('statamic.api.enabled', true);
        config()->set('statamic.api.resources.collections', true);

        // Changing our config values above won't register the API routes. To avoid
        // undefined route errors, we'll register what we need here.
        $this->app['router']->get('/api/collection/{collection}/entries/{handle}', fn () => null)->name('statamic.api.collections.entries.show');
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->app->instance('request', Request::create('/api/collections/pages/entries'));

        $collection = tap(Collection::make('pages'))->save();
        $entry = tap(Entry::make()->collection($collection)->data(['title' => 'Foo', 'description' => 'Bar']))->save();

        $field = (new SeoProFieldtype)->setField((new Field('test', ['type' => 'seo_pro']))->setParent($entry));

        $augment = $field->augment([
            'title' => 'Foo',
            'description' => 'Bar',
        ]);

        $this->assertArrayHasKeys([
            'title',
            'description',
            'site_name',
            'site_name_position',
            'site_name_separator',
            'canonical_url',
            'priority',
            'change_frequency',
            'compiled_title',
            'og_title',
            'prev_url',
            'next_url',
            'home_url',
            'humans_txt',
            'site',
            'alternate_locales',
            'current_hreflang',
            'last_modified',
            'twitter_card',
            'twitter_title',
            'twitter_description',
        ], $augment);

        $this->assertEquals('Foo', (string) $augment['title']);
        $this->assertEquals('Bar', (string) $augment['description']);
        $this->assertEquals('Site Name', (string) $augment['site_name']); // Site default
    }
}
