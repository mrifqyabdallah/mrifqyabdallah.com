import { useState, useEffect, useRef, useCallback } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { Search, Tag, X, Rss, ArrowLeft } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { index, show, feed } from '@/routes/blog';
import { Blog, PaginatedBlogs } from '@/types/blog'
import AppLogoIcon from '@/components/app-logo-icon';

interface Props {
    blogs: PaginatedBlogs;
    search: string;
    tag: string;
}

export default function BlogIndex({ blogs: initialBlogs, search: initialSearch, tag: initialTag }: Props) {
    const [blogs, setBlogs]       = useState<Blog[]>(initialBlogs.data);
    const [nextCursor, setCursor] = useState<string | null>(initialBlogs.next_cursor);
    const [loading, setLoading]   = useState(false);
    const [search, setSearch]     = useState(initialSearch ?? "");
    const [tag, setTag]           = useState(initialTag ?? "");

    const sentinelRef   = useRef<HTMLDivElement>(null);
    const searchTimer   = useRef<ReturnType<typeof setTimeout> | null>(null);
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
                    only: ["blogs", "search", "tag"],
                    onSuccess: (page) => {
                        const props = page.props as unknown as Props;
                        setBlogs(props.blogs.data);
                        setCursor(props.blogs.next_cursor);
                    },
                }
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
            headers: { 'X-Inertia': 'true', 'X-Inertia-Partial-Data': 'blogs', 'X-Inertia-Partial-Component': 'Blog/Index' },
        });

        const data = await response.json();
        setBlogs(prev => [...prev, ...data.props.blogs.data]);
        setCursor(data.props.blogs.next_cursor);
        setLoading(false);
    }, [nextCursor, loading, search, tag]);

    useEffect(() => {
        const el = sentinelRef.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            (entries) => { if (entries[0].isIntersecting) loadMore(); },
            { rootMargin: "200px" }
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [loadMore]);

    // -------------------------------------------------------------------------
    // Clear filters
    // -------------------------------------------------------------------------

    const clearFilters = () => {
        setSearch("");
        setTag("");
    };

    const hasFilters = search || tag;

    return (
        <>
            <Head title="Blog" />

            <div className="min-h-screen bg-background">
                <div className="max-w-3xl mx-auto px-4 py-16">

                    {/* Header */}
                    <div className="mb-12">
                        <div className="flex items-center gap-x-2 mb-2">
                            <a
                                href="/"
                                title="Back to Homepage"
                            >
                                <AppLogoIcon className="size-8" />
                            </a>
                            <h1 className="text-4xl font-bold tracking-tight">Community Blog</h1>
                            <a
                                href={feed().url}
                                className="text-muted-foreground hover:text-foreground transition-colors ml-auto"
                                title="RSS Feed"
                            >
                                <Rss className="w-5 h-5" />
                            </a>
                        </div>
                        <p className="text-muted-foreground mb-2    ">
                            Thoughts, guides, and contributions from the community.
                        </p>
                        <Link className="text-muted-foreground text-xs italic"
                            href="/blog/welcome-and-how-to-contribute"
                        >
                            Want to contribute? read here
                        </Link>
                    </div>

                    {/* Search + filter bar */}
                    <div className="mb-8 space-y-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search posts..."
                                className="pl-9"
                            />
                            {search && (
                                <button
                                    onClick={() => setSearch("")}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                        </div>

                        {tag && (
                            <div className="flex items-center gap-2 text-sm">
                                <span className="text-muted-foreground">Filtered by tag:</span>
                                <Badge
                                    variant="secondary"
                                    className="gap-1 cursor-pointer hover:bg-destructive hover:text-white transition-colors"
                                    onClick={() => setTag("")}
                                >
                                    <Tag className="w-3 h-3" />
                                    {tag}
                                    <X className="w-3 h-3" />
                                </Badge>
                            </div>
                        )}

                        {hasFilters && (
                            <button
                                onClick={clearFilters}
                                className="text-xs text-muted-foreground hover:text-foreground underline underline-offset-2 transition-colors"
                            >
                                Clear all filters
                            </button>
                        )}
                    </div>

                    {/* Blog list */}
                    {blogs.length === 0 ? (
                        <div className="text-center py-20 text-muted-foreground">
                            <p className="text-lg mb-1">No posts found</p>
                            {hasFilters && (
                                <p className="text-sm">
                                    Try a different search term or{" "}
                                    <button
                                        onClick={clearFilters}
                                        className="underline underline-offset-2 hover:text-foreground transition-colors"
                                    >
                                        clear filters
                                    </button>
                                </p>
                            )}
                        </div>
                    ) : (
                        <div className="divide-y divide-border">
                            {blogs.map((blog) => (
                                <BlogCard
                                    key={blog.slug}
                                    blog={blog}
                                    onTagClick={(t) => { setTag(t); setSearch(""); }}
                                />
                            ))}
                        </div>
                    )}

                    {/* Infinite scroll sentinel */}
                    <div ref={sentinelRef} className="py-4 flex justify-center">
                        {loading && (
                            <div className="w-5 h-5 border-2 border-border border-t-foreground rounded-full animate-spin" />
                        )}
                        {!loading && !nextCursor && blogs.length > 0 && (
                            <p className="text-xs text-muted-foreground">You've reached the end.</p>
                        )}
                    </div>
                </div>
            </div>
        </>
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
    const date = new Date(blog.published_at).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });

    return (
        <article className="py-8 group">
            <Link href={show(blog.slug).url} className="block">
                <div className="flex items-center gap-2 text-xs text-muted-foreground mb-2">
                    <time dateTime={blog.published_at}>{date}</time>
                    <span>·</span>
                    <span>{blog.creator}</span>
                </div>

                <h2 className="text-xl font-semibold tracking-tight mb-2 group-hover:text-primary transition-colors">
                    {blog.title}
                </h2>

                <p className="text-muted-foreground text-sm leading-relaxed mb-4 line-clamp-2">
                    {blog.excerpt}
                </p>
            </Link>

            <div className="flex flex-wrap gap-1.5">
                {blog.tags.map((tag) => (
                    <button
                        key={tag}
                        onClick={() => onTagClick(tag)}
                        className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-muted text-muted-foreground hover:bg-primary hover:text-primary-foreground transition-colors"
                    >
                        <Tag className="w-2.5 h-2.5" />
                        {tag}
                    </button>
                ))}
            </div>
        </article>
    );
}
