<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBlogStats;
use App\Jobs\GeneratePostStats;
use App\Models\Blog;
use App\Models\BlogView;
use Illuminate\Console\Command;

class BlogStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:stats {--all : Regenerate stats for every blog post, not just ones active today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute job to calculate blog statistics in the background';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ((bool) $this->option('all')) {
            return $this->generateAll();
        }
 
        return $this->generateToday();
    }

    private function generateAll(): int
    {
        /** @var list<int> $blogIds */
        $blogIds = Blog::query()->pluck('id')->all();
 
        if (empty($blogIds)) {
            $this->components->warn('No blog posts found.');
            return self::SUCCESS;
        }
 
        $this->components->info("Queueing stats for " . count($blogIds) . " post(s)...");
        return $this->executeGenerate($blogIds);
    }
 
    private function generateToday(): int
    {
        /** @var list<int> $activeBlogIds */
        $activeBlogIds = BlogView::query()
            ->where('date', today()->toDateString())
            ->distinct()
            ->pluck('blog_id')
            ->all();
 
        if (empty($activeBlogIds)) {
            $this->components->warn('No posts had views today. Nothing queued.');
            return self::SUCCESS;
        }
 
        $this->components->info("Queueing stats for {$this->count($activeBlogIds)} active post(s) today...");
        return $this->executeGenerate($activeBlogIds);
    }

    /**
     * @param list<int> $blogIds
     */
    private function executeGenerate(array $blogIds): int
    { 
        $bar = $this->output->createProgressBar(count($blogIds));
        $bar->start();
 
        foreach ($blogIds as $blogId) {
            GeneratePostStats::dispatch($blogId);
            $bar->advance();
        }
 
        $bar->finish();
        $this->newLine(2);
 
        GenerateBlogStats::dispatch();
        $this->components->info('Job "blog overview stats" queued.');
 
        return self::SUCCESS;
    }
}
