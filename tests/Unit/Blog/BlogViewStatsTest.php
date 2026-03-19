<?php

use App\Dto\BlogDailyView;
use App\Dto\BlogMonthlyView;
use App\Dto\BlogViewStats;
use App\Dto\BlogYearlyView;
use App\Dto\PostTotalView;
use Carbon\CarbonImmutable;

function makeBlogViewStats(
    int     $totalViews  = 0,
    array   $daily       = [],
    array   $monthly     = [],
    array   $yearly      = [],
    array   $topPosts    = [],
    ?CarbonImmutable $generatedAt = null,
): BlogViewStats {
    return new BlogViewStats(
        totalViews:  $totalViews,
        daily:       $daily,
        monthly:     $monthly,
        yearly:      $yearly,
        topPosts:    $topPosts,
        generatedAt: $generatedAt ?? now(),
    );
}

it('contains all required keys', function (): void {
    $array = makeBlogViewStats()->toArray();

    expect($array)
        ->toHaveKey('total_views')
        ->toHaveKey('daily')
        ->toHaveKey('monthly')
        ->toHaveKey('yearly')
        ->toHaveKey('top_posts')
        ->toHaveKey('generated_at');
});

it('maps total views', function (): void {
    expect(makeBlogViewStats(totalViews: 99)->toArray()['total_views'])->toBe(99);
});

it('serializes daily entries', function (): void {
    $array = makeBlogViewStats(
        daily: [new BlogDailyView(date: '2024-06-01', views: 5)],
    )->toArray();

    expect($array['daily'])->toHaveCount(1)
        ->and($array['daily'][0])->toBe(['date' => '2024-06-01', 'views' => 5]);
});

it('serializes monthly entries', function (): void {
    $array = makeBlogViewStats(
        monthly: [new BlogMonthlyView(month: '2024-06', views: 20)],
    )->toArray();

    expect($array['monthly'])->toHaveCount(1)
        ->and($array['monthly'][0])->toBe(['month' => '2024-06', 'views' => 20]);
});

it('serializes yearly entries', function (): void {
    $array = makeBlogViewStats(
        yearly: [new BlogYearlyView(year: '2024', views: 200)],
    )->toArray();

    expect($array['yearly'])->toHaveCount(1)
        ->and($array['yearly'][0])->toBe(['year' => '2024', 'views' => 200]);
});

it('serializes top posts', function (): void {
    $array = makeBlogViewStats(
        topPosts: [new PostTotalView(
            blogId:    1,
            blogTitle: 'Popular',
            blogSlug:  'popular',
            views:     50,
        )],
    )->toArray();

    expect($array['top_posts'])->toHaveCount(1)
        ->and($array['top_posts'][0])->toBe([
            'blog_id'    => 1,
            'blog_title' => 'Popular',
            'blog_slug'  => 'popular',
            'views'      => 50,
        ]);
});

it('formats generated_at as iso string', function (): void {
    $array = makeBlogViewStats(
        generatedAt: CarbonImmutable::parse('2024-06-15T08:30:00Z'),
    )->toArray();

    expect($array['generated_at'])->toStartWith('2024-06-15T');
});

it('defaults all collection fields to empty lists', function (): void {
    $array = makeBlogViewStats()->toArray();

    expect($array['daily'])->toBe([])
        ->and($array['monthly'])->toBe([])
        ->and($array['yearly'])->toBe([])
        ->and($array['top_posts'])->toBe([]);
});
