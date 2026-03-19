<?php

declare(strict_types=1);

use App\Dto\PostDailyView;
use App\Dto\PostMonthlyView;
use App\Dto\PostViewStats;
use App\Dto\PostYearlyView;
use Carbon\CarbonImmutable;

function makePostViewStats(
    int     $blogId     = 1,
    string  $blogTitle  = 'Test Post',
    string  $blogSlug   = 'test-post',
    int     $totalViews = 0,
    array   $daily      = [],
    array   $monthly    = [],
    array   $yearly     = [],
    ?CarbonImmutable $generatedAt = null,
): PostViewStats {
    return new PostViewStats(
        blogId:      $blogId,
        blogTitle:   $blogTitle,
        blogSlug:    $blogSlug,
        totalViews:  $totalViews,
        daily:       $daily,
        monthly:     $monthly,
        yearly:      $yearly,
        generatedAt: $generatedAt ?? now(),
    );
}

it('contains all required keys', function (): void {
    $array = makePostViewStats()->toArray();

    expect($array)
        ->toHaveKey('blog_id')
        ->toHaveKey('blog_title')
        ->toHaveKey('blog_slug')
        ->toHaveKey('total_views')
        ->toHaveKey('daily')
        ->toHaveKey('monthly')
        ->toHaveKey('yearly')
        ->toHaveKey('generated_at');
});

it('maps scalar fields correctly', function (): void {
    $array = makePostViewStats(
        blogId:     5,
        blogTitle:  'My Post',
        blogSlug:   'my-post',
        totalViews: 42,
    )->toArray();

    expect($array['blog_id'])->toBe(5)
        ->and($array['blog_title'])->toBe('My Post')
        ->and($array['blog_slug'])->toBe('my-post')
        ->and($array['total_views'])->toBe(42);
});

it('serializes daily entries', function (): void {
    $array = makePostViewStats(
        daily: [new PostDailyView(date: '2024-06-01', views: 3)],
    )->toArray();

    expect($array['daily'])->toHaveCount(1)
        ->and($array['daily'][0])->toBe(['date' => '2024-06-01', 'views' => 3]);
});

it('serializes monthly entries', function (): void {
    $array = makePostViewStats(
        monthly: [new PostMonthlyView(month: '2024-06', views: 15)],
    )->toArray();

    expect($array['monthly'])->toHaveCount(1)
        ->and($array['monthly'][0])->toBe(['month' => '2024-06', 'views' => 15]);
});

it('serializes yearly entries', function (): void {
    $array = makePostViewStats(
        yearly: [new PostYearlyView(year: '2024', views: 100)],
    )->toArray();

    expect($array['yearly'])->toHaveCount(1)
        ->and($array['yearly'][0])->toBe(['year' => '2024', 'views' => 100]);
});

it('formats generated_at as iso string', function (): void {
    $array = makePostViewStats(
        generatedAt: CarbonImmutable::parse('2024-06-15T10:00:00Z'),
    )->toArray();

    expect($array['generated_at'])->toStartWith('2024-06-15T');
});

it('defaults all collection fields to empty lists', function (): void {
    $array = makePostViewStats()->toArray();

    expect($array['daily'])->toBe([])
        ->and($array['monthly'])->toBe([])
        ->and($array['yearly'])->toBe([]);
});
