<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 登录与角色权限中间件
 * 用法：->middleware(Auth::class, 'admin') 或 'admin,counselor'
 */
class Auth
{
    public function handle(Request $request, Closure $next, string $roles = ''): Response
    {
        $user = session('user');
        if (!$user) {
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '登录已失效，请重新登录'], 401);
            }
            return redirect('/login');
        }

        // 角色校验
        if ($roles !== '') {
            $allow = array_map('trim', explode(',', $roles));
            if (!in_array($user['role'] ?? '', $allow, true)) {
                if ($request->isAjax()) {
                    return json(['code' => 403, 'msg' => '没有权限执行此操作'], 403);
                }
                return response(view('error', [
                    'title' => '没有权限',
                    'msg'   => '当前账号无权访问该页面',
                    'user'  => $user,
                ]), 403);
            }
        }

        return $next($request);
    }
}
