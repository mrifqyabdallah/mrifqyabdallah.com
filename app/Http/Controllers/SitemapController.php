<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SitemapController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = public_path('sitemap.xml');

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
