<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 宿舍房间
 */
class Room extends Model
{
    protected $name = 'room';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    public function issues()
    {
        return $this->hasMany(Issue::class, 'room_id', 'id');
    }

    /**
     * 房间全名，如：3号楼 502
     */
    public function getFullNameAttr($value, $data): string
    {
        return $data['building'] . ' ' . $data['room_no'];
    }
}
