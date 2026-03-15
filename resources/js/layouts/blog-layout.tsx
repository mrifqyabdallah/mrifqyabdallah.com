import type { ReactNode } from 'react';
import AppearanceToggleFloat from '@/components/appearance-toggle-float';

interface BlogLayoutProps {
    children: ReactNode;
    style?: React.CSSProperties;
}

export default function BlogLayout({ children, style }: BlogLayoutProps) {
    return (
        <div
            data-blog-layout
            className="min-h-screen bg-background text-foreground"
            style={{
                backgroundImage: `
                    radial-gradient(ellipse var(--fade-w) var(--fade-h) at var(--fade-pos), var(--bg) 0%, transparent 100%),
                    linear-gradient(var(--grid-color) 1px, transparent 1px),
                    linear-gradient(90deg, var(--grid-color) 1px, transparent 1px)
                `,
                backgroundSize: '100% 100%, 24px 24px, 24px 24px',
                ...style,
            }}
        >
            <style>{`
                [data-blog-layout] {
                    --grid-color: rgba(0,0,0,0.06);
                    --bg: rgba(255,255,255,1);
                    --fade-w: 100%;
                    --fade-h: 90%;
                    --fade-pos: 50% 50%;
                    background-attachment: fixed;
                }
                .dark [data-blog-layout] {
                    --grid-color: rgba(255,255,255,0.12);
                    --bg: rgba(9,9,11,1);
                }
            `}</style>
            {children}
            <AppearanceToggleFloat />
        </div>
    );
}
