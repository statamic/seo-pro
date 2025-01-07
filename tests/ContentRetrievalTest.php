<?php

namespace Tests;

use Statamic\SeoPro\Content\ContentMapper;
use Statamic\SeoPro\Content\ContentRetriever;

class ContentRetrievalTest extends TestCase
{
    private function makeContentRetriever(): ContentRetriever
    {
        return new ContentRetriever(new ContentMapper);
    }

    /** @test */
    public function it_extracts_content_from_statamic_comments()
    {
        $retriever = $this->makeContentRetriever();

        $this->assertSame('', $retriever->getContentFromString(''));

        $this->assertSame('', $retriever->getContentFromString('<!--statamic:content-->'));
        $this->assertSame('', $retriever->getContentFromString('<!--/statamic:content-->'));

        $this->assertSame(
            'The Content',
            $retriever->getContentFromString('<!--statamic:content-->The Content<!--/statamic:content-->')
        );

        $this->assertSame(
            'The Content',
            $retriever->getContentFromString('<!--statamic:content-->The <!--/statamic:content--><!--statamic:content-->Content<!--/statamic:content-->')
        );
    }

    /** @test */
    public function it_extracts_content_from_article_tags()
    {
        $retriever = $this->makeContentRetriever();

        $this->assertSame(
            '<article>Test</article>',
            $retriever->getContentFromString('Leading <article>Test</article> Trailing')
        );

        $this->assertSame(
            '<article>Test</article><article>More Content</article>',
            $retriever->getContentFromString('Leading <article>Test</article> Trailing <article>More Content</article>')
        );
    }
}
