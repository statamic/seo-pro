<?php

namespace Tests\Reporting;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\SeoPro\Reporting\Report;
use Tests\TestCase;

class FilterReportPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Entry::all()->each->delete();
        Term::all()->each->delete();

        if ($this->files->exists($path = storage_path('statamic/seopro/reports'))) {
            $this->files->deleteDirectory($path);
        }
    }

    #[Test]
    public function it_filters_pages_down_to_those_failing_a_rule()
    {
        $this->generateReportWithDuplicateTitles();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.reports.pages.index', [
                'seo_pro_report' => 1,
                'rule' => 'UniqueTitleTag',
            ]))
            ->assertOk();

        $pages = collect($response->json('data'));

        // Only the four terms sharing the duplicate title should be returned.
        $this->assertCount(4, $pages);

        $pages->each(function ($page) {
            $titleResult = collect($page['results'])->firstWhere('handle', 'UniqueTitleTag');

            $this->assertNotNull($titleResult);
            $this->assertNotSame('pass', $titleResult['status']);
        });
    }

    #[Test]
    public function it_returns_all_pages_when_no_rule_is_given()
    {
        $this->generateReportWithDuplicateTitles();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.reports.pages.index', ['seo_pro_report' => 1]))
            ->assertOk();

        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_returns_no_pages_for_a_rule_that_nothing_fails()
    {
        $this->generateReportWithDuplicateTitles();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.reports.pages.index', [
                'seo_pro_report' => 1,
                'rule' => 'NoUnderscoresInUrl',
            ]))
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    protected function generateReportWithDuplicateTitles()
    {
        collect(range(1, 5))->each(function ($i) {
            Term::make()
                ->slug('test-term-'.$i)
                ->taxonomy('topics')
                ->set('title', $i === 5 ? 'Unique Term Title' : 'Duplicate Title')
                ->save();
        });

        Report::create()->save()->generate();
    }
}
