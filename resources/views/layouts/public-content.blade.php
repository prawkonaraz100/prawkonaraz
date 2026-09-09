@php
    $meta = $meta ?? [];
    $title = $meta['title'] ?? config('app.name', 'prawkonaraz.pl');
    $description = $meta['description'] ?? null;
    $canonical = $meta['canonical'] ?? url()->current();
    $image = $meta['image'] ?? null;
    $imageAlt = $meta['image_alt'] ?? null;
    $imageWidth = $meta['image_width'] ?? null;
    $imageHeight = $meta['image_height'] ?? null;
    $preloadImage = $meta['preload_image'] ?? null;
    $ogType = $meta['og_type'] ?? 'website';
    $robots = $meta['robots'] ?? 'index,follow,max-image-preview:large';
    $authorName = $meta['author_name'] ?? null;
    $publishedTime = $meta['published_time'] ?? null;
    $modifiedTime = $meta['modified_time'] ?? null;
    $breadcrumbs = $breadcrumbs ?? [];
    $structuredData = $structuredData ?? [];
    $structuredDataScripts = [];
    if (is_array($structuredData) && $structuredData !== []) {
        $isSingleStructuredDataPayload = array_key_exists('@context', $structuredData)
            || array_key_exists('@graph', $structuredData)
            || array_key_exists('@type', $structuredData);

        $structuredDataScripts = $isSingleStructuredDataPayload
            ? [$structuredData]
            : array_values(array_filter($structuredData, fn ($schema) => is_array($schema) && $schema !== []));
    }
    $viteHotPath = public_path('hot');
    $viteManifestPath = public_path('build/manifest.json');
    $hasViteAssets = file_exists($viteHotPath) || file_exists($viteManifestPath);
@endphp
<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        <x-site.favicons />
        <meta name="robots" content="{{ $robots }}">
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        @if ($authorName)
            <meta name="author" content="{{ $authorName }}">
        @endif
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:locale" content="pl_PL">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ $canonical }}">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
            @if ($imageAlt)
                <meta property="og:image:alt" content="{{ $imageAlt }}">
            @endif
            @if ($imageWidth)
                <meta property="og:image:width" content="{{ $imageWidth }}">
            @endif
            @if ($imageHeight)
                <meta property="og:image:height" content="{{ $imageHeight }}">
            @endif
        @endif
        @if ($publishedTime)
            <meta property="article:published_time" content="{{ $publishedTime }}">
        @endif
        @if ($modifiedTime)
            <meta property="article:modified_time" content="{{ $modifiedTime }}">
        @endif
        <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $title }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        @if ($image)
            <meta name="twitter:image" content="{{ $image }}">
            @if ($imageAlt)
                <meta name="twitter:image:alt" content="{{ $imageAlt }}">
            @endif
        @endif
        @if ($preloadImage)
            <link rel="preload" href="{{ $preloadImage }}" as="image">
        @endif
        <x-analytics.google-tag />
        @if ($hasViteAssets)
            @vite(['resources/js/public-content.ts'])
        @endif
        <style>
            :root {
                --site-shell-max: 1440px;
                --site-shell-padding: 16px;
            }

            .site-shell,
            .content-shell {
                width: 100%;
                max-width: var(--site-shell-max);
                margin: 0 auto;
                padding-left: var(--site-shell-padding);
                padding-right: var(--site-shell-padding);
            }

            .content-band {
                background: #ffffff;
            }

            .content-muted {
                color: #4b5563;
            }

            .content-kicker {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #0d47a1;
            }

            .content-prose p + p {
                margin-top: 1rem;
            }

            .content-hero-section {
                padding-top: 2.25rem;
                padding-bottom: 2.25rem;
            }

            .content-section-spacious {
                padding-top: 1.75rem;
                padding-bottom: 2rem;
            }

            .content-section-gentle-top {
                padding-top: 3.25rem;
                padding-bottom: 2rem;
            }

            @media (min-width: 768px) {
                .content-hero-section {
                    padding-top: 2.75rem;
                    padding-bottom: 2.75rem;
                }

                .content-section-spacious {
                    padding-top: 2.25rem;
                    padding-bottom: 2.25rem;
                }

                .content-section-gentle-top {
                    padding-top: 3.75rem;
                    padding-bottom: 2.25rem;
                }
            }

            @media (min-width: 640px) {
                :root {
                    --site-shell-padding: 24px;
                }
            }

            @media (min-width: 1280px) {
                :root {
                    --site-shell-padding: 32px;
                }
            }
        </style>
        @foreach ($structuredDataScripts as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endforeach
    </head>
    <body class="flex min-h-screen flex-col bg-white text-slate-950">
        @hasSection('site_header')
            @yield('site_header')
        @else
            <x-site.home-header :immediate="true" />
        @endif

        <main class="flex-1">
            @if ($breadcrumbs !== [])
                @php
                    $breadcrumbBandClass = trim($__env->yieldContent('breadcrumb_band_class')) ?: 'content-band';
                    $breadcrumbShellClass = trim($__env->yieldContent('breadcrumb_shell_class')) ?: 'content-shell';
                    $breadcrumbNavClass = trim($__env->yieldContent('breadcrumb_nav_class'));
                    $breadcrumbListClass = trim($__env->yieldContent('breadcrumb_list_class'));
                @endphp
                <div class="{{ $breadcrumbBandClass }}">
                    <div class="{{ $breadcrumbShellClass }}" style="padding-top: 8px; padding-bottom: 10px;">
                        <x-seo.breadcrumbs
                            :items="$breadcrumbs"
                            :nav-class="$breadcrumbNavClass"
                            :list-class="$breadcrumbListClass"
                        />
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <x-site.public-footer />
        @guest
            <x-site.auth-drawer-root
                :lazy="(bool) config('performance.public_auth_drawers.lazy', false)"
            />
        @endguest
        <x-analytics.google-consent-banner />
        @stack('scripts')
    </body>
</html>
