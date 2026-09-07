<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ExportRedirectsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_export_redirects_as_csv()
    {
        Facades\Redirect::make()
            ->id('one')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        Facades\Redirect::make()
            ->id('two')
            ->source('/another-old')
            ->destination('/another-new')
            ->responseCode(302)
            ->enabled(false)
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.export'))
            ->assertOk()
            ->assertDownload('redirects.csv');

        $csv = $this->parseCsv($response->getFile()->getContent());

        $this->assertCount(2, $csv);
        $this->assertEquals('/old-url', $csv[0]['source']);
        $this->assertEquals('/new-url', $csv[0]['destination']);
        $this->assertEquals('301', $csv[0]['response_code']);
        $this->assertEquals('true', $csv[0]['enabled']);
        $this->assertEquals('/another-old', $csv[1]['source']);
        $this->assertEquals('/another-new', $csv[1]['destination']);
        $this->assertEquals('302', $csv[1]['response_code']);
        $this->assertEquals('false', $csv[1]['enabled']);
    }

    #[Test]
    public function export_includes_description()
    {
        $redirect = Facades\Redirect::make()
            ->id('one')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true);

        $redirect->set('description', 'Legacy campaign URL');
        $redirect->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.export'))
            ->assertOk();

        $csv = $this->parseCsv($response->getFile()->getContent());

        $this->assertEquals('Legacy campaign URL', $csv[0]['description']);
    }

    #[Test]
    public function cant_export_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.redirects.export'))
            ->assertRedirect('/cp');
    }

    private function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", trim($content)));
        $headers = str_getcsv(array_shift($lines));

        return array_map(function ($line) use ($headers) {
            return array_combine($headers, str_getcsv($line));
        }, $lines);
    }
}
