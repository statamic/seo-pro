<?php

namespace Tests\Localized;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Support\Str;

class ReportTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Entry::all()->filter(fn ($entry) => $entry->hasOrigin())->each->delete();
        Entry::all()->each->delete();
        Term::all()->each->delete();

        if ($this->files->exists($path = $this->reportsPath())) {
            $this->files->deleteDirectory($path);
        }
    }

    /**
     * @see https://github.com/statamic/seo-pro/issues/213
     */
    #[Test]
    public function it_does_not_flag_duplicate_titles_and_descriptions_across_sites()
    {
        $this->makeArticle('default', 'one', 'Shared Title', 'Shared Description');
        $this->makeArticle('french', 'two', 'Shared Title', 'Shared Description');

        $this->assertEqualsIgnoringLineEndings(0, $this->getReportResult('UniqueTitleTag'));
        $this->assertEqualsIgnoringLineEndings(0, $this->getReportResult('UniqueMetaDescription'));
    }

    /**
     * @see https://github.com/statamic/seo-pro/issues/213
     */
    #[Test]
    public function it_still_flags_duplicate_titles_and_descriptions_within_the_same_site()
    {
        $this->makeArticle('default', 'one', 'Shared Title', 'Shared Description');
        $this->makeArticle('default', 'two', 'Shared Title', 'Shared Description');
        $this->makeArticle('french', 'three', 'Shared Title', 'Shared Description');

        $this->assertEqualsIgnoringLineEndings(2, $this->getReportResult('UniqueTitleTag'));
        $this->assertEqualsIgnoringLineEndings(2, $this->getReportResult('UniqueMetaDescription'));
    }

    protected function makeArticle($site, $slug, $title, $description)
    {
        Entry::make()
            ->collection('articles')
            ->blueprint('articles')
            ->locale($site)
            ->slug($slug)
            ->date('2024-01-01')
            ->set('title', $title)
            ->set('seo', ['title' => $title, 'description' => $description])
            ->save();

        return $this;
    }

    protected function reportsPath($path = null)
    {
        if ($path) {
            $path = Str::ensureLeft($path, '/');
        }

        return storage_path('statamic/seopro/reports').$path;
    }

    protected function getReportResult($key)
    {
        Carbon::setTestNow(now());

        Report::create()->save()->generate();

        return YAML::file($this->reportsPath('1/report.yaml'))->parse()['results'][$key];
    }

    public static function assertEqualsIgnoringLineEndings($needle, $haystack, $message = ''): void
    {
        parent::assertEquals(
            is_string($needle) ? static::normalizeMultilineString($needle) : $needle,
            is_string($haystack) ? static::normalizeMultilineString($haystack) : $haystack,
            $message
        );
    }
}
