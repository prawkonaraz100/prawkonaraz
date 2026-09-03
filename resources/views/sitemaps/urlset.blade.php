@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
@if ($includeImages)
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
@endif
>
@foreach ($urls as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
@if (! empty($item['lastmod']))
        <lastmod>{{ $item['lastmod'] }}</lastmod>
@endif
@if ($includeImages)
    @foreach ($item['images'] as $image)
        <image:image>
            <image:loc>{{ $image }}</image:loc>
        </image:image>
    @endforeach
@endif
    </url>
@endforeach
</urlset>
