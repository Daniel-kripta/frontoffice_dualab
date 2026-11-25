<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Peticiones firmadas como las de DuaLab Central (formularios y correo de info@) */
    protected function central(string $usuario = 'Cynthia'): ClienteCentral
    {
        return new ClienteCentral($this, $usuario);
    }

    /** Para ClienteCentral (transformHeadersToServerVars es protegido) */
    public function cabecerasServidor(array $cabeceras): array
    {
        return $this->transformHeadersToServerVars($cabeceras);
    }
}
