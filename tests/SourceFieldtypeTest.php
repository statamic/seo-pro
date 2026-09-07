<?php

namespace Tests;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Fields\Field;
use Statamic\SeoPro\Fieldtypes\SourceFieldtype;

class SourceFieldtypeTest extends TestCase
{
    #[Test]
    public function resolves_placeholders_for_entry()
    {
        Blink::flush();

        $this->setSeoInSiteDefaults([
            'site_name' => 'Test Site',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
        ]);

        $collection = tap(Collection::make('posts'))->save();

        $entry = tap(Entry::make()
            ->collection($collection)
            ->slug('hello-world')
            ->data(['title' => 'Hello World']))
            ->save();

        $this->app->instance('request', Request::create('/cp/collections/posts/entries/'.$entry->id()));

        $field = (new SourceFieldtype)->setField(
            (new Field('site_name', [
                'type' => 'seo_pro_source',
                'field' => false,
            ]))
                ->setParent($entry)
                ->setValue(['source' => 'inherit', 'value' => null])
        );

        $preloaded = $field->preload();

        $this->assertArrayHasKey('placeholder', $preloaded);
        $this->assertEquals('Test Site', $preloaded['placeholder']);
    }

    #[Test]
    public function resolves_placeholders_for_term()
    {
        Blink::flush();

        $this->setSeoInSiteDefaults([
            'site_name' => 'Test Site',
        ]);

        $taxonomy = tap(Taxonomy::make('tags'))->save();

        $term = tap(Term::make()
            ->taxonomy($taxonomy)
            ->slug('featured')
            ->data(['title' => 'Featured']))
            ->save();

        $this->app->instance('request', Request::create('/cp/taxonomies/tags/terms/'.$term->slug()));

        $field = (new SourceFieldtype)->setField(
            (new Field('site_name', [
                'type' => 'seo_pro_source',
                'field' => false,
            ]))
                ->setParent($term)
                ->setValue(['source' => 'inherit', 'value' => null])
        );

        $preloaded = $field->preload();

        $this->assertArrayHasKey('placeholder', $preloaded);
        $this->assertEquals('Test Site', $preloaded['placeholder']);
    }

    #[Test]
    public function resolves_placeholders_for_section_defaults()
    {
        Blink::flush();

        $this->setSeoInSiteDefaults([
            'site_name' => 'My Site Name',
            'title' => '@seo:title',
        ]);

        // Register a fake section defaults route
        Route::name('statamic.cp.seo-pro.section-defaults.collections.edit')
            ->get('/cp/seo-pro/section-defaults/collections/{collection}/edit', fn () => null);

        // Create request matching the route
        $request = Request::create('/cp/seo-pro/section-defaults/collections/articles/edit');
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);

        $field = (new SourceFieldtype)->setField(
            (new Field('site_name', [
                'type' => 'seo_pro_source',
                'field' => false,
            ]))
                ->setValue(['source' => 'inherit', 'value' => null])
        );

        $preloaded = $field->preload();

        $this->assertArrayHasKey('placeholder', $preloaded);
        $this->assertEquals('My Site Name', $preloaded['placeholder']);
    }

    #[Test]
    public function cant_resolve_placeholders_on_unknown_object()
    {
        Blink::flush();

        $this->app->instance('request', Request::create('/about'));

        $field = (new SourceFieldtype)->setField(
            (new Field('title', [
                'type' => 'seo_pro_source',
                'field' => ['type' => 'text'],
            ]))
                ->setValue(['source' => 'inherit', 'value' => null])
        );

        $preloaded = $field->preload();

        $this->assertArrayHasKey('placeholder', $preloaded);
        $this->assertNull($preloaded['placeholder']);
    }

    /**
     * @see https://github.com/statamic/seo-pro/issues/521
     */
    #[Test]
    public function json_ld_schema_referencing_seo_fields_doesnt_cause_infinite_loop_()
    {
        Blink::flush();

        $collection = tap(Collection::make('articles'))->save();

        $this->setSeoOnCollection($collection, [
            'title' => '@seo:title',
            'json_ld_schema' => '{"@context": "https://schema.org", "@type": "Article", "headline": "{{ seo:title }}"}',
        ]);

        $entry = tap(Entry::make()
            ->collection($collection)
            ->slug('test-article')
            ->data(['title' => 'My Article Title']))
            ->save();

        $this->app->instance('request', Request::create('/cp/collections/articles/entries/'.$entry->id()));

        $field = (new SourceFieldtype)->setField(
            (new Field('title', [
                'type' => 'seo_pro_source',
                'field' => ['type' => 'text'],
            ]))
                ->setParent($entry)
                ->setValue(['source' => 'inherit', 'value' => null])
        );

        $preloaded = $field->preload(); // Shouldn't cause an infinite loop.

        $this->assertArrayHasKey('placeholder', $preloaded);
        $this->assertEquals('My Article Title', $preloaded['placeholder']);
    }
}
