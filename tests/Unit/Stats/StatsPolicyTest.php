<?php

use App\Models\User;
use App\Policies\StatsPolicy;

beforeEach(function () {
    $this->policy = new StatsPolicy;
});

it('allows admin to view opcache', function () {
    $admin = User::factory()->make(['is_admin' => true]);

    expect($this->policy->viewOpcache($admin))->toBeTrue();
});

it('denies non-admin from viewing opcache', function () {
    $user = User::factory()->make(['is_admin' => false]);

    expect($this->policy->viewOpcache($user))->toBeFalse();
});
