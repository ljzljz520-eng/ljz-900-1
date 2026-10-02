<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\facade\View;
use think\Response;
use think\Validate;

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
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 渲染模板
     */
    protected function fetch(string $template, array $vars = []): Response
    {
        return Response::create(View::fetch($template, $vars), 'html');
    }

    /**
     * 验证数据
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 保存一张上传图片到 public/uploads/YYYYmmdd/，返回相对 URL 路径
     */
    protected function storeImage(\think\file\UploadedFile $file): string
    {
        $subDir = date('Ymd');
        $dir    = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subDir;
        $name   = bin2hex(random_bytes(8)) . '.' . strtolower($file->getOriginalExtension());
        $file->move($dir, $name);

        return '/uploads/' . $subDir . '/' . $name;
    }

    /**
     * 校验一组上传图片，返回错误信息或 null
     * @param \think\file\UploadedFile[] $files
     */
    protected function checkImages(array $files): ?string
    {
        $maxSize = (int) config('dorm.upload_max_size');
        $exts    = config('dorm.upload_ext');
        $mimes   = config('dorm.upload_mime');

        $validate = new Validate();
        $validate->rule([
            'file' => 'file|fileSize:' . $maxSize . '|fileExt:' . implode(',', $exts) . '|fileMime:' . implode(',', $mimes),
        ])->message([
            'file.fileSize' => '图片大小不能超过 ' . intval($maxSize / 1048576) . 'MB',
            'file.fileExt'  => '仅支持 ' . implode('/', $exts) . ' 格式图片',
            'file.fileMime' => '文件不是有效的图片',
            'file.file'     => '文件上传失败',
        ]);

        foreach ($files as $file) {
            if (!$file || !$validate->check(['file' => $file])) {
                return $validate->getError() ?: '图片校验失败';
            }
        }
        return null;
    }
}
