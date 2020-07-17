{!! $xmlHeader !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

@foreach ($sitemap->pages() as $page)
    <url>
        <loc>{{ $page->loc() }}</loc>
        <lastmod>{{ $page->lastmod() }}</lastmod>
        <changefreq>{{ $page->changefreq() }}</changefreq>
        <priority>{{ $page->priority() }}</priority>
    </url>
@endforeach

</urlset>
