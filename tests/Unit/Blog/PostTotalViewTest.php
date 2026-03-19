<?php

declare(strict_types=1);

use App\Dto\PostTotalView;

it('returns correct array shape', function (): void {
    $dto = new PostTotalView(
        blogId:    1,
        blogTitle: 'Hello World',
        blogSlug:  'hello-world',
        views:     99,
    );

    expect($dto->toArray())->toBe([
        'blog_id'    => 1,
        'blog_title' => 'Hello World',
        'blog_slug'  => 'hello-world',
        'views'      => 99,
    ]);
});

it('exposes properties directly', function (): void {
    $dto = new PostTotalView(
        blogId:    2,
        blogTitle: 'My Post',
        blogSlug:  'my-post',
        views:     10,
    );

    expect($dto->blogId)->toBe(2)
        ->and($dto->blogTitle)->toBe('My Post')
        ->and($dto->blogSlug)->toBe('my-post')
        ->and($dto->views)->toBe(10);
});
