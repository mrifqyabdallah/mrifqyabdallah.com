<?php

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostYearlyView;
use App\Models\Blog;
use App\Models\BlogView;
use App\Queries\PostViewStatsQuery;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->blog = Blog::factory()->create(['published_at' => '2022-01-01']);
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
        $blog = Blog::factory()->create([
            'title' => 'My Title',
            'slug' => 'my-title',
            'published_at' => '2024-01-01',
        ]);
        $stats = postQuery($blog, $this->now)->get();

        expect($stats->blogId)->toBe($blog->id)
            ->and($stats->blogTitle)->toBe('My Title')
            ->and($stats->blogSlug)->toBe('my-title');
    });

    it('calculates total_views as sum across all years', function (): void {
        BlogView::factory()->for($this->blog)->count(3)->create(['date' => '2023-06-01']);
        BlogView::factory()->for($this->blog)->count(4)->create(['date' => '2024-06-01']);

        expect(postQuery($this->blog, $this->now)->get()->totalViews)->toBe(7);
    });

    it('returns zero total_views when no views exist', function (): void {
        expect(postQuery($this->blog, $this->now)->get()->totalViews)->toBe(0);
    });

    it('sets generated_at to now', function (): void {
        expect(postQuery($this->blog, $this->now)->get()->generatedAt->eq($this->now))->toBeTrue();
    });
});

describe('daily()', function () {
    it('fills zero-view months within the window', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2023-07-01']);
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']);

        $months = array_column(postQuery($this->blog, $this->now)->monthly(), 'month');

        expect($months)->toContain('2023-07')
            ->toContain('2024-06')
            ->toContain('2024-01'); // gap month with zero views
    });

    it('zero-view days have views of 0', function (): void {
        $results = postQuery($this->blog, $this->now)->daily();

        expect($results[0]->views)->toBe(0);
    });

    it('spans exactly 30 days when published_at is before the window', function (): void {
        $results = postQuery($this->blog, $this->now)->daily();

        expect($results)->toHaveCount(30);
    });

    it('starts from published_at when it is more recent than the 30-day window', function (): void {
        $blog = Blog::factory()->create(['published_at' => '2024-06-10']);

        $results = postQuery($blog, $this->now)->daily();
        $dates = array_column($results, 'date');

        expect($dates[0])->toBe('2024-06-10')
            ->and($results)->toHaveCount(6); // June 10–15
    });

    it('includes views on the window start day', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-05-17']); // 30th day
        $dates = array_column(postQuery($this->blog, $this->now)->daily(), 'date');

        expect($dates)->toContain('2024-05-17');
    });

    it('does not include days before the window', function (): void {
        $dates = array_column(postQuery($this->blog, $this->now)->daily(), 'date');

        expect($dates)->not->toContain('2024-05-16');
    });

    it('aggregates multiple views on the same day', function (): void {
        BlogView::factory()->for($this->blog)->count(4)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->daily();
        $day = collect($results)->firstWhere('date', '2024-06-15');

        expect($day->views)->toBe(4);
    });

    it('excludes views from other blogs', function (): void {
        $other = Blog::factory()->create(['published_at' => '2024-01-01']);
        BlogView::factory()->for($other)->count(3)->create(['date' => '2024-06-15']);
        BlogView::factory()->for($this->blog)->count(1)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->daily();
        $day = collect($results)->firstWhere('date', '2024-06-15');

        expect($day->views)->toBe(1);
    });

    it('orders results by date ascending', function (): void {
        $dates = array_column(postQuery($this->blog, $this->now)->daily(), 'date');

        expect($dates)->toBe(array_values(array_unique($dates)))
            ->and($dates[0])->toBeLessThan($dates[count($dates) - 1]);
    });

    it('returns PostDailyView DTOs', function (): void {
        expect(postQuery($this->blog, $this->now)->daily())
            ->each->toBeInstanceOf(PostDailyView::class);
    });
});

describe('monthly()', function () {
    it('fills zero-view months within the window', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2023-07-01']);
        BlogView::factory()->for($this->blog)->create(['date' => '2024-06-15']);

        $months = array_column(postQuery($this->blog, $this->now)->monthly(), 'month');

        expect($months)->toContain('2023-07')
            ->toContain('2024-06')
            ->toContain('2024-01'); // gap month with zero views
    });

    it('spans exactly 12 months when published_at is before the window', function (): void {
        $results = postQuery($this->blog, $this->now)->monthly();

        expect($results)->toHaveCount(12);
    });

    it('starts from published_at month when more recent than the 12-month window', function (): void {
        $blog = Blog::factory()->create(['published_at' => '2024-04-15']);

        $results = postQuery($blog, $this->now)->monthly();
        $months = array_column($results, 'month');

        expect($months[0])->toBe('2024-04')
            ->and($results)->toHaveCount(3);
    });

    it('does not include months before the window', function (): void {
        $months = array_column(postQuery($this->blog, $this->now)->monthly(), 'month');

        expect($months)->not->toContain('2023-06');
    });

    it('aggregates views per month', function (): void {
        BlogView::factory()->for($this->blog)->count(3)->create(['date' => '2024-06-01']);
        BlogView::factory()->for($this->blog)->count(2)->create(['date' => '2024-06-15']);

        $results = postQuery($this->blog, $this->now)->monthly();
        $month = collect($results)->firstWhere('month', '2024-06');

        expect($month->views)->toBe(5);
    });

    it('returns PostMonthlyView DTOs', function (): void {
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

    it('returns PostYearlyView DTOs', function (): void {
        BlogView::factory()->for($this->blog)->create(['date' => '2024-01-01']);

        expect(postQuery($this->blog, $this->now)->yearly())
            ->each->toBeInstanceOf(PostYearlyView::class);
    });
});
