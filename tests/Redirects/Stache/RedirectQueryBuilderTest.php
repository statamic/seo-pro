<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RedirectQueryBuilderTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_query_columns()
    {
        Redirect::make()->id('abc')->sourceUrl('https://cool-runnings.com/old-url')->destinationUrl('https://cool-runnings.com/new-url')->save();
        Redirect::make()->id('def')->sourceUrl('https://cool-runnings.com/old-url-2')->destinationUrl('https://cool-runnings.com/new-url-2')->save();
        Redirect::make()->id('ghi')->sourceUrl('https://cool-runnings.com/old-url-3')->destinationUrl('https://cool-runnings.com/new-url-3')->save();

        $query = Redirect::query()->where('source_url', 'https://cool-runnings.com/old-url-2');

        $this->assertCount(1, $query->get());
        $this->assertEquals('def', $query->first()->id());
    }

    #[Test]
    public function sorting_by_unsafe_method_does_not_invoke_it()
    {
        Redirect::make()->id('abc')->save();
        Redirect::make()->id('def')->save();
        Redirect::make()->id('ghi')->save();

        $count = Redirect::all()->count();
        $this->assertGreaterThan(0, $count);

        Redirect::query()->orderBy('delete', 'asc')->get();

        $this->assertCount($count, Redirect::all());
    }
}
