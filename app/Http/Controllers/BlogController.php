<?php

namespace App\Http\Controllers;

use App\Enums\BlogStatus;
use App\Exceptions\BlogNotFoundException;
use App\Jobs\RecordBlogView;
use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $tag = $request->string('tag')->trim()->value();

        $blogs = Blog::query()
            ->published()
            ->when($search, fn (Builder $q): Builder => $q->search($search))
            ->when($tag, fn (Builder $q): Builder => $q->whereTagged($tag))
            ->latest('published_at')
            ->cursorPaginate(10)
            ->withQueryString();

        return Inertia::render('blog/index', compact('search', 'tag', 'blogs'));
    }

    public function show(Request $request, string $slug): mixed
    {
        $blog = Blog::where('slug', $slug)->firstOr(function () {
            throw new BlogNotFoundException;
        });

        RecordBlogView::dispatch(
            blogId: $blog->id,
            userId: $request->user()?->id,
            visitorHash: $request->user()
                ? null
                : hash('sha256', $request->session()->getId()),
        );

        return Inertia::render('blog/show', [
            'blog' => $blog->only([
                'slug', 'title', 'creator', 'excerpt',
                'content', 'tags', 'status', 'published_at',
            ]),
            'viewCount' => $blog->view_count,
            'isArchived' => $blog->status == BlogStatus::Archived,
        ]);
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Blog $blog): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('delete', $blog);

        $blog->update(['status' => BlogStatus::Archived]);

        return redirect()
            ->route('blog.index')
            ->with('success', "'{$blog->title}' has been archived.");
    }
}
