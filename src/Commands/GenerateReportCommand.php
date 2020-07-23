<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Reporting\Report;

class GenerateReportCommand extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seopro:generate-report {--report= : The ID of a report to generate. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an SEO report.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Generating...');

        $id = $this->option('report');

        $report = Report::create($id)->generate();

        $this->info("Report <comment>[{$report->id()}]</comment> generated.");
    }
}
