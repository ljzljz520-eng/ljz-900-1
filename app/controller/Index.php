<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;

class Index extends BaseController
{
    /**
     * 首页：按登录状态跳转
     */
    public function index()
    {
        $user = session('admin_user');
        if ($user) {
            return redirect(($user['role'] ?? '') === 'counselor' ? '/counselor' : '/admin');
        }
        return redirect((string) url('Auth/login'));
    }
}
