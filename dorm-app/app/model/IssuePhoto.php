<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

class IssuePhoto extends Model
{
    protected $name = 'issue_photos';
    protected $pk = 'id';

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }
}
