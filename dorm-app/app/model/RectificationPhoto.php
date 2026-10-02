<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

class RectificationPhoto extends Model
{
    protected $name = 'rectification_photos';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
}
