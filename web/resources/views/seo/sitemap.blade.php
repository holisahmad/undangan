@php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $home }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ $themes }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    @foreach ($templates as $template)
        <url>
            <loc>{{ route('templates.demo', ['template' => $template->slug]) }}</loc>
            <lastmod>{{ $template->updated_at->toAtomString() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    <url>
        <loc>{{ route('showcase.index') }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @foreach ($showcase as $entry)
        <url>
            <loc>{{ route('showcase.show', ['slug' => $entry->slug]) }}</loc>
            <lastmod>{{ $entry->updated_at->toAtomString() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.5</priority>
        </url>
    @endforeach
    <url>
        <loc>{{ $register }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>{{ $login }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ $privacy }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ $terms }}</loc>
        <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
