<?php

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success(array $data = [], int $status = 200): never
{
    json_response([
        'ok' => true,
        'data' => $data,
    ], $status);
}

function json_error(string $message, int $status = 400, array $extra = []): never
{
    json_response([
        'ok' => false,
        'error' => $message,
        'details' => $extra,
    ], $status);
}

function redirect_to(string $url, int $status = 302): never
{
    header('Location: ' . $url, true, $status);
    exit;
}

function request_data(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            $cached = [];
            return $cached;
        }
        $decoded = json_decode($raw, true);
        $cached = is_array($decoded) ? $decoded : [];
        return $cached;
    }

    if (request_method() === 'GET') {
        $cached = $_GET;
        return $cached;
    }

    if (in_array(request_method(), ['PUT', 'PATCH', 'DELETE'], true)) {
        $raw = file_get_contents('php://input');
        parse_str($raw ?: '', $parsed);
        $cached = is_array($parsed) ? $parsed : [];
        return $cached;
    }

    $cached = $_POST;
    return $cached;
}
