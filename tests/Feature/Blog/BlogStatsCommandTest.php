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
    it('dispatches post job for every published blog by default', function (): void {
        $a = Blog::factory()->published()->create();
        $b = Blog::factory()->published()->create();

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 2);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $a->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $b->id);
    });

    it('dispatches blog stats job when published post exist', function (): void {
        Blog::factory()->published()->count(2)->create();

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });

    it('excludes archived blogs by default', function (): void {
        $published = Blog::factory()->published()->create();
        $archived = Blog::factory()->archived()->create();

        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 1);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $published->id);
        Queue::assertNotPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $archived->id);
    });

    it('warns and dispatches nothing when no published blogs exist', function (): void {
        $this->artisan('blog:stats')->assertSuccessful();

        Queue::assertNothingPushed();
    });
});

describe('blog:stats --today', function () {
    it('dispatches post job for each published blog viewed today', function (): void {
        $a = Blog::factory()->published()->create();
        $b = Blog::factory()->published()->create();
        BlogView::factory()->for($a)->create(['date' => today()->toDateString()]);
        BlogView::factory()->for($b)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 2);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $a->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $b->id);
    });

    it('dispatches blog stats job when active published posts exist today', function (): void {
        $blog = Blog::factory()->published()->create();
        BlogView::factory()->for($blog)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });

    it('excludes archived blogs viewed today by default', function (): void {
        $published = Blog::factory()->published()->create();
        $archived = Blog::factory()->archived()->create();
        BlogView::factory()->for($published)->create(['date' => today()->toDateString()]);
        BlogView::factory()->for($archived)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 1);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $published->id);
        Queue::assertNotPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $archived->id);
    });

    it('warns and dispatches nothing when no views today', function (): void {
        $blog = Blog::factory()->published()->create();
        BlogView::factory()->for($blog)->create(['date' => today()->subDay()->toDateString()]);

        $this->artisan('blog:stats --today')->assertSuccessful();

        Queue::assertNothingPushed();
    });

    it('deduplicates blog ids so each post job dispatched once', function (): void {
        $blog = Blog::factory()->published()->create();
        BlogView::factory()->for($blog)->count(5)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 1);
    });
});

describe('blog:stats --archived', function () {
    it('includes archived blogs when --archived flag is passed', function (): void {
        $published = Blog::factory()->published()->create();
        $archived = Blog::factory()->archived()->create();

        $this->artisan('blog:stats --archived')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 2);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $published->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $archived->id);
    });

    it('dispatches blog stats job with --archived flag', function (): void {
        Blog::factory()->archived()->count(2)->create();

        $this->artisan('blog:stats --archived')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });

    it('warns and dispatches nothing when no blogs exist with --archived flag', function (): void {
        $this->artisan('blog:stats --archived')->assertSuccessful();

        Queue::assertNothingPushed();
    });
});

describe('blog:stats --today --archived', function () {
    it('includes archived blogs viewed today with both flags', function (): void {
        $published = Blog::factory()->published()->create();
        $archived = Blog::factory()->archived()->create();
        BlogView::factory()->for($published)->create(['date' => today()->toDateString()]);
        BlogView::factory()->for($archived)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today --archived')->assertSuccessful();

        Queue::assertPushed(GeneratePostStats::class, 2);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $published->id);
        Queue::assertPushed(GeneratePostStats::class, fn (GeneratePostStats $job): bool => $job->blogId === $archived->id);
    });

    it('dispatches blog stats job when archived blog viewed today with both flags', function (): void {
        $archived = Blog::factory()->archived()->create();
        BlogView::factory()->for($archived)->create(['date' => today()->toDateString()]);

        $this->artisan('blog:stats --today --archived')->assertSuccessful();

        Queue::assertPushed(GenerateBlogStats::class, 1);
    });
});
