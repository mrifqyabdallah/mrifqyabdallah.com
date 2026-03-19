<?php

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostYearlyView;
use App\Models\Blog;
use App\Models\BlogView;
use App\Queries\PostViewStatsQuery;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->blog = Blog::factory()->create();
    $this->now = CarbonImmutable::parse('2024-06-15 12:00:00');
});

function postQuery(Blog $blog, CarbonImmutable $now): PostViewStatsQuery
{
    return new PostViewStatsQuery(blogId: $blog->id, now: $now);
}

describe('get()', function () {
    it('returns correct structure', function (): void {
        expect(postQuery($this->blog, $this->now)->get())
            ->toHaveKey('blog_id')
            ->toHaveKey('blog_title')
            ->toHaveKey('blog_slug')
            ->toHaveKey('total_views')
            ->toHaveKey('daily')
            ->toHaveKey('monthly')
            ->toHaveKey('yearly')
            ->toHaveKey('generated_at');
    });

    it('attaches blog metadata', function (): void {
        $blog = Blog::factory()->create(['title' => 'My Title', 'slug' => 'my-title']);
        $stats = postQuery($blog, $this->now)->get();

        expect($stats->blogId)->toBe($blog->id)
            ->and($stats->blogTitle)->toBe('My Title')
            ->and($stats->blogSlug)->toBe('my-title');
    });

    it('calculates total_views as sum across all years', function (): void {
        BlogView::factory()->for($this->blog)->count(3)->create(['date' => '2023-06-01']);
        BlogView::factory()->for($this->blog)->count(4)->create(['date' => '2024-06-01']);

        $stats = postQuery($this->blog, $this->now)->get();

        expect($stats->totalViews)->toBe(7);
    });

    it('returns zero total_views when no views exist', function (): void {
        $stats = postQuery($this->blog, $this->now)->get();

        expect($stats->totalViews)->toBe(0);
    });

    it('sets generated_at to now', function (): void {
        expect(postQuery($this->blog, $this->now)->get()->generatedAt->eq($this->now))->toBeTrue();
    });
});

describe('daily()', function () {
    it('returns views within last 30 days', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-05-17']); // 30 days before is included
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']); // today
        BlogView::factory()->for($this->blog)->create(['date' => '2024-05-16']); // 31 days before is excluded

        $dates = array_column(postQuery($this->blog, $this->now)->daily(), 'date');

        expect($dates)->toContain('2024-05-17')
            ->toContain('2024-06-15')
            ->not->toContain('2024-05-16');
    });

    it('aggregates multiple views on the same day', function (): void {
        BlogView::factory()->for($this->blog)->count(4)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->daily();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(4);
    });

    it('excludes other blogs from daily results', function (): void {
        $other = Blog::factory()->create();
        BlogView::factory()->for($other)->count(3)->create(['date' => '2024-06-15']);
        BlogView::factory()->for($this->blog)->count(1)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->daily();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(1);
    });

    it('returns empty array when no views exist', function (): void {
        expect(postQuery($this->blog, $this->now)->daily())->toBe([]);
    });

    it('orders daily results by date ascending', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-10']);
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-01']);
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-05']);

        $dates = array_column(postQuery($this->blog, $this->now)->daily(), 'date');

        expect($dates)->toBe(['2024-06-01', '2024-06-05', '2024-06-10']);
    });

    it('returns PostDailyView DTO', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']);

        expect(postQuery($this->blog, $this->now)->daily())
            ->each->toBeInstanceOf(PostDailyView::class);
    });
});

describe('monthly()', function () {
    it('returns views within the last 12 months', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2023-07-01']); // 12 months before is included
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']); // this month
        BlogView::factory()->for($this->blog)->create(['date' => '2023-06-30']); // 13 months before is excluded

        $months = array_column(postQuery($this->blog, $this->now)->monthly(), 'month');

        expect($months)->toContain('2023-07')
            ->toContain('2024-06')
            ->not->toContain('2023-06');
    });

    it('aggregates views per month', function (): void {
        BlogView::factory()->for($this->blog)->count(3)->create(['date' => '2024-06-01']);
        BlogView::factory()->for($this->blog)->count(2)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->monthly();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(5);
    });

    it('returns PostMonthlyView DTO', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-01']);

        expect(postQuery($this->blog, $this->now)->monthly())
            ->each->toBeInstanceOf(PostMonthlyView::class);
    });
});

describe('yearly()', function () {
    it('covers all time for yearly stats', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2020-01-01']);
        BlogView::factory()->for($this->blog)->create(['date' => '2022-06-01']);
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']);

        $years = array_column(postQuery($this->blog, $this->now)->yearly(), 'year');

        expect($years)->toBe(['2020', '2022', '2024']);
    });

    it('aggregates views per year', function (): void {
        BlogView::factory()->for($this->blog)->count(5)->create(['date' => '2024-01-01']);
        BlogView::factory()->for($this->blog)->count(3)->create(['date' => '2024-12-31']);

        $results = postQuery($this->blog, $this->now)->yearly();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(8)
            ->and($results[0]->year)->toBe('2024');
    });

    it('returns PostYearlyView DTO', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-01-01']);

        expect(postQuery($this->blog, $this->now)->yearly())
            ->each->toBeInstanceOf(PostYearlyView::class);
    });
});
