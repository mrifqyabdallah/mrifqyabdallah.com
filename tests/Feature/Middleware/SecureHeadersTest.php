<?php

use App\Http\Middleware\SecureHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('adds secure headers to response', function () {
    $middleware = new SecureHeaders;
    $request = Request::create('/');

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains')
        ->and($response->headers->get('Content-Security-Policy'))
        ->toBe('upgrade-insecure-requests')
        ->and($response->headers->get('X-Content-Type-Options'))
        ->toBe('nosniff');
});
