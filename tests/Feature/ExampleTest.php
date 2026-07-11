<?php

it('shows the welcome page with login access', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('show tech-support all')
        ->assertSee('Iniciar sesión');
});
