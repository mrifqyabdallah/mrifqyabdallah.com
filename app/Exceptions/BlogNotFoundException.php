<?php

namespace App\Exceptions;

use App\Models\Blog;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class BlogNotFoundException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): Response
    {
        $latest = Blog::published()
            ->latest('published_at')
            ->limit(3)
            ->get(['slug', 'title', 'excerpt', 'published_at']);

        return Inertia::render('blog/not-found', [
            'latest' => $latest,
        ])
            ->toResponse($request)
            ->setStatusCode(404);
    }
}
