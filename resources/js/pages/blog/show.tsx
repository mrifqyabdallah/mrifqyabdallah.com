import { Head, Link, router, usePage } from "@inertiajs/react";
import { Tag, Eye, Calendar, User, ArrowLeft, AlertTriangle, Trash2 } from "lucide-react";
import React, { useEffect, useState, useMemo } from "react";
import type { Components } from "react-markdown";
import ReactMarkdown from "react-markdown";
import rehypeRaw from "rehype-raw";
import remarkGfm from "remark-gfm";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import BlogLayout from '@/layouts/blog-layout';
import { index, destroy } from '@/routes/blog';
import type { Blog } from '@/types/blog';

interface Props {
    blog: Blog;
    viewCount: number;
    isArchived: boolean;
}

interface SharedProps {
    auth: {
        user: {
            is_admin: boolean;
        } | null;
    };
}

function slugify(text: string): string {
    return text
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]/g, '');
}

interface TocItem {
    id: string;
    text: string;
    level: number;
}

function extractHeadings(content: string): TocItem[] {
    const regex = /^(#{1,6})\s+(.+)$/gm;
    const items: TocItem[] = [];
    let match;

    while ((match = regex.exec(content)) !== null) {
        const level = match[1].length;
        const text  = match[2].trim();
        items.push({ id: slugify(text), text, level });
    }

    return items;
}

function TableOfContents({ items }: { items: TocItem[] }) {
    const [activeId, setActiveId] = useState<string>('');

    useEffect(() => {
        if (items.length === 0) return;

        const handleScroll = () => {
            const headings = items
                .map(({ id }) => document.getElementById(id))
                .filter(Boolean) as HTMLElement[];

            const center = window.innerHeight / 2;
            let closest: HTMLElement | null = null;
            let closestDist = Infinity;

            for (const el of headings) {
                const rect = el.getBoundingClientRect();
                const dist = Math.abs(rect.top - center);
                if (dist < closestDist) {
                    closestDist = dist;
                    closest = el;
                }
            }

            if (closest) setActiveId(closest.id);
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        return () => window.removeEventListener('scroll', handleScroll);
    }, [items]);

    if (items.length === 0) return null;

    const minLevel = Math.min(...items.map(i => i.level));

    const handleClick = (id: string) => {
        const el = document.getElementById(id);
        if (!el) return;

        const top = el.getBoundingClientRect().top
            + window.scrollY
            - (window.innerHeight / 2)
            + (el.offsetHeight / 2);

        window.scrollTo({ top, behavior: 'smooth' });
        window.history.pushState(null, '', `#${id}`);
        setActiveId(id);
    };

    return (
        <nav className="space-y-1">
            <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground mb-3">
                On this page
            </p>
            {items.map((item) => {
                const indent   = (item.level - minLevel) * 12;
                const isActive = activeId === item.id;

                return (
                    <button
                        key={item.id}
                        style={{ paddingLeft: `${indent}px` }}
                        onClick={() => handleClick(item.id)}
                        className={[
                            "block w-full text-left text-sm py-0.5 transition-colors truncate cursor-pointer",
                            "hover:text-foreground",
                            isActive
                                ? "text-foreground font-bold"
                                : "text-muted-foreground font-normal",
                        ].join(' ')}
                    >
                        {item.text}
                    </button>
                );
            })}
        </nav>
    );
}

export default function BlogShow({ blog, viewCount, isArchived }: Props) {
    const { auth } = usePage<SharedProps>().props;
    const isAdmin  = auth?.user?.is_admin ?? false;

    const tocItems = extractHeadings(blog.content);

    const publishedDate = new Date(blog.published_at).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });

    const handleDelete = () => {
        if (confirm(`Archive "${blog.title}"? It will no longer appear in the blog list.`)) {
            router.delete(destroy(blog.id).url);
        }
    };

    return (
        <BlogLayout style={{ '--fade-w': '70%', '--fade-h': '70%' } as React.CSSProperties}>
            <Head>
                <title>{blog.title}</title>
                <meta name="description" content={blog.excerpt} />
                <meta property="og:title" content={blog.title} />
                <meta property="og:description" content={blog.excerpt} />
                <meta property="og:type" content="article" />
                <meta property="article:published_time" content={blog.published_at} />
                <meta property="article:author" content={blog.creator} />
                {blog.tags.map((tag) => (
                    <meta key={tag} property="article:tag" content={tag} />
                ))}
            </Head>

            <div className="max-w-6xl mx-auto px-4 py-16">
                <div className="flex gap-16">

                    {/* Main content */}
                    <div className="min-w-0 flex-1 max-w-3xl">

                        <Link
                            href={index().url}
                            className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors mb-10"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            All posts
                        </Link>

                        {isArchived && (
                            <Alert className="mb-8 border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950">
                                <AlertTriangle className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                <AlertDescription className="text-amber-800 dark:text-amber-300">
                                    Sorry, this post has been archived and no longer available.
                                </AlertDescription>
                            </Alert>
                        )}

                        <header className={`mb-10 ${isArchived && "opacity-25 pointer-events-none select-none"}`}>
                            <h1 className="text-4xl font-bold tracking-tight leading-tight mb-4">
                                {blog.title}
                            </h1>

                            <p className="text-lg text-muted-foreground leading-relaxed mb-6">
                                {blog.excerpt}
                            </p>

                            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground pb-6 border-b-3 border-neutral-300 dark:border-neutral-600">
                                <span className="inline-flex items-center gap-1.5">
                                    <User className="w-3.5 h-3.5" />
                                    {blog.creator}
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <Calendar className="w-3.5 h-3.5" />
                                    <time dateTime={blog.published_at}>{publishedDate}</time>
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <Eye className="w-3.5 h-3.5" />
                                    {viewCount.toLocaleString()} {viewCount > 1 ? "views" : "view"}
                                </span>

                                {isAdmin && !isArchived && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleDelete}
                                        className="ml-auto text-destructive hover:text-destructive hover:bg-destructive/10 gap-1.5"
                                    >
                                        <Trash2 className="w-3.5 h-3.5" />
                                        Archive
                                    </Button>
                                )}
                            </div>

                            {blog.tags.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 pt-5">
                                    {blog.tags.map((tag) => (
                                        <Link key={tag} href={index({ query: { tag: tag } }).url}>
                                            <Badge
                                                variant="secondary"
                                                className="gap-1 hover:bg-primary hover:text-primary-foreground transition-colors cursor-pointer"
                                            >
                                                <Tag className="w-3 h-3" />
                                                {tag}
                                            </Badge>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </header>

                        {!isArchived && <MarkdownContent content={blog.content} />}
                    </div>

                    {/* TOC sidebar */}
                    {tocItems.length > 0 && (
                        <aside className="hidden xl:block w-56 shrink-0">
                            <div
                                className="sticky top-16 rounded-lg p-4"
                                style={{
                                    backgroundImage: `url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/><feColorMatrix type='saturate' values='0'/></filter><rect width='200' height='200' filter='url(%23n)' opacity='0.4'/></svg>")`,
                                    backgroundRepeat: 'repeat',
                                    backgroundSize: '200px 200px',
                                }}
                            >
                                <TableOfContents items={tocItems} />
                            </div>
                        </aside>
                    )}

                </div>
            </div>
        </BlogLayout>
    );
}

interface MarkdownContentProps {
    content: string;
}

function MarkdownContent({ content }: MarkdownContentProps) {
    const components = useMemo<Components>(() => {
        const makeHeading = (level: 1 | 2 | 3 | 4 | 5 | 6) => {
            return function Heading({ children }: { children: React.ReactNode }) {
                const id = slugify(String(children));
                return React.createElement(
                    `h${level}`,
                    { id, className: "scroll-mt-30" },
                    <a href={`#${id}`} className="no-underline hover:underline">
                        {children}
                    </a>
                );
            };
        };

        return {
            h1: makeHeading(1),
            h2: makeHeading(2),
            h3: makeHeading(3),
            h4: makeHeading(4),
            h5: makeHeading(5),
            h6: makeHeading(6),
            p({ children, ...props }) {
                const text = String(children);
                const match = text.match(/^::youtube\[([a-zA-Z0-9_-]+)\]$/);
                if (match) {
                    return (
                        <div className="relative aspect-video my-6 rounded-lg overflow-hidden border border-border">
                            <iframe
                                src={`https://www.youtube.com/embed/${match[1]}`}
                                title="YouTube video"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowFullScreen
                                className="absolute inset-0 w-full h-full"
                            />
                        </div>
                    );
                }
                return <p {...props}>{children}</p>;
            },
        };
    }, []);

    return (
        <div className="prose prose-neutral dark:prose-invert prose-base max-w-none
            prose-headings:font-bold prose-headings:tracking-tight
            prose-a:text-primary prose-a:underline
            prose-code:before:content-none prose-code:after:content-none
            prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm
            prose-pre:bg-muted prose-pre:border prose-pre:border-border
            prose-img:rounded-lg prose-img:border prose-img:border-border
        ">
            <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                rehypePlugins={[rehypeRaw]}
                components={components}
            >
                {content}
            </ReactMarkdown>
        </div>
    );
}
