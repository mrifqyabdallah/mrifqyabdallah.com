<?php

namespace App\Console\Commands;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BlogSync extends Command
{
    protected $signature = 'blogs:sync';

    protected $description = 'Sync markdown files in resources/blogs/ to the database';

    public function __construct(
        private readonly BlogService $blogService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $root = config()->string('filesystems.disks.blogs.root');

        if (! is_dir($root)) {
            $this->error("Directory not found: {$root}");

            return self::FAILURE;
        }

        $disk = Storage::disk('blogs');

        if (! $disk->exists('/')) {
            $this->error('Directory not found');

            return self::FAILURE;
        }

        /** @var array<string> $files */
        $files = $disk->files('/');
        $files = array_filter($files, fn ($f) => str_ends_with($f, '.md'));

        if (empty($files)) {
            $this->warn('No markdown files found in resources/blogs/');
        }

        $scannedFilenames = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $filename) {
            $contents = $disk->get($filename);

            if (! $contents) {
                $this->warn("  ⚠ Skipped (failed to get file): {$filename}");
                $skipped++;

                continue;
            }

            $data = $this->blogService->parseFile($filename, $contents);

            if (! $data) {
                $this->warn("  ⚠ Skipped (invalid): {$filename}");
                $skipped++;

                continue;
            }

            $scannedFilenames[] = $filename;

            $existing = Blog::where('source_file', $filename)->first();

            Blog::updateOrCreate(
                ['source_file' => $data['source_file']],
                [...$data, 'status' => BlogStatus::Published]
            );

            $this->line(sprintf('  ✓ %s: %s',
                $existing ? 'Updated' : 'Created',
                $data['source_file'],
            ));

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }
        }

        // Archive any DB records whose files no longer exist
        $archived = Blog::whereNotIn('source_file', $scannedFilenames)
            ->where('status', BlogStatus::Published)
            ->get();

        if ($archived->isNotEmpty()) {
            Blog::whereIn('id', $archived->pluck('id'))
                ->update(['status' => BlogStatus::Archived]);
        }

        foreach ($archived as $blog) {
            $this->line("  ✗ Archived (file removed): {$blog->source_file}");
        }

        $this->newLine();
        $this->components->success("created: {$created}, updated: {$updated}, archived: {$archived->count()}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
