import { Head, Link } from "@inertiajs/react";
import { ArrowRight, ScrollText } from "lucide-react";
import BlogLayout from '@/layouts/blog-layout';
import { index, show } from "@/routes/blog";

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
            <BlogLayout style={{ '--fade-w': '60%', '--fade-h': '80%', '--fade-pos': '50% 50%' } as React.CSSProperties}>
                <Head title="Post not found" />

                <div className="min-h-screen flex items-center justify-center px-4">
                    <div className="max-w-lg w-full text-center">
                        <p className="text-6xl font-bold text-muted-foreground/40 dark:text-muted-foreground/80 mb-4">404</p>
                        <h1 className="text-2xl font-bold tracking-tight mb-2">Post not found</h1>
                        <p className="text-muted-foreground mb-10">
                            This blog post doesn't exist or has been removed.
                        </p>

                        {latest.length > 0 && (
                            <div className="text-left mb-10">
                                <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground mb-4">
                                    Latest posts
                                </p>
                                <div className="space-y-3">
                                    {latest.map((blog) => (
                                        <Link
                                            key={blog.slug}
                                            href={show(blog.slug).url}
                                            className="flex items-start justify-between gap-4 p-3 rounded-lg border border-border hover:bg-muted transition-colors group"
                                        >
                                            <div className="min-w-0">
                                                <p className="font-medium text-sm truncate">{blog.title}</p>
                                                <p className="text-xs text-muted-foreground truncate mt-0.5">{blog.excerpt}</p>
                                            </div>
                                            <ArrowRight className="w-4 h-4 shrink-0 text-muted-foreground group-hover:text-foreground transition-colors mt-0.5" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="flex items-center justify-center gap-6 text-sm">
                            <Link
                                href={index().url}
                                className="inline-flex items-center gap-1.5 underline underline-offset-4 hover:text-primary transition-colors"
                            >
                                <ScrollText className="w-3.5 h-3.5" />
                                See all posts
                            </Link>
                        </div>
                    </div>
                </div>
            </BlogLayout>
        </>
    );
}