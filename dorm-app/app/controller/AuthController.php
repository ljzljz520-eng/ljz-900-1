<?php
declare(strict_types=1);
namespace app\controller;

use app\BaseController;
use app\model\User;
use think\facade\Session;
use think\facade\View;
use think\request;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (Session::get('user_id')) {
            return redirect((string) url(Session::get('role') === 'admin' ? '/admin/issues' : '/counselor/issues'));
        }
        return View::fetch('/auth/login', ['error' => '']);
    }

    public function login()
    {
        $username = trim((string) $this->request->post('username', ''));
        $password = (string) $this->request->post('password', '');
        if ($username === '' || $password === '') {
            return View::fetch('/auth/login', ['error' => '请输入账号和密码']);
        }
        /** @var User|null $user */
        $user = User::where('username', $username)->find();
        if (!$user || !$user->is_active || !password_verify($password, $user->password_hash)) {
            return View::fetch('/auth/login', ['error' => '账号或密码错误']);
        }
        Session::set('user_id', $user->id);
        Session::set('username', $user->username);
        Session::set('real_name', $user->real_name);
        Session::set('role', $user->role);
        Session::regenerate(); // 防 session 固定
        $home = $user->role === 'admin' ? '/admin/issues' : '/counselor/issues';
        return redirect((string) url($home));
    }

    public function logout()
    {
        Session::clear();
        return redirect((string) url('/login'));
    }
}
