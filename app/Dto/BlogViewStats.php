<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

final readonly class BlogViewStats
{
    /**
     * @param  list<BlogDailyView>  $daily
     * @param  list<BlogMonthlyView>  $monthly
     * @param  list<BlogYearlyView>  $yearly
     * @param  list<PostTotalView>  $topPosts
     */
    public function __construct(
        public int $totalViews,
        public array $daily,
        public array $monthly,
        public array $yearly,
        public array $topPosts,
        public CarbonImmutable $generatedAt,
    ) {}

    /**
     * @return array{
     *     total_views: int,
     *     daily: array<BlogDailyView>,
     *     monthly: array<BlogMonthlyView>,
     *     yearly: array<BlogYearlyView>,
     *     top_posts: array<PostTotalView>,
     *     generated_at: ?string
     * }
     */
    public function toArray(): array
    {
        /** @var BlogDailyView[] $daily */
        $daily = array_map(
            static fn (BlogDailyView $d) => $d->toArray(),
            $this->daily,
        );

        /** @var BlogMonthlyView[] $monthly */
        $monthly = array_map(
            static fn (BlogMonthlyView $m) => $m->toArray(),
            $this->monthly,
        );

        /** @var BlogYearlyView[] $yearly */
        $yearly = array_map(
            static fn (BlogYearlyView $y) => $y->toArray(),
            $this->yearly,
        );

        /** @var PostTotalView[] $top_posts */
        $top_posts = array_map(
            static fn (PostTotalView $t) => $t->toArray(),
            $this->topPosts,
        );

        return [
            'total_views' => $this->totalViews,
            'daily' => $daily,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'top_posts' => $top_posts,
            'generated_at' => $this->generatedAt->toISOString(),
        ];
    }
}
