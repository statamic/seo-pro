<?php

namespace Tests\Redirects;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportRedirectsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    #[Test]
    public function can_import_redirects_with_source_and_destination_only()
    {
        $this->uploadCsv("source,destination\n/old,/new\n/another-old,/another-new");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk()
            ->assertJson(['created' => 2, 'updated' => 0]);

        $redirect = Facades\Redirect::query()->where('source', '/old')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/new', $redirect->destination());
        $this->assertEquals(301, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function can_import_redirects_with_all_columns()
    {
        $this->uploadCsv("source,destination,response_code,enabled,description\n/old,/new,302,false,Legacy page");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk()
            ->assertJson(['created' => 1, 'updated' => 0]);

        $redirect = Facades\Redirect::query()->where('source', '/old')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/new', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertFalse($redirect->enabled());
        $this->assertEquals('Legacy page', $redirect->get('description'));
    }

    #[Test]
    public function import_updates_existing_redirects()
    {
        Facades\Redirect::make()
            ->id('existing')
            ->source('/old')
            ->destination('/original')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->uploadCsv("source,destination\n/old,/updated");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk()
            ->assertJson(['created' => 0, 'updated' => 1]);

        $redirect = Facades\Redirect::find('existing');

        $this->assertEquals('/updated', $redirect->destination());
    }

    #[Test]
    public function import_preserves_existing_values_when_optional_columns_are_absent()
    {
        $existing = Facades\Redirect::make()
            ->id('existing')
            ->source('/old')
            ->destination('/original')
            ->responseCode(302)
            ->enabled(false);

        $existing->set('description', 'Keep this note');
        $existing->save();

        $this->uploadCsv("source,destination\n/old,/updated");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk();

        $redirect = Facades\Redirect::find('existing');

        $this->assertEquals('/updated', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertFalse($redirect->enabled());
        $this->assertEquals('Keep this note', $redirect->get('description'));
    }

    #[Test]
    public function import_overrides_values_when_optional_columns_are_present()
    {
        $existing = Facades\Redirect::make()
            ->id('existing')
            ->source('/old')
            ->destination('/original')
            ->responseCode(301)
            ->enabled(true);

        $existing->set('description', 'Old note');
        $existing->save();

        $this->uploadCsv("source,destination,response_code,enabled,description\n/old,/updated,302,false,New note");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk();

        $redirect = Facades\Redirect::find('existing');

        $this->assertEquals('/updated', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertFalse($redirect->enabled());
        $this->assertEquals('New note', $redirect->get('description'));
    }

    #[Test]
    public function import_skips_rows_with_empty_source()
    {
        $this->uploadCsv("source,destination\n,/new\n/valid,/destination");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk()
            ->assertJson(['created' => 1, 'updated' => 0]);

        $this->assertNull(Facades\Redirect::query()->where('source', '')->first());
        $this->assertNotNull(Facades\Redirect::query()->where('source', '/valid')->first());
    }

    #[Test]
    public function import_validates_file_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function import_validates_csv_has_required_headers()
    {
        $this->uploadCsv("url,target\n/old,/new");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertUnprocessable()
            ->assertJson(['message' => 'The CSV must have at least two columns: source, destination.']);
    }

    #[Test]
    public function cant_import_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this->uploadCsv("source,destination\n/old,/new");

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertForbidden();

        $this->assertNull(Facades\Redirect::query()->where('source', '/old')->first());
    }

    #[Test]
    public function import_cleans_up_uploaded_file()
    {
        $this->uploadCsv("source,destination\n/old,/new");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk();

        Storage::disk('local')->assertMissing('statamic/file-uploads/import.csv');
    }

    #[Test]
    public function import_uses_configured_default_response_code()
    {
        config(['statamic.seo-pro.redirects.default_response_code' => 302]);

        $this->uploadCsv("source,destination\n/old,/new");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source', '/old')->first();

        $this->assertEquals(302, $redirect->responseCode());
    }

    #[Test]
    public function import_handles_human_friendly_column_names()
    {
        $this->uploadCsv("Source,Destination,Response Code,Enabled,Description\n/old,/new,302,false,A note");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.import'), ['file' => ['import.csv']])
            ->assertOk()
            ->assertJson(['created' => 1, 'updated' => 0]);

        $redirect = Facades\Redirect::query()->where('source', '/old')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/new', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertFalse($redirect->enabled());
        $this->assertEquals('A note', $redirect->get('description'));
    }

    private function uploadCsv(string $content, string $filename = 'import.csv'): void
    {
        Storage::disk('local')->put("statamic/file-uploads/{$filename}", $content);
    }
}
