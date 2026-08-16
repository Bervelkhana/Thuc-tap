<?php

namespace Tests\Unit;

use App\Services\NvidiaNimChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AiFallbackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function chat_returns_success_when_ai_responds()
    {
        $this->withoutMiddleware();
        $chatService = Mockery::mock(NvidiaNimChatService::class)->makePartial();
        $chatService->shouldReceive('chat')
            ->once()
            ->andReturn([
                'success' => true,
                'reply' => 'Xin chào, tôi có thể giúp gì cho bạn?',
            ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Xin chào',
            'history' => [],
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'reply' => 'Xin chào, tôi có thể giúp gì cho bạn?',
                ],
            ]);
    }

    /** @test */
    public function chat_returns_error_when_ai_returns_failure()
    {
        $this->withoutMiddleware();
        $chatService = Mockery::mock(NvidiaNimChatService::class)->makePartial();
        $chatService->shouldReceive('chat')
            ->once()
            ->andReturn([
                'success' => false,
                'reply' => 'Server AI đang bận. Vui lòng thử lại sau.',
            ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Tìm CPU',
            'history' => [],
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'error',
                'data' => [
                    'reply' => 'Server AI đang bận. Vui lòng thử lại sau.',
                ],
            ]);
    }

    /** @test */
    public function chat_returns_error_when_ai_throws_connection_exception()
    {
        $this->withoutMiddleware();
        $chatService = Mockery::mock(NvidiaNimChatService::class)->makePartial();
        $chatService->shouldReceive('chat')
            ->once()
            ->andReturn([
                'success' => false,
                'reply' => 'Không thể kết nối tới server AI. Vui lòng kiểm tra mạng và thử lại.',
            ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Tìm RAM',
            'history' => [],
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'error',
            ]);

        $this->assertStringContainsString('Không thể kết nối', $response->json('data.reply'));
    }

    /** @test */
    public function chat_logs_error_when_ai_fails()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Chat Error', Mockery::type('array'));

        $this->withoutMiddleware();
        $chatService = Mockery::mock(NvidiaNimChatService::class)->makePartial();
        $chatService->shouldReceive('chat')
            ->andReturn([
                'success' => false,
                'reply' => 'Có lỗi xảy ra.',
            ]);

        $this->postJson('/api/chat', [
            'message' => 'Test',
            'history' => [],
        ]);
    }
}