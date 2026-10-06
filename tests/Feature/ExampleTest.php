<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_the_public_homepage_renders_from_seeded_school_records(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Emmaculate Academy')
            ->assertSee('Determined to make a difference')
            ->assertSee('Raising Future Leaders with Integrity, Knowledge &amp; Purpose', false);
    }
}
