<?php

namespace App\Http\Controllers;

use App\Exceptions\BlogNotFoundException;
use App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

final class StatsController extends Controller
{
    public function blog(): Response
    {
        return Inertia::render('stats/blog', [
            'stats' => $this->readJson('stats/blog/overview.json'),
        ]);
    }

    public function post(string $slug): Response
    {
        $blog = Blog::where('slug', $slug)->firstOr(function() {
            throw new BlogNotFoundException();
        });

        return Inertia::render('stats/post', [
            'blog'  => $blog->only('id', 'title', 'slug'),
            'stats' => $this->readJson("stats/blogpost/{$blog->id}.json"),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        if ($contents === null) {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException) {
            return null;
        }
    }
}
