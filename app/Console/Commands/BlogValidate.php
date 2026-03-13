<?php

namespace App\Console\Commands;

use App\Services\BlogSyncService;
use Illuminate\Console\Command;

class BlogValidate extends Command
{
    protected $signature = 'blogs:validate {--files= : Comma-separated list of files to validate (defaults to all)}';

    protected $description = 'Validate frontmatter and filename format of blog markdown files';

    public function __construct(private readonly BlogSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $blogsPath = resource_path('blogs');
        $filesOption = $this->option('files');

        if (! is_dir($blogsPath)) {
            $this->error("Directory not found: {$blogsPath}");

            return self::FAILURE;
        }

        if ($filesOption) {
            $files = array_map(
                fn ($f) => $blogsPath.'/'.trim(basename($f)),
                explode(',', $filesOption)
            );
        } else {
            $files = glob("{$blogsPath}/*.md");
        }

        if (empty($files)) {
            $this->info('No markdown files to validate.');

            return self::SUCCESS;
        }

        $hasErrors = false;

        foreach ($files as $filePath) {
            if (! file_exists($filePath)) {
                $this->warn("  ⚠ File not found: {$filePath}");

                continue;
            }

            $filename = basename($filePath);
            $contents = file_get_contents($filePath);

            if (! $contents) {
                $this->warn("  ⚠ Failed to get content: {$filePath}");

                continue;
            }

            $errors = $this->syncService->validateFrontmatter($filename, $contents);

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
