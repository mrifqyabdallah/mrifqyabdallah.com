<?php

use App\Jobs\DeployApp;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

it('runs the deploy command and logs output', function () {
    Process::fake([
        'make -C /srv/mrifqyabdallah.com server.deploy' => Process::result('Deployed successfully'),
    ]);

    $logged = [];

    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = [
            'level' => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    (new DeployApp)->handle();

    Process::assertRan('make -C /srv/mrifqyabdallah.com server.deploy');

    expect($logged)->toHaveCount(1)
        ->and($logged[0]['level'])->toBe('info')
        ->and($logged[0]['message'])->toBe('Deployment finished')
        ->and($logged[0]['context'])->toBe(['output' => 'Deployed successfully']);
});
