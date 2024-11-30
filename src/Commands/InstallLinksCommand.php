<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Config\EnvWriter;

class InstallLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:links-install';

    protected $description = 'Assists with installing and configuration content linking features.';

    public function handle()
    {
        $this->info('Linking Installation');

        if (! $this->confirm('Do you have a database connection configured?')) {
            $this->error('Please configure a database in order to use content linking.');
            exit;
        }

        if ($this->confirm('Would you like to publish and run the content linking migrations?', true)) {
            $this->call('vendor:publish', [
                '--tag' => 'seo-pro-migrations',
                '--force' => true,
            ]);
            $this->call('migrate');
        }

        $writer = new EnvWriter;

        if ($this->confirm('Would you like to configure your OpenAI API access?', true)) {
            $writer->open(base_path('.env'));
            if ($this->confirm('Will you be using a third-party, OpenAI compatible API?')) {
                $writer->setValue(
                    'SEO_PRO_OPENAI_BASE_URI',
                    $this->ask('What is the base URL for the API you will be using?'),
                    'statamic.seo-pro.linking.openai.base_uri',
                );
            }

            $writer->setValue(
                'SEO_PRO_OPENAI_API_KEY',
                $this->ask('Please enter the API key to make requests'),
                'statamic.seo-pro.linking.openai.api_key'
            )->setValue(
                'SEO_PRO_OPENAI_MODEL',
                $this->ask('What AI model would you like to use?', 'text-embedding-3-small'),
                'statamic.seo-pro.linking.openai.model',
            );
        }

        if ($this->confirm('Would you like to enable content linking?', true)) {
            $writer->setValue(
                'SEO_PRO_LINKING_ENABLED',
                'true',
                'statamic.seo-pro.linking.enabled',
            );
        }

        $writer->save();
    }
}
