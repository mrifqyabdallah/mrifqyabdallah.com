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
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class BlogViewStatsQuery
{
    const DAILY_WINDOW_DAYS = 30;

    const MONTHLY_WINDOW_MONTHS = 12;

    const TOP_POSTS_LIMIT = 10;

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
        $cutoff = $this->now
            ->subDays(self::DAILY_WINDOW_DAYS - 1)
            ->toDateString();

        $data = DB::query()->fromRaw(
            "generate_series(?::date, ?::date, '1 day'::interval) AS calendar(date)",
            [$cutoff, $this->now->toDateString()],
        )
            ->leftJoin('blog_views', function (JoinClause $join) {
                $join->on('calendar.date', '=', DB::raw('blog_views.date::date'));
            })
            ->selectRaw("TO_CHAR(calendar.date, 'YYYY-MM-DD') AS view_date")
            ->selectRaw('COUNT(blog_views.id) AS views')
            ->where('calendar.date', '>=', $cutoff)
            ->groupBy('view_date')
            ->orderBy('view_date')
            ->get()
            ->map(static fn (object $row): BlogDailyView => new BlogDailyView(
                date: (string) $row->view_date, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
            ))
            ->all();

        return array_values($data);
    }

    /** @return list<BlogMonthlyView> */
    public function monthly(): array
    {
        $cutoff = $this->now
            ->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)
            ->startOfMonth()
            ->toDateString();

        $data = DB::query()->fromRaw(
            "generate_series(date_trunc('month', ?::date), date_trunc('month', ?::date), '1 month'::interval) AS calendar(month)",
            [$cutoff, $this->now->toDateString()],
        )
            ->leftJoin('blog_views', function (JoinClause $join) {
                $join->on(DB::raw('calendar.month'), '=', DB::raw("date_trunc('month', blog_views.date)"));
            })
            ->selectRaw("TO_CHAR(calendar.month, 'YYYY-MM') AS month")
            ->selectRaw('COUNT(blog_views.id) AS views')
            ->groupByRaw('month')
            ->orderByRaw('month')
            ->get()
            ->map(static fn (object $row): BlogMonthlyView => new BlogMonthlyView(
                month: (string) $row->month, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
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
                year: (string) $row->year, // @phpstan-ignore-line
                views: (int) $row->views, // @phpstan-ignore-line
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
