<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Redirects\WildcardUrlMatcher;
use Tests\TestCase;

class WildcardUrlMatcherTest extends TestCase
{
    #[Test]
    public function it_matches_a_single_wildcard()
    {
        $captures = WildcardUrlMatcher::match('/blog/*', '/blog/hello');

        $this->assertNotNull($captures);
        $this->assertEquals(['hello'], $captures);
    }

    #[Test]
    public function it_matches_multiple_wildcards()
    {
        $captures = WildcardUrlMatcher::match('/blog/*/posts/*', '/blog/2026/posts/hello-world');

        $this->assertNotNull($captures);
        $this->assertEquals(['2026', 'hello-world'], $captures);
    }

    #[Test]
    public function it_does_not_match_wildcards_across_slashes()
    {
        $this->assertNull(WildcardUrlMatcher::match('/blog/*', '/blog/2026/01/my-post'));
    }

    #[Test]
    public function it_returns_null_for_non_matching_paths()
    {
        $this->assertNull(WildcardUrlMatcher::match('/blog/*', '/articles/hello'));
    }

    #[Test]
    public function it_does_not_match_when_wildcard_has_nothing_to_capture()
    {
        $this->assertNull(WildcardUrlMatcher::match('/blog/*', '/blog'));
        $this->assertNull(WildcardUrlMatcher::match('/blog/*', '/blog/'));
    }

    #[Test]
    public function it_escapes_special_regex_characters_in_pattern()
    {
        $captures = WildcardUrlMatcher::match('/path/to/file.html/*', '/path/to/file.html/page');

        $this->assertNotNull($captures);
        $this->assertEquals(['page'], $captures);
    }

    #[Test]
    public function it_resolves_destination_with_single_capture()
    {
        $result = WildcardUrlMatcher::resolveDestination('/articles/$1', ['hello']);

        $this->assertEquals('/articles/hello', $result);
    }

    #[Test]
    public function it_resolves_destination_with_multiple_captures()
    {
        $result = WildcardUrlMatcher::resolveDestination('/articles/$1/entries/$2', ['2026', 'hello-world']);

        $this->assertEquals('/articles/2026/entries/hello-world', $result);
    }

    #[Test]
    public function it_leaves_unmatched_placeholders_intact()
    {
        $result = WildcardUrlMatcher::resolveDestination('/articles/$1/$2', ['hello']);

        $this->assertEquals('/articles/hello/$2', $result);
    }

    #[Test]
    public function it_counts_wildcards()
    {
        $this->assertEquals(0, WildcardUrlMatcher::wildcardCount('/exact/path'));
        $this->assertEquals(1, WildcardUrlMatcher::wildcardCount('/blog/*'));
        $this->assertEquals(2, WildcardUrlMatcher::wildcardCount('/blog/*/posts/*'));
        $this->assertEquals(3, WildcardUrlMatcher::wildcardCount('/*/*/*.html'));
    }
}
