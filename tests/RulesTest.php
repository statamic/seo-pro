<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\Rules\AuthorMetadata;
use Statamic\SeoPro\Reporting\Rules\OpenGraphMetadata;
use Statamic\SeoPro\Reporting\Rules\PublishedDate;
use Statamic\SeoPro\Reporting\Rules\TwitterCardMetadata;
use Statamic\SeoPro\SiteDefaults;

class RulesTest extends TestCase
{
    protected function createPageWithData($data, $entryData = [])
    {
        $report = Report::create();
        
        // Create an entry if we need specific entry data
        if (!empty($entryData)) {
            $entry = Entry::make()
                ->collection('articles') 
                ->slug('test-entry')
                ->data(array_merge(['title' => 'Test Entry'], $entryData));
                
            if (isset($entryData['date'])) {
                $entry->date($entryData['date']);
            }
            
            $entry->save();
            
            $cascade = (new Cascade)
                ->with(SiteDefaults::load()->all())
                ->withCurrent($entry)
                ->with($data);
        } else {
            // Just use cascade data without an entry
            $defaults = [
                'title' => 'Test Page',
                'description' => null,
                'canonical_url' => 'http://test.com/page',
            ];
            $mergedData = array_merge($defaults, $data);
            $cascade = (new Cascade)->with(SiteDefaults::load()->all())->with($mergedData);
        }
        
        $cascadeData = $cascade->get();
        return new Page('test-id', $cascadeData, $report);
    }

    /** @test */
    public function published_date_rule_passes_when_date_exists()
    {
        $page = $this->createPageWithData([], ['date' => '2023-05-15']);
        
        $rule = new PublishedDate();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function published_date_rule_fails_when_date_is_missing()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new PublishedDate();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('fail', $rule->pageStatus());
    }

    /** @test */
    public function author_metadata_rule_passes_when_author_exists()
    {
        $page = $this->createPageWithData([], ['author' => 'John Doe']);
        
        $rule = new AuthorMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function author_metadata_rule_warns_when_author_is_missing()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new AuthorMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('warning', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_with_complete_data()
    {
        $page = $this->createPageWithData([
            'og_type' => 'article',
            'og_image' => 'image.jpg',
            'og_description' => 'Description',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_fails_without_critical_data()
    {
        $page = $this->createPageWithData([
            'og_description' => 'Description',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('fail', $rule->pageStatus());
    }

    /** @test */
    public function open_graph_metadata_rule_passes_with_image()
    {
        $page = $this->createPageWithData([
            'og_type' => 'article',
            'og_image' => 'image.jpg',
        ]);
        
        $rule = new OpenGraphMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_warns_without_twitter_image()
    {
        $page = $this->createPageWithData([]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('warning', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_passes_with_complete_twitter_data()
    {
        $page = $this->createPageWithData([
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Title',
            'twitter_description' => 'Description',
            'twitter_image' => 'image.jpg',
        ]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('pass', $rule->pageStatus());
    }

    /** @test */
    public function twitter_card_metadata_rule_warns_with_partial_twitter_data()
    {
        $page = $this->createPageWithData([
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Title',
        ]);
        
        $rule = new TwitterCardMetadata();
        $rule->setReport(Report::create());
        $rule->setPage($page);
        
        $this->assertEquals('warning', $rule->pageStatus());
    }

    /** @test */
    public function published_date_rule_processes_correctly()
    {
        // Generate articles instead of entries since they support dates
        collect(range(1, 3))->each(function ($i) {
            Entry::make()
                ->collection('articles')
                ->slug('test-article-'.$i)
                ->set('title', 'Test Article '.$i)
                ->date($i <= 2 ? '2023-05-15' : null)
                ->save();
        });
        
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('PublishedDate');
        // Some entries don't have dates, so it should fail
        $this->assertEquals(1, $result); // 1 entry missing date
    }

    /** @test */
    public function author_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        Entry::all()
            ->take(2)
            ->each(fn ($entry) => $entry->data(['author' => 'Test Author'])->save());
        
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('AuthorMetadata');
        $this->assertEquals(1, $result); // 1 entry missing author
    }

    /** @test */
    public function open_graph_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        Entry::all()
            ->take(1)
            ->each(fn ($entry) => $entry->data([
                'seo' => [
                    'og_type' => 'article',
                    'og_image' => 'image.jpg',
                    'og_description' => 'Description',
                ]
            ])->save());
        
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('OpenGraphMetadata');
        $this->assertEquals(2, $result); // 2 entries missing og_image
    }

    /** @test */
    public function twitter_card_metadata_rule_processes_correctly()
    {
        $this->generateEntries(3);
        
        Entry::all()
            ->take(1)
            ->each(fn ($entry) => $entry->data([
                'seo' => [
                    'twitter_card' => 'summary_large_image',
                    'twitter_title' => 'Title',
                    'twitter_image' => 'twitter.jpg',
                ]
            ])->save());
        
        Report::create()->save()->generate();
        
        $result = $this->getReportResult('TwitterCardMetadata');
        $this->assertEquals(2, $result); // 2 entries missing twitter_image
    }

    protected function generateEntries($count)
    {
        collect(range(1, $count))->each(function ($i) {
            Entry::make()
                ->collection('articles')
                ->blueprint('article')
                ->slug('test-entry-'.$i)
                ->set('title', 'Test Entry '.$i)
                ->save();
        });

        return $this;
    }

    protected function getReportResult($key)
    {
        Report::create()->save()->generate();
        
        return \Statamic\Facades\YAML::file(storage_path('statamic/seopro/reports/1/report.yaml'))->parse()['results'][$key];
    }
}