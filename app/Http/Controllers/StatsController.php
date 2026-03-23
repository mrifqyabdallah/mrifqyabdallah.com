<?php

namespace App\Http\Controllers;

use App\Exceptions\BlogNotFoundException;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $blog = Blog::where('slug', $slug)->firstOr(function () {
            throw new BlogNotFoundException;
        });

        return Inertia::render('stats/post', [
            'blog' => $blog->only('id', 'title', 'slug'),
            'stats' => $this->readJson("stats/blogpost/{$blog->id}.json"),
        ]);
    }

    public function opcache(Request $request): Response
    {
        Gate::authorize('viewOpcache', 'stats');

        $includeScripts = $request->boolean('include_scripts', false);
        $data = opcache_get_status($includeScripts);

        if (is_array($data)) {
            array_walk_recursive($data, function (&$value) {
                if (is_float($value) && (is_nan($value) || is_infinite($value))) {
                    $value = null;
                }
            });
        }

        return Inertia::render('stats/opcache', [
            'data' => $data,
            'includeScripts' => $includeScripts,
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
