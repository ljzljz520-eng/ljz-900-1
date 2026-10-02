<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Issue;
use app\model\Photo;
use app\model\Room;
use think\Request;

/**
 * 辅导员：汇总页按房间 + key 成对查看问题图/整改图，并确认整改结果
 */
class Counselor extends BaseController
{
    /**
     * 汇总页：按房间分组，每个 key 下问题图与整改图成对展示
     */
    public function index(Request $request)
    {
        $building = trim((string) $request->get('building', ''));
        $status   = trim((string) $request->get('status', ''));
        $keyword  = trim((string) $request->get('keyword', ''));

        $query = Issue::with(['room', 'photos']);

        if ($building !== '') {
            $query->hasWhere('room', function ($q) use ($building) {
                $q->where('building', $building);
            });
        }
        if ($status !== '' && in_array($status, [Issue::STATUS_PENDING, Issue::STATUS_SUBMITTED, Issue::STATUS_CONFIRMED], true)) {
            $query->where('status', $status);
        }
        if ($keyword !== '') {
            // 关键字同时匹配问题单 key 或房号
            $roomIds = Room::where('room_no', 'like', '%' . $keyword . '%')->column('id');
            $query->where(function ($q) use ($keyword, $roomIds) {
                $q->where('issue_key', 'like', '%' . $keyword . '%');
                if ($roomIds) {
                    $q->whereOr('room_id', 'in', $roomIds);
                }
            });
        }

        $issues = $query->order('id', 'desc')->paginate([
            'list_rows' => 10,
            'query'     => $request->get(),
        ]);

        // 按房间分组，并把问题图/整改图按顺序配对
        $groups = [];
        foreach ($issues as $issue) {
            $problems = $rectifies = [];
            foreach ($issue->photos as $photo) {
                if ($photo->type === Photo::TYPE_PROBLEM) {
                    $problems[] = $photo;
                } else {
                    $rectifies[] = $photo;
                }
            }
            $pairs = [];
            $max   = max(count($problems), count($rectifies));
            for ($i = 0; $i < $max; $i++) {
                $pairs[] = [
                    'problem' => $problems[$i] ?? null,
                    'rectify' => $rectifies[$i] ?? null,
                ];
            }

            $rid = $issue->room_id;
            if (!isset($groups[$rid])) {
                $groups[$rid] = ['room' => $issue->room, 'issues' => []];
            }
            $groups[$rid]['issues'][] = ['issue' => $issue, 'pairs' => $pairs];
        }

        return $this->fetch('counselor/index', [
            'groups'    => $groups,
            'issues'    => $issues,
            'buildings' => Room::distinct(true)->order('building', 'asc')->column('building'),
            'building'  => $building,
            'status'    => $status,
            'keyword'   => $keyword,
        ]);
    }

    /**
     * 整改结果确认：confirm=确认完成 / reject=退回待整改
     */
    public function confirm(Request $request)
    {
        $issue  = Issue::find($request->post('id/d'));
        $action = (string) $request->post('action', '');
        if (!$issue || !in_array($action, ['confirm', 'reject'], true)) {
            return redirect('/counselor')->with('error', '操作无效');
        }

        if ($action === 'confirm') {
            if ($issue->status !== Issue::STATUS_SUBMITTED) {
                return redirect('/counselor')->with('error', '仅“已提交整改”的问题单可确认完成');
            }
            $issue->status = Issue::STATUS_CONFIRMED;
            $msg           = '已确认完成';
        } else {
            if ($issue->status === Issue::STATUS_PENDING) {
                return redirect('/counselor')->with('error', '该问题单已是待整改状态');
            }
            $issue->status = Issue::STATUS_PENDING;
            $msg           = '已退回为待整改';
        }
        $issue->save();

        return redirect('/counselor')->with('success', $msg . '：' . $issue->issue_key);
    }
}
