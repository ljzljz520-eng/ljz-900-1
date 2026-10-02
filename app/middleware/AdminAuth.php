<?php
declare (strict_types = 1);

namespace app\middleware;

use app\model\AdminUser;
use Closure;
use think\Request;
use think\Response;

/**
 * 管理员权限：必须登录且角色为 admin
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('admin_user');
        if (!$user) {
            return redirect((string) url('Auth/login'))->with('error', '请先登录');
        }
        if (($user['role'] ?? '') !== AdminUser::ROLE_ADMIN) {
            return redirect((string) url('Counselor/index'))->with('error', '当前账号无管理员权限');
        }
        return $next($request);
    }
}
