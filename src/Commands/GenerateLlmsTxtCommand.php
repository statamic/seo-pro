<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Site;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;
use Throwable;

class GenerateLlmsTxtCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:generate:llms-txt
        {--site=* : Generate only the specified Statamic site handle(s)}';

    protected $description = 'Generate physical llms.txt files from the SEO Pro settings.';

    public function handle(LlmsTxtGenerator $generator): int
    {
        $requested = collect($this->option('site'))->filter();

        if ($unknown = $requested->diff(Site::all()->keys())->first()) {
            $this->components->error("Unknown Statamic site [{$unknown}].");

            return self::FAILURE;
        }

        $sites = ($requested->isEmpty()
            ? Site::all()
            : $requested->map(fn (string $handle) => Site::get($handle)))
            ->filter(fn ($site) => Llms::get($site)->enabled())
            ->values();

        if ($sites->isEmpty()) {
            $this->components->info('llms.txt is not enabled for any selected site.');

            return self::SUCCESS;
        }

        if ($collision = $sites->groupBy(fn ($site) => $generator->path($site))->first(fn ($group) => $group->count() > 1)) {
            $handles = $collision->map->handle()->implode(', ');
            $this->components->error("The selected sites [{$handles}] resolve to the same physical llms.txt path. Generate one site per deployment with --site.");

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            try {
                $result = $generator->generate(null, $site);
            } catch (Throwable $exception) {
                report($exception);
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->components->success($result['changed']
                ? "Generated llms.txt for [{$site->handle()}]."
                : "llms.txt for [{$site->handle()}] is already up to date.");
            $this->line("Path: <comment>{$result['path']}</comment>");
            $this->line("Generated: <comment>{$result['timestamp']}</comment>");
        }

        return self::SUCCESS;
    }
}
