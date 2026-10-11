<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The application answers: the root leads to sign-in, and sign-in renders.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertStatus(200);
    }
}
