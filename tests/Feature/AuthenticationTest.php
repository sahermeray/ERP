<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows the email login form and redirects guests from the dashboard', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Email address')
        ->assertSee('Password')
        ->assertSee('Sign in');

    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});

it('returns clear validation errors for an incomplete login', function () {
    $this->from('/login')
        ->post('/login', ['email' => '', 'password' => ''])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email', 'password']);
});

it('signs in a user with valid credentials and opens the dashboard', function () {
    $user = User::factory()->create([
        'name' => 'Records Officer',
        'email' => 'officer@example.test',
        'password' => 'secure-password',
    ]);

    $this->post('/login', [
        'email' => 'officer@example.test',
        'password' => 'secure-password',
    ])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Records Officer')
        ->assertSee(config('app.name'))
        ->assertSee('Sign out');
});

it('rejects invalid credentials without signing in', function () {
    User::factory()->create([
        'email' => 'officer@example.test',
        'password' => 'secure-password',
    ]);

    $this->from('/login')
        ->post('/login', [
            'email' => 'officer@example.test',
            'password' => 'incorrect-password',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);

    $this->assertGuest();
});

it('logs out the user and invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['session-marker' => 'active'])
        ->post('/logout')
        ->assertRedirect(route('login'))
        ->assertSessionMissing('session-marker');

    $this->assertGuest();
});
