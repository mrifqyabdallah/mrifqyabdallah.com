<?php

use Illuminate\Support\Facades\Storage;

it('fails when blogs directory does not exist', function (): void {
    config(['filesystems.disks.blogs.root' => '/nonexistent/path']);

    $this->artisan('blogs:validate')->assertFailed();
});

describe('blogs:validate', function () {
    beforeEach(function (): void {
        Storage::fake('blogs');
    });

    it('succeeds with no files and outputs no files message', function (): void {
        $this->artisan('blogs:validate')
            ->expectsOutputToContain('No markdown files to validate.')
            ->assertSuccessful();
    });

    it('passes a valid markdown file', function (): void {
        putBlogFile('2026-03-21-hello-world.md', generateBlogMarkdown());

        $this->artisan('blogs:validate')
            ->assertSuccessful();
    });

    it('fails a file with invalid filename format', function (): void {
        putBlogFile('invalid-filename.md', generateBlogMarkdown());

        $this->artisan('blogs:validate')
            ->expectsOutputToContain('invalid-filename.md')
            ->assertFailed();
    });

    it('fails a file with missing frontmatter fields', function (): void {
        putBlogFile('2026-03-21-hello-world.md', "---\n---\n\nBody only.");

        $this->artisan('blogs:validate')
            ->expectsOutputToContain('2026-03-21-hello-world.md')
            ->assertFailed();
    });

    it('warns when a specific file from --files option does not exist', function (): void {
        $this->artisan('blogs:validate', ['--files' => 'nonexistent.md'])
            ->expectsOutputToContain('File not found')
            ->assertSuccessful();
    });

    it('validates only the files specified in --files option', function (): void {
        putBlogFile('2026-03-21-valid.md', generateBlogMarkdown());
        putBlogFile('2026-03-21-invalid.md', "---\n---\n\nBody only.");

        $this->artisan('blogs:validate', ['--files' => '2026-03-21-valid.md'])
            ->assertSuccessful();
    });

    it('outputs validation errors for each invalid file', function (): void {
        putBlogFile('2026-03-21-hello-world.md', "---\n---\n\nBody only.");

        $this->artisan('blogs:validate')
            ->expectsOutputToContain('Validation failed')
            ->assertFailed();
    });

    it('outputs success message when all files are valid', function (): void {
        putBlogFile('2026-03-21-hello-world.md', generateBlogMarkdown());

        $this->artisan('blogs:validate')
            ->expectsOutputToContain('All blog files are valid.')
            ->assertSuccessful();
    });

    it('validates multiple files and fails if any are invalid', function (): void {
        putBlogFile('2026-03-21-valid.md', generateBlogMarkdown());
        putBlogFile('2026-03-21-invalid.md', "---\n---\n\nBody only.");

        $this->artisan('blogs:validate')
            ->expectsOutputToContain('Validation failed')
            ->assertFailed();
    });
});
