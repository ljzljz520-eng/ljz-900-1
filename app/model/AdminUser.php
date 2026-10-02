<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 后台账号（管理员/辅导员）
 */
class AdminUser extends Model
{
    protected $name = 'admin_user';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    const ROLE_ADMIN      = 'admin';
    const ROLE_COUNSELOR  = 'counselor';

    /**
     * 校验登录，成功返回用户实例，失败返回 null
     */
    public static function checkLogin(string $username, string $password): ?self
    {
        $user = self::where('username', $username)->find();
        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }
        return $user;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
