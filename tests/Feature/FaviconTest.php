<?php

use App\Models\User;

test('public auth marketing and legal pages render the favicon link', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('/favicon.svg', false);
})->with([
    'home',
    'login',
    'register',
    'admin.login',
    'legal.terms',
    'legal.privacy',
    'legal.cookies',
    'legal.refunds',
    'legal.acceptable-use',
]);

test('authenticated faq support page renders the favicon link', function () {
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('support.index'))
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('/favicon.svg', false);
});
