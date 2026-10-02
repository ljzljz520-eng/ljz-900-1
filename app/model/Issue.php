<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 整改问题单（一条记录 = 一个 key + 一组扣分项 + 一个整改二维码 token）
 */
class Issue extends Model
{
    protected $name = 'issue';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    const STATUS_PENDING   = 'pending';    // 待整改
    const STATUS_SUBMITTED = 'submitted';  // 学生已提交整改图
    const STATUS_CONFIRMED = 'confirmed';  // 辅导员已确认

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'issue_id', 'id')
            ->order('sort', 'asc')->order('id', 'asc');
    }

    /**
     * 问题照片（管理员上传）
     */
    public function problemPhotos()
    {
        return $this->hasMany(Photo::class, 'issue_id', 'id')
            ->where('type', Photo::TYPE_PROBLEM)
            ->order('sort', 'asc')->order('id', 'asc');
    }

    /**
     * 整改照片（学生上传）
     */
    public function rectifyPhotos()
    {
        return $this->hasMany(Photo::class, 'issue_id', 'id')
            ->where('type', Photo::TYPE_RECTIFY)
            ->order('sort', 'asc')->order('id', 'asc');
    }

    /**
     * 生成唯一的问题单 key：楼栋-房号-日期-随机串，如 3-502-20261002-K7Z2
     */
    public static function makeKey(Room $room): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $key = sprintf(
                '%s-%s-%s-%s',
                $room->building,
                $room->room_no,
                date('Ymd'),
                substr(str_shuffle(str_repeat($chars, 4)), 0, 4)
            );
        } while (static::where('issue_key', $key)->find());
        return $key;
    }

    /**
     * 生成唯一 token（用于整改二维码链接）
     */
    public static function makeToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (static::where('token', $token)->find());
        return $token;
    }
}
