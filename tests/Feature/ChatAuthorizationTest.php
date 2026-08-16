<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@techgear.vn',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'admin' => [
                        'id',
                        'email',
                        'name',
                    ],
                    'token',
                ],
            ]);
    }

    /** @test */
    public function admin_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@techgear.vn',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Email hoặc mật khẩu không đúng',
            ]);
    }

    /** @test */
    public function admin_cannot_login_with_missing_email()
    {
        $response = $this->postJson('/api/admin/login', [
            'password' => 'admin123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function admin_cannot_login_with_missing_password()
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@techgear.vn',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function guest_can_access_public_chat_endpoint()
    {
        $this->withoutMiddleware();
        $response = $this->postJson('/api/chat', [
            'message' => 'Xin chào',
            'history' => [],
        ]);

        $response->assertOk();
    }

    /** @test */
    public function guest_can_access_product_catalog()
    {
        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }
}