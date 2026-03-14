import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ScrollText } from 'lucide-react';
import BlogLayout from '@/layouts/blog-layout';
import { index, show } from '@/routes/blog';

interface LatestBlog {
    slug: string;
    title: string;
    excerpt: string;
    published_at: string;
}

interface Props {
    latest: LatestBlog[];
}

export default function BlogNotFound({ latest }: Props) {
    return (
        <>
            <BlogLayout
                style={
                    {
                        '--fade-w': '60%',
                        '--fade-h': '80%',
                        '--fade-pos': '50% 50%',
                    } as React.CSSProperties
                }
            >
                <Head title="Post not found" />

                <div className="flex min-h-screen items-center justify-center px-4">
                    <div className="w-full max-w-lg text-center">
                        <p className="mb-4 text-6xl font-bold text-muted-foreground/40 dark:text-muted-foreground/80">
                            404
                        </p>
                        <h1 className="mb-2 text-2xl font-bold tracking-tight">
                            Post not found
                        </h1>
                        <p className="mb-10 text-muted-foreground">
                            This blog post doesn't exist or has been removed.
                        </p>

                        {latest.length > 0 && (
                            <div className="mb-10 text-left">
                                <p className="mb-4 text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                    Latest posts
                                </p>
                                <div className="space-y-3">
                                    {latest.map((blog) => (
                                        <Link
                                            key={blog.slug}
                                            href={show(blog.slug).url}
                                            className="group flex items-start justify-between gap-4 rounded-lg border border-border p-3 transition-colors hover:bg-muted"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {blog.title}
                                                </p>
                                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                    {blog.excerpt}
                                                </p>
                                            </div>
                                            <ArrowRight className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground transition-colors group-hover:text-foreground" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="flex items-center justify-center gap-6 text-sm">
                            <Link
                                href={index().url}
                                className="inline-flex items-center gap-1.5 underline underline-offset-4 transition-colors hover:text-primary"
                            >
                                <ScrollText className="h-3.5 w-3.5" />
                                See all posts
                            </Link>
                        </div>
                    </div>
                </div>
            </BlogLayout>
        </>
    );
}
