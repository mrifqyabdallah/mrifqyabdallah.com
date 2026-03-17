<?php

namespace App\Dto;

final readonly class PostHistoryView
{
    /**
     * @param list<PostDailyView> $daily
     */
    public function __construct(
        public int $blogId,
        public string $blogTitle,
        public string $blogSlug,
        public array $daily,
    ) {}

    /**
     * @return array{
     *     blog_id: int,
     *     blog_title: string,
     *     blog_slug: string,
     *     daily: list<array{date: string, views: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'blog_id' => $this->blogId,
            'blog_title' => $this->blogTitle,
            'blog_slug' => $this->blogSlug,
            'daily' => array_map(
                static fn (PostDailyView $d) => $d->toArray(),
                $this->daily,
            ),
        ];
    }
}
