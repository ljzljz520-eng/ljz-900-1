<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\User;

class Index extends BaseController
{
    /** 首页：按角色跳转 */
    public function index()
    {
        if ($this->user) {
            return redirect($this->user['role'] === User::ROLE_COUNSELOR ? '/summary' : '/admin');
        }
        return redirect('/login');
    }

    /** 登录页 */
    public function login()
    {
        if ($this->user) {
            return redirect('/');
        }
        return $this->view('login');
    }

    /** 登录提交 */
    public function doLogin()
    {
        $username = trim((string) $this->request->post('username', ''));
        $password = (string) $this->request->post('password', '');

        $user = User::where('username', $username)->find();
        if (!$user || !password_verify($password, $user->password)) {
            return $this->view('login', [
                'error'    => '用户名或密码错误',
                'username' => $username,
            ]);
        }

        session('user', [
            'id'       => $user->id,
            'username' => $user->username,
            'name'     => $user->name,
            'role'     => $user->role,
        ]);

        return redirect($user->role === User::ROLE_COUNSELOR ? '/summary' : '/admin');
    }

    /** 退出登录 */
    public function logout()
    {
        session('user', null);
        return redirect('/login');
    }
}
