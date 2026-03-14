import type { LucideIcon } from 'lucide-react';
import { SunMoon, Moon, Sun } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const icons: Record<Appearance, LucideIcon> = {
    light: Sun,
    dark: Moon,
    system: SunMoon,
};

export default function AppearanceToggleFloat({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: SunMoon, label: 'System' },
    ];

    // Close on outside click
    useEffect(() => {
        function handleClick(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    const ActiveIcon = icons[appearance];

    return (
        <div
            ref={ref}
            className={cn(
                'fixed right-5 bottom-5 z-50 flex flex-col items-end gap-2',
                className,
            )}
            {...props}
        >
            {/* Option buttons — slide up when open */}
            <div
                className={cn(
                    'flex flex-col items-end gap-1.5 transition-all duration-200',
                    open
                        ? 'pointer-events-auto translate-y-0 opacity-100'
                        : 'pointer-events-none translate-y-2 opacity-0',
                )}
            >
                {tabs.map(({ value, icon: Icon, label }) => (
                    <button
                        key={value}
                        onClick={() => {
                            updateAppearance(value);
                            setOpen(false);
                        }}
                        className={cn(
                            'flex items-center gap-2 rounded-full py-1.5 pr-4 pl-3 text-sm shadow-md transition-colors',
                            appearance === value
                                ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                : 'bg-white text-neutral-600 hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700',
                        )}
                    >
                        <Icon className="h-3.5 w-3.5" />
                        {label}
                    </button>
                ))}
            </div>

            {/* FAB trigger */}
            <button
                onClick={() => setOpen((v) => !v)}
                aria-label="Toggle appearance menu"
                className={cn(
                    'flex h-10 w-10 items-center justify-center rounded-full shadow-lg transition-all duration-200',
                    'bg-white text-neutral-700 hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700',
                    'border border-neutral-200 dark:border-neutral-700',
                    open && 'rotate-180',
                )}
            >
                <ActiveIcon className="h-4 w-4 transition-transform duration-200" />
            </button>
        </div>
    );
}
