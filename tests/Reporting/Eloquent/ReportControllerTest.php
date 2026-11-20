<?php

namespace Tests\Reporting\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.reports.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function create_redirects_to_the_new_report_and_show_finds_it()
    {
        $this->actingAs(User::make()->makeSuper()->save());

        $this->get(cp_route('seo-pro.reports.create'))->assertRedirect(cp_route('seo-pro.reports.show', 1));
        $this->assertDatabaseHas('seo_pro_reports', ['id' => 1]);

        $this->get(cp_route('seo-pro.reports.show', 1))->assertOk();
        $this->get(cp_route('seo-pro.reports.show', 99))->assertNotFound();
        $this->getJson(cp_route('seo-pro.reports.pages.index', 1))->assertOk();
        $this->get(cp_route('seo-pro.reports.index'))->assertOk();

        $this->delete(cp_route('seo-pro.reports.destroy', 1));
        $this->assertDatabaseMissing('seo_pro_reports', ['id' => 1]);
    }
}
