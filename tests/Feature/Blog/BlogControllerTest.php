<?php

use App\Enums\BlogStatus;
use App\Jobs\RecordBlogView;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

// ---------------------------------------------------------------------------
// GET /blog (index)
// ---------------------------------------------------------------------------

describe('GET /blog', function () {
    it('returns the blog index page', function () {
        Blog::factory()->published()->count(3)->create();

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('blog/index')
                ->has('blogs.data', 3)
            );
    });

    it('only shows published blogs', function () {
        Blog::factory()->published()->count(2)->create();
        Blog::factory()->archived()->create();

        $this->get(route('blog.index'))
            ->assertInertia(fn ($page) => $page
                ->has('blogs.data', 2)
            );
    });

    it('excludes upcoming blogs', function () {
        Blog::factory()->published()->count(2)->create();
        Blog::factory()->upcoming()->create();

        $this->get(route('blog.index'))
            ->assertInertia(fn ($page) => $page
                ->has('blogs.data', 2)
            );
    });

    it('filters by search term', function () {
        Blog::factory()->published()->create(['title' => 'Laravel Tips']);
        Blog::factory()->published()->create(['title' => 'React Patterns']);

        $this->get(route('blog.index', ['search' => 'Laravel']))
            ->assertInertia(fn ($page) => $page
                ->has('blogs.data', 1)
            );
    });

    it('filters by tag', function () {
        Blog::factory()->published()->create(['tags' => ['laravel', 'php']]);
        Blog::factory()->published()->create(['tags' => ['react']]);

        $this->get(route('blog.index', ['tag' => 'laravel']))
            ->assertInertia(fn ($page) => $page
                ->has('blogs.data', 1)
            );
    });

    it('passes search and tag props to the page', function () {
        $this->get(route('blog.index', ['search' => 'foo', 'tag' => 'bar']))
            ->assertInertia(fn ($page) => $page
                ->where('search', 'foo')
                ->where('tag', 'bar')
            );
    });

    it('paginates results', function () {
        Blog::factory()->published()->count(15)->create();

        $this->get(route('blog.index'))
            ->assertInertia(fn ($page) => $page
                ->has('blogs.data', 10)
                ->has('blogs.next_cursor')
            );
    });
});

// ---------------------------------------------------------------------------
// GET /blog/{slug} (show)
// ---------------------------------------------------------------------------

describe('GET /blog/{slug}', function () {
    it('shows a published blog', function () {
        $blog = Blog::factory()->published()->create();

        $this->get(route('blog.show', $blog->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('blog/show')
                ->where('blog.slug', $blog->slug)
                ->where('isArchived', false)
            );
    });

    it('shows archived blog with isArchived flag', function () {
        $blog = Blog::factory()->archived()->create();

        $this->get(route('blog.show', $blog->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('isArchived', true));
    });

    it('returns 404 with latest blogs when slug not found', function () {
        Blog::factory()->published()->count(3)->create();

        $this->get(route('blog.show', 'non-existent-slug'))
            ->assertStatus(404)
            ->assertInertia(fn ($page) => $page
                ->component('blog/not-found')
                ->has('latest', 3)
            );
    });

    it('dispatches RecordBlogView job for guests', function () {
        $blog = Blog::factory()->published()->create();

        $this->get(route('blog.show', $blog->slug));

        Queue::assertPushed(RecordBlogView::class, fn ($job) => $job->blogId === $blog->id &&
            $job->userId === null &&
            $job->visitorHash !== null
        );
    });

    it('dispatches RecordBlogView job with user id for authenticated users', function () {
        $blog = Blog::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('blog.show', $blog->slug));

        Queue::assertPushed(RecordBlogView::class, fn ($job) => $job->blogId === $blog->id &&
            $job->userId === $user->id &&
            $job->visitorHash === null
        );
    });
});

// ---------------------------------------------------------------------------
// DELETE /blog/{blog} (destroy)
// ---------------------------------------------------------------------------

describe('DELETE /blog/{blog}', function () {
    it('allows admin to archive a blog', function () {
        $admin = User::factory()->create(['is_admin' => true]);
        $blog = Blog::factory()->published()->create();

        $this->actingAs($admin)
            ->delete(route('blog.destroy', $blog))
            ->assertRedirect(route('blog.index'))
            ->assertSessionHas('success');

        expect($blog->fresh()->status)->toBe(BlogStatus::Archived);
    });

    it('forbids non-admin from archiving a blog', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $blog = Blog::factory()->published()->create();

        $this->actingAs($user)
            ->delete(route('blog.destroy', $blog))
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        $blog = Blog::factory()->published()->create();

        $this->delete(route('blog.destroy', $blog))
            ->assertRedirect(route('login'));
    });
});
