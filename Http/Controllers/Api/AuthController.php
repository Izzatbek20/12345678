<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public  function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);
        $user = User::where('username', $request->username)->first();

        if (!$user or !Hash::check($request->password, $user->password_hash)) {
            return response(['message' => 'xatolik'], 401);
        }
        $token = $user->createToken('api_token')->plainTextToken;

        $response = [
            'token' => $token,
            'role' => $user->role,
        ];
        return  $response;
    }
}
