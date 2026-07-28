<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Login admin (simple auth - dùng credentials cứng cho demo)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Admin credentials cứng (để demo, bạn có thể move vào database sau)
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');

        if (
            $request->input('email') === $adminEmail &&
            $request->input('password') === $adminPassword
        ) {
            // Tạo token (có thể dùng JWT sau)
            $token = 'admin_' . bin2hex(random_bytes(16));

            return response()->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'admin' => [
                        'id' => 1,
                        'email' => $adminEmail,
                        'name' => 'Admin',
                    ],
                    'token' => $token,
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Email hoặc mật khẩu không đúng',
        ], 401);
    }

    /**
     * Logout admin
     */
    public function logout()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Đăng xuất thành công',
        ]);
    }
}
