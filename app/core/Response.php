<?php
/**
 * Response — JSON response helpers
 */

class Response
{
    /**
     * Kirim JSON response sukses
     */
    public static function json(mixed $data = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Kirim JSON error response
     */
    public static function error(string $message, int $status = 400, mixed $errors = null): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Require request method (jika tidak sesuai, return 405)
     */
    public static function requireMethod(string ...$methods): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
            self::error('Method not allowed.', 405);
        }
    }

    /**
     * Require JSON body, return decoded payload
     */
    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::error('Invalid JSON body.', 400);
        }
        return $data;
    }
}
