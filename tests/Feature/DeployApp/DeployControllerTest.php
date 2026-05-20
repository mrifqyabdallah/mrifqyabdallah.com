<?php

use App\Jobs\DeployApp;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['app.deploy_token' => 'test-token']);
});

it('returns 200 with empty body', function () {
    Queue::fake();

    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => 'test-token',
    ])
        ->assertNoContent();
});

it('returns 403 with wrong token', function () {
    Queue::fake();

    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => 'wrong-token',
    ])
        ->assertStatus(403);
});

it('dispatches DeployApp', function () {
    Queue::fake();

    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => 'test-token',
    ]);

    Queue::assertPushed(DeployApp::class);
});
