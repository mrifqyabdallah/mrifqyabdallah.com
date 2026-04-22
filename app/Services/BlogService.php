<?php

namespace App\Services;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class BlogService
{
    /**
     * Parse a markdown file into structured data ready for DB upsert.
     * Returns null if the file is invalid.
     *
     * @return null|array{
     *     slug: string,
     *     source_file: string,
     *     title: string,
     *     creator: string,
     *     excerpt: string,
     *     content: string,
     *     tags: array<int, string>,
     *     published_at: string
     * }
     */
    public function parseFile(string $filename, string $contents): ?array
    {
        $parsed = $this->parseFilename($filename);

        if (! $parsed) {
            return null;
        }

        $document = YamlFrontMatter::parse($contents);

        $title = $document->matter('title');
        $creator = $document->matter('creator');
        $excerpt = $document->matter('excerpt');
        $tags = $document->matter('tags');
        $body = trim($document->body());

        if (
            ! is_string($title) || empty($title) ||
            ! is_string($creator) || empty($creator) ||
            ! is_string($excerpt) || empty($excerpt) ||
            empty($body) ||
            ! is_array($tags) || ! array_is_list($tags) || (array_filter($tags, 'is_string') !== $tags)
        ) {
            return null;
        }

        return [
            'slug' => $parsed['slug'],
            'source_file' => $filename,
            'title' => $title,
            'creator' => $creator,
            'excerpt' => $excerpt,
            'content' => $body,
            'tags' => Arr::wrap($tags),
            'published_at' => $parsed['date'],
        ];
    }

    /**
     * Extract slug and date from filename.
     * Expected format: yyyy-mm-dd-title-slug.md
     *
     * @return null|array{
     *      date: string,
     *      slug: string,
     * }
     *
     * @throws InvalidFormatException
     */
    public function parseFilename(string $filename): ?array
    {
        // Strip .md extension and directory path
        $base = pathinfo($filename, PATHINFO_FILENAME);

        // Match yyyy-mm-dd- prefix
        if (! preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $base, $matches)) {
            return null;
        }

        $dateString = $matches[1];
        $slugPart = $matches[2];

        // Validate it's a real date
        try {
            Carbon::parse($dateString);
            $date = Carbon::createFromFormat('Y-m-d', $dateString)?->startOfDay();
        } catch (\Exception) {
            return null;
        }

        // Ensure slug is valid kebab-case
        if (is_null($date) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugPart)) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'slug' => $slugPart,
        ];
    }

    /**
     * Validate all required frontmatter fields are present and well-formed.
     * Returns array of error messages, empty array if valid.
     *
     * @return array<int, string>
     */
    public function validateFrontmatter(string $filename, string $contents): array
    {
        $errors = [];

        if (! $this->parseFilename($filename)) {
            $errors[] = 'Filename must match format: yyyy-mm-dd-title-slug.md (kebab-case slug, e.g. 2026-03-11-my-first-blog.md)';
        }

        $document = YamlFrontMatter::parse($contents);

        if (empty($document->matter('title'))) {
            $errors[] = 'Missing required field: title';
        }

        if (empty($document->matter('creator'))) {
            $errors[] = 'Missing required field: creator';
        }

        if (empty($document->matter('excerpt'))) {
            $errors[] = 'Missing required field: excerpt';
        }

        $tags = $document->matter('tags');
        if (empty($tags)) {
            $errors[] = 'Missing required field: tags (must be a non-empty array)';
        } elseif (! is_array($tags)) {
            $errors[] = 'Invalid tags format: must be an array, e.g. [laravel, php]';
        }

        if (empty(trim($document->body()))) {
            $errors[] = 'Blog content (body) cannot be empty';
        }

        return $errors;
    }
}
