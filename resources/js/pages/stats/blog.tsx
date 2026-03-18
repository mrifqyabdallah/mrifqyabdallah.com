import { Head, Link } from '@inertiajs/react'
import { useState } from 'react'
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Eye, TrendingUp, BookOpen, BarChart2, ArrowUpRight, Clock, ArrowLeft } from 'lucide-react'
import { post as post_stats } from '@/routes/stats';
import { index } from '@/routes/blog'
import BlogLayout from '@/layouts/blog-layout';

// ── types ─────────────────────────────────────────────────────────────────────

type Period = 'daily' | 'monthly' | 'yearly'

interface BlogDailyView   { date: string;  views: number }
interface BlogMonthlyView { month: string; views: number }
interface BlogYearlyView  { year: string;  views: number }

interface PostTotalView {
    blog_id:    number
    blog_title: string
    blog_slug:  string
    views:      number
}

interface BlogStats {
    total_views:  number
    daily:        BlogDailyView[]
    monthly:      BlogMonthlyView[]
    yearly:       BlogYearlyView[]
    top_posts:    PostTotalView[]
    generated_at: string
}

interface Props {
    stats: BlogStats | null
}

// ── helpers ───────────────────────────────────────────────────────────────────

function formatDate(str: string) {
    return new Date(str).toLocaleDateString('en-US', { month: 'numeric', day: 'numeric' })
}

function formatMonth(str: string) {
    const [y, m] = str.split('-')
    return new Date(+y, +m - 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' })
}

function formatNumber(n: number) {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

// ── sub-components ────────────────────────────────────────────────────────────

function StatCard({
    icon: Icon,
    label,
    value,
    sub,
}: {
    icon: React.ElementType
    label: string
    value: string | number
    sub?: string
}) {
    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">{label}</p>
                        <p className="mt-1 text-3xl font-bold tracking-tight">{value}</p>
                        {sub && <p className="mt-1 text-xs text-muted-foreground">{sub}</p>}
                    </div>
                    <div className="rounded-full bg-primary/10 p-3">
                        <Icon className="h-5 w-5 text-primary" />
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}

function ViewsChart({ stats, period }: { stats: BlogStats; period: Period }) {
    const data =
        period === 'daily'
            ? stats.daily.map(r => ({ label: formatDate(r.date), views: r.views }))
            : period === 'monthly'
            ? stats.monthly.map(r => ({ label: formatMonth(r.month), views: r.views }))
            : stats.yearly.map(r => ({ label: r.year, views: r.views }))

    if (!data.length) {
        return (
            <div className="flex h-56 items-center justify-center text-sm text-muted-foreground">
                No data for this period yet.
            </div>
        )
    }

    const chartConfig = {
        views: {
            label: 'Views',
            color: 'var(--chart-2)',
        },
    } satisfies ChartConfig

    const axisProps = {
        tick: { fontSize: 11 },
        tickLine: false as const,
        axisLine: false as const,
    }

    if (period === 'yearly') {
        return (
            <ChartContainer config={chartConfig} className="h-[220px] w-full">
                <BarChart data={data} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                    <XAxis dataKey="label" {...axisProps} />
                    <YAxis {...axisProps} tickFormatter={formatNumber} />
                    <ChartTooltip cursor={false} content={<ChartTooltipContent />} />
                    <Bar dataKey="views" fill="var(--chart-1)" radius={[4, 4, 0, 0]} />
                </BarChart>
            </ChartContainer>
        )
    }

    return (
        <ChartContainer config={chartConfig} className="h-[220px] w-full">
            <AreaChart data={data} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                <defs>
                    <linearGradient id="blogGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%"  stopColor="var(--chart-2)" stopOpacity={0.8} />
                        <stop offset="95%" stopColor="var(--chart-2)" stopOpacity={0.1} />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis dataKey="label" {...axisProps} />
                <YAxis {...axisProps} tickFormatter={formatNumber} />
                <ChartTooltip cursor={false} content={<ChartTooltipContent />} />
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
    )
}

// ── page ──────────────────────────────────────────────────────────────────────

export default function BlogStatsPage({ stats }: Props) {
    const [period, setPeriod] = useState<Period>('daily')

    const generatedAt = stats?.generated_at
        ? new Date(stats.generated_at).toLocaleString('en-US', {
              month: 'short', day: 'numeric',
              hour: '2-digit', minute: '2-digit',
          })
        : null

    const last30DayViews = stats?.daily.reduce((s, r) => s + r.views, 0) ?? 0

    return (
        <>
            <BlogLayout>
                <Head title="Blog Statistics" />
                <div className="mx-auto max-w-5xl space-y-8 px-4 py-10">

                    {/* Header */}
                    <Link
                        href={index().url}
                        className="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" />
                        Back to Blog
                    </Link>
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Blog Statistics</h1>
                            <p className="mt-1 text-muted-foreground">
                                Aggregated view counts across all posts.
                            </p>
                        </div>
                        {generatedAt && (
                            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Clock className="h-3.5 w-3.5" />
                                Updated {generatedAt}
                            </span>
                        )}
                    </div>

                    {/* No data */}
                    {!stats && (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center gap-3 py-20 text-center">
                                <BarChart2 className="h-10 w-10 text-muted-foreground/40" />
                                <p className="font-medium">No stats available yet.</p>
                                <p className="text-sm text-muted-foreground">
                                    Stats are generated overnight. Check back tomorrow.
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
                                    label="Last 30 Days"
                                    value={formatNumber(last30DayViews)}
                                    sub="rolling window"
                                />
                                <StatCard
                                    icon={BookOpen}
                                    label="Top Posts"
                                    value={stats.top_posts.length}
                                    sub="tracked"
                                />
                            </div>

                            {/* Chart */}
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-base font-semibold">
                                        Views Over Time
                                    </CardTitle>
                                    <Tabs value={period} onValueChange={v => setPeriod(v as Period)}>
                                        <TabsList className="h-8">
                                            <TabsTrigger value="daily"   className="px-3 text-xs">Daily</TabsTrigger>
                                            <TabsTrigger value="monthly" className="px-3 text-xs">Monthly</TabsTrigger>
                                            <TabsTrigger value="yearly"  className="px-3 text-xs">Yearly</TabsTrigger>
                                        </TabsList>
                                    </Tabs>
                                </CardHeader>
                                <CardContent>
                                    <ViewsChart stats={stats} period={period} />
                                </CardContent>
                            </Card>

                            {/* Top 10 posts */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Top Posts</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {stats.top_posts.length === 0 ? (
                                        <p className="px-6 py-8 text-center text-sm text-muted-foreground">
                                            No data yet.
                                        </p>
                                    ) : (
                                        <ul className="divide-y">
                                            {stats.top_posts.map((post, i) => (
                                                <li key={post.blog_id}>
                                                    <Link
                                                        href={post_stats(post.blog_slug).url}
                                                        className="flex items-center gap-4 px-6 py-3.5 transition-colors hover:bg-muted/50"
                                                    >
                                                        <span className="w-5 font-mono text-sm text-muted-foreground">
                                                            {i + 1}
                                                        </span>
                                                        <span className="flex-1 truncate text-sm font-medium">
                                                            {post.blog_title}
                                                        </span>
                                                        <Badge variant="secondary" className="shrink-0">
                                                            {formatNumber(post.views)} views
                                                        </Badge>
                                                        <ArrowUpRight className="h-4 w-4 shrink-0 text-muted-foreground" />
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </CardContent>
                            </Card>
                        </>
                    )}
                </div>
            </BlogLayout>
        </>
    )
}
