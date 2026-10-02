<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

class DeductionItem extends Model
{
    protected $name = 'deduction_items';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
}
