<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeployApp implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $output = $this->executeDeploy();

        Log::info('Deployment finished', ['output' => $output]);
    }

    protected function executeDeploy(): ?string
    {
        return shell_exec('cd /srv/mrifqyabdallah.com && make server.deploy 2>&1');
    }
}
