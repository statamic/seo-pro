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

        $this->components->success($result['changed']
            ? 'Generated robots.txt.'
            : 'robots.txt is already up to date.');
        $this->line("Path: <comment>{$result['path']}</comment>");
        $this->line("Generated: <comment>{$result['timestamp']}</comment>");

        return self::SUCCESS;
    }
}
