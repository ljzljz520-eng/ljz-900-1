<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 宿舍房间
 */
class Room extends Model
{
    protected $name = 'rooms';
    protected $autoWriteTimestamp = 'datetime';

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'room_id');
    }

    /**
     * 显示名：1号楼 101
     */
    public function getLabelAttr($value, $data): string
    {
        return ($data['building'] ?? '') . ' ' . ($data['room_no'] ?? '');
    }
}
