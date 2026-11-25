<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** «/» es la portada de la web (HtmlPublicoTest); aquí basta con que la app arranque. */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
    }
}
