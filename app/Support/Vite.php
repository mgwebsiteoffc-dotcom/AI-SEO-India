<?php

namespace App\Support;

/**
 * Root-relative Vite asset URLs read from public/build/manifest.json.
 *
 * Laravel's @vite() directive generates *absolute* URLs rooted at APP_URL.
 * If APP_URL is stale (e.g. after a sandbox/preview host change or a proxy
 * swap), every stylesheet/script on the page 404s and the site renders as
 * unstyled HTML. Serving assets from root-relative paths keeps the site
 * styled on any host — localhost, preview tunnels and production alike.
 */
class Vite
{
    private static ?array $manifest = null;

    public static function manifest(): array
    {
        if (self::$manifest === null) {
            $path = public_path('build/manifest.json');
            self::$manifest = is_file($path)
                ? (json_decode((string) file_get_contents($path), true) ?: [])
                : [];
        }

        return self::$manifest;
    }

    /** "/build/<file>" for an entry (empty string when not built yet). */
    public static function asset(string $entry): string
    {
        $file = self::manifest()[$entry]['file'] ?? '';

        return $file !== '' ? '/build/'.ltrim((string) $file, '/') : '';
    }

    public static function css(): string
    {
        return self::asset('resources/css/app.css');
    }

    public static function js(): string
    {
        return self::asset('resources/js/app.js');
    }

    /** Extra CSS chunks emitted for a JS entry (empty if none). */
    public static function cssForJs(string $entry): array
    {
        return array_map(
            fn ($href) => '/build/'.ltrim((string) $href, '/'),
            self::manifest()[$entry]['css'] ?? []
        );
    }
}
