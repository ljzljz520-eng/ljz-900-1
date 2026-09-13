<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 照片（kind: problem=问题照片 fix=整改照片）
 */
class Photo extends Model
{
    protected $name = 'photos';
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;

    const KIND_PROBLEM = 'problem';
    const KIND_FIX     = 'fix';

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** 原图 URL */
    public function getUrlAttr($value, $data): string
    {
        return '/' . ltrim($data['file_path'] ?? '', '/');
    }

    /** 缩略图 URL */
    public function getThumbUrlAttr($value, $data): string
    {
        return '/' . ltrim($data['thumb_path'] ?? $data['file_path'] ?? '', '/');
    }
}
