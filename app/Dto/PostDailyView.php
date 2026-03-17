<?php

namespace App\Dto;

final readonly class PostDailyView
{
    public function __construct(
        /** YYYY-MM-DD */
        public string $date,
        public int $views,
    ) {}

    /** @return array{date: string, views: int} */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'views' => $this->views,
        ];
    }
}
