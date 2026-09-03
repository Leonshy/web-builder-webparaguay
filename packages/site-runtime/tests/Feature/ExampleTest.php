<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_without_a_published_site_returns_404(): void
    {
        $this->get('/')->assertNotFound();
    }
}
