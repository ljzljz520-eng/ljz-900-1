<?php
declare (strict_types = 1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Photo;
use app\model\Room;
use app\model\Ticket as TicketModel;
use app\model\TicketDeduction;
use app\service\UploadService;
use think\facade\Db;

/**
 * 整改单管理（仅管理员）
 */
class Ticket extends BaseController
{
    /** 整改单列表 */
    public function index()
    {
        $status = $this->request->get('status', '');
        $kw     = trim((string) $this->request->get('kw', ''));

        $query = TicketModel::with(['room', 'deductions'])
            ->withCount(['problemPhotos', 'fixPhotos'])
            ->order('id', 'desc');

        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int) $status);
        }
        if ($kw !== '') {
            $query->whereLike('ticket_key', '%' . $kw . '%');
        }

        $list = $query->paginate(['list_rows' => 15, 'query' => $this->request->get()]);

        return $this->view('admin/ticket_list', [
            'list'   => $list,
            'status' => $status,
            'kw'     => $kw,
        ]);
    }

    /** 新建整改单页面 */
    public function create()
    {
        return $this->view('admin/ticket_create', [
            'rooms'   => Room::order('building', 'asc')->order('room_no', 'asc')->select(),
            'presets' => config('dorm.deduction_presets'),
        ]);
    }

    /** 保存整改单（问题照片 + 扣分项） */
    public function save()
    {
        $roomId = (int) $this->request->post('room_id', 0);
        $remark = trim((string) $this->request->post('remark', ''));
        $items  = (array) $this->request->post('items/a', []);   // 扣分项名称数组
        $points = (array) $this->request->post('points/a', []);  // 对应分值数组

        if (!Room::find($roomId)) {
            return $this->fail('请选择有效的宿舍房间');
        }

        // 组装扣分项
        $deductions = [];
        foreach ($items as $i => $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $point = isset($points[$i]) ? (float) $points[$i] : 0;
            if ($point <= 0 || $point > 20) {
                return $this->fail('扣分项「' . $item . '」分值无效（0~20）');
            }
            $deductions[] = ['item' => mb_substr($item, 0, 100), 'points' => $point];
        }
        if (!$deductions) {
            return $this->fail('请至少填写一个扣分项');
        }

        $files = $this->request->file('photos') ?: [];
        if (!is_array($files)) {
            $files = [$files];
        }
        if (!$files) {
            return $this->fail('请至少上传一张问题照片');
        }

        Db::startTrans();
        try {
            $ticket              = new TicketModel();
            $ticket->ticket_key  = TicketModel::generateKey();
            $ticket->room_id     = $roomId;
            $ticket->token       = TicketModel::generateToken();
            $ticket->status      = TicketModel::STATUS_PENDING;
            $ticket->remark      = mb_substr($remark, 0, 255);
            $ticket->created_by  = $this->user['id'];
            $ticket->save();

            foreach ($deductions as $d) {
                $ticket->deductions()->save($d);
            }
            foreach ($files as $file) {
                UploadService::saveImage($file, $ticket->id, Photo::KIND_PROBLEM, $this->user['username']);
            }
            Db::commit();
        } catch (\DomainException $e) {
            Db::rollback();
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->fail('保存失败：' . $e->getMessage());
        }

        return $this->ok(['id' => $ticket->id, 'url' => '/admin/ticket/' . $ticket->id],
            '整改单创建成功，key：' . $ticket->ticket_key);
    }

    /** 整改单详情（含二维码、照片管理、复查） */
    public function detail($id)
    {
        $ticket = TicketModel::with(['room', 'deductions', 'problemPhotos', 'fixPhotos'])->find((int) $id);
        if (!$ticket) {
            return response(view('error', ['title' => '404', 'msg' => '整改单不存在', 'user' => $this->user]), 404);
        }
        $studentUrl = $this->request->domain() . '/s/' . $ticket->token;

        return $this->view('admin/ticket_detail', [
            'ticket'     => $ticket,
            'studentUrl' => $studentUrl,
        ]);
    }

    /** 输出整改二维码（SVG，内容为带 token 的学生端链接） */
    public function qrcode($id)
    {
        $ticket = TicketModel::find((int) $id);
        if (!$ticket) {
            return response('not found', 404);
        }
        $url = $this->request->domain() . '/s/' . $ticket->token;

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(320, 2),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $svg = (new \BaconQrCode\Writer($renderer))->writeString($url);

        return response($svg)->contentType('image/svg+xml');
    }

    /** 复查：通过 / 驳回 */
    public function review($id)
    {
        $ticket = TicketModel::find((int) $id);
        if (!$ticket) {
            return $this->fail('整改单不存在');
        }
        $action = (string) $this->request->post('action', '');
        $note   = mb_substr(trim((string) $this->request->post('note', '')), 0, 255);

        if (!in_array($action, ['approve', 'reject'], true)) {
            return $this->fail('无效的操作');
        }
        if ((int) $ticket->status !== TicketModel::STATUS_SUBMITTED) {
            return $this->fail('当前状态不能复查（需学生先提交整改）');
        }

        $ticket->status      = $action === 'approve' ? TicketModel::STATUS_APPROVED : TicketModel::STATUS_REJECTED;
        $ticket->review_note = $note;
        $ticket->reviewed_at = date('Y-m-d H:i:s');
        $ticket->save();

        return $this->ok([], $action === 'approve' ? '已复查通过' : '已驳回，等待学生重新整改');
    }

    /** 补传问题照片 */
    public function uploadPhoto($id)
    {
        $ticket = TicketModel::find((int) $id);
        if (!$ticket) {
            return $this->fail('整改单不存在');
        }
        $file = $this->request->file('photo');
        if (!$file) {
            return $this->fail('请选择图片');
        }
        try {
            $photo = UploadService::saveImage($file, $ticket->id, Photo::KIND_PROBLEM, $this->user['username']);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->ok([
            'id'    => $photo->id,
            'url'   => $photo->url,
            'thumb' => $photo->thumb_url,
        ], '上传成功');
    }

    /** 删除照片（问题照/整改照均可，管理员权限） */
    public function deletePhoto($id)
    {
        $photo = Photo::find((int) $id);
        if (!$photo) {
            return $this->fail('照片不存在');
        }
        UploadService::deletePhoto($photo);
        return $this->ok([], '照片已删除');
    }

    /** 照片排序：接收有序 id 数组 */
    public function sortPhotos($id)
    {
        $ticket = TicketModel::find((int) $id);
        if (!$ticket) {
            return $this->fail('整改单不存在');
        }
        $kind = (string) $this->request->post('kind', '');
        $ids  = (array) $this->request->post('ids/a', []);
        if (!in_array($kind, [Photo::KIND_PROBLEM, Photo::KIND_FIX], true) || !$ids) {
            return $this->fail('参数错误');
        }
        $sort = 1;
        foreach ($ids as $pid) {
            Photo::where('ticket_id', $ticket->id)->where('kind', $kind)
                ->where('id', (int) $pid)->update(['sort' => $sort++]);
        }
        return $this->ok([], '排序已保存');
    }
}
