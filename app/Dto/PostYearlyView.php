<?php

namespace App\Dto;

final readonly class PostYearlyView
{
    public function __construct(
        public string $year,
        public int $views,
    ) {}

    /** @return array{year: string, views: int} */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'views' => $this->views,
        ];
    }
}
