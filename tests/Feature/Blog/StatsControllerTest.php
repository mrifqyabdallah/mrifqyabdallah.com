<?php

use App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

describe('GET /blog/stats', function () {
    it('renders blog/stats page', function (): void {
        Storage::fake('public');

        $this->get(route('stats.blog'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/blog')
            );
    });

    it('passes null stats when overview file is missing', function (): void {
        Storage::fake('public');

        $this->get(route('stats.blog'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/blog')
                ->where('stats', null)
            );
    });

    it('passes decoded stats when overview file exists', function (): void {
        Storage::fake('public');

        Storage::disk('public')->put(
            'stats/blog/overview.json',
            json_encode(['total_views' => 42, 'daily' => [], 'generated_at' => now()->toISOString()]),
        );

        $this->get(route('stats.blog'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/blog')
                ->where('stats.total_views', 42)
            );
    });

    it('passes null stats when overview file contains invalid json', function (): void {
        Storage::fake('public');

        Storage::disk('public')->put('stats/blog/overview.json', 'not-valid-json{{');

        $this->get(route('stats.blog'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats', null)
            );
    });
});

describe('GET /blog/{slug}/stats', function () {
    it('renders /blog/{slug}/stats page', function (): void {
        Storage::fake('public');
        $blog = Blog::factory()->create();

        $this->get(route('stats.post', $blog->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/post')
            );
    });

    it('passes blog data to page', function (): void {
        Storage::fake('public');
        $blog = Blog::factory()->create(['title' => 'Hello World', 'slug' => 'hello-world']);

        $this->get(route('stats.post', $blog->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/post')
                ->where('blog.id', $blog->id)
                ->where('blog.title', 'Hello World')
                ->where('blog.slug', 'hello-world')
            );
    });

    it('passes null stats when post file is missing', function (): void {
        Storage::fake('public');
        $blog = Blog::factory()->create();

        $this->get(route('stats.post', $blog->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/post')
                ->where('stats', null)
            );
    });

    it('passes decoded stats when post file exists', function (): void {
        Storage::fake('public');
        $blog = Blog::factory()->create();

        Storage::disk('public')->put(
            "stats/blogpost/{$blog->id}.json",
            json_encode([
                'blog_id' => $blog->id,
                'total_views' => 7,
                'daily' => [],
                'monthly' => [],
                'yearly' => [],
                'generated_at' => now()->toISOString(),
            ]),
        );

        $this->get(route('stats.post', $blog->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('stats/post')
                ->where('stats.total_views', 7)
            );
    });

    it('passes null stats when post file contains invalid json', function (): void {
        Storage::fake('public');
        $blog = Blog::factory()->create();

        Storage::disk('public')->put("stats/blogs/{$blog->id}.json", 'not-valid-json{{');

        $this->get(route('stats.post', $blog->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats', null)
            );
    });

    it('returns 404 for nonexistent blog', function (): void {
        $this->get(route('stats.post', 'non-existent-slug'))
            ->assertNotFound();
    });
});
