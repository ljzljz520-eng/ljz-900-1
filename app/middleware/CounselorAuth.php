<?php
declare (strict_types = 1);

namespace app\middleware;

use app\model\AdminUser;
use Closure;
use think\Request;
use think\Response;

/**
 * 辅导员权限：登录的辅导员或管理员均可访问汇总页
 */
class CounselorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('admin_user');
        if (!$user) {
            return redirect((string) url('Auth/login'))->with('error', '请先登录');
        }
        if (!in_array($user['role'] ?? '', [AdminUser::ROLE_ADMIN, AdminUser::ROLE_COUNSELOR], true)) {
            return redirect((string) url('Auth/login'))->with('error', '无权访问');
        }
        return $next($request);
    }
}
