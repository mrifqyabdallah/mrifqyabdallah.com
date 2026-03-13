<?php

namespace App\Console\Commands;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Services\BlogSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BlogSync extends Command
{
    protected $signature = 'blogs:sync';

    protected $description = 'Sync markdown files in resources/blogs/ to the database';

    public function __construct(
        private readonly BlogSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $blogsPath = resource_path('blogs');

        if (! is_dir($blogsPath)) {
            $this->error("Directory not found: {$blogsPath}");

            return self::FAILURE;
        }

        $files = glob("{$blogsPath}/*.md") ?: [];

        if (empty($files)) {
            $this->warn('No markdown files found in resources/blogs/');
        }

        $scannedFilenames = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $contents = file_get_contents($filePath);

            if (! $contents) {
                $this->warn("  ⚠ Skipped (failed to get file): {$filename}");
                $skipped++;

                continue;
            }

            $data = $this->syncService->parseFile($filename, $contents);

            if (! $data) {
                $this->warn("  ⚠ Skipped (invalid format): {$filename}");
                $skipped++;

                continue;
            }

            $scannedFilenames[] = $filename;

            $existing = Blog::where('source_file', $filename)->first();

            DB::transaction(function () use ($data, $existing, &$created, &$updated) {
                /** @var array<string, mixed> $blogData */
                $blogData = array_merge($data, ['status' => BlogStatus::Published]);

                Blog::updateOrCreate(
                    ['source_file' => $data['source_file']],
                    $blogData
                );

                $this->line(sprintf("  ✓ %s: %s", 
                    $existing ? 'Updated' : 'Created',
                    $data['source_file'],
                ));

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            });
        }

        // Archive any DB records whose files no longer exist
        $archived = Blog::whereNotIn('source_file', $scannedFilenames)
            ->where('status', BlogStatus::Published)
            ->get();

        foreach ($archived as $blog) {
            $blog->update(['status' => BlogStatus::Archived]);
            $this->line("  ✗ Archived (file removed): {$blog->source_file}");
        }

        $this->newLine();
        $this->components->success("created: {$created}, updated: {$updated}, archived: {$archived->count()}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
