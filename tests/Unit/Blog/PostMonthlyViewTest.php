<?php

declare(strict_types=1);

use App\Dto\PostMonthlyView;

it('returns correct array shape', function (): void {
    $dto = new PostMonthlyView(month: '2024-06', views: 42);

    expect($dto->toArray())->toBe([
        'month' => '2024-06',
        'views' => 42,
    ]);
});

it('exposes properties directly', function (): void {
    $dto = new PostMonthlyView(month: '2024-06', views: 42);

    expect($dto->month)->toBe('2024-06')
        ->and($dto->views)->toBe(42);
});

it('handles zero views', function (): void {
    $dto = new PostMonthlyView(month: '2024-06', views: 0);

    expect($dto->toArray()['views'])->toBe(0);
});
