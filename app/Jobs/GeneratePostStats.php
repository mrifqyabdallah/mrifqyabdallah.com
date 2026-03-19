<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Queries\PostViewStatsQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JsonException;

final class GeneratePostStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $blogId,
    ) {}

    /**
     * @throws JsonException
     */
    public function handle(): void
    {
        $blog = Blog::select(['id', 'title', 'slug'])->find($this->blogId);

        if (! $blog) {
            return;
        }

        $stats = (new PostViewStatsQuery(blogId: $blog, now: now()))->get();

        Storage::disk('public')->put(
            "stats/blogpost/{$this->blogId}.json",
            json_encode($stats->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }
}
