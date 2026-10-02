<?php
declare(strict_types=1);

use think\facade\Session;

if (!function_exists('current_user')) {
    function current_user(): array
    {
        return [
            'id'        => (int) Session::get('user_id'),
            'username'  => (string) Session::get('username'),
            'real_name' => (string) Session::get('real_name'),
            'role'      => (string) Session::get('role'),
        ];
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Session::get('role') === 'admin';
    }
}
