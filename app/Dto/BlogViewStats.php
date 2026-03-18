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
     */
    public function __construct(
        public int $totalViews,
        public array $daily,
        public array $monthly,
        public array $yearly,
        public array $topPosts,
        public CarbonImmutable $generatedAt,
    ) {}

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
            'generated_at' => $this->generatedAt->toISOString(),
        ];
    }
}
