<?php

namespace App\Dto;

final readonly class PostTotalView
{
    public function __construct(
        public int $blogId,
        public string $blogTitle,
        public string $blogSlug,
        public int $views,
    ) {}

    /** @return array{blog_id: int, blog_title: string, blog_slug: string, views: int} */
    public function toArray(): array
    {
        return [
            'blog_id' => $this->blogId,
            'blog_title' => $this->blogTitle,
            'blog_slug' => $this->blogSlug,
            'views' => $this->views,
        ];
    }
}
