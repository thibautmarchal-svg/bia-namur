<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // En CI, `npm run build` ne tourne pas avant Pest, donc le manifest
        // Vite n'existe pas. @vite() dans resources/views/app.blade.php
        // throw ViteManifestNotFoundException → page d'erreur HTML lang="en"
        // → tests Inertia plantent avec "Not a valid Inertia response".
        // withoutVite() injecte un faux callback Vite qui retourne des assets
        // vides : on perd la verification du build cote tests, mais c'est OK
        // (le job CI separe "Build Vite" valide deja la prod build).
        $this->withoutVite();
    }
}
