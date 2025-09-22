<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email' => 'required|email',
			'password' => 'required|string',
		]);

		if (!Auth::attempt($credentials)) {
			return response()->json([
				'error' => [
					'code' => 'AUTH_FAILED',
					'message' => 'Invalid credentials',
				],
			], 401);
		}

		/** @var User $user */
		$user = Auth::user();
		$token = $user->createToken('access')->plainTextToken;

		return response()->json([
			'accessToken' => $token,
			'user' => [
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'role' => $user->role ?? 'trader',
			],
		]);
	}

	public function me(Request $request)
	{
		$user = $request->user();
		return response()->json([
			'id' => $user->id,
			'name' => $user->name,
			'email' => $user->email,
			'role' => $user->role ?? 'trader',
		]);
	}

	public function logout(Request $request)
	{
		/** @var User $user */
		$user = $request->user();
		$user->currentAccessToken()?->delete();
		return response()->json(['success' => true]);
	}
}
