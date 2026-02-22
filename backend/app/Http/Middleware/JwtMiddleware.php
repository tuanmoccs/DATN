<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
  public function handle(Request $request, Closure $next): mixed
  {
    try {
      $user = JWTAuth::parseToken()->authenticate();

      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User không tồn tại'
        ], 404);
      }
    } catch (TokenExpiredException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token đã hết hạn'
      ], 401);
    } catch (TokenInvalidException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token không hợp lệ'
      ], 401);
    } catch (JWTException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token không được cung cấp'
      ], 401);
    }

    return $next($request);
  }
}
