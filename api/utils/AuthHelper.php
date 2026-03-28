<?php

class AuthHelper {
    private static $secret_key = "votre_cle_secrete_super_securisee_123456"; // À changer pour la prod

    public static function generateJWT($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = self::base64UrlEncode($header);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24); // 24 heures
        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', "$header.$payload", self::$secret_key, true);
        $signature = self::base64UrlEncode($signature);
        
        return "$header.$payload.$signature";
    }

    public static function validateJWT($token) {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            error_log("JWT Validation failed: Invalid parts count (" . count($tokenParts) . ")");
            return false;
        }
        
        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $signatureProvided = $tokenParts[2];
        
        $signatureCheck = hash_hmac('sha256', "$header.$payload", self::$secret_key, true);
        $signatureCheck = self::base64UrlEncode($signatureCheck);
        
        if ($signatureCheck !== $signatureProvided) {
            error_log("JWT Validation failed: Signature mismatch");
            return false;
        }
        
        $payloadDecoded = json_decode(self::base64UrlDecode($payload), true);
        if (!$payloadDecoded) {
            error_log("JWT Validation failed: Could not decode payload");
            return false;
        }

        if (isset($payloadDecoded['exp']) && $payloadDecoded['exp'] < time()) {
            error_log("JWT Validation failed: Token expired");
            return false;
        }
        
        return $payloadDecoded;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    public static function getAuthToken() {
        $authorizationHeader = null;
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $authorizationHeader = $headers['Authorization'];
            }
        }

        if ($authorizationHeader) {
            if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
