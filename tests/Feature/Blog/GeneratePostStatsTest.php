<?php

use App\Jobs\GeneratePostStats;
use App\Models\Blog;
use App\Models\BlogView;
use App\Queries\BlogViewStatsQuery;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

function runPostStatsJob(int $blogId): void
{
    (new GeneratePostStats($blogId))->handle();
}

function readPublicJson(string $path): array
{
    $contents = Storage::disk('public')->get($path);
    expect($contents)->not->toBeNull();

    return json_decode($contents, associative: true);
}

it('writes json file for post', function (): void {
    $blog = Blog::factory()->create();
    BlogView::factory()->for($blog)->count(3)->create(['date' => today()->toDateString()]);

    runPostStatsJob($blog->id);

    Storage::disk('public')->assertExists("stats/blogpost/{$blog->id}.json");
});

test('generated json file contains correct structure', function (): void {
    $blog = Blog::factory()->create(['title' => 'My Post', 'slug' => 'my-post']);
    BlogView::factory()->for($blog)->count(2)->create(['date' => today()->toDateString()]);

    runPostStatsJob($blog->id);

    $data = readPublicJson("stats/blogpost/{$blog->id}.json");

    expect($data['blog_id'])->toBe($blog->id)
        ->and($data['blog_title'])->toBe('My Post')
        ->and($data['blog_slug'])->toBe('my-post')
        ->and($data['total_views'])->toBe(2)
        ->and($data)
        ->toHaveKey('daily')
        ->toHaveKey('monthly')
        ->toHaveKey('yearly')
        ->toHaveKey('generated_at');
});

test('total_views is sum across all years', function (): void {
    $blog = Blog::factory()->create();
    BlogView::factory()->for($blog)->count(3)->create(['date' => '2023-01-10']);
    BlogView::factory()->for($blog)->count(5)->create(['date' => today()->toDateString()]);

    runPostStatsJob($blog->id);

    expect(readPublicJson("stats/blogpost/{$blog->id}.json")['total_views'])->toBe(8);
});

test('daily() generates data from the last 30 days, capped by the post\'s publication date', function (): void {
    $oldBlog = Blog::factory()->create(['published_at' => today()->subDays(29)]);
    $newerBlog = Blog::factory()->create(['published_at' => today()->subDays(28)]);
    $newestBlog = Blog::factory()->create(['published_at' => today()->subDays(6)]);

    collect([$oldBlog, $newerBlog, $newestBlog])->each(function (Blog $blog) {
        BlogView::factory()->for($blog)->count(4)->create(['date' => today()->toDateString()]);

        runPostStatsJob($blog->id);
    });

    expect(readPublicJson("stats/blogpost/{$oldBlog->id}.json")['daily'])->toHaveCount(30);
    expect(readPublicJson("stats/blogpost/{$newerBlog->id}.json")['daily'])->toHaveCount(29);
    expect(readPublicJson("stats/blogpost/{$newestBlog->id}.json")['daily'])->toHaveCount(7);
});

test('monthly() generates data from the last 12 months, capped by the post\'s publication month', function (): void {
    $oldBlog = Blog::factory()->create(['published_at' => today()->subMonths(11)]);
    $newerBlog = Blog::factory()->create(['published_at' => today()->subMonths(10)]);
    $newestBlog = Blog::factory()->create(['published_at' => today()->subMonths(2)]);

    collect([$oldBlog, $newerBlog, $newestBlog])->each(function (Blog $blog) {
        BlogView::factory()->for($blog)->count(4)->create(['date' => today()->toDateString()]);

        runPostStatsJob($blog->id);
    });

    expect(readPublicJson("stats/blogpost/{$oldBlog->id}.json")['monthly'])->toHaveCount(12);
    expect(readPublicJson("stats/blogpost/{$newerBlog->id}.json")['monthly'])->toHaveCount(11);
    expect(readPublicJson("stats/blogpost/{$newestBlog->id}.json")['monthly'])->toHaveCount(3);
});

test('daily, monthly, and yearly views in the generated json has correct structure', function (): void {
    $blog = Blog::factory()->create(['published_at' => today()->subYear()]);
    BlogView::factory()->for($blog)->count(4)->create(['date' => today()->toDateString()]);

    runPostStatsJob($blog->id);

    $data = readPublicJson("stats/blogpost/{$blog->id}.json");

    expect(end($data['daily'])['views'])->toBe(4)
        ->and(end($data['daily'])['date'])->toBe(today()->toDateString())
        ->and($data['daily'][0]['views'])->toBe(0)
        ->and($data['daily'][0]['date'])->toBe(today()->subDays(BlogViewStatsQuery::DAILY_WINDOW_DAYS - 1)->toDateString());

    expect(end($data['monthly'])['views'])->toBe(4)
        ->and(end($data['monthly'])['month'])->toBe(today()->format('Y-m'))
        ->and($data['monthly'][0]['views'])->toBe(0)
        ->and($data['monthly'][0]['month'])->toBe(today()->subMonths(BlogViewStatsQuery::MONTHLY_WINDOW_MONTHS - 1)->format('Y-m'));

    expect(end($data['yearly'])['views'])->toBe(4)
        ->and(end($data['yearly'])['year'])->toBe(today()->format('Y'));
});

test('generated_at is an iso8601 string', function (): void {
    $blog = Blog::factory()->create();

    runPostStatsJob($blog->id);

    $data = readPublicJson("stats/blogpost/{$blog->id}.json");

    expect($data['generated_at'])
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('silently exits when blog does not exist', function (): void {
    runPostStatsJob(9999);

    Storage::disk('public')->assertMissing('stats/blogs/9999.json');
});

it('overwrites existing file on re-run', function (): void {
    $blog = Blog::factory()->create();
    BlogView::factory()->for($blog)->count(1)->create(['date' => today()->toDateString()]);
    runPostStatsJob($blog->id);

    BlogView::factory()->for($blog)->count(2)->create(['date' => today()->toDateString()]);
    runPostStatsJob($blog->id);

    expect(readPublicJson("stats/blogpost/{$blog->id}.json")['total_views'])->toBe(3);
});
