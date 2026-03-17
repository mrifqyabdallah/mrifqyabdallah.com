<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

final readonly class PostViewStats
{
    /**
     * @param list<PostDailyView> $daily
     * @param list<PostMonthlyView> $monthly
     * @param list<PostYearlyView> $yearly
     */
    public function __construct(
        public int $blogId,
        public string $blogTitle,
        public string $blogSlug,
        public int $totalViews,
        public array $daily,
        public array $monthly,
        public array $yearly,
        public CarbonImmutable $generatedAt,
    ) {}

    /**
     * @return array{
     *     blog_id: int,
     *     blog_title: string,
     *     blog_slug: string,
     *     total_views: int,
     *     daily: list<array{date: string, views: int}>,
     *     monthly: list<array{month: string, views: int}>,
     *     yearly: list<array{year: string, views: int}>,
     *     generated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'blog_id' => $this->blogId,
            'blog_title' => $this->blogTitle,
            'blog_slug' => $this->blogSlug,
            'total_views' => $this->totalViews,
            'daily' => array_map(
                static fn (PostDailyView $d) => $d->toArray(),
                $this->daily,
            ),
            'monthly' => array_map(
                static fn (PostMonthlyView $m) => $m->toArray(),
                $this->monthly,
            ),
            'yearly' => array_map(
                static fn (PostYearlyView $y) => $y->toArray(),
                $this->yearly,
            ),
            'generated_at' => $this->generatedAt->toISOString(),
        ];
    }
}
