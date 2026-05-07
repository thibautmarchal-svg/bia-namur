<?php

it('renders /mentions-legales', function () {
    $this->get('/mentions-legales')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Legal/Mentions')
            ->has('updatedAt'),
        );
});

it('renders /cgu', function () {
    $this->get('/cgu')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Legal/Terms')
            ->has('updatedAt'),
        );
});

it('renders /confidentialite', function () {
    $this->get('/confidentialite')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Legal/Privacy')
            ->has('updatedAt'),
        );
});

it('legal pages send X-Robots-Tag noindex header', function () {
    foreach (['/mentions-legales', '/cgu', '/confidentialite'] as $path) {
        $response = $this->get($path);
        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
});
