<?php

namespace Statamic\Addons\SeoPro\Commands;

use Statamic\Extend\Command;
use Statamic\Addons\SeoPro\Reporting\Report;

class GenerateReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seopro:report:generate {--report= : The ID of a report to generate. }';

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
