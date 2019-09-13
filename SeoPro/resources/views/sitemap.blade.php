{!! $xmlHeader !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">

@foreach ($sitemap->pages() as $page)
    <url>
        <loc>{{ $page->loc() }}</loc>
        <lastmod>{{ $page->lastmod() }}</lastmod>
        <changefreq>{{ $page->changefreq() }}</changefreq>
        <priority>{{ $page->priority() }}</priority>
@if (count($page->alternateLocales()))
        <xhtml:link rel="alternate" hreflang="{{ $page->locale() }}" href="{{ $page->loc() }}"/>
@foreach ($page->alternateLocales() as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate['locale'] }}" href="{{ $alternate['url'] }}"/>
@endforeach
@endif
    </url>
@endforeach

</urlset>
