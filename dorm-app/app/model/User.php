<?php
declare(strict_types=1);
namespace app\model;

use think\Model;

/**
 * @property int $id
 * @property string $username
 * @property string $real_name
 * @property string $role
 * @property int $is_active
 */
class User extends Model
{
    protected $name = 'users';
    protected $pk = 'id';

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
