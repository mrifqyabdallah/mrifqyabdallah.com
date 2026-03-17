<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

final readonly class BlogViewStats
{
    /**
     * @param list<BlogDailyView> $daily
     * @param list<BlogMonthlyView> $monthly
     * @param list<BlogYearlyView> $yearly
     * @param list<PostTotalView> $topPosts
     * @param list<PostHistoryView> $postHistories
     */
    public function __construct(
        public int $totalViews,
        public array $daily,
        public array $monthly,
        public array $yearly,
        public array $topPosts,
        public array $postHistories,
        public CarbonImmutable $generatedAt,
    ) {}

    /**
     * @return array{
     *     total_views: int,
     *     daily: list<array{date: string, views: int}>,
     *     monthly: list<array{month: string, views: int}>,
     *     yearly: list<array{year: string, views: int}>,
     *     top_posts: list<array{blog_id: int, blog_title: string, blog_slug: string, views: int}>,
     *     post_histories: list<array{blog_id: int, blog_title: string, blog_slug: string, daily: list<array{date: string, views: int}>}>,
     *     generated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'total_views' => $this->totalViews,
            'daily' => array_map(
                static fn (BlogDailyView $d) => $d->toArray(),
                $this->daily,
            ),
            'monthly' => array_map(
                static fn (BlogMonthlyView $m) => $m->toArray(),
                $this->monthly,
            ),
            'yearly' => array_map(
                static fn (BlogYearlyView $y) => $y->toArray(),
                $this->yearly,
            ),
            'top_posts' => array_map(
                static fn (PostTotalView $t) => $t->toArray(),
                $this->topPosts,
            ),
            'post_histories' => array_map(
                static fn (PostHistoryView $p) => $p->toArray(),
                $this->postHistories,
            ),
            'generated_at' => $this->generatedAt->toISOString(),
        ];
    }
}
