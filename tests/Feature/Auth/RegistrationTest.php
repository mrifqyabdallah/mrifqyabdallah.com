<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register using whitelisted email', function () {
    $email = 'test@example.com';
    $user = ['name' => 'Test User', 'email' => $email];

    \App\Models\UserRegistration::create(['email' => $email]);

    $response = $this->post(route('register.store'), [
        ...$user,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', $user);
    $this->assertDatabaseHas('user_registrations', [
        'email' => $email,
        'user_id' => \App\Models\User::where('email', $email)->value('id'),
    ]);
});

test('new users cannot register using whitelisted email that has been used', function () {
    $email = 'test@example.com';

    $userRegistration = \App\Models\UserRegistration::create(['email' => $email]);
    $user = \App\Models\User::factory()->create(['email' => $email]);
    $userRegistration->user()->associate($user);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test-another@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('new users cannot register using non-whitelisted email', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});
