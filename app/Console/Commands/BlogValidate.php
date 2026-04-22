<?php

namespace App\Console\Commands;

use App\Services\BlogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BlogValidate extends Command
{
    protected $signature = 'blogs:validate {--files= : Comma-separated list of files to validate (defaults to all)}';

    protected $description = 'Validate frontmatter and filename format of blog markdown files';

    public function __construct(private readonly BlogService $blogService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $root = config()->string('filesystems.disks.blogs.root');
        $filesOption = $this->option('files');

        if (! is_dir($root)) {
            $this->error("Directory not found: {$root}");

            return self::FAILURE;
        }

        $disk = Storage::disk('blogs');

        if ($filesOption) {
            $files = array_map(
                fn ($f) => trim(basename($f)),
                explode(',', $filesOption)
            );
        } else {
            /** @var array<string> $files_in_disk */
            $files_in_disk = $disk->files('/');
            $files = collect($files_in_disk)
                ->filter(fn ($f) => str_ends_with($f, '.md'))
                ->all();
        }

        if (empty($files)) {
            $this->info('No markdown files to validate.');

            return self::SUCCESS;
        }

        $hasErrors = false;

        foreach ($files as $filename) {
            if (! $disk->exists($filename)) {
                $this->warn("  ⚠ File not found: {$filename}");

                continue;
            }

            $contents = $disk->get($filename);

            if (! $contents) {
                $this->warn("  ⚠ Failed to get content: {$filename}");

                continue;
            }

            $errors = $this->blogService->validateFrontmatter($filename, $contents);

            if (empty($errors)) {
                $this->line("  ✓ <fg=green>{$filename}</>");
            } else {
                $hasErrors = true;
                $this->line("  ✗ <fg=red>{$filename}</>");
                foreach ($errors as $error) {
                    $this->line("      - {$error}");
                }
            }
        }

        $this->newLine();

        if ($hasErrors) {
            $this->components->error('Validation failed. Please fix the errors above.');

            return self::FAILURE;
        }

        $this->components->success('All blog files are valid.');

        return self::SUCCESS;
    }
}
