<?php

use Symfony\Component\Yaml\Yaml;

test('compose.prod.yaml is valid and targets the production Dockerfile', function () {
    $path = dirname(__DIR__, 2).'/compose.prod.yaml';

    expect(file_exists($path))->toBeTrue();

    $parsed = Yaml::parseFile($path);

    expect($parsed)->toBeArray()
        ->and($parsed['services'])->toBeArray()
        ->and($parsed['services']['app'])->toBeArray()
        ->and($parsed['services']['app']['build']['dockerfile'])->toBe('docker/8.4/Dockerfile.prod')
        ->and($parsed['services']['app']['expose'])->toContain('80');
});
