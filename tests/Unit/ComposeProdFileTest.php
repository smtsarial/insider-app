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
        ->and($parsed['services']['app']['expose'])->toContain('80')
        ->and($parsed['services']['app']['environment']['RESET_DATABASE_ON_DEPLOY'])->toBe('${RESET_DATABASE_ON_DEPLOY:-true}');
});

test('production database init script is valid bash', function () {
    $path = dirname(__DIR__, 2).'/docker/8.4/prod-database-init.sh';

    expect(file_exists($path))->toBeTrue();

    exec('bash -n '.escapeshellarg($path).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, implode("\n", $output));
});
