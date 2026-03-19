<?php

use App\Jobs\GenerateBlogStats;
use App\Jobs\GeneratePostStats;
use App\Models\Blog;
use App\Models\BlogView;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

describe('blog:stats', function () {
    it('dispatches post job for each active blog today', function (): void {
        $a = Blog::factory()->create();
        $b = Blog::factory()->create();

        BlogView::factory()->for($a)->create(['date' => today()->toDateString()]);
        BlogView::factory()->for($b)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 2);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $a->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $b->id);
    });

    it('dispatches blog stats job when active posts exist today', function (): void {
        $blog = Blog::factory()->create();
        BlogView::factory()->for($blog)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });

    it('dispatches nothing when no views today', function (): void {
        $blog = Blog::factory()->create();
        BlogView::factory()->for($blog)->create(['date' => today()->subDay()->toDateString()]);

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertNothingPushed();
    });

    it('deduplicates blog ids so each post job dispatched once', function (): void {
        $blog = Blog::factory()->create();
        BlogView::factory()->for($blog)->count(5)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 1);
    });
});

describe('blog:stats --all', function () {
    it('dispatches post job for every blog with --all flag', function (): void {
        $a = Blog::factory()->create();
        $b = Blog::factory()->create();
        $c = Blog::factory()->create();

        BlogView::factory()->for($a)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --all')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 3);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $b->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $c->id);
    });

    it('dispatches blog stats job with --all flag', function (): void {
        Blog::factory()->count(3)->create();

        $this->artisan('blog:stats --all')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });

    it('warns and dispatches nothing when no blogs exist with --all flag', function (): void {
        $this->artisan('blog:stats --all')->assertSuccessful();

        Queue::assertNothingPushed();
    });

    it('includes blogs with no views with --all flag', function (): void {
        $withViews = Blog::factory()->create();
        $withoutViews = Blog::factory()->create();

        BlogView::factory()->for($withViews)->create();

        $this->artisan('blog:stats --all')->assertSuccessful();

        Queue::assertPushed(
            GeneratePostStats::class,
            fn (GeneratePostStats $job): bool => $job->blogId === $withoutViews->id,
        );
    });
});
