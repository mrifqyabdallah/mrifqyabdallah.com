<?php

declare(strict_types=1);

namespace App\Queries;

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostViewStats;
use App\Dto\PostYearlyView;
use App\Models\BlogView;
use Carbon\CarbonImmutable;

final class PostViewStatsQuery
{
    private const DAILY_WINDOW_DAYS     = 30;
    private const MONTHLY_WINDOW_MONTHS = 12;

    public function __construct(
        private readonly int $blogId,
        private readonly CarbonImmutable $now,
    ) {}

    public function get(int $blogId, string $blogTitle, string $blogSlug): PostViewStats
    {
        $yearly = $this->yearly();

        return new PostViewStats(
            blogId: $blogId,
            blogTitle: $blogTitle,
            blogSlug: $blogSlug,
            totalViews: array_sum(array_map(
                static fn (PostYearlyView $y) => $y->views,
                $yearly,
            )),
            daily: $this->daily(),
            monthly: $this->monthly(),
            yearly: $yearly,
            generatedAt: $this->now,
        );
    }

    /** @return list<PostDailyView> */
    public function daily(): array
    {
        $cutoff = $this->now->copy()
            ->subDays(self::DAILY_WINDOW_DAYS - 1)
            ->toDateString();

        return BlogView::query()
            ->selectRaw('date, COUNT(*) AS views')
            ->where('blog_id', $this->blogId)
            ->where('date', '>=', $cutoff)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(static fn (BlogView $row): PostDailyView => new PostDailyView(
                date: (string) $row->date,    // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<PostMonthlyView> */
    public function monthly(): array
    {
        $cutoff = $this->now->copy()
            ->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)
            ->startOfMonth()
            ->toDateString();

        return BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') AS month, COUNT(*) AS views")
            ->where('blog_id', $this->blogId)
            ->where('date', '>=', $cutoff)
            ->groupByRaw("TO_CHAR(date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(date, 'YYYY-MM')")
            ->get()
            ->map(static fn (BlogView $row): PostMonthlyView => new PostMonthlyView(
                month: (string) $row->month,  // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<PostYearlyView> */
    public function yearly(): array
    {
        return BlogView::query()
            ->selectRaw("EXTRACT(YEAR FROM date)::int AS year, COUNT(*) AS views")
            ->where('blog_id', $this->blogId)
            ->groupByRaw('EXTRACT(YEAR FROM date)')
            ->orderByRaw('EXTRACT(YEAR FROM date)')
            ->get()
            ->map(static fn (BlogView $row): PostYearlyView => new PostYearlyView(
                year: (string) $row->year,    // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }
}
