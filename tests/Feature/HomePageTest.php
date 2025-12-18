<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_should_see_name_of_app()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee(env("APP_NAME"));
    }
}
