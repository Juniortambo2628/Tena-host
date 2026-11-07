<?php

it('keeps phones at 100% zoom: no zoom-in when a field is tapped', function (string $url) {
    $this->get($url)->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">', false);
})->with(['/login', '/']);
