<?php

namespace App\Dto;

final readonly class PostMonthlyView
{
    public function __construct(
        /** YYYY-MM */
        public string $month,
        public int $views,
    ) {}

    /** @return array{month: string, views: int} */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'views' => $this->views,
        ];
    }
}
