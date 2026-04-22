<?php

use App\Services\BlogService;

beforeEach(function () {
    $this->service = new BlogService;
});

describe('parseFile', function () {
    it('parses a valid markdown file', function () {
        $contents = <<<'MD'
---
title: My First Blog
creator: johndoe
tags: [laravel, php]
excerpt: A short description about this blog.
---

# Hello World

This is the content of the blog post.
MD;

        $result = $this->service->parseFile('2026-03-11-my-first-blog.md', $contents);

        expect($result)->not->toBeNull()
            ->and($result['slug'])->toBe('my-first-blog')
            ->and($result['title'])->toBe('My First Blog')
            ->and($result['creator'])->toBe('johndoe')
            ->and($result['excerpt'])->toBe('A short description about this blog.')
            ->and($result['tags'])->toBe(['laravel', 'php'])
            ->and($result['published_at'])->toBe('2026-03-11')
            ->and($result['source_file'])->toBe('2026-03-11-my-first-blog.md')
            ->and($result['content'])->toContain('Hello World');
    });

    it('returns null when filename is invalid', function () {
        $contents = "---\ntitle: Test\ncreator: x\nexcerpt: y\ntags: [a]\n---\nBody";

        expect($this->service->parseFile('invalid.md', $contents))->toBeNull();
    });

    it('returns null when required frontmatter is missing', function () {
        $contents = "---\ntitle: Test\n---\nBody here";

        expect($this->service->parseFile('2026-03-11-test.md', $contents))->toBeNull();
    });

    it('returns null when body is empty', function () {
        $contents = "---\ntitle: Test\ncreator: x\nexcerpt: y\ntags: [a]\n---\n";

        expect($this->service->parseFile('2026-03-11-test.md', $contents))->toBeNull();
    });

    it('returns null when tags is not array', function () {
        $contents = "---\ntitle: Test\ncreator: x\nexcerpt: y\ntags: laravel\n---\nBody here";

        expect($this->service->parseFile('2026-03-11-test.md', $contents))->toBeNull();
    });
});

describe('parseFilename', function () {
    it('extracts slug and date from a valid filename', function () {
        $result = $this->service->parseFilename('2026-03-11-my-first-blog.md');

        expect($result)->toBe([
            'date' => '2026-03-11',
            'slug' => 'my-first-blog',
        ]);
    });

    it('handles multi-word slugs', function () {
        $result = $this->service->parseFilename('2026-03-11-laravel-and-react-tips.md');

        expect($result['slug'])->toBe('laravel-and-react-tips');
        expect($result['date'])->toBe('2026-03-11');
    });

    it('returns null when filename has no date prefix', function () {
        expect($this->service->parseFilename('my-first-blog.md'))->toBeNull();
    });

    it('returns null when date is invalid', function () {
        expect($this->service->parseFilename('2026-99-99-my-blog.md'))->toBeNull();
    });

    it('returns null when slug contains uppercase', function () {
        expect($this->service->parseFilename('2026-03-11-My-Blog.md'))->toBeNull();
    });

    it('returns null when slug contains spaces', function () {
        expect($this->service->parseFilename('2026-03-11-my blog.md'))->toBeNull();
    });

    it('strips directory path when present', function () {
        $result = $this->service->parseFilename('/some/path/2026-03-11-my-blog.md');

        expect($result['slug'])->toBe('my-blog');
    });
});

describe('validateFrontmatter', function () {
    it('returns no errors for a valid file', function () {
        $contents = <<<'MD'
---
title: My First Blog
creator: johndoe
tags: [laravel, php]
excerpt: A short description.
---

Content here.
MD;

        expect($this->service->validateFrontmatter('2026-03-11-my-first-blog.md', $contents))
            ->toBeEmpty();
    });

    it('reports invalid filename format', function () {
        $contents = "---\ntitle: T\ncreator: x\nexcerpt: y\ntags: [a]\n---\nBody";

        $errors = $this->service->validateFrontmatter('bad-filename.md', $contents);

        expect($errors)->toContain(
            'Filename must match format: yyyy-mm-dd-title-slug.md (kebab-case slug, e.g. 2026-03-11-my-first-blog.md)'
        );
    });

    it('reports each missing field separately', function () {
        $contents = "---\n---\n";

        $errors = $this->service->validateFrontmatter('2026-03-11-test.md', $contents);

        expect($errors)
            ->toContain('Missing required field: title')
            ->toContain('Missing required field: creator')
            ->toContain('Missing required field: excerpt')
            ->toContain('Missing required field: tags (must be a non-empty array)')
            ->toContain('Blog content (body) cannot be empty');
    });

    it('reports invalid tags format', function () {
        $contents = "---\ntitle: T\ncreator: x\nexcerpt: y\ntags: not-an-array\n---\nBody";

        $errors = $this->service->validateFrontmatter('2026-03-11-test.md', $contents);

        expect($errors)->toContain('Invalid tags format: must be an array, e.g. [laravel, php]');
    });
});
