<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Photo;
use think\file\UploadedFile;

/**
 * 图片上传服务：校验、存储、生成缩略图
 */
class UploadService
{
    /**
     * 保存一张上传的图片并写入 photo 记录
     *
     * @param UploadedFile $file       上传文件
     * @param int          $ticketId   整改单ID
     * @param string       $kind       problem=问题照片 fix=整改照片
     * @param string       $uploadedBy 上传者标识
     */
    public static function saveImage(UploadedFile $file, int $ticketId, string $kind, string $uploadedBy): Photo
    {
        $allowExt = (array) config('dorm.upload_allow_ext');
        $maxSize  = (int) config('dorm.upload_max_size');

        // 录入人标识校验：非空、限长（photos.uploaded_by VARCHAR(50)）
        $uploadedBy = trim($uploadedBy);
        if ($uploadedBy === '' || mb_strlen($uploadedBy) > 50) {
            throw new \DomainException('录入人标识无效');
        }

        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, $allowExt, true)) {
            throw new \DomainException('仅支持 ' . implode('/', $allowExt) . ' 格式的图片');
        }
        if ($file->getSize() > $maxSize) {
            throw new \DomainException('图片大小不能超过 ' . (int) ($maxSize / 1048576) . 'MB');
        }
        $mime = (string) $file->getMime();
        if (!str_starts_with($mime, 'image/')) {
            throw new \DomainException('文件不是有效的图片');
        }

        // 存储：public/uploads/YYYYmm/随机名.扩展名
        // 文件名只允许服务端生成的随机串 + 白名单扩展名；
        // 禁止把业务编号（ticket_key）、原始文件名等用户输入拼进文件名，防路径穿越与信息泄露
        $sub = date('Ym');
        $dir = public_path() . 'uploads' . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('上传目录创建失败');
        }
        $name     = bin2hex(random_bytes(8));
        $filename = $name . '.' . $ext;
        $file->move($dir, $filename);

        $path  = 'uploads/' . $sub . '/' . $filename;
        $thumb = 'uploads/' . $sub . '/' . $name . '_thumb.' . $ext;
        try {
            self::makeThumb($dir . DIRECTORY_SEPARATOR . $filename,
                $dir . DIRECTORY_SEPARATOR . $name . '_thumb.' . $ext, 480);
        } catch (\Throwable $e) {
            $thumb = $path; // 缩略图失败时用原图，不阻塞上传
        }

        $photo              = new Photo();
        $photo->ticket_id   = $ticketId;
        $photo->kind        = $kind;
        $photo->file_path   = $path;
        $photo->thumb_path  = $thumb;
        $photo->sort        = (int) Photo::where('ticket_id', $ticketId)->where('kind', $kind)->max('sort') + 1;
        $photo->uploaded_by = $uploadedBy;
        $photo->save();

        return $photo;
    }

    /**
     * 用 GD 生成缩略图（宽 $width，等比）
     */
    public static function makeThumb(string $src, string $dst, int $width): void
    {
        $info = getimagesize($src);
        if (!$info) {
            throw new \RuntimeException('无法读取图片');
        }
        [$w, $h] = $info;
        if ($w <= $width) {
            copy($src, $dst);
            return;
        }
        $nw = $width;
        $nh = max(1, (int) round($h * $width / $w));

        $srcImg = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($src),
            'image/png'  => imagecreatefrompng($src),
            'image/gif'  => imagecreatefromgif($src),
            'image/webp' => imagecreatefromwebp($src),
            default      => throw new \RuntimeException('不支持的图片类型'),
        };
        if (!$srcImg) {
            throw new \RuntimeException('图片解码失败');
        }
        $dstImg = imagecreatetruecolor($nw, $nh);
        // PNG/GIF 保留透明
        if (in_array($info['mime'], ['image/png', 'image/gif'], true)) {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
        }
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
        match ($info['mime']) {
            'image/jpeg' => imagejpeg($dstImg, $dst, 82),
            'image/png'  => imagepng($dstImg, $dst, 6),
            'image/gif'  => imagegif($dstImg, $dst),
            'image/webp' => imagewebp($dstImg, $dst, 82),
            default      => null,
        };
    }

    /**
     * 删除照片记录及磁盘文件
     */
    public static function deletePhoto(Photo $photo): void
    {
        foreach ([$photo->file_path, $photo->thumb_path] as $p) {
            if ($p) {
                $abs = public_path() . ltrim($p, '/');
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
        }
        $photo->delete();
    }
}
