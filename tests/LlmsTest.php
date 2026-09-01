<?php

namespace Tests;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\AddonSettingsSaved;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\SeoPro\Llms\LlmsRenderCache;
use Statamic\SeoPro\Llms\LlmsRenderer;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;

class LlmsTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('llms.txt'));

        parent::tearDown();
    }

    #[Test]
    public function the_frontend_route_is_opt_in()
    {
        $this->get('/llms.txt')->assertNotFound();
        $this->assertFalse(Llms::get()->enabled());
    }

    #[Test]
    public function it_renders_managed_content_and_parses_antlers()
    {
        config(['app.name' => 'Cool Runnings']);
        $this->saveEnabled([
            'title' => '{{ config:app:name }}',
            'summary' => 'The fastest team on ice.',
            'details' => "A site about bobsledding.\nBuilt in Jamaica.",
            'sections' => [[
                'title' => 'Documentation',
                'links' => [[
                    'title' => 'About {{ config:app:name }}',
                    'url' => 'https://example.com/about',
                    'description' => 'Learn more about the team.',
                ]],
            ]],
        ]);

        $response = $this->get('/llms.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=UTF-8')
            ->assertHeader('Cache-Control', 'public');

        $this->assertEqualsIgnoringLineEndings(<<<'TXT'
# Cool Runnings

> The fastest team on ice.

A site about bobsledding.
Built in Jamaica.

## Documentation

- [About Cool Runnings](https://example.com/about): Learn more about the team.
TXT."\n", $response->content());
    }

    #[Test]
    public function it_renders_custom_source_after_parsing_antlers()
    {
        config(['app.name' => 'Cool Runnings']);
        $this->saveEnabled([
            'mode' => 'custom',
            'custom_source' => "# {{ config:app:name }}\r\n\r\nCustom content.\r\n",
        ]);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertContent("# Cool Runnings\n\nCustom content.\n");
    }

    #[Test]
    #[DataProvider('additionalH1Provider')]
    public function it_rejects_documents_with_more_than_one_markdown_h1(string $source)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain exactly one Markdown H1 heading');

        app(LlmsRenderer::class)->render(new LlmsDocument([
            'enabled' => true,
            'mode' => 'custom',
            'custom_source' => $source,
        ]));
    }

    public static function additionalH1Provider(): array
    {
        return [
            'ATX heading' => ["# First\n\n# Second"],
            'Setext heading' => ["# First\n\nSecond\n======"],
            'heading in a blockquote' => ["# First\n\n> # Second"],
        ];
    }

    #[Test]
    public function h1_syntax_inside_a_fenced_code_block_is_not_counted_as_a_heading()
    {
        $source = "# First\n\n```md\n# Example code\n```";

        $this->assertSame(
            $source."\n",
            app(LlmsRenderer::class)->render(new LlmsDocument([
                'enabled' => true,
                'mode' => 'custom',
                'custom_source' => $source,
            ])),
        );
    }

    #[Test]
    public function it_returns_a_conditional_response_using_an_etag()
    {
        $this->saveEnabled(['title' => 'Cool Runnings']);
        $etag = $this->get('/llms.txt')->assertOk()->headers->get('ETag');

        $this->withHeader('If-None-Match', $etag)
            ->get('/llms.txt')
            ->assertStatus(304)
            ->assertContent('');
    }

    #[Test]
    public function rendered_content_is_cached_until_its_fingerprint_changes()
    {
        $renderer = new class extends LlmsRenderer
        {
            public int $renders = 0;

            public function render(LlmsDocument|array $document, string|\Statamic\Sites\Site|null $site = null): string
            {
                $this->renders++;

                return parent::render($document, $site);
            }
        };
        $cache = new LlmsRenderCache(new Repository(new ArrayStore), $renderer);
        $document = new LlmsDocument(['enabled' => true, 'title' => 'First']);

        $this->assertSame("# First\n", $cache->get($document));
        $this->assertSame("# First\n", $cache->get($document));
        $this->assertSame(1, $renderer->renders);

        $this->assertSame("# Second\n", $cache->get(new LlmsDocument([
            'enabled' => true,
            'title' => 'Second',
        ])));
        $this->assertSame(2, $renderer->renders);
    }

    #[Test]
    public function saving_does_not_create_a_physical_file_until_generation_is_requested()
    {
        $document = new LlmsDocument(['enabled' => true, 'title' => 'Cool Runnings']);
        $result = app(LlmsTxtGenerator::class)->sync($document);

        $this->assertFalse($result['changed']);
        $this->assertFileDoesNotExist(public_path('llms.txt'));
        $this->get('/llms.txt')->assertOk()->assertContent("# Cool Runnings\n");
    }

    #[Test]
    public function a_generated_file_is_kept_in_sync_and_removed_when_disabled()
    {
        $generator = app(LlmsTxtGenerator::class);
        $first = new LlmsDocument(['enabled' => true, 'title' => 'First']);
        $generator->generate($first);

        $this->assertSame("# First\n", $this->files->get(public_path('llms.txt')));
        $this->assertTrue($generator->status()['managed']);

        $generator->sync(new LlmsDocument(['enabled' => true, 'title' => 'Second']));
        $this->assertSame("# Second\n", $this->files->get(public_path('llms.txt')));

        $result = $generator->sync(new LlmsDocument(['enabled' => false, 'title' => 'Second']));
        $this->assertTrue($result['removed']);
        $this->assertFileDoesNotExist(public_path('llms.txt'));
        $this->assertNull(Llms::generated());
    }

    #[Test]
    public function an_unmanaged_file_is_never_overwritten_or_removed()
    {
        $generator = app(LlmsTxtGenerator::class);
        $legacy = "# Manually managed\n";
        $this->files->put(public_path('llms.txt'), $legacy);
        $document = new LlmsDocument(['enabled' => true, 'title' => 'SEO Pro']);

        $generator->sync($document);
        $this->assertSame($legacy, $this->files->get(public_path('llms.txt')));
        $this->assertFalse($generator->status()['managed']);

        try {
            $generator->generate($document);
            $this->fail('Generation should refuse an unmanaged file.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('is not managed by SEO Pro', $exception->getMessage());
        }

        $generator->sync(new LlmsDocument(['enabled' => false, 'title' => 'SEO Pro']));
        $this->assertSame($legacy, $this->files->get(public_path('llms.txt')));
    }

    #[Test]
    public function manually_changing_a_generated_file_relinquishes_ownership()
    {
        $generator = app(LlmsTxtGenerator::class);
        $generator->generate(new LlmsDocument(['enabled' => true, 'title' => 'First']));
        $this->files->put(public_path('llms.txt'), "# Changed manually\n");

        $generator->sync(new LlmsDocument(['enabled' => true, 'title' => 'Second']));

        $this->assertSame("# Changed manually\n", $this->files->get(public_path('llms.txt')));
        $this->assertNull(Addon::get('statamic/seo-pro')->settings()->get('llms.sites.default.generated'));
        $this->assertFalse($generator->status()['managed']);
    }

    #[Test]
    public function a_failed_settings_save_restores_the_managed_file_and_settings()
    {
        $generator = app(LlmsTxtGenerator::class);
        $generator->generate(new LlmsDocument(['enabled' => true, 'title' => 'First']));
        $previousContents = $this->files->get(public_path('llms.txt'));
        $previousSettings = Llms::settingsSnapshot();

        Event::listen(AddonSettingsSaved::class, function () {
            throw new \RuntimeException('The settings listener failed.');
        });

        try {
            $generator->sync(new LlmsDocument(['enabled' => true, 'title' => 'Second']));
            $this->fail('Synchronization should have failed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('The settings listener failed.', $exception->getMessage());
        }

        $this->assertSame($previousContents, $this->files->get(public_path('llms.txt')));
        $this->assertSame($previousSettings, Llms::settingsSnapshot());
        $this->assertTrue($generator->status()['managed']);
        $this->assertSame([], glob(public_path('.seo-pro-llms-*')));
    }

    #[Test]
    public function enabled_llms_txt_is_exposed_to_the_meta_cascade()
    {
        $this->saveEnabled(['title' => 'Cool Runnings']);

        $this->assertSame('http://cool-runnings.com/llms.txt', (new Cascade)->get()['llms_txt']);
    }

    private function saveEnabled(array $overrides = []): void
    {
        Llms::saveWithoutGenerated(new LlmsDocument(array_replace([
            'enabled' => true,
            'title' => 'Cool Runnings',
        ], $overrides)), Site::default());
    }
}
