<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Issue;
use app\model\Photo;
use app\model\Room;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\facade\Db;
use think\Request;

/**
 * 管理员：房间管理、上传问题照片、生成 key/扣分项/整改二维码
 */
class Admin extends BaseController
{
    /**
     * 仪表盘：房间列表 + 各状态问题数
     */
    public function index(Request $request)
    {
        $building = trim((string) $request->get('building', ''));

        $query = Room::order('building', 'asc')->order('room_no', 'asc');
        if ($building !== '') {
            $query->where('building', $building);
        }
        $rooms = $query->select();

        // 统计每个房间各状态问题数
        $countMap = [];
        foreach (Issue::field('room_id,status,COUNT(*) AS c')->group('room_id,status')->select() as $row) {
            $countMap[$row->room_id][$row->status] = (int) $row->c;
        }

        $buildings = Room::distinct(true)->order('building', 'asc')->column('building');

        return $this->fetch('admin/index', [
            'rooms'     => $rooms,
            'countMap'  => $countMap,
            'buildings' => $buildings,
            'building'  => $building,
        ]);
    }

    /**
     * 新建房间表单
     */
    public function roomCreate()
    {
        return $this->fetch('admin/room_create');
    }

    /**
     * 保存房间
     */
    public function roomStore(Request $request)
    {
        $building = trim((string) $request->post('building', ''));
        $roomNo   = trim((string) $request->post('room_no', ''));

        if ($building === '' || $roomNo === '') {
            return $this->fetch('admin/room_create', ['error' => '楼栋和房号不能为空', 'building' => $building, 'room_no' => $roomNo]);
        }
        if (Room::where('building', $building)->where('room_no', $roomNo)->find()) {
            return $this->fetch('admin/room_create', ['error' => '该房间已存在', 'building' => $building, 'room_no' => $roomNo]);
        }

        Room::create(['building' => $building, 'room_no' => $roomNo]);
        return redirect('/admin')->with('success', "房间 {$building} {$roomNo} 创建成功");
    }

    /**
     * 删除房间（连带问题单与照片文件）
     */
    public function roomDelete(Request $request)
    {
        $room = Room::find($request->post('id/d'));
        if (!$room) {
            return redirect('/admin')->with('error', '房间不存在');
        }
        Db::transaction(function () use ($room) {
            foreach (Issue::where('room_id', $room->id)->select() as $issue) {
                $this->deleteIssuePhotos($issue);
                $issue->delete();
            }
            $room->delete();
        });
        return redirect('/admin')->with('success', '房间及其问题单已删除');
    }

    /**
     * 房间详情：该房间的全部问题单
     */
    public function roomDetail(int $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return redirect('/admin')->with('error', '房间不存在');
        }
        $issues = Issue::where('room_id', $id)->order('id', 'desc')->select();
        return $this->fetch('admin/room_detail', ['room' => $room, 'issues' => $issues]);
    }

    /**
     * 新建问题单表单（选择房间、扣分项、上传问题照片）
     */
    public function issueCreate(Request $request)
    {
        return $this->fetch('admin/issue_create', [
            'rooms'   => Room::order('building', 'asc')->order('room_no', 'asc')->select(),
            'roomId'  => $request->get('room_id/d', 0),
            'items'   => config('dorm.deduct_items'),
        ]);
    }

    /**
     * 保存问题单：生成 key、扣分项、token，并保存问题照片
     */
    public function issueStore(Request $request)
    {
        $roomId  = $request->post('room_id/d', 0);
        $items   = (array) $request->post('items/a', []);
        $custom  = trim((string) $request->post('custom_item', ''));
        $score   = $request->post('deduct_score/d', 0);
        $remark  = trim((string) $request->post('remark', ''));

        $room = Room::find($roomId);
        if (!$room) {
            return redirect((string) url('Admin/issueCreate'))->with('error', '请选择有效的宿舍房间');
        }

        $preset = config('dorm.deduct_items');
        $picked = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '' && isset($preset[$item])) {
                $picked[] = $item;
            }
        }
        if ($custom !== '') {
            $picked[] = $custom;
        }
        if (!$picked) {
            return redirect((string) url('Admin/issueCreate', ['room_id' => $roomId]))->with('error', '请至少选择或填写一个扣分项');
        }
        if ($score < 0 || $score > 100) {
            return redirect((string) url('Admin/issueCreate', ['room_id' => $roomId]))->with('error', '扣分值需在 0~100 之间');
        }

        $files = $request->file('photos') ?: [];
        if (!is_array($files)) {
            $files = [$files];
        }
        $files = array_values(array_filter($files));
        if (!$files) {
            return redirect((string) url('Admin/issueCreate', ['room_id' => $roomId]))->with('error', '请至少上传一张问题照片');
        }
        if ($err = $this->checkImages($files)) {
            return redirect((string) url('Admin/issueCreate', ['room_id' => $roomId]))->with('error', $err);
        }

        $user  = session('admin_user');
        $issue = null;
        Db::transaction(function () use (&$issue, $room, $picked, $score, $remark, $files, $user) {
            $issue               = new Issue();
            $issue->room_id      = $room->id;
            $issue->issue_key    = Issue::makeKey($room);
            $issue->deduct_item  = implode('、', $picked);
            $issue->deduct_score = $score;
            $issue->token        = Issue::makeToken();
            $issue->remark       = $remark;
            $issue->status       = Issue::STATUS_PENDING;
            $issue->admin_id     = $user['id'] ?? 0;
            $issue->save();

            $this->savePhotos($issue, $files, Photo::TYPE_PROBLEM, $user['name'] ?? '管理员');
        });

        return redirect((string) url('Admin/issueDetail', ['id' => $issue->id]))
            ->with('success', '问题单已创建，key：' . $issue->issue_key);
    }

    /**
     * 问题单详情：key、扣分项、整改二维码、照片管理与排序
     */
    public function issueDetail(int $id)
    {
        $issue = Issue::with(['room'])->find($id);
        if (!$issue) {
            return redirect('/admin')->with('error', '问题单不存在');
        }
        $rectifyUrl = (string) url('Student/show', ['token' => $issue->token], false, true);

        return $this->fetch('admin/issue_detail', [
            'issue'         => $issue,
            'problemPhotos' => $issue->problemPhotos,
            'rectifyPhotos' => $issue->rectifyPhotos,
            'rectifyUrl'    => $rectifyUrl,
        ]);
    }

    /**
     * 删除问题单（连带照片文件）
     */
    public function issueDelete(Request $request)
    {
        $issue = Issue::find($request->post('id/d'));
        if ($issue) {
            $roomId = $issue->room_id;
            Db::transaction(function () use ($issue) {
                $this->deleteIssuePhotos($issue);
                $issue->delete();
            });
            return redirect((string) url('Admin/roomDetail', ['id' => $roomId]))->with('success', '问题单已删除');
        }
        return redirect('/admin')->with('error', '问题单不存在');
    }

    /**
     * 修改问题单状态（退回待整改）
     */
    public function issueStatus(Request $request)
    {
        $issue  = Issue::find($request->post('id/d'));
        $status = (string) $request->post('status', '');
        if (!$issue || !in_array($status, [Issue::STATUS_PENDING, Issue::STATUS_SUBMITTED, Issue::STATUS_CONFIRMED], true)) {
            return redirect('/admin')->with('error', '操作无效');
        }
        $issue->status = $status;
        $issue->save();
        return redirect((string) url('Admin/issueDetail', ['id' => $issue->id]))->with('success', '状态已更新为：' . status_text($status));
    }

    /**
     * 追加问题照片（ajax 或表单）
     */
    public function photoUpload(Request $request)
    {
        $issue = Issue::find($request->post('issue_id/d'));
        if (!$issue) {
            return json(['code' => 1, 'msg' => '问题单不存在']);
        }
        $type = $request->post('type') === Photo::TYPE_RECTIFY ? Photo::TYPE_RECTIFY : Photo::TYPE_PROBLEM;

        $files = $request->file('photos') ?: [];
        if (!is_array($files)) {
            $files = [$files];
        }
        $files = array_values(array_filter($files));
        if (!$files) {
            return json(['code' => 1, 'msg' => '请选择图片']);
        }
        if ($err = $this->checkImages($files)) {
            return json(['code' => 1, 'msg' => $err]);
        }

        $user = session('admin_user');
        $this->savePhotos($issue, $files, $type, $user['name'] ?? '管理员');

        return json(['code' => 0, 'msg' => '上传成功']);
    }

    /**
     * 照片排序（ajax）：id + dir(up/down)，与同类型相邻照片交换顺序
     */
    public function photoSort(Request $request)
    {
        $photo = Photo::find($request->post('id/d'));
        $dir   = $request->post('dir') === 'up' ? 'up' : 'down';
        if (!$photo) {
            return json(['code' => 1, 'msg' => '照片不存在']);
        }

        // 先重排消除重复排序值，再交换
        Photo::resequence($photo->issue_id, $photo->type);

        $query = Photo::where('issue_id', $photo->issue_id)->where('type', $photo->type);
        $sibling = $dir === 'up'
            ? (clone $query)->where('sort', '<', $photo->sort)->order('sort', 'desc')->order('id', 'desc')->find()
            : (clone $query)->where('sort', '>', $photo->sort)->order('sort', 'asc')->order('id', 'asc')->find();

        if (!$sibling) {
            return json(['code' => 1, 'msg' => $dir === 'up' ? '已经是第一张' : '已经是最后一张']);
        }

        Db::transaction(function () use ($photo, $sibling) {
            $tmp           = $photo->sort;
            $photo->sort   = $sibling->sort;
            $sibling->sort = $tmp;
            $photo->save();
            $sibling->save();
        });

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 删除照片（ajax）
     */
    public function photoDelete(Request $request)
    {
        $photo = Photo::find($request->post('id/d'));
        if (!$photo) {
            return json(['code' => 1, 'msg' => '照片不存在']);
        }
        $this->deletePhotoFile($photo);
        $photo->delete();
        Photo::resequence($photo->issue_id, $photo->type);
        return json(['code' => 0, 'msg' => '已删除']);
    }

    /**
     * 输出整改二维码 PNG（内容为带 token 的学生整改链接）
     */
    public function qrcode(string $token)
    {
        $issue = Issue::where('token', $token)->find();
        if (!$issue) {
            return response('not found', 404);
        }
        $url    = (string) url('Student/show', ['token' => $token], false, true);
        $result = (new PngWriter())->write(new QrCode(data: $url, size: 320, margin: 10));

        return response($result->getString(), 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * 保存一组上传图片到指定问题单
     * @param \think\file\UploadedFile[] $files
     */
    private function savePhotos(Issue $issue, array $files, string $type, string $uploader): void
    {
        $sort = Photo::nextSort($issue->id, $type);
        foreach ($files as $file) {
            Photo::create([
                'issue_id'  => $issue->id,
                'type'      => $type,
                'file_path' => $this->storeImage($file),
                'sort'      => $sort++,
                'uploader'  => $uploader,
            ]);
        }
    }

    /**
     * 删除问题单全部照片（文件 + 记录）
     */
    private function deleteIssuePhotos(Issue $issue): void
    {
        foreach (Photo::where('issue_id', $issue->id)->select() as $photo) {
            $this->deletePhotoFile($photo);
            $photo->delete();
        }
    }

    /**
     * 删除照片文件
     */
    private function deletePhotoFile(Photo $photo): void
    {
        $file = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . ltrim($photo->file_path, '/');
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
