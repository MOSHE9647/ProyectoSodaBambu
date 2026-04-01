<?php

use App\Http\Middleware\PreventBack;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit Story: Prevent Browser Back Button
 * Tests the PreventBack middleware functionality to ensure proper cache control
 * headers are set to prevent browser back button from displaying cached pages.
 */
test('CP-01_EIF-21_QA1 - sets Cache-Control header to prevent caching', function () {
    // Given: a request going through PreventBack middleware
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the middleware processes the request
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Cache-Control header includes no-store
    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store');
});

test('CP-02_EIF-21_QA1 - includes no-cache directive in Cache-Control', function () {
    // Given: a middleware processing a request
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the middleware adds headers
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Cache-Control includes no-cache directive
    expect($response->headers->get('Cache-Control'))
        ->toContain('no-cache');
});

test('CP-03_EIF-21_QA1 - includes must-validate in Cache-Control header', function () {
    // Given: a request processed by PreventBack
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: headers are set
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Cache-Control includes must-validate
    expect($response->headers->get('Cache-Control'))
        ->toContain('must-validate');
});

test('CP-04_EIF-21_QA1 - sets max-age to zero in Cache-Control', function () {
    // Given: a response passing through the middleware
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the response is processed
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Cache-Control sets max-age=0
    expect($response->headers->get('Cache-Control'))
        ->toContain('max-age=0');
});

test('CP-05_EIF-21_QA1 - sets Pragma header to no-cache', function () {
    // Given: a request through the middleware
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the middleware adds headers
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Pragma is set to no-cache
    expect($response->headers->get('Pragma'))->toBe('no-cache');
});

test('CP-06_EIF-21_QA1 - sets Expires header to past date', function () {
    // Given: a response being processed
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the middleware processes the request
    $response = $middleware->handle($request, function () {
        return new Response('Test Content');
    });

    // Then: Expires is set to a date in the past
    $expiresHeader = $response->headers->get('Expires');
    expect($expiresHeader)->toBe('Sat, 01 Jan 2000 00:00:00 GMT');
});

test('CP-07_EIF-21_QA1 - runs next closure and returns response as-is', function () {
    // Given: middleware with a next closure
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');
    $originalContent = 'Original Response Content';

    // When: the middleware calls next
    $response = $middleware->handle($request, function () use ($originalContent) {
        return new Response($originalContent);
    });

    // Then: the original response content is preserved
    expect($response->getContent())->toBe($originalContent);
});

test('CP-08_EIF-21_QA1 - returns Response object from handle method', function () {
    // Given: a request and closure returning Response
    $middleware = new PreventBack;
    $request = Request::create('/test', 'GET');

    // When: the middleware processes it
    $response = $middleware->handle($request, function () {
        return new Response('Test');
    });

    // Then: the return type is Response
    expect($response)->toBeInstanceOf(Response::class);
});
