<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Robots\RobotsPolicy;
use Statamic\SeoPro\Robots\RobotsTxtGenerator;

class RobotsTest extends LocalizedTestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('robots.txt'));

        parent::tearDown();
    }

    #[Test]
    public function all_sites_share_one_policy_and_each_authority_gets_a_sitemap_line()
    {
        $content = app(RobotsTxtGenerator::class)->generate(new RobotsPolicy([
            'disallow' => ['/private/'],
        ]))['contents'];

        $this->assertStringContainsString('Disallow: /private/', $content);
        $this->assertSame(1, substr_count($content, 'Sitemap: http://cool-runnings.com/sitemap.xml'));
        $this->assertSame(1, substr_count($content, 'Sitemap: http://corse-fantastiche.it/sitemap.xml'));
    }
}
