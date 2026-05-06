<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Error;
use Statamic\SeoPro\Redirects\Stache\ErrorRepository;
use Statamic\Support\Str;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ErrorRepositoryTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(ErrorRepository::class);
    }

    #[Test]
    public function can_find_error()
    {
        Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(5)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $error = $this->repo->find('abc');

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals('abc', $error->id());
        $this->assertEquals('/broken-link', $error->url());
        $this->assertEquals(5, $error->hits());
        $this->assertEquals('2026-04-21 12:00:00', $error->lastHitAt());
    }

    #[Test]
    public function can_save_error()
    {
        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(3)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertStringContainsString('errors/abc.yaml', $error->path());

        $yaml = YAML::file($error->path())->parse();

        $this->assertEquals('/broken-link', $yaml['url']);
        $this->assertEquals(3, $yaml['hits']);
        $this->assertEquals('2026-04-21 12:00:00', $yaml['last_hit_at']);
    }

    #[Test]
    public function it_generates_id_from_url_when_saving_without_id()
    {
        $error = Facades\Error::make()
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertEquals('broken-link', $error->id());
    }

    #[Test]
    public function it_generates_a_uuid_id_when_url_cannot_be_slugged()
    {
        $error = Facades\Error::make()
            ->url('/)')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertTrue(Str::isUuid($error->id()));
        $this->assertStringContainsString('errors/'.$error->id().'.yaml', $error->path());
    }

    #[Test]
    public function it_appends_suffix_when_generated_id_already_exists()
    {
        Facades\Error::make()
            ->id('broken-link')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $error = Facades\Error::make()
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 13:00:00');

        $this->repo->save($error);

        $this->assertEquals('broken-link-1', $error->id());
    }

    #[Test]
    public function it_increments_suffix_when_multiple_duplicates_exist()
    {
        Facades\Error::make()
            ->id('broken-link')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('broken-link-1')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 13:00:00')
            ->save();

        $error = Facades\Error::make()
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 14:00:00');

        $this->repo->save($error);

        $this->assertEquals('broken-link-2', $error->id());
    }

    #[Test]
    public function it_does_not_change_existing_id_when_saving()
    {
        $error = Facades\Error::make()
            ->id('my-custom-id')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertEquals('my-custom-id', $error->id());
    }

    #[Test]
    public function can_delete_error()
    {
        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $error->save();

        $this->assertFileExists($error->path());

        $this->repo->delete($error);

        $this->assertFileDoesNotExist($error->path());
    }
}
