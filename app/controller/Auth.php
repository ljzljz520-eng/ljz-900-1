<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AdminUser;
use think\Request;

/**
 * 登录 / 注销（管理员与辅导员共用入口）
 */
class Auth extends BaseController
{
    public function login()
    {
        if (session('admin_user')) {
            return redirect('/');
        }
        return $this->fetch('auth/login');
    }

    public function doLogin(Request $request)
    {
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        if ($username === '' || $password === '') {
            return $this->fetch('auth/login', ['error' => '请输入用户名和密码', 'username' => $username]);
        }

        $user = AdminUser::checkLogin($username, $password);
        if (!$user) {
            return $this->fetch('auth/login', ['error' => '用户名或密码错误', 'username' => $username]);
        }

        session('admin_user', [
            'id'       => $user->id,
            'username' => $user->username,
            'name'     => $user->name,
            'role'     => $user->role,
        ]);

        return redirect($user->isAdmin() ? '/admin' : '/counselor');
    }

    public function logout()
    {
        session('admin_user', null);
        return redirect((string) url('Auth/login'));
    }
}
