import { Head, Link } from '@inertiajs/react';
import {
    Eye,
    TrendingUp,
    CalendarDays,
    ArrowLeft,
    BarChart2,
    Clock,
} from 'lucide-react';
import { useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import BlogLayout from '@/layouts/blog-layout';
import { show } from '@/routes/blog';
import { blog as blog_stats } from '@/routes/stats';

// ── types ─────────────────────────────────────────────────────────────────────

type Period = 'daily' | 'monthly' | 'yearly';

interface PostDailyView {
    date: string;
    views: number;
}
interface PostMonthlyView {
    month: string;
    views: number;
}
interface PostYearlyView {
    year: string;
    views: number;
}

interface PostStatsView {
    blog_id: number;
    blog_title: string;
    blog_slug: string;
    total_views: number;
    daily: PostDailyView[];
    monthly: PostMonthlyView[];
    yearly: PostYearlyView[];
    generated_at: string;
}

interface Props {
    blog: { id: number; title: string; slug: string };
    stats: PostStatsView | null;
}

interface RechartsTickProps {
    x: number;
    y: number;
    payload: {
        value: string;
        offset: number;
    };
    index: number;
}

// ── helpers ───────────────────────────────────────────────────────────────────

function formatDate(str: string) {
    return new Date(str).toLocaleDateString('en-US', {
        month: 'numeric',
        day: 'numeric',
    });
}

function formatMonth(str: string) {
    const [y, m] = str.split('-');
    return new Date(+y, +m - 1).toLocaleDateString('en-US', {
        month: 'short',
        year: '2-digit',
    });
}

function formatNumber(n: number) {
    return new Intl.NumberFormat('en-US', {
        notation: 'compact',
        maximumFractionDigits: 2,
    }).format(n);
}

function peakViews(rows: { views: number }[]) {
    return rows.length ? Math.max(...rows.map((r) => r.views)) : 0;
}

function DailyTick(props: RechartsTickProps & { data: PostDailyView[] }) {
    const { x, y, payload, index, data } = props;
    const date = new Date(data[index]?.date ?? payload.value);
    const day = date.getDate();

    const isFirstOfMonth =
        index === 0 ||
        new Date(data[index - 1]?.date).getMonth() !== date.getMonth();

    const month = isFirstOfMonth
        ? date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' })
        : null;

    return (
        <g transform={`translate(${x},${y})`}>
            <text
                x={0}
                y={0}
                dy={12}
                textAnchor="middle"
                fontSize={10}
                fill="currentColor"
                className="fill-muted-foreground"
            >
                {day}
            </text>
            {month && (
                <text
                    x={0}
                    y={0}
                    dy={24}
                    textAnchor="middle"
                    fontSize={10}
                    fontWeight={600}
                    fill="currentColor"
                    className="fill-foreground"
                >
                    {month}
                </text>
            )}
        </g>
    );
}

// ── sub-components ────────────────────────────────────────────────────────────

function StatCard({
    icon: Icon,
    label,
    value,
    sub,
}: {
    icon: React.ElementType;
    label: string;
    value: string | number;
    sub?: string;
}) {
    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">
                            {label}
                        </p>
                        <p className="mt-1 text-3xl font-bold tracking-tight">
                            {value}
                        </p>
                        {sub && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                {sub}
                            </p>
                        )}
                    </div>
                    <div className="rounded-full bg-primary/10 p-3">
                        <Icon className="h-5 w-5 text-primary" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function ViewsChart({
    stats,
    period,
}: {
    stats: PostStatsView;
    period: Period;
}) {
    const data =
        period === 'daily'
            ? stats.daily.map((r) => ({
                  label: formatDate(r.date),
                  views: r.views,
              }))
            : period === 'monthly'
              ? stats.monthly.map((r) => ({
                    label: formatMonth(r.month),
                    views: r.views,
                }))
              : stats.yearly.map((r) => ({ label: r.year, views: r.views }));

    if (!data.length) {
        return (
            <div className="flex h-56 items-center justify-center text-sm text-muted-foreground">
                No data for this period yet.
            </div>
        );
    }

    const chartConfig = {
        views: {
            label: 'Views',
            color: 'var(--chart-2)',
        },
    } satisfies ChartConfig;

    const axisProps = {
        tick: { fontSize: 11 },
        tickLine: false as const,
        axisLine: false as const,
    };

    if (period === 'yearly') {
        return (
            <ChartContainer config={chartConfig} className="h-[220px] w-full">
                <BarChart
                    data={data}
                    margin={{ top: 4, right: 8, left: -20, bottom: 0 }}
                >
                    <CartesianGrid
                        strokeDasharray="3 3"
                        className="stroke-muted"
                    />
                    <XAxis dataKey="label" {...axisProps} />
                    <YAxis {...axisProps} tickFormatter={formatNumber} />
                    <ChartTooltip
                        cursor={false}
                        content={<ChartTooltipContent />}
                    />
                    <Bar
                        dataKey="views"
                        fill="var(--chart-1)"
                        radius={[4, 4, 0, 0]}
                    />
                </BarChart>
            </ChartContainer>
        );
    }

    return (
        <ChartContainer config={chartConfig} className="h-[220px] w-full">
            <AreaChart
                data={data}
                margin={{ top: 4, right: 8, left: -20, bottom: 0 }}
            >
                <defs>
                    <linearGradient id="blogGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop
                            offset="5%"
                            stopColor="var(--chart-2)"
                            stopOpacity={0.8}
                        />
                        <stop
                            offset="95%"
                            stopColor="var(--chart-2)"
                            stopOpacity={0.1}
                        />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis
                    dataKey="label"
                    {...(period === 'daily'
                        ? {
                              tickLine: false,
                              axisLine: false,
                              height: 40,
                              interval: 0,
                              tick: (props: RechartsTickProps) => (
                                  <DailyTick {...props} data={stats.daily} />
                              ),
                          }
                        : axisProps)}
                />
                <YAxis {...axisProps} tickFormatter={formatNumber} />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent />}
                />
                <Area
                    type="monotone"
                    dataKey="views"
                    stroke="var(--chart-2)"
                    fill="url(#blogGrad)"
                    fillOpacity={0.4}
                    dot={true}
                    activeDot={{ r: 4 }}
                />
            </AreaChart>
        </ChartContainer>
    );
}

// ── page ──────────────────────────────────────────────────────────────────────

export default function PostStatsPage({ blog, stats }: Props) {
    const [period, setPeriod] = useState<Period>('daily');

    const generatedAt = stats?.generated_at
        ? new Date(stats.generated_at).toLocaleString('en-US', {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
              timeZoneName: 'short',
          })
        : null;

    return (
        <>
            <BlogLayout>
                <Head title={`Stats — ${blog.title}`} />
                <div className="mx-auto max-w-5xl space-y-8 px-4 py-10">
                    {/* Back + header */}
                    <div>
                        <Link
                            href={blog_stats().url}
                            className="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            All statistics
                        </Link>

                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h1 className="mb-2 text-2xl font-bold tracking-tight">
                                    {blog.title}
                                </h1>
                                {generatedAt && (
                                    <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Clock className="h-3.5 w-3.5" />
                                        Updated {generatedAt}
                                    </span>
                                )}
                            </div>
                            <div>
                                <Button asChild>
                                    <Link href={show(blog.slug).url}>
                                        Read this post -&gt;
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* No data */}
                    {!stats && (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center gap-3 py-20 text-center">
                                <BarChart2 className="h-10 w-10 text-muted-foreground/40" />
                                <p className="font-medium">
                                    No stats available yet.
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Stats are generated overnight. Check back
                                    tomorrow.
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {stats && (
                        <>
                            {/* KPI cards */}
                            <div className="grid gap-4 sm:grid-cols-3">
                                <StatCard
                                    icon={Eye}
                                    label="Total Views"
                                    value={formatNumber(stats.total_views)}
                                    sub="all time"
                                />
                                <StatCard
                                    icon={TrendingUp}
                                    label="Peak Day"
                                    value={formatNumber(peakViews(stats.daily))}
                                    sub="single-day record"
                                />
                                <StatCard
                                    icon={CalendarDays}
                                    label="Peak Month"
                                    value={formatNumber(
                                        peakViews(stats.monthly),
                                    )}
                                    sub="single-month record"
                                />
                            </div>

                            {/* Chart */}
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-base font-semibold">
                                        Views Over Time
                                    </CardTitle>
                                    <Tabs
                                        value={period}
                                        onValueChange={(v) =>
                                            setPeriod(v as Period)
                                        }
                                    >
                                        <TabsList className="h-8">
                                            <TabsTrigger
                                                value="daily"
                                                className="px-3 text-xs"
                                            >
                                                Daily
                                            </TabsTrigger>
                                            <TabsTrigger
                                                value="monthly"
                                                className="px-3 text-xs"
                                            >
                                                Monthly
                                            </TabsTrigger>
                                            <TabsTrigger
                                                value="yearly"
                                                className="px-3 text-xs"
                                            >
                                                Yearly
                                            </TabsTrigger>
                                        </TabsList>
                                    </Tabs>
                                </CardHeader>
                                <CardContent>
                                    <ViewsChart stats={stats} period={period} />
                                </CardContent>
                            </Card>
                        </>
                    )}
                </div>
            </BlogLayout>
        </>
    );
}
