<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

class Rectification extends Model
{
    protected $name = 'rectifications';
    protected $pk = 'id';

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    public function photos()
    {
        return $this->hasMany(RectificationPhoto::class, 'rectification_id')->order('sort asc, id asc');
    }
}
