<?php

use App\Dto\BlogDailyView;

it('returns correct array shape', function (): void {
    $dto = new BlogDailyView(date: '2024-06-01', views: 5);

    expect($dto->toArray())->toBe([
        'date'  => '2024-06-01',
        'views' => 5,
    ]);
});

it('exposes properties directly', function (): void {
    $dto = new BlogDailyView(date: '2024-06-01', views: 5);

    expect($dto->date)->toBe('2024-06-01')
        ->and($dto->views)->toBe(5);
});

it('handles zero views', function (): void {
    $dto = new BlogDailyView(date: '2024-06-01', views: 0);

    expect($dto->toArray()['views'])->toBe(0);
});
