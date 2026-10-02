<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 照片（问题图 / 整改图，sort 控制排序）
 */
class Photo extends Model
{
    protected $name = 'photo';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    const TYPE_PROBLEM = 'problem';  // 问题照片
    const TYPE_RECTIFY = 'rectify';  // 整改照片

    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id', 'id');
    }

    /**
     * 该问题单某类型照片的下一个排序号
     */
    public static function nextSort(int $issueId, string $type): int
    {
        return (int) self::where('issue_id', $issueId)->where('type', $type)->max('sort') + 1;
    }

    /**
     * 将某问题单某类型的照片按当前顺序重排为 1..n（消除重复排序值）
     */
    public static function resequence(int $issueId, string $type): void
    {
        $photos = self::where('issue_id', $issueId)->where('type', $type)
            ->order('sort', 'asc')->order('id', 'asc')->select();
        $i = 1;
        foreach ($photos as $photo) {
            if ((int) $photo->sort !== $i) {
                $photo->sort = $i;
                $photo->save();
            }
            $i++;
        }
    }
}
