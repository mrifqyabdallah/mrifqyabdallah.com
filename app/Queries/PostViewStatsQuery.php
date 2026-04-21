<?php

namespace App\Queries;

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostViewStats;
use App\Dto\PostYearlyView;
use App\Models\Blog;
use App\Models\BlogView;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class PostViewStatsQuery
{
    private const DAILY_WINDOW_DAYS = 30;

    private const MONTHLY_WINDOW_MONTHS = 12;

    private readonly Blog $blog;

    public function __construct(
        private readonly int $blogId,
        private readonly CarbonImmutable $now,
    ) {
        $this->blog = Blog::select(['id', 'title', 'slug', 'published_at'])->findOrFail($this->blogId);
    }

    public function get(): PostViewStats
    {
        $yearly = $this->yearly();

        return new PostViewStats(
            blogId: $this->blog->id,
            blogTitle: $this->blog->title,
            blogSlug: $this->blog->slug,
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
        $windowStart = $this->now->subDays(self::DAILY_WINDOW_DAYS - 1)->startOfDay();

        $startDate = $this->blog->published_at->startOfDay()->gt($windowStart)
            ? $this->blog->published_at->startOfDay()
            : $windowStart;

        $data = DB::query()->fromRaw(
            "generate_series(?::date, ?::date, '1 day'::interval) AS calendar(date)",
            [$startDate->toDateString(), $this->now->toDateString()],
        )
            ->leftJoin('blog_views', function (JoinClause $join) {
                $join->on('calendar.date', '=', DB::raw('blog_views.date::date'))
                    ->where('blog_views.blog_id', '=', $this->blog->id);
            })
            ->selectRaw("TO_CHAR(calendar.date, 'YYYY-MM-DD') AS view_date, COUNT(blog_views.id) AS views")
            ->groupByRaw('view_date')
            ->orderByRaw('view_date')
            ->get()
            ->map(static fn (object $row): PostDailyView => new PostDailyView(
                date: (string) $row->view_date, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<PostMonthlyView> */
    public function monthly(): array
    {
        $windowStart = $this->now->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)->startOfMonth();
        $published_at = $this->blog->published_at->startOfMonth();
        $startDate = $published_at->gt($windowStart) ? $published_at : $windowStart;

        $data = DB::query()->fromRaw(
            "generate_series(date_trunc('month', ?::date), date_trunc('month', ?::date), '1 month'::interval) AS calendar(month)",
            [$startDate->toDateString(), $this->now->toDateString()],
        )
            ->leftJoin('blog_views', function (JoinClause $join) {
                $join->on(DB::raw('calendar.month'), '=', DB::raw("date_trunc('month', blog_views.date)"))
                    ->where('blog_views.blog_id', '=', $this->blog->id);
            })
            ->selectRaw("TO_CHAR(calendar.month, 'YYYY-MM') AS month, COUNT(blog_views.id) AS views")
            ->groupByRaw('month')
            ->orderByRaw('month')
            ->get()
            ->map(static fn (object $row): PostMonthlyView => new PostMonthlyView(
                month: (string) $row->month, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<PostYearlyView> */
    public function yearly(): array
    {
        $data = BlogView::query()
            ->selectRaw('EXTRACT(YEAR FROM date)::int AS year, COUNT(*) AS views')
            ->where('blog_id', $this->blogId)
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
