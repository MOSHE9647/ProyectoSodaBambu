<?php

it('redirects to the login page when accessing the home route without authentication', function () {
    $response = $this->get(route('home'));

    $response->assertRedirectToRoute('login');
});
