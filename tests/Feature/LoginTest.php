<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use App\Http\Controllers\Auth\LoginController;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;

class LoginTest extends TestCase
{
    public function test_login_url_should_see_login_to_your()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee("Login to your " . env("APP_NAME"));
    }

    public function test_login_url_should_see_username()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee("username");
    }

    public function test_login_url_should_see_password()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee("password");
    }
    //public function test_login_validation()
    //{
    //    Event::fake();
    //    $request = Request::create('/login', 'POST',[
    //        'username' => 'vivek kumar',
    //        'password' => 'vivek@123',
    //    ]);
    //    $businessUtil = new BusinessUtil();
    //    $moduleUtil = new ModuleUtil();
    //    $controller = new LoginController($businessUtil, $moduleUtil);
    //    $response = $controller->store($request);
    //    $this->assertEquals(200, $response->getStatusCode());
    //}

}
