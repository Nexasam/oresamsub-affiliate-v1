<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $affiliate = session('affiliate');
        $name = (string) ($affiliate?->name ?: config('app.name', 'OresamSub'));
        $icon = $affiliate?->logo
            ? url(ltrim($affiliate->logo, '/'))
            : asset('assets/logo_imgs/favicon/android-chrome-512x512.png');
        $extension = strtolower(pathinfo(parse_url($icon, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        $type = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return response()->json([
            'id' => '/?affiliate='.($affiliate?->slug ?: 'default'),
            'name' => $name,
            'short_name' => mb_substr($name, 0, 24),
            'start_url' => '/login',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#2563eb',
            'orientation' => 'portrait',
            'icons' => [[
                'src' => $icon,
                'sizes' => 'any',
                'type' => $type,
                'purpose' => 'any',
            ]],
        ])->withHeaders([
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
