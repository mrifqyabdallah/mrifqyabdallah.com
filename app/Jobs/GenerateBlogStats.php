<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Queries\BlogViewStatsQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JsonException;

final class GenerateBlogStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @throws JsonException
     */
    public function handle(): void
    {
        $stats = (new BlogViewStatsQuery(now: now()))->get();

        Storage::disk('public')->put(
            'stats/blog/overview.json',
            json_encode($stats->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }
}
