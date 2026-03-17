<?php

declare(strict_types=1);

namespace App\Queries;

use App\Dto\BlogDailyView;
use App\Dto\BlogMonthlyView;
use App\Dto\BlogViewStats;
use App\Dto\BlogYearlyView;
use App\Dto\PostDailyView;
use App\Dto\PostHistoryView;
use App\Dto\PostTotalView;
use App\Models\Blog;
use App\Models\BlogView;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

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
            postHistories: $this->postHistories(),
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

        return BlogView::query()
            ->selectRaw('date, COUNT(*) AS views')
            ->where('date', '>=', $cutoff)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(static fn (BlogView $row): BlogDailyView => new BlogDailyView(
                date:  (string) $row->date,   // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<BlogMonthlyView> */
    public function monthly(): array
    {
        $cutoff = $this->now->copy()
            ->subMonths(self::MONTHLY_WINDOW_MONTHS - 1)
            ->startOfMonth()
            ->toDateString();

        return BlogView::query()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') AS month, COUNT(*) AS views")
            ->where('date', '>=', $cutoff)
            ->groupByRaw("TO_CHAR(date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(date, 'YYYY-MM')")
            ->get()
            ->map(static fn (BlogView $row): BlogMonthlyView => new BlogMonthlyView(
                month: (string) $row->month,  // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<BlogYearlyView> */
    public function yearly(): array
    {
        return BlogView::query()
            ->selectRaw("EXTRACT(YEAR FROM date)::int AS year, COUNT(*) AS views")
            ->groupByRaw('EXTRACT(YEAR FROM date)')
            ->orderByRaw('EXTRACT(YEAR FROM date)')
            ->get()
            ->map(static fn (BlogView $row): BlogYearlyView => new BlogYearlyView(
                year: (string) $row->year,    // @phpstan-ignore-line cast.useless
                views: (int) $row->views,     // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<PostTotalView> */
    public function topPosts(): array
    {
        return BlogView::query()
            ->selectRaw('blog_id, COUNT(*) AS views')
            ->groupBy('blog_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(self::TOP_POSTS_LIMIT)
            ->with('blog:id,title,slug')
            ->get()
            ->filter(static fn (BlogView $row): bool => $row->blog !== null)
            ->map(static fn (BlogView $row): PostTotalView => new PostTotalView(
                blogId: $row->blog->id,
                blogTitle: $row->blog->title,
                blogSlug: $row->blog->slug,
                views: (int) $row->views, // @phpstan-ignore-line cast.useless
            ))
            ->values()
            ->all();
    }

    /** @return list<PostHistoryView> */
    public function postHistories(): array
    {
        $cutoff = $this->now->copy()
            ->subDays(self::DAILY_WINDOW_DAYS - 1)
            ->toDateString();

        /** @var Collection<int, Collection<int, BlogView>> $byBlog */
        $byBlog = BlogView::query()
            ->selectRaw('blog_id, date, COUNT(*) AS views')
            ->where('date', '>=', $cutoff)
            ->groupBy('blog_id', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('blog_id');

        return Blog::query()
            ->select(['id', 'title', 'slug'])
            ->get()
            ->map(static function (Blog $blog) use ($byBlog): PostHistoryView {
                /** @var Collection<int, BlogView> $rows */
                $rows = $byBlog->get($blog->id, collect());

                return new PostHistoryView(
                    blogId: $blog->id,
                    blogTitle: $blog->title,
                    blogSlug: $blog->slug,
                    daily: $rows
                        ->map(static fn (BlogView $row): PostDailyView => new PostDailyView(
                            date: (string) $row->date,    // @phpstan-ignore-line cast.useless
                            views: (int) $row->views,     // @phpstan-ignore-line cast.useless
                        ))
                        ->values()
                        ->all(),
                );
            })
            ->values()
            ->all();
    }
}
