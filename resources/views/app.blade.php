<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $viteHotPath = public_path('hot');
        $viteManifestPath = public_path('build/manifest.json');
        $hasViteAssets = file_exists($viteHotPath) || file_exists($viteManifestPath);
        $showLocalViteHint = ! $hasViteAssets && app()->hasDebugModeEnabled();
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <x-site.favicons />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <x-analytics.google-tag />

        <!-- Scripts -->
        @routes('app')
        @if ($hasViteAssets)
            @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
            @inertiaHead
        @elseif ($showLocalViteHint)
            <style>
                :root {
                    color-scheme: light;
                    font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
                }

                body {
                    margin: 0;
                    background: linear-gradient(180deg, #fff8f1 0%, #f7f2eb 100%);
                    color: #17161b;
                }

                .vite-warning {
                    min-height: 100vh;
                    display: grid;
                    place-items: center;
                    padding: 24px;
                }

                .vite-warning__card {
                    width: min(100%, 760px);
                    border: 1px solid #eadfce;
                    border-radius: 24px;
                    background: rgba(255, 255, 255, 0.96);
                    box-shadow: 0 24px 60px rgba(23, 22, 27, 0.08);
                    padding: 32px;
                }

                .vite-warning__eyebrow {
                    display: inline-flex;
                    align-items: center;
                    border-radius: 999px;
                    background: #fff2e6;
                    color: #a6540a;
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 0.14em;
                    padding: 8px 12px;
                    text-transform: uppercase;
                }

                .vite-warning h1 {
                    margin: 18px 0 0;
                    font-size: clamp(28px, 4vw, 42px);
                    line-height: 1.04;
                    letter-spacing: -0.04em;
                }

                .vite-warning p {
                    margin: 14px 0 0;
                    font-size: 15px;
                    line-height: 1.7;
                    color: #564d42;
                }

                .vite-warning pre {
                    margin: 18px 0 0;
                    padding: 16px 18px;
                    border-radius: 16px;
                    background: #17161b;
                    color: #f7f5f1;
                    overflow-x: auto;
                    font-size: 14px;
                    line-height: 1.6;
                }

                .vite-warning code {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                }
            </style>
        @endif
    </head>
    <body class="font-sans antialiased">
        @if ($hasViteAssets)
            @inertia
            <x-analytics.google-consent-banner />
        @elseif ($showLocalViteHint)
            <main class="vite-warning">
                <section class="vite-warning__card">
                    <span class="vite-warning__eyebrow">Frontend build missing</span>
                    <h1>Brakuje assetow Vite dla tej instancji lokalnej.</h1>
                    <p>
                        Laravel probowal zaladowac <code>public/build/manifest.json</code>, ale ten plik nie istnieje
                        dla aktualnie uruchomionego serwera. Najczesciej wystarczy odbudowac frontend albo uruchomic
                        projekt przez launcher.
                    </p>
                    <pre><code>npm run build
URUCHOM-PROJEKT.cmd</code></pre>
                    <p>
                        Gdy korzystasz z <code>php artisan serve</code>, uruchom najpierw build frontendu.
                        Gdy korzystasz z Dockera, launcher zrobi to za Ciebie.
                    </p>
                </section>
            </main>
            <x-analytics.google-consent-banner />
        @endif
    </body>
</html>
