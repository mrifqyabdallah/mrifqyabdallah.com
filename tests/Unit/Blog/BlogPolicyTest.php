<?php

use App\Models\Blog;
use App\Models\User;
use App\Policies\BlogPolicy;

beforeEach(function () {
    $this->policy = new BlogPolicy;
});

it('allows admin to delete a blog', function () {
    $admin = User::factory()->make(['is_admin' => true]);
    $blog = Blog::factory()->make();

    expect($this->policy->delete($admin, $blog))->toBeTrue();
});

it('denies non-admin from deleting a blog', function () {
    $user = User::factory()->make(['is_admin' => false]);
    $blog = Blog::factory()->make();

    expect($this->policy->delete($user, $blog))->toBeFalse();
});
