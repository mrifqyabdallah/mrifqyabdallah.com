<?php

use App\Models\Blog;

it('returns 200 with rss+xml content type', function () {
    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
});

it('returns valid xml', function () {
    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
});

it('includes published blogs in the feed', function () {
    $blog = Blog::factory()->published()->create();

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertSeeText($blog->title)
        ->assertSeeText($blog->creator)
        ->assertSeeText(route('blog.show', $blog->slug));
});

it('excludes archived blogs from the feed', function () {
    $blog = Blog::factory()->archived()->create();

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertDontSeeText($blog->title);
});

it('excludes upcoming blogs from the feed', function () {
    $blog = Blog::factory()->upcoming()->create();

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertDontSeeText($blog->title);
});

it('orders blogs by published_at descending', function () {
    $older = Blog::factory()->published()->create(['published_at' => now()->subDays(10)]);
    $newer = Blog::factory()->published()->create(['published_at' => now()->subDay()]);

    $content = $this->get(route('blog.feed'))->content();

    expect(strpos($content, $newer->title))->toBeLessThan(strpos($content, $older->title));
});

it('limits the feed to 50 items', function () {
    Blog::factory()->published()->count(55)->create();

    $content = $this->get(route('blog.feed'))->content();
    $itemCount = substr_count($content, '<item>');

    expect($itemCount)->toBe(50);
});

it('includes blog tags as category elements', function () {
    Blog::factory()->published()->create(['tags' => ['laravel', 'php']]);

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertSeeText('laravel')
        ->assertSeeText('php');
});

it('escapes special characters in excerpt', function () {
    Blog::factory()->published()->create([
        'excerpt' => 'Learn about A & B, <strong>today</strong>',
    ]);

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertSee('Learn about A &amp; B', false);
});

it('sets cache control header', function () {
    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=3600, public');
});

it('includes the atom self link', function () {
    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertSee(route('blog.feed'), false);
});
