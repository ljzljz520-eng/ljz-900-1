<?php
declare(strict_types=1);
namespace app\middleware;

use think\facade\Session;
use think\Request;
use think\Response;

/**
 * 登录用户（管理员或辅导员）通用鉴权
 */
class Auth
{
    public function handle(Request $request, \Closure $next)
    {
        $uid = Session::get('user_id');
        if (!$uid) {
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '未登录或会话已过期'])->code(401);
            }
            return redirect((string) url('/login'));
        }
        $request->userId   = (int) $uid;
        $request->userRole = (string) Session::get('role');
        $request->userName = (string) Session::get('real_name');
        return $next($request);
    }
}
