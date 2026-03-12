<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Response;

class BlogFeedController extends Controller
{
    public function __invoke(): Response
    {
        $blogs = Blog::query()
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(50)
            ->get(['slug', 'title', 'excerpt', 'creator', 'tags', 'published_at']);

        $appName = config('app.name');
        $now = now()->toRfc2822String();

        $items = $blogs->map(function (Blog $blog) {
            $url = route('blog.show', ['slug' => $blog->slug]);
            $pubDate = $blog->published_at->toRfc2822String();
            $tags = collect($blog->tags)
                ->map(fn ($t) => "<category>{$t}</category>")
                ->implode("\n      ");
            $excerpt = htmlspecialchars($blog->excerpt, ENT_XML1);

            return <<<XML
    <item>
      <title>{$blog->title}</title>
      <link>{$url}</link>
      <guid isPermaLink="true">{$url}</guid>
      <description>{$excerpt}</description>
      <author>{$blog->creator}</author>
      <pubDate>{$pubDate}</pubDate>
      {$tags}
    </item>
XML;
        })->implode("\n");

        $routeIndex = route('blog.index');
        $routeFeed = route('blog.feed');
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{$appName} Blog</title>
    <link>{$routeIndex}</link>
    <description>Latest posts from {$appName}</description>
    <language>en-us</language>
    <lastBuildDate>{$now}</lastBuildDate>
    <atom:link href="{$routeFeed}" rel="self" type="application/rss+xml"/>
{$items}
  </channel>
</rss>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
