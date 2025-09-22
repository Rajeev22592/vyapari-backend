<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
	/**
	 * Handle an incoming request.
	 * Usage: ->middleware('role:admin') or 'role:admin,editor'
	 */
	public function handle(Request $request, Closure $next, string ...$roles): Response
	{
		$user = $request->user();
		if (!$user) {
			return response()->json([
				'error' => [
					'code' => 'UNAUTHENTICATED',
					'message' => 'Authentication required',
				],
			], 401);
		}

		$role = $user->role ?? 'trader';
		if (!empty($roles) && !in_array($role, $roles, true)) {
			return response()->json([
				'error' => [
					'code' => 'FORBIDDEN',
					'message' => 'Insufficient permissions',
				],
			], 403);
		}

		return $next($request);
	}
}
