<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Config\EnvWriter;

class SetupLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:links-setup';

    protected $description = 'Assists with the configuration of content linking features.';

    public function handle()
    {
        $this->info('Content Linking Setup');

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

        if (! file_exists(base_path('.env'))) {
            $this->error('The .env file could not be located.');
            return;
        }

        $writer->open(base_path('.env'));

        if ($this->confirm('Would you like to configure your OpenAI API access?', true)) {
            if ($usingOpenAiAlternative = $this->confirm('Will you be using an alternative, OpenAI compatible API?')) {
                if ($baseUri = $this->ask('What is the base URL for the API you will be using?')) {
                    $writer->setValue(
                        'SEO_PRO_OPENAI_BASE_URI',
                        $baseUri,
                        'statamic.seo-pro.linking.openai.base_uri',
                    );
                } else {
                    $this->error('An API base URI is required when using an OpenAI API alternative.');
                    return;
                }
            }

            if ($apiKey = $this->ask('Please enter the API key to make requests')) {
                $writer->setValue(
                    'SEO_PRO_OPENAI_API_KEY',
                    $apiKey,
                    'statamic.seo-pro.linking.openai.api_key'
                );
            } else {
                if (! $usingOpenAiAlternative) {
                    $this->error('An API key is required to interact with the OpenAI API.');
                    return;
                }
            }

            if ($aiModel = $this->ask('What AI model would you like to use?', 'text-embedding-3-small')) {
                $writer->setValue(
                    'SEO_PRO_OPENAI_MODEL',
                    $aiModel,
                    'statamic.seo-pro.linking.openai.model',
                );
            } else {
                $this->error('An AI model name is required.');
                return;
            }
        } else {
            $this->warn('Skipping API setup. Be sure to configure API access before attempting to use content linking features.');
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
