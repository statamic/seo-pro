<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Robots\RobotsTxtGenerator;

class GenerateRobotsTxtCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('robots.txt'));

        parent::tearDown();
    }

    #[Test]
    public function it_generates_robots_txt_from_the_command_line()
    {
        $this
            ->artisan('statamic:seo-pro:generate:robots-txt')
            ->expectsOutputToContain('Generated robots.txt.')
            ->expectsOutputToContain(public_path('robots.txt'))
            ->assertSuccessful();

        $this->assertFileExists(public_path('robots.txt'));
        $this->assertStringContainsString(
            'Sitemap: http://cool-runnings.com/sitemap.xml',
            $this->files->get(public_path('robots.txt')),
        );
    }

    #[Test]
    public function it_reports_when_robots_txt_is_already_up_to_date()
    {
        app(RobotsTxtGenerator::class)->generate();

        $this
            ->artisan('statamic:seo-pro:generate:robots-txt')
            ->expectsOutputToContain('robots.txt is already up to date.')
            ->expectsOutputToContain(public_path('robots.txt'))
            ->assertSuccessful();
    }
}
