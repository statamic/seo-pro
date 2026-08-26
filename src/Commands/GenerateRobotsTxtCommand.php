<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Robots\RobotsTxtGenerator;
use Throwable;

class GenerateRobotsTxtCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:generate:robots-txt';

    protected $description = 'Generate public/robots.txt from the SEO Pro settings.';

    public function handle(RobotsTxtGenerator $generator): int
    {
        try {
            $result = $generator->generate();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->success('Generated robots.txt.');
        $this->line("Path: <comment>{$result['path']}</comment>");
        $this->line("Generated: <comment>{$result['timestamp']}</comment>");

        return self::SUCCESS;
    }
}
