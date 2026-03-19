<?php

use App\Dto\BlogDailyView;
use App\Dto\BlogMonthlyView;
use App\Dto\BlogYearlyView;
use App\Dto\PostTotalView;
use App\Models\Blog;
use App\Models\BlogView;
use App\Queries\BlogViewStatsQuery;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->now = CarbonImmutable::parse('2024-06-15 12:00:00');
});

function blogQuery(CarbonImmutable $now): BlogViewStatsQuery
{
    return new BlogViewStatsQuery(now: $now);
}

describe('get()', function() {
    it('returns correct structure', function (): void {
        expect(blogQuery($this->now)->get())
            ->toHaveKey('total_views')
            ->toHaveKey('daily')
            ->toHaveKey('monthly')
            ->toHaveKey('yearly')
            ->toHaveKey('top_posts')
            ->toHaveKey('generated_at');
    });

    it('sets generated_at to now', function (): void {
        expect(blogQuery($this->now)->get()->generatedAt->eq($this->now))->toBeTrue();
    });
});

describe('totalViews()', function() {
    it('counts all rows for total views', function (): void {
        BlogView::factory()->count(7)->create();

        expect(blogQuery($this->now)->totalViews())->toBe(7);
    });

    it('returns zero total views with no data', function (): void {
        expect(blogQuery($this->now)->totalViews())->toBe(0);
    });
});

describe('daily()', function() {
    it('aggregates daily views across all blogs', function (): void {
        $a = Blog::factory()->create();
        $b = Blog::factory()->create();

        BlogView::factory()->for($a)->count(2)->create(['date' => '2024-06-15']);
        BlogView::factory()->for($b)->count(3)->create(['date' => '2024-06-15']);

        $results = blogQuery($this->now)->daily();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(5);
    });

    it('excludes dates outside the 30-day window', function (): void {
        $blog = Blog::factory()->create();
        BlogView::factory()->for($blog)->create(['date' => '2024-05-17']); // included
        BlogView::factory()->for($blog)->create(['date' => '2024-05-16']); // excluded

        $dates = array_column(blogQuery($this->now)->daily(), 'date');

        expect($dates)->toContain('2024-05-17')
            ->not->toContain('2024-05-16');
    });

    it('returns BlogDailyView DTO', function (): void {
        BlogView::factory()->create(['date' => '2024-06-15']);

        expect(blogQuery($this->now)->daily())
            ->each->toBeInstanceOf(BlogDailyView::class);
    });
});

describe('monthly()', function() {
    it('aggregates monthly views across all blogs', function (): void {
        $a = Blog::factory()->create();
        $b = Blog::factory()->create();

        BlogView::factory()->for($a)->count(4)->create(['date' => '2024-06-01']);
        BlogView::factory()->for($b)->count(6)->create(['date' => '2024-06-15']);

        $results = blogQuery($this->now)->monthly();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(10);
    });

    it('returns BlogMonthlyView DTO', function (): void {
        BlogView::factory()->create(['date' => '2024-06-01']);

        expect(blogQuery($this->now)->monthly())
            ->each->toBeInstanceOf(BlogMonthlyView::class);
    });
});

describe('yearly()', function() {
    it('aggregates yearly views across all blogs', function (): void {
        $a = Blog::factory()->create();
        $b = Blog::factory()->create();

        BlogView::factory()->for($a)->count(3)->create(['date' => '2024-01-01']);
        BlogView::factory()->for($b)->count(5)->create(['date' => '2024-06-01']);

        $results = blogQuery($this->now)->yearly();

        expect($results)->toHaveCount(1)
            ->and($results[0]->views)->toBe(8)
            ->and($results[0]->year)->toBe('2024');
    });

    it('returns BlogYearlyView DTO', function (): void {
        BlogView::factory()->create(['date' => '2024-01-01']);

        expect(blogQuery($this->now)->yearly())
            ->each->toBeInstanceOf(BlogYearlyView::class);
    });
});

describe('topPosts()', function() {
    it('orders top posts by views descending', function (): void {
        $popular = Blog::factory()->create();
        $quiet   = Blog::factory()->create();

        BlogView::factory()->for($popular)->count(10)->create();
        BlogView::factory()->for($quiet)->count(2)->create();

        $results = blogQuery($this->now)->topPosts();

        expect($results)->toHaveCount(2)
            ->and($results[0]->blogId)->toBe($popular->id)
            ->and($results[0]->views)->toBe(10)
            ->and($results[1]->blogId)->toBe($quiet->id);
    });

    it('limits top posts to 10', function (): void {
        $blogs = Blog::factory()->count(15)->create();
        foreach ($blogs as $blog) {
            BlogView::factory()->for($blog)->create();
        }

        expect(blogQuery($this->now)->topPosts())->toHaveCount(10);
    });

    it('returns empty array when no views exist', function (): void {
        Blog::factory()->count(3)->create();

        expect(blogQuery($this->now)->topPosts())->toBe([]);
    });

    it('returns PostTotalView DTO', function (): void {
        $blog = Blog::factory()->create();
        BlogView::factory()->for($blog)->create();

        expect(blogQuery($this->now)->topPosts())
            ->each->toBeInstanceOf(PostTotalView::class);
    });
});
