<?php

use Inertia\Testing\AssertableInertia as Assert;

test('league test scenarios page renders inertia payload', function () {
    $this->get(route('league.test-scenarios'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('League/TestScenarios')
            ->has('matchSimulation')
            ->has('standings')
            ->has('fixtures')
            ->has('titlePrediction')
            ->where('canRunUnitTests', true)
            ->where('matchSimulation.deterministic.seed', 42_001));
});
