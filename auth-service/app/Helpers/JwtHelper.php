<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static function getSecretKey()
    {
        return env('JWT_SECRET', 'your-secret-key-here-change-in-production');
    }

    public static function generateToken($userId, $email)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId,
            'email' => $email,
        ];

        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }

    public static function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }    public static function getTokenFromRequest($request)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return str_replace('Bearer ', '', $authHeader);
    }
}
