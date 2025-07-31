<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // Sanctum 會自動處理 Token 的發放和 Cookie 的設定
        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout(); // 清除 session
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete(); // 撤銷當前 token
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
