<?php

use App\Enums\BlogStatus;
use App\Models\Blog;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->blogsPath = resource_path('blogs');
    File::ensureDirectoryExists($this->blogsPath);
});

afterEach(function (): void {
    File::cleanDirectory($this->blogsPath);
});

function validMarkdown(
    string $title = 'Hello World',
    string $creator = 'John',
    string $excerpt = 'Short excerpt',
    array $tags = ['laravel', 'php'],
    string $body = 'This is the body content.',
): string {
    $tagsYaml = implode(', ', array_map(fn ($t) => $t, $tags));

    return <<<MD
---
title: "{$title}"
creator: "{$creator}"
excerpt: "{$excerpt}"
tags: [{$tagsYaml}]
---

{$body}
MD;
}

function putBlogFile(string $filename, string $contents): void
{
    File::put(resource_path("blogs/{$filename}"), $contents);
}

describe('blogs:sync', function () {
    it('fails when blogs directory does not exist', function (): void {
        File::deleteDirectory(resource_path('blogs'));

        $this->artisan('blogs:sync')->assertFailed();
    });

    it('warns when no markdown files found', function (): void {
        $this->artisan('blogs:sync')
            ->expectsOutputToContain('No markdown files found')
            ->assertSuccessful();
    });

    it('creates a new blog from a valid markdown file', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());

        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::where('source_file', '2026-03-21-hello-world.md')->exists())->toBeTrue();
    });

    it('sets status to published on create', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());

        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::where('source_file', '2026-03-21-hello-world.md')->first()->status)
            ->toBe(BlogStatus::Published);
    });

    it('updates an existing blog', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());
        $this->artisan('blogs:sync')->assertSuccessful();

        putBlogFile('2026-03-21-hello-world.md', validMarkdown(title: 'Updated Title'));
        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::where('source_file', '2026-03-21-hello-world.md')->first()->title)
            ->toBe('Updated Title');
        expect(Blog::count())->toBe(1);
    });

    it('skips a file with invalid filename format', function (): void {
        putBlogFile('invalid-filename.md', validMarkdown());

        $this->artisan('blogs:sync')
            ->expectsOutputToContain('Skipped (invalid)')
            ->assertSuccessful();

        expect(Blog::count())->toBe(0);
    });

    it('skips a file with missing frontmatter fields', function (): void {
        putBlogFile('2026-03-21-hello-world.md', "---\n---\n\nBody only.");

        $this->artisan('blogs:sync')
            ->expectsOutputToContain('Skipped (invalid)')
            ->assertSuccessful();

        expect(Blog::count())->toBe(0);
    });

    it('archives published blogs whose files no longer exist', function (): void {
        $blog = Blog::factory()->create([
            'source_file' => '2026-03-21-removed-post.md',
            'status' => BlogStatus::Published,
        ]);

        $this->artisan('blogs:sync')->assertSuccessful();

        expect($blog->fresh()->status)->toBe(BlogStatus::Archived);
    });

    it('does not re-archive already archived blogs', function (): void {
        Blog::factory()->create([
            'source_file' => '2026-03-21-removed-post.md',
            'status' => BlogStatus::Archived,
        ]);

        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::where('status', BlogStatus::Archived)->count())->toBe(1);
    });

    it('outputs archived filename in result', function (): void {
        Blog::factory()->create([
            'source_file' => '2026-03-21-removed-post.md',
            'status' => BlogStatus::Published,
        ]);

        $this->artisan('blogs:sync')
            ->expectsOutputToContain('2026-03-21-removed-post.md')
            ->assertSuccessful();
    });

    it('outputs correct summary counts', function (): void {
        $existing = Blog::factory()->create([
            'source_file' => '2026-03-20-existing-post.md',
            'status' => BlogStatus::Published,
        ]);

        Blog::factory()->create([
            'source_file' => '2026-03-19-removed-post.md',
            'status' => BlogStatus::Published,
        ]);

        putBlogFile('2026-03-20-existing-post.md', validMarkdown(title: 'Updated'));
        putBlogFile('2026-03-21-new-post.md', validMarkdown());
        putBlogFile('invalid-file.md', validMarkdown());

        $this->artisan('blogs:sync')
            ->expectsOutputToContain('created: 1, updated: 1, archived: 1, skipped: 1')
            ->assertSuccessful();
    });

    it('does not archive blogs that still have their file present', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());
        $this->artisan('blogs:sync')->assertSuccessful();

        $blog = Blog::where('source_file', '2026-03-21-hello-world.md')->first();
        expect($blog->status)->toBe(BlogStatus::Published);
    });

    it('sets the correct slug from the filename', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());

        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::first()->slug)->toBe('hello-world');
    });

    it('sets published_at from the filename date', function (): void {
        putBlogFile('2026-03-21-hello-world.md', validMarkdown());

        $this->artisan('blogs:sync')->assertSuccessful();

        expect(Blog::first()->published_at->toDateString())->toBe('2026-03-21');
    });
});
