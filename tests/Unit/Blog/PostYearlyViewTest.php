<?php

declare(strict_types=1);

use App\Dto\PostYearlyView;

it('returns correct array shape', function (): void {
    $dto = new PostYearlyView(year: '2024', views: 310);

    expect($dto->toArray())->toBe([
        'year'  => '2024',
        'views' => 310,
    ]);
});

it('exposes properties directly', function (): void {
    $dto = new PostYearlyView(year: '2024', views: 310);

    expect($dto->year)->toBe('2024')
        ->and($dto->views)->toBe(310);
});

it('handles zero views', function (): void {
    $dto = new PostYearlyView(year: '2024', views: 0);

    expect($dto->toArray()['views'])->toBe(0);
});
