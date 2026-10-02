<?php
declare(strict_types=1);
namespace app\controller\counselor;

use app\BaseController;
use app\model\Issue;
use app\model\IssuePhoto;
use app\model\RectificationPhoto;
use think\facade\Db;
use think\facade\View;
use think\response\Json;

class IssueController extends BaseController
{
    /**
     * 汇总页：以房间为维度，列出每个房间下的问题单（含问题图/整改图）。
     * 辅导员只看自己负责房间；管理员可看全部。
     */
    public function index()
    {
        $roomId  = (int) $this->request->param('room_id', 0);
        $status  = $this->request->param('status', '');
        $keyword = trim((string) $this->request->param('keyword', ''));
        $onlyNeedReview = (int) $this->request->param('need_review', 0);

        $issueQuery = Issue::with(['room', 'item', 'photos', 'rectification.photos']);
        if ($this->request->userRole === 'counselor') {
            $issueQuery->where('counselor_id', $this->request->userId);
        }
        if ($roomId) {
            $issueQuery->where('room_id', $roomId);
        }
        if ($status !== '') {
            $issueQuery->where('status', (int) $status);
        }
        if ($keyword !== '') {
            $issueQuery->whereLike('issue_key', '%' . $keyword . '%');
        }
        if ($onlyNeedReview) {
            $issueQuery->where('status', Issue::STATUS_SUBMITTED);
        }
        $issues = $issueQuery->order('room_id asc, id desc')->select();

        // 按房间分组
        $grouped = [];
        foreach ($issues as $issue) {
            $rid = $issue->room_id;
            if (!isset($grouped[$rid])) {
                $grouped[$rid] = [
                    'room'   => $issue->room,
                    'issues' => [],
                ];
            }
            $grouped[$rid]['issues'][] = $issue;
        }

        // 房间下拉（辅导员自己的房间）
        $roomQuery = Db::name('rooms');
        if ($this->request->userRole === 'counselor') {
            $roomQuery->where('counselor_id', $this->request->userId);
        }
        $rooms = $roomQuery->order('building asc, room_no asc')->select()->toArray();

        // 统计
        $statQuery = Db::name('issues');
        if ($this->request->userRole === 'counselor') {
            $statQuery->where('counselor_id', $this->request->userId);
        }
        $stats = [
            'pending'   => (clone $statQuery)->where('status', Issue::STATUS_PENDING)->count(),
            'submitted' => (clone $statQuery)->where('status', Issue::STATUS_SUBMITTED)->count(),
            'passed'    => (clone $statQuery)->where('status', Issue::STATUS_PASSED)->count(),
            'rejected'  => (clone $statQuery)->where('status', Issue::STATUS_REJECTED)->count(),
        ];

        return View::fetch('/counselor/issues', [
            'grouped'        => $grouped,
            'rooms'          => $rooms,
            'stats'          => $stats,
            'roomId'         => $roomId,
            'status'         => $status,
            'keyword'        => $keyword,
            'needReview'     => $onlyNeedReview,
            'statusMap'      => Issue::statusMap(),
            'nav'            => 'summary',
        ]);
    }

    /** 单个问题详情（成对查看） */
    public function read(int $id)
    {
        $issue = Issue::with(['room', 'item', 'photos', 'rectification.photos', 'counselor'])->find($id);
        if (!$issue) {
            return response('问题单不存在', 404);
        }
        $this->assertCanAccess($issue);
        return View::fetch('/counselor/detail', [
            'issue'     => $issue,
            'statusMap' => Issue::statusMap(),
            'nav'       => 'summary',
        ]);
    }

    /** 通过：整改合格 */
    public function approve(int $id): Json
    {
        $issue = Issue::find($id);
        if (!$issue) {
            return json(['code' => 1, 'msg' => '问题单不存在'])->code(404);
        }
        $this->assertCanAccess($issue);
        if (!in_array((int) $issue->status, [Issue::STATUS_SUBMITTED, Issue::STATUS_REJECTED], true)) {
            return json(['code' => 1, 'msg' => '当前状态不可通过（需学生先提交整改）'])->code(400);
        }
        if (!$issue->rectification || $issue->rectification->photos->isEmpty()) {
            return json(['code' => 1, 'msg' => '还没有整改照片，无法通过'])->code(400);
        }
        $issue->save(['status' => Issue::STATUS_PASSED, 'close_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '已通过']);
    }

    /** 驳回：要求重新整改，学生可继续替换图片 */
    public function reject(int $id): Json
    {
        $issue = Issue::find($id);
        if (!$issue) {
            return json(['code' => 1, 'msg' => '问题单不存在'])->code(404);
        }
        $this->assertCanAccess($issue);
        if ((int) $issue->status !== Issue::STATUS_SUBMITTED) {
            return json(['code' => 1, 'msg' => '仅待复核状态可驳回'])->code(400);
        }
        $issue->save(['status' => Issue::STATUS_REJECTED, 'close_time' => null]);
        return json(['code' => 0, 'msg' => '已驳回，学生可重新上传']);
    }

    private function assertCanAccess(Issue $issue): void
    {
        if ($this->request->userRole === 'counselor'
            && (int) $issue->counselor_id !== (int) $this->request->userId) {
            $isAjax = $this->request->isAjax();
            $response = $isAjax
                ? json(['code' => 403, 'msg' => '无权访问该问题单'])->code(403)
                : \think\Response::create('403 无权访问该问题单', 'html', 403);
            throw new \think\exception\HttpResponseException($response);
        }
    }
}
