<?php
declare(strict_types=1);
namespace app\middleware;

use think\facade\Session;
use think\Request;

/**
 * 管理员 + 辅导员均可
 */
class CounselorAuth
{
    public function handle(Request $request, \Closure $next)
    {
        $role = Session::get('role');
        if (!in_array($role, ['admin', 'counselor'], true)) {
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '未登录或会话已过期'])->code(401);
            }
            return redirect((string) url('/login'));
        }
        return $next($request);
    }
}
