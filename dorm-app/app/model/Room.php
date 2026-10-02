<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

class Room extends Model
{
    protected $name = 'rooms';
    protected $pk = 'id';

    public function label(): string
    {
        return $this->building . ' ' . $this->room_no;
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'room_id');
    }
}
