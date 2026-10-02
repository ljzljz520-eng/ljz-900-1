<?php
declare(strict_types=1);
namespace app\service;

use think\exception\ValidateException;
use think\File;
use think\facade\Filesystem;

class PhotoService
{
    public const TYPE_ISSUE    = 'issues';
    public const TYPE_RECTIFY  = 'rectify';

    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_SIZE_MB  = 10;

    /**
     * 保存一张上传图片
     *
     * @return string 可公开访问的相对路径，如 /uploads/issues/202610/xxx.jpg
     * @throws ValidateException
     */
    public static function save(File $file, string $type): string
    {
        if (!in_array($type, [self::TYPE_ISSUE, self::TYPE_RECTIFY], true)) {
            throw new ValidateException('非法图片类型');
        }
        if (!$file->isValid()) {
            throw new ValidateException('上传文件无效：' . $file->getOriginalName());
        }
        if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
            throw new ValidateException('图片不能超过 ' . self::MAX_SIZE_MB . 'MB');
        }

        $ext = strtolower($file->extension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new ValidateException('仅支持 jpg/jpeg/png/webp/gif 格式');
        }

        // 真实 MIME 校验，防改后缀
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = (string) finfo_file($finfo, $file->getPathname());
            finfo_close($finfo);
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if ($mime && !in_array($mime, $allowedMime, true)) {
            throw new ValidateException('文件内容不是有效图片');
        }

        // getimagesize 二次确认
        $info = @getimagesize($file->getPathname());
        if ($info === false) {
            throw new ValidateException('无法读取图片信息');
        }

        // 统一扩展名（jpeg -> jpg）。putFile 的命名闭包只返回“不含扩展名”的主名，
        // 框架会自动按校验后的 $ext 补扩展名
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $dir = $type . '/' . date('Ym');
        $name = Filesystem::disk('public')->putFile($dir, $file, function () {
            return date('YmdHis') . '_' . bin2hex(random_bytes(6));
        });
        if (!$name) {
            throw new ValidateException('图片保存失败');
        }
        // 框架按原始扩展名落盘；jpeg 场景做一次纠正
        if ($ext !== strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            $diskRoot = public_path() . 'uploads' . DIRECTORY_SEPARATOR;
            $oldFull = $diskRoot . str_replace('/', DIRECTORY_SEPARATOR, $name);
            $newName = preg_replace('/\.[^.]+$/', '.' . $ext, $name);
            $newFull = $diskRoot . str_replace('/', DIRECTORY_SEPARATOR, $newName);
            if (is_file($oldFull) && @rename($oldFull, $newFull)) {
                $name = $newName;
            }
        }
        return '/uploads/' . str_replace('\\', '/', $name);
    }

    /**
     * 删除磁盘文件（按相对路径）
     */
    public static function delete(string $publicPath): void
    {
        $relative = preg_replace('#^/uploads/#', '', $publicPath);
        if (!$relative || str_contains($relative, '..')) {
            return;
        }
        $full = public_path() . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
