<?php
declare (strict_types = 1);

namespace app;

use think\App;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 当前登录用户（session 中的数组）
     * @var array|null
     */
    protected ?array $user = null;

    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->user    = session('user') ?: null;

        $this->initialize();
    }

    protected function initialize()
    {
    }

    /**
     * 校验当前登录用户角色（控制器层二次校验，不依赖路由中间件配置）
     * @throws \think\exception\HttpException
     */
    protected function requireRole(string ...$roles): void
    {
        if (!$this->user || !in_array($this->user['role'] ?? '', $roles, true)) {
            throw new \think\exception\HttpException(403, '没有权限执行此操作');
        }
    }

    /**
     * 渲染视图并返回 HTML 响应
     */
    protected function view(string $template, array $vars = [])
    {
        $vars['user'] = $this->user;
        return response(view($template, $vars));
    }

    /**
     * JSON 成功响应
     */
    protected function ok($data = [], string $msg = 'ok')
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * JSON 失败响应
     */
    protected function fail(string $msg, int $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg]);
    }
}
