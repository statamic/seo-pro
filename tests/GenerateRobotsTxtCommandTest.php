<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;

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
}
