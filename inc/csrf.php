<?php

function csrf_cookie_name(): string
{
    return (string) config('security.csrf_cookie_name', 'csrf_token');
}

function csrf_ensure_token(): string
{
    $cookieName = csrf_cookie_name();
    if (!empty($_COOKIE[$cookieName])) {
        return (string) $_COOKIE[$cookieName];
    }

    $token = random_token(24);
    set_cookie_value($cookieName, $token, (int) config('security.guest_ttl', 31536000), false);
    return $token;
}

function csrf_token(): string
{
    return csrf_ensure_token();
}

function csrf_validate_request(): void
{
    if (in_array(request_method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    $headers = request_headers_normalized();
    $request = request_data();
    $provided = $headers['x-csrf-token'] ?? ($request['_csrf'] ?? '');
    $expected = $_COOKIE[csrf_cookie_name()] ?? '';

    if (!is_string($provided) || $provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
        json_error('不正なリクエストです。ページを再読み込みして、もう一度お試しください。', 419);
    }
}
