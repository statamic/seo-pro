<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Fields\Field;
use Statamic\SeoPro\Fieldtypes\RedirectSourceFieldtype;

class RedirectSourceFieldtypeTest extends TestCase
{
    private function fieldtype(): RedirectSourceFieldtype
    {
        return (new RedirectSourceFieldtype)->setField(new Field('source', ['type' => 'redirect_source']));
    }

    #[Test]
    public function it_returns_null_values_as_is()
    {
        $this->assertNull($this->fieldtype()->process(null));
    }

    #[Test]
    public function it_returns_empty_values_as_is()
    {
        $this->assertEmpty($this->fieldtype()->process(''));
    }

    #[Test]
    public function it_keeps_relative_paths_unchanged()
    {
        $this->assertEquals('/about', $this->fieldtype()->process('/about'));
    }

    #[Test]
    public function it_strips_the_site_domain_from_pasted_urls()
    {
        $this->assertEquals('/about', $this->fieldtype()->process('http://cool-runnings.com/about'));
    }

    #[Test]
    public function it_strips_the_site_domain_with_https()
    {
        $this->assertEquals('/about', $this->fieldtype()->process('https://cool-runnings.com/about'));
    }

    #[Test]
    public function it_prepends_slash_if_missing()
    {
        $this->assertEquals('/about', $this->fieldtype()->process('about'));
    }

    #[Test]
    public function it_handles_root_path()
    {
        $this->assertEquals('/', $this->fieldtype()->process('/'));
    }

    #[Test]
    public function it_handles_paths_with_wildcards()
    {
        $this->assertEquals('/blog/*', $this->fieldtype()->process('/blog/*'));
    }

    #[Test]
    public function it_does_not_strip_unrelated_domains()
    {
        $this->assertEquals('/https://other-site.com/about', $this->fieldtype()->process('https://other-site.com/about'));
    }

    #[Test]
    public function it_preloads_site_url()
    {
        $preloaded = $this->fieldtype()->preload();

        $this->assertArrayHasKey('site_url', $preloaded);
        $this->assertEquals('http://cool-runnings.com', $preloaded['site_url']);
    }
}
