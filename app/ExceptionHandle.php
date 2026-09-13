<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    public function render($request, Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            if ($request->isAjax()) {
                return json(['code' => 1, 'msg' => $e->getError()], 422);
            }
            return response($e->getError(), 422);
        }

        // AJAX 请求统一返回 JSON
        if ($request->isAjax() && !($e instanceof HttpResponseException)) {
            $code = $e instanceof HttpException ? $e->getStatusCode() : 500;
            $msg  = $this->app->isDebug() ? $e->getMessage() : '服务器开小差了，请稍后再试';
            return json(['code' => $code, 'msg' => $msg], $code >= 100 ? $code : 500);
        }

        return parent::render($request, $e);
    }
}
