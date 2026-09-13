<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 整改单
 * status: 0待整改 1已提交整改 2复查通过 3复查驳回
 */
class Ticket extends Model
{
    protected $name = 'tickets';
    protected $autoWriteTimestamp = 'datetime';

    const STATUS_PENDING   = 0;
    const STATUS_SUBMITTED = 1;
    const STATUS_APPROVED  = 2;
    const STATUS_REJECTED  = 3;

    /**
     * 状态机：允许的状态流转（关键环节不可跳过）
     * 待整改 → 已提交；已提交 → 通过/驳回；驳回 → 已提交；通过 → 终态
     */
    const TRANSITIONS = [
        self::STATUS_PENDING   => [self::STATUS_SUBMITTED],
        self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_REJECTED  => [self::STATUS_SUBMITTED],
        self::STATUS_APPROVED  => [],
    ];

    public static function statusText(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING   => '待整改',
            self::STATUS_SUBMITTED => '已提交整改',
            self::STATUS_APPROVED  => '复查通过',
            self::STATUS_REJECTED  => '复查驳回',
            default                => '未知',
        };
    }

    public static function statusClass(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING   => 'badge-warn',
            self::STATUS_SUBMITTED => 'badge-info',
            self::STATUS_APPROVED  => 'badge-ok',
            self::STATUS_REJECTED  => 'badge-bad',
            default                => '',
        };
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function deductions()
    {
        return $this->hasMany(TicketDeduction::class, 'ticket_id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'ticket_id')->order('sort', 'asc');
    }

    /** 问题照片（管理员上传） */
    public function problemPhotos()
    {
        return $this->hasMany(Photo::class, 'ticket_id')->where('kind', 'problem')->order('sort', 'asc');
    }

    /** 整改照片（学生上传） */
    public function fixPhotos()
    {
        return $this->hasMany(Photo::class, 'ticket_id')->where('kind', 'fix')->order('sort', 'asc');
    }

    /**
     * 生成唯一整改单 key：DC-20260913-XXXXXX
     */
    public static function generateKey(): string
    {
        do {
            $key = 'DC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (self::where('ticket_key', $key)->find());
        return $key;
    }

    /**
     * 生成学生端 token
     */
    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (self::where('token', $token)->find());
        return $token;
    }

    /**
     * 学生端是否可编辑（待整改 / 被驳回）
     */
    public function isEditable(): bool
    {
        return in_array((int) $this->status, [self::STATUS_PENDING, self::STATUS_REJECTED], true);
    }

    /**
     * 校验是否允许流转到目标状态（所有状态变更必须先过此校验）
     */
    public function canTransitionTo(int $to): bool
    {
        return in_array($to, self::TRANSITIONS[(int) $this->status] ?? [], true);
    }

    /**
     * 流程产物（问题照/整改照）是否允许变更
     * 学生提交后（已提交/已归档）照片即冻结，防止跳过提交、复查环节篡改证据
     */
    public function isPhotoMutable(): bool
    {
        return $this->isEditable();
    }

    /**
     * 扣分总分
     */
    public function totalPoints(): float
    {
        return (float) $this->deductions()->sum('points');
    }
}
