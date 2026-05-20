<?php

use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['app.deploy_token' => 'test-token']);
});

it('rejects request with missing token', function () {
    $this->postJson('/api/deploy')
        ->assertStatus(403);
});

it('rejects request with empty token', function () {
    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => '',
    ])->assertStatus(403);
});

it('rejects request with wrong token', function () {
    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => 'wrong-token',
    ])->assertStatus(403);
});

it('passes request with correct token', function () {
    Queue::fake();

    $this->postJson('/api/deploy', [], [
        'X-Deploy-Token' => 'test-token',
    ])->assertNoContent();
});
