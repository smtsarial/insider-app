<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Route::middleware('web')->get('/__asset_scheme_check', function () {
        return response(asset('build/assets/app.css'));
    });
});

test('asset URLs use https when the proxy forwards X-Forwarded-Proto', function () {
    $response = $this->withHeaders([
        'X-Forwarded-Proto' => 'https',
    ])->get('/__asset_scheme_check');

    $response->assertOk();
    expect($response->getContent())->toStartWith('https://');
    expect($response->getContent())->not->toStartWith('http://');
});
