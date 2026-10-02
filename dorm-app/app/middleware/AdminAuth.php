<?php
declare(strict_types=1);
namespace app\middleware;

use think\facade\Session;
use think\Request;
use think\Response;

/**
 * 仅管理员
 */
class AdminAuth
{
    public function handle(Request $request, \Closure $next)
    {
        if (Session::get('role') !== 'admin') {
            if ($request->isAjax()) {
                return json(['code' => 403, 'msg' => '无权限：仅管理员可操作'])->code(403);
            }
            return Response::create('403 无权限：仅管理员可访问', 'html', 403);
        }
        return $next($request);
    }
}
