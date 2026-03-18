import { Head, Link, router } from '@inertiajs/react';
import { Search, Tag, X, Rss, ChartNoAxesCombined } from 'lucide-react';
import { useState, useEffect, useRef, useCallback } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import BlogLayout from '@/layouts/blog-layout';
import { index, show, feed } from '@/routes/blog';
import { blog as stats } from '@/routes/stats';
import type { Blog, PaginatedBlogs } from '@/types/blog';

interface Props {
    blogs: PaginatedBlogs;
    search: string;
    tag: string;
}

export default function BlogIndex({
    blogs: initialBlogs,
    search: initialSearch,
    tag: initialTag,
}: Props) {
    const [blogs, setBlogs] = useState<Blog[]>(initialBlogs.data);
    const [nextCursor, setCursor] = useState<string | null>(
        initialBlogs.next_cursor,
    );
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(initialSearch ?? '');
    const [tag, setTag] = useState(initialTag ?? '');

    const sentinelRef = useRef<HTMLDivElement>(null);
    const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isFirstRender = useRef(true);

    // -------------------------------------------------------------------------
    // Reset list when filters change
    // -------------------------------------------------------------------------

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (searchTimer.current) clearTimeout(searchTimer.current);

        searchTimer.current = setTimeout(() => {
            router.get(
                index().url,
                { search: search || undefined, tag: tag || undefined },
                {
                    preserveState: true,
                    preserveScroll: false,
                    only: ['blogs', 'search', 'tag'],
                    onSuccess: (page) => {
                        const props = page.props as unknown as Props;
                        setBlogs(props.blogs.data);
                        setCursor(props.blogs.next_cursor);
                    },
                },
            );
        }, 300);

        return () => {
            if (searchTimer.current) clearTimeout(searchTimer.current);
        };
    }, [search, tag]);

    // -------------------------------------------------------------------------
    // Infinite scroll — load next page
    // -------------------------------------------------------------------------

    const loadMore = useCallback(async () => {
        if (!nextCursor || loading) return;

        setLoading(true);

        const params = new URLSearchParams();
        params.set('cursor', nextCursor);
        if (search) params.set('search', search);
        if (tag) params.set('tag', tag);

        const response = await fetch(`/blog?${params.toString()}`, {
            headers: {
                'X-Inertia': 'true',
                'X-Inertia-Partial-Data': 'blogs',
                'X-Inertia-Partial-Component': 'Blog/Index',
            },
        });

        const data = await response.json();
        setBlogs((prev) => [...prev, ...data.props.blogs.data]);
        setCursor(data.props.blogs.next_cursor);
        setLoading(false);
    }, [nextCursor, loading, search, tag]);

    useEffect(() => {
        const el = sentinelRef.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) loadMore();
            },
            { rootMargin: '200px' },
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [loadMore]);

    // -------------------------------------------------------------------------
    // Clear filters
    // -------------------------------------------------------------------------

    const clearFilters = () => {
        setSearch('');
        setTag('');
    };

    const hasFilters = search || tag;

    return (
        <BlogLayout>
            <Head title="Blog" />

            <div className="mx-auto max-w-3xl px-4 py-16">
                {/* Header */}
                <div className="mb-12">
                    <div className="mb-2 flex items-center gap-x-2">
                        <a href="/" title="Back to Homepage">
                            <AppLogoIcon className="size-8" />
                        </a>
                        <h1 className="text-4xl font-bold tracking-tight">
                            Community Blog
                        </h1>
                        <Link
                            href={stats().url}
                            className=" ml-auto text-muted-foreground transition-colors hover:text-foreground"
                            title="Statistics"
                        >
                            <ChartNoAxesCombined className="h-5 w-5" />
                        </Link>
                        <a
                            href={feed().url}
                            className="text-muted-foreground transition-colors hover:text-foreground"
                            title="RSS Feed"
                        >
                            <Rss className="h-5 w-5" />
                        </a>
                    </div>
                    <p className="mb-2 text-muted-foreground">
                        Thoughts, guides, and contributions from the community.
                    </p>
                    <Link
                        className="text-xs text-muted-foreground italic"
                        href="/blog/welcome-and-how-to-contribute"
                    >
                        Want to contribute? read here
                    </Link>
                </div>

                {/* Search + filter bar */}
                <div className="mb-8 space-y-3">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search posts..."
                            className="border-2 border-neutral-300 pl-9 dark:border-neutral-600"
                        />
                        {search && (
                            <button
                                onClick={() => setSearch('')}
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>

                    {tag && (
                        <div className="flex items-center gap-2 text-sm">
                            <span className="text-muted-foreground">
                                Filtered by tag:
                            </span>
                            <Badge
                                variant="secondary"
                                className="cursor-pointer gap-1 transition-colors hover:bg-destructive hover:text-white"
                                onClick={() => setTag('')}
                            >
                                <Tag className="h-3 w-3" />
                                {tag}
                                <X className="h-3 w-3" />
                            </Badge>
                        </div>
                    )}

                    {hasFilters && (
                        <button
                            onClick={clearFilters}
                            className="text-xs text-muted-foreground underline underline-offset-2 transition-colors hover:text-foreground"
                        >
                            Clear all filters
                        </button>
                    )}
                </div>

                {/* Blog list */}
                {blogs.length === 0 ? (
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="mb-1 text-lg">No posts found</p>
                        {hasFilters && (
                            <p className="text-sm">
                                Try a different search term or{' '}
                                <button
                                    onClick={clearFilters}
                                    className="underline underline-offset-2 transition-colors hover:text-foreground"
                                >
                                    clear filters
                                </button>
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="divide-y divide-neutral-300 dark:divide-neutral-600">
                        {blogs.map((blog) => (
                            <BlogCard
                                key={blog.slug}
                                blog={blog}
                                onTagClick={(t) => {
                                    setTag(t);
                                    setSearch('');
                                }}
                            />
                        ))}
                    </div>
                )}

                {/* Infinite scroll sentinel */}
                <div ref={sentinelRef} className="flex justify-center py-4">
                    {loading && (
                        <div className="h-5 w-5 animate-spin rounded-full border-2 border-border border-t-foreground" />
                    )}
                    {!loading && !nextCursor && blogs.length > 0 && (
                        <p className="text-xs text-muted-foreground">
                            You've reached the end.
                        </p>
                    )}
                </div>
            </div>
        </BlogLayout>
    );
}

// -----------------------------------------------------------------------------
// BlogCard
// -----------------------------------------------------------------------------

interface BlogCardProps {
    blog: Blog;
    onTagClick: (tag: string) => void;
}

function BlogCard({ blog, onTagClick }: BlogCardProps) {
    const date = new Date(blog.published_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <article className="group py-8">
            <Link href={show(blog.slug).url} className="block">
                <div className="mb-2 flex items-center gap-2 text-xs text-muted-foreground">
                    <time dateTime={blog.published_at}>{date}</time>
                    <span>·</span>
                    <span>{blog.creator}</span>
                </div>

                <h2 className="mb-2 text-xl font-semibold tracking-tight transition-colors group-hover:text-primary">
                    {blog.title}
                </h2>

                <p className="mb-4 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                    {blog.excerpt}
                </p>
            </Link>

            <div className="flex flex-wrap gap-1.5">
                {blog.tags.map((tag) => (
                    <button
                        key={tag}
                        onClick={() => onTagClick(tag)}
                        className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground transition-colors hover:bg-primary hover:text-primary-foreground"
                    >
                        <Tag className="h-2.5 w-2.5" />
                        {tag}
                    </button>
                ))}
            </div>
        </article>
    );
}
