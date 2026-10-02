<?php
declare(strict_types=1);
namespace app\model;

use think\Model;
use think\facade\Db;

class Issue extends Model
{
    protected $name = 'issues';
    protected $pk = 'id';

    public const STATUS_PENDING   = 0; // 待整改
    public const STATUS_SUBMITTED = 1; // 学生已提交，待复核
    public const STATUS_PASSED    = 2; // 辅导员通过
    public const STATUS_REJECTED  = 3; // 辅导员驳回，需重新整改

    public static function statusMap(): array
    {
        return [
            self::STATUS_PENDING   => ['待整改', 'tag-danger'],
            self::STATUS_SUBMITTED => ['待复核', 'tag-warning'],
            self::STATUS_PASSED    => ['已通过', 'tag-success'],
            self::STATUS_REJECTED  => ['已驳回', 'tag-info'],
        ];
    }

    public function statusText(): array
    {
        return self::statusMap()[(int) $this->status] ?? ['未知', 'tag-info'];
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function item()
    {
        return $this->belongsTo(DeductionItem::class, 'deduction_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function photos()
    {
        return $this->hasMany(IssuePhoto::class, 'issue_id')->order('sort asc, id asc');
    }

    public function rectification()
    {
        return $this->hasOne(Rectification::class, 'issue_id');
    }

    public static function generateKey(): string
    {
        // 例：XG2610-A1B2C3，短且唯一
        for ($i = 0; $i < 10; $i++) {
            $key = 'XG' . date('ym') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            if (!Db::name('issues')->where('issue_key', $key)->find()) {
                return $key;
            }
        }
        // 极端情况再退化为更长随机串
        return 'XG' . date('ym') . '-' . strtoupper(bin2hex(random_bytes(8)));
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(24)); // 48 位十六进制
    }

    public function studentUrl(): string
    {
        return (string) url('/s/:token', ['token' => $this->token], false, true);
    }

    public function studentPath(): string
    {
        return '/s/' . $this->token;
    }
}
