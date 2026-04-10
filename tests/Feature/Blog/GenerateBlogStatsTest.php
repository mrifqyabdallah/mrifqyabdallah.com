<?php

use App\Jobs\GenerateBlogStats;
use App\Models\Blog;
use App\Models\BlogView;
use App\Queries\BlogViewStatsQuery;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

function runBlogStatsJob(): void
{
    (new GenerateBlogStats)->handle();
}

function readOverviewJson(): array
{
    $contents = Storage::disk('public')->get('stats/blog/overview.json');
    expect($contents)->not->toBeNull('stats/blog/overview.json was not written');

    return json_decode($contents, associative: true);
}

it('writes overview json file', function (): void {
    Blog::factory()->create();

    runBlogStatsJob();

    Storage::disk('public')->assertExists('stats/blog/overview.json');
});

test('generated json contains correct structure', function (): void {
    Blog::factory()->create();
    runBlogStatsJob();

    expect(readOverviewJson())
        ->toHaveKey('total_views')
        ->toHaveKey('daily')
        ->toHaveKey('monthly')
        ->toHaveKey('yearly')
        ->toHaveKey('top_posts')
        ->toHaveKey('generated_at');
});

test('total_views reflects all blog views', function (): void {
    $a = Blog::factory()->create();
    $b = Blog::factory()->create();

    BlogView::factory()->for($a)->count(4)->create();
    BlogView::factory()->for($b)->count(6)->create();

    runBlogStatsJob();

    expect(readOverviewJson()['total_views'])->toBe(10);
});

test('total_views is zero when no views exist', function (): void {
    runBlogStatsJob();

    expect(readOverviewJson()['total_views'])->toBe(0);
});

test('daily, monthly, and yearly views in the generated json has correct structure', function (): void {
    $blog = Blog::factory()->create();
    BlogView::factory()->for($blog)->count(4)->create(['date' => today()->toDateString()]);

    runBlogStatsJob();

    $data = readOverviewJson();
    $daily_windows = BlogViewStatsQuery::DAILY_WINDOW_DAYS;
    $monthly_windows = BlogViewStatsQuery::MONTHLY_WINDOW_MONTHS;

    expect($data['daily'])->toHaveCount($daily_windows)
        ->and($data['daily'][0]['views'])->toBe(0)
        ->and($data['daily'][0]['date'])->toBe(today()->subDays($daily_windows - 1)->toDateString())
        ->and(end($data['daily'])['views'])->toBe(4)
        ->and(end($data['daily'])['date'])->toBe(today()->toDateString());

    expect($data['monthly'])->toHaveCount($monthly_windows)
        ->and($data['monthly'][0]['views'])->toBe(0)
        ->and($data['monthly'][0]['month'])->toBe(today()->subMonths($monthly_windows - 1)->format('Y-m'))
        ->and(end($data['monthly'])['views'])->toBe(4)
        ->and(end($data['monthly'])['month'])->toBe(today()->format('Y-m'));

    expect($data['yearly'])->toHaveCount(1)
        ->and($data['yearly'][0]['views'])->toBe(4)
        ->and($data['yearly'][0]['year'])->toBe(today()->format('Y'));
});

test('top_posts are ordered by views descending', function (): void {
    $popular = Blog::factory()->create();
    $quiet = Blog::factory()->create();

    BlogView::factory()->for($popular)->count(9)->create();
    BlogView::factory()->for($quiet)->count(2)->create();

    runBlogStatsJob();

    $topPosts = readOverviewJson()['top_posts'];

    expect($topPosts)->toHaveCount(2)
        ->and($topPosts[0]['blog_id'])->toBe($popular->id)
        ->and($topPosts[0]['views'])->toBe(9);
});

test('top_post entry has correct structure', function (): void {
    $blog = Blog::factory()->create(['title' => 'Hello World', 'slug' => 'hello-world']);
    BlogView::factory()->for($blog)->count(100)->create();

    runBlogStatsJob();

    $entry = readOverviewJson()['top_posts'][0];

    expect($entry)
        ->toHaveKey('blog_id')
        ->toHaveKey('blog_title')
        ->toHaveKey('blog_slug')
        ->toHaveKey('views');

    expect($entry['blog_title'])->toBe('Hello World')
        ->and($entry['blog_slug'])->toBe('hello-world')
        ->and($entry['views'])->toBe(100);
});

test('generated_at is an iso8601 string', function (): void {
    runBlogStatsJob();

    expect(readOverviewJson()['generated_at'])
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('overwrites existing file on re-run', function (): void {
    $blog = Blog::factory()->create();
    BlogView::factory()->for($blog)->count(1)->create();
    runBlogStatsJob();

    BlogView::factory()->for($blog)->count(4)->create();
    runBlogStatsJob();

    expect(readOverviewJson()['total_views'])->toBe(5);
});
