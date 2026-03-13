<?php

namespace App\Jobs;

use App\Models\BlogView;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordBlogView implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $blogId,
        public readonly ?int $userId,
        public readonly ?string $visitorHash,
    ) {}

    public function handle(): void
    {
        if ($this->userId) {
            $this->recordForAuthUser();
        } else {
            $this->recordForGuest();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::warning('Failed to record blog view', [
            'blog_id' => $this->blogId,
            'user_id' => $this->userId,
            'visitor_hash' => $this->visitorHash,
            'error' => $e->getMessage(),
        ]);
    }

    private function recordForAuthUser(): void
    {
        BlogView::updateOrCreate(
            [
                'blog_id' => $this->blogId,
                'user_id' => $this->userId,
                'date' => today(),
            ],
            ['visitor_hash' => null]
        );
    }

    private function recordForGuest(): void
    {
        if (! $this->visitorHash) {
            return;
        }

        BlogView::updateOrCreate(
            [
                'blog_id' => $this->blogId,
                'visitor_hash' => $this->visitorHash,
                'date' => today(),
            ],
            ['user_id' => null]
        );
    }
}
