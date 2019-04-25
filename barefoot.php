<?php

namespace barefoot;

/**
 * Barefoot
 * Streamlined tools for PHP minimalists.
 */

// Responses

function route(array $routes, callable $not_found): void
{
    $path = '/'.trim(explode('?', $_SERVER['PATH_INFO'])[0], '/');
    foreach ($routes as $route => $callable) {
        $route = '/'.trim($route, '/');
        $regex = '/^'.str_replace('/', '\/', $route).'$/';
        if (1 === preg_match($regex, $path, $matches)) {
            call_user_func_array($callable, array_slice($matches, 1));
            return;
        }
    }
    $not_found();
}

function redirect_and_exit(string $location): void
{
    header("Location: ${location}", true, 302);
    exit;
}

// URLs

function url_make_from_path(string $path): string
{
    static $base_url = null;
    if (null === $base_url) {
        $base_url = "//{$_SERVER['HTTP_HOST']}/";
    }
    return $base_url.trim($path, '/');
}

// Request data

function request_get_headers(): array
{
    return array_filter(
        $_SERVER,
        function ($key) {
            return 0 === strpos($key, 'HTTP_');
        },
        ARRAY_FILTER_USE_KEY
    );
}

function request_get_ip_address(string $default = '0.0.0.0'): string
{
    $ip = $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
        return $default;
    }
    return $ip;
}

// CSRF protection

function csrf_get_token(string $id): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = [];
    }
    if (!isset($_SESSION['csrf'][$id])) {
        $_SESSION['csrf'][$id] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'][$id];
}

function csrf_validate_token(string $id, string $token, callable $invalid): void
{
    if (!hash_equals(csrf_get_token($id), $token)) {
        $invalid();
    }
}

function csrf_unset_token(string $id): void
{
    unset($_SESSION['csrf'][$id]);
}

// Flash messages

function flash_set_message(string $key, string $value): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $value;
}

function flash_get_message(string $key, string $default = ''): string
{
    if (
        !isset($_SESSION['flash']) ||
        !isset($_SESSION['flash'][$key])
    ) {
        return $default;
    }
    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}
