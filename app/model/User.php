<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户（admin=管理员 counselor=辅导员）
 */
class User extends Model
{
    protected $name = 'users';
    protected $autoWriteTimestamp = 'datetime';

    const ROLE_ADMIN      = 'admin';
    const ROLE_COUNSELOR  = 'counselor';
}
