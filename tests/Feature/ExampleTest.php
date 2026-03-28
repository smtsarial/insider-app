<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home renders league hub', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('League/Index')
            ->where('surface', 'home'));
});
