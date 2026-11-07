<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('answers a GET to a POST-only route with a 405 page, not a 500', function () {
    $response = $this->get('/portal/connect');

    $response->assertStatus(405);
    $response->assertSee('Method Not Allowed');
});

it('answers a wrong-method JSON request with a 405', function () {
    $this->getJson('/portal/connect')->assertStatus(405);
});

it('falls back to the shared error page for a status without its own view', function () {
    Route::get('/__test/gone', fn () => throw new HttpException(410));

    $response = $this->get('/__test/gone');

    $response->assertStatus(410);
    $response->assertSee('410');
    $response->assertSee('Something went wrong');
});
