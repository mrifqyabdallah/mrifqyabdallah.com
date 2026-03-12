<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;

class SitemapGenerate extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public/sitemap.xml from published blog posts';

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $blogs = Blog::query()
            ->published()
            ->orderBy('published_at', 'desc')
            ->get(['slug', 'published_at']);

        $urls = collect();

        // Static pages
        $urls->push($this->urlEntry($baseUrl.'/blog', null, 'weekly'));

        // Blog posts
        foreach ($blogs as $blog) {
            $urls->push($this->urlEntry(
                $baseUrl.'/blog/'.$blog->slug,
                $blog->published_at->toDateString(),
                'monthly'
            ));
        }

        $xml = $this->buildXml($urls->all());

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('sitemap.xml generated with '.($urls->count()).' URLs.');

        return self::SUCCESS;
    }

    private function urlEntry(string $loc, ?string $lastmod, string $changefreq): string
    {
        $lastmodTag = $lastmod ? "\n    <lastmod>{$lastmod}</lastmod>" : '';

        return <<<XML
  <url>
    <loc>{$loc}</loc>{$lastmodTag}
    <changefreq>{$changefreq}</changefreq>
  </url>
XML;
    }

    private function buildXml(array $urls): string
    {
        $urlsString = implode("\n", $urls);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urlsString}
</urlset>
XML;
    }
}
