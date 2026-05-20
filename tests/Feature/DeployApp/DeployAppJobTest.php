<?php

use App\Jobs\DeployApp;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

it('runs the deploy command and logs output', function () {
    $logged = [];

    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = [
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    $job = new class extends DeployApp
    {
        protected function executeDeploy(): ?string
        {
            return 'Deployed successfully';
        }
    };

    $job->handle();

    expect($logged)->toHaveCount(1)
        ->and($logged[0]['level'])->toBe('info')
        ->and($logged[0]['message'])->toBe('Deployment finished')
        ->and($logged[0]['context'])->toBe(['output' => 'Deployed successfully']);
});
