<?php

namespace App\Queries;

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostViewStats;
use App\Dto\PostYearlyView;
use App\Models\Blog;
use App\Models\BlogView;
use Carbon\CarbonImmutable;

final class PostViewStatsQuery
{
    private const DAILY_WINDOW_DAYS = 30;

    private const MONTHLY_WINDOW_MONTHS = 12;

    public function __construct(
        private readonly Blog|int $blogId,
        private readonly CarbonImmutable $now,
    ) {}

    public function get(): PostViewStats
    {
        $blog = $this->blogId instanceof Blog ? $this->blogId :
            Blog::select(['id', 'title', 'slug'])->findOrFail($this->blogId);

        $yearly = $this->yearly();

        return new PostViewStats(
            blogId: $blog->id,
            blogTitle: $blog->title,
            blogSlug: $blog->slug,
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
        $blog_id = $this->blogId instanceof Blog ? $this->blogId->id : $this->blogId;
        $cutoff = $this->now->copy()
            ->subDays(self::DAILY_WINDOW_DAYS - 1)
            ->toDateString();

        $data = BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') AS view_date, COUNT(*) AS views")
            ->where('blog_id', $blog_id)
            ->where('date', '>=', $cutoff)
            ->groupByRaw('view_date')
            ->orderByRaw('view_date')
            ->get()
            ->map(static fn (BlogView $row): PostDailyView => new PostDailyView(
                date: (string) $row->view_date, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<PostMonthlyView> */
    public function monthly(): array
    {
        $blog_id = $this->blogId instanceof Blog ? $this->blogId->id : $this->blogId;
        $cutoff = $this->now->copy()
            ->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)
            ->startOfMonth()
            ->toDateString();

        $data = BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') AS month, COUNT(*) AS views")
            ->where('blog_id', $blog_id)
            ->where('date', '>=', $cutoff)
            ->groupByRaw('month')
            ->orderByRaw('month')
            ->get()
            ->map(static fn (BlogView $row): PostMonthlyView => new PostMonthlyView(
                month: (string) $row->month, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<PostYearlyView> */
    public function yearly(): array
    {
        $blog_id = $this->blogId instanceof Blog ? $this->blogId->id : $this->blogId;

        $data = BlogView::query()
            ->selectRaw('EXTRACT(YEAR FROM date)::int AS year, COUNT(*) AS views')
            ->where('blog_id', $blog_id)
            ->groupByRaw('year')
            ->orderByRaw('year')
            ->get()
            ->map(static fn (BlogView $row): PostYearlyView => new PostYearlyView(
                year: (string) $row->year, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }
}
