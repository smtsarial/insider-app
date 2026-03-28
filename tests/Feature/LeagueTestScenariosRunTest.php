<?php

test('league test scenarios run returns json with command output', function () {
    $response = $this->postJson(route('league.test-scenarios.run'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'exit_code',
            'output',
            'command' => [
                'binary',
                'args',
            ],
        ]);

    $args = $response->json('command.args');
    expect($args)->toBeArray()
        ->and($args)->toContain('test')
        ->and($args)->toContain('--compact');
});
