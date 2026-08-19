<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Login attempt', [
            'email' => $request->input('email'),
            'password_length' => strlen((string) $request->input('password')),
            'headers' => $request->headers->all(),
        ]);

        try {
            $validated = $request->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:6',
            ]);

            Log::info('Validated credentials', [
                'email' => $validated['email'],
            ]);

            $user = User::where('email', $validated['email'])->first();

            Log::info('User lookup result', [
                'user_found' => $user ? true : false,
                'user_role' => $user->role ?? 'N/A',
            ]);

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                Log::warning('Login failed: invalid credentials', [
                    'email' => $validated['email'],
                    'user_found' => $user ? true : false,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Email hoặc mật khẩu không đúng',
                ], 401);
            }

            if (! $user->isAdmin()) {
                Log::warning('Login failed: not admin', [
                    'email' => $validated['email'],
                    'role' => $user->role,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Tài khoản không có quyền truy cập admin.',
                ], 403);
            }

            $user->tokens()->delete();

            $token = $user->createToken('admin-token')->plainTextToken;

            Log::info('Login success', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'admin' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                ],
            ]);
        } catch (ValidationException $e) {
            Log::error('Login validation error', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng xuất thành công',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => [
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                    'name' => $request->user()->name,
                    'role' => $request->user()->role,
                ],
            ],
        ]);
    }
}
