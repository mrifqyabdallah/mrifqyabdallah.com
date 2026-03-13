<?php

use App\Jobs\RecordBlogView;
use App\Models\Blog;
use App\Models\BlogView;
use App\Models\User;

// ---------------------------------------------------------------------------
// Guest view deduplication
// ---------------------------------------------------------------------------

describe('RecordBlogView — guest', function () {
    it('creates a view record for a guest', function () {
        $blog = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync(
            blogId: $blog->id,
            userId: null,
            visitorHash: 'abc123',
        );

        expect(BlogView::count())->toBe(1);
    });

    it('does not duplicate guest view on same day', function () {
        $blog = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync($blog->id, null, 'abc123');
        RecordBlogView::dispatchSync($blog->id, null, 'abc123');

        expect(BlogView::count())->toBe(1);
    });

    it('counts a new view on a different day', function () {
        $blog = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync($blog->id, null, 'abc123');

        $this->travel(1)->days();

        RecordBlogView::dispatchSync($blog->id, null, 'abc123');

        expect(BlogView::count())->toBe(2);
    });

    it('counts different visitors separately on same day', function () {
        $blog = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync($blog->id, null, 'visitor-a');
        RecordBlogView::dispatchSync($blog->id, null, 'visitor-b');

        expect(BlogView::count())->toBe(2);
    });

    it('does nothing when visitor hash is null', function () {
        $blog = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync($blog->id, null, null);

        expect(BlogView::count())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Authenticated user view deduplication
// ---------------------------------------------------------------------------

describe('RecordBlogView — authenticated user', function () {
    it('creates a view record for an authenticated user', function () {
        $blog = Blog::factory()->published()->create();
        $user = User::factory()->create();

        RecordBlogView::dispatchSync($blog->id, $user->id, null);

        expect(BlogView::count())->toBe(1);
        expect(BlogView::first()->user_id)->toBe($user->id);
    });

    it('does not duplicate auth user view on same day', function () {
        $blog = Blog::factory()->published()->create();
        $user = User::factory()->create();

        RecordBlogView::dispatchSync($blog->id, $user->id, null);
        RecordBlogView::dispatchSync($blog->id, $user->id, null);

        expect(BlogView::count())->toBe(1);
    });

    it('counts a new view on a different day', function () {
        $blog = Blog::factory()->published()->create();
        $user = User::factory()->create();

        RecordBlogView::dispatchSync($blog->id, $user->id, null);

        $this->travel(1)->days();

        RecordBlogView::dispatchSync($blog->id, $user->id, null);

        expect(BlogView::count())->toBe(2);
    });

    it('counts different users separately on same day', function () {
        $blog = Blog::factory()->published()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        RecordBlogView::dispatchSync($blog->id, $userA->id, null);
        RecordBlogView::dispatchSync($blog->id, $userB->id, null);

        expect(BlogView::count())->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// Cross-blog isolation
// ---------------------------------------------------------------------------

describe('RecordBlogView — cross-blog', function () {
    it('tracks views per blog independently', function () {
        $blogA = Blog::factory()->published()->create();
        $blogB = Blog::factory()->published()->create();

        RecordBlogView::dispatchSync($blogA->id, null, 'visitor-x');
        RecordBlogView::dispatchSync($blogB->id, null, 'visitor-x');

        expect(BlogView::count())->toBe(2);
    });
});
