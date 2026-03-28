import React, { useEffect, useState, useMemo } from 'react';
import type { Components } from 'react-markdown';
import ReactMarkdown from 'react-markdown';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import {
    oneLight,
    oneDark,
} from 'react-syntax-highlighter/dist/esm/styles/prism';
import rehypeRaw from 'rehype-raw';
import remarkGfm from 'remark-gfm';

function slugify(text: string): string {
    return text
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]/g, '');
}

interface MarkdownRendererProps {
    content: string;
}

export default function MarkdownRenderer({ content }: MarkdownRendererProps) {
    const [isDark, setIsDark] = useState(() =>
        document.documentElement.classList.contains('dark'),
    );

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setIsDark(document.documentElement.classList.contains('dark'));
        });
        observer.observe(document.documentElement, {
            attributeFilter: ['class'],
        });
        return () => observer.disconnect();
    }, []);

    const components = useMemo<Components>(() => {
        const makeHeading = (level: 1 | 2 | 3 | 4 | 5 | 6) => {
            return function Heading({
                children,
            }: {
                children: React.ReactNode;
            }) {
                const id = slugify(String(children));
                return React.createElement(
                    `h${level}`,
                    { id, className: 'scroll-mt-30' },
                    <a href={`#${id}`} className="no-underline hover:underline">
                        {children}
                    </a>,
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
            img({ src, alt }) {
                return (
                    <img
                        src={src}
                        alt={alt ?? ''}
                        loading="lazy"
                        className="rounded-lg border border-border"
                    />
                );
            },
            pre({ children }) {
                const child = React.Children.toArray(children)[0];
                if (React.isValidElement(child) && child.type === 'code') {
                    const { className, children: code } = child.props as {
                        className?: string;
                        children: string;
                    };
                    const lang =
                        /language-(\w+)/.exec(className ?? '')?.[1] ?? 'text';
                    const codeString = String(code).trimEnd();

                    return (
                        <div className="group relative">
                            <CopyButton code={codeString} />
                            <SyntaxHighlighter
                                language={lang}
                                style={isDark ? oneDark : oneLight}
                                customStyle={{
                                    margin: 0,
                                    borderRadius: 0,
                                    border: '1px solid var(--border)',
                                    fontSize: '0.875em',
                                    fontFamily:
                                        "'Cascadia Code Variable', monospace",
                                    fontWeight: 350,
                                }}
                                codeTagProps={{
                                    style: {
                                        fontFamily:
                                            "'Cascadia Code Variable', monospace",
                                        fontWeight: 350,
                                    },
                                }}
                            >
                                {codeString}
                            </SyntaxHighlighter>
                        </div>
                    );
                }
                return <pre>{children}</pre>;
            },
            p({ children, ...props }) {
                const text = String(children);
                const match = text.match(/^::youtube\[([a-zA-Z0-9_-]+)\]$/);
                if (match) {
                    return <YoutubeEmbed id={match[1]} />;
                }
                return <p {...props}>{children}</p>;
            },
        };
    }, [isDark]);

    return (
        <div className="prose prose-base max-w-none prose-neutral dark:prose-invert prose-headings:font-bold prose-headings:tracking-tight prose-a:text-primary prose-a:underline prose-code:rounded prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:text-sm prose-code:before:content-none prose-code:after:content-none prose-pre:border prose-pre:border-border prose-pre:bg-muted prose-img:rounded-lg prose-img:border prose-img:border-border">
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

function CopyButton({ code }: { code: string }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(code);
        } catch {
            const el = document.createElement('textarea');
            el.value = code;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <button
            onClick={handleCopy}
            className="absolute top-2 right-2 z-10 rounded border border-border bg-muted px-2 py-1 text-xs text-muted-foreground transition-opacity hover:text-foreground"
        >
            {copied ? 'Copied!' : 'Copy'}
        </button>
    );
}

function YoutubeEmbed({ id }: { id: string }) {
    const [active, setActive] = useState(false);
    const [loaded, setLoaded] = useState(false);

    if (!active) {
        return (
            <div
                className="relative my-6 aspect-video cursor-pointer overflow-hidden rounded-lg border border-border"
                onClick={() => setActive(true)}
            >
                <img
                    src={`https://i.ytimg.com/vi/${id}/hqdefault.jpg`}
                    alt="YouTube video thumbnail"
                    className="h-full w-full object-cover"
                />
                <div className="absolute inset-0 flex items-center justify-center">
                    <div className="rounded-full bg-black/70 px-5 py-3 text-2xl text-white">
                        ▶
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="relative my-6 aspect-video overflow-hidden rounded-lg border border-border">
            {!loaded && (
                <div className="absolute inset-0 flex items-center justify-center bg-muted">
                    <div className="h-8 w-8 animate-spin rounded-full border-4 border-border border-t-foreground" />
                </div>
            )}
            <iframe
                src={`https://www.youtube.com/embed/${id}?autoplay=1`}
                title="YouTube video"
                allowFullScreen
                onLoad={() => setLoaded(true)}
                className="absolute inset-0 h-full w-full"
            />
        </div>
    );
}
