<?php

namespace App\Queries;

use App\Dto\BlogDailyView;
use App\Dto\BlogMonthlyView;
use App\Dto\BlogViewStats;
use App\Dto\BlogYearlyView;
use App\Dto\PostTotalView;
use App\Models\Blog;
use App\Models\BlogView;
use Carbon\CarbonImmutable;

final class BlogViewStatsQuery
{
    private const DAILY_WINDOW_DAYS = 30;

    private const MONTHLY_WINDOW_MONTHS = 12;

    private const TOP_POSTS_LIMIT = 10;

    public function __construct(
        private readonly CarbonImmutable $now,
    ) {}

    public function get(): BlogViewStats
    {
        return new BlogViewStats(
            totalViews: $this->totalViews(),
            daily: $this->daily(),
            monthly: $this->monthly(),
            yearly: $this->yearly(),
            topPosts: $this->topPosts(),
            generatedAt: $this->now,
        );
    }

    public function totalViews(): int
    {
        return BlogView::query()->count();
    }

    /** @return list<BlogDailyView> */
    public function daily(): array
    {
        $cutoff = $this->now->copy()
            ->subDays(self::DAILY_WINDOW_DAYS - 1)
            ->toDateString();

        $data = BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') AS view_date, COUNT(*) AS views")
            ->where('date', '>=', $cutoff)
            ->groupBy('view_date')
            ->orderBy('view_date')
            ->get()
            ->map(static fn (BlogView $row): BlogDailyView => new BlogDailyView(
                date: (string) $row->view_date, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<BlogMonthlyView> */
    public function monthly(): array
    {
        $cutoff = $this->now->copy()
            ->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)
            ->startOfMonth()
            ->toDateString();

        $data = BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') AS month, COUNT(*) AS views")
            ->where('date', '>=', $cutoff)
            ->groupByRaw('month')
            ->orderByRaw('month')
            ->get()
            ->map(static fn (BlogView $row): BlogMonthlyView => new BlogMonthlyView(
                month: (string) $row->month,  // @phpstan-ignore-line
                views: (int) $row->views,     // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<BlogYearlyView> */
    public function yearly(): array
    {
        $data = BlogView::query()
            ->selectRaw('EXTRACT(YEAR FROM date)::int AS year, COUNT(*) AS views')
            ->groupByRaw('year')
            ->orderByRaw('year')
            ->get()
            ->map(static fn (BlogView $row): BlogYearlyView => new BlogYearlyView(
                year: (string) $row->year,    // @phpstan-ignore-line
                views: (int) $row->views,     // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<PostTotalView> */
    public function topPosts(): array
    {
        $data = BlogView::query()
            ->selectRaw('blog_id, COUNT(*) AS views')
            ->groupBy('blog_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(self::TOP_POSTS_LIMIT)
            ->with('blog:id,title,slug')
            ->get()
            ->filter(static fn (BlogView $row): bool => $row->blog !== null)
            ->map(static function (BlogView $row): PostTotalView {
                assert($row->blog instanceof Blog);

                return new PostTotalView(
                    blogId: $row->blog->id,
                    blogTitle: $row->blog->title,
                    blogSlug: $row->blog->slug,
                    views: (int) $row->views, // @phpstan-ignore-line
                );
            })
            ->all();

        return array_values($data);
    }
}
