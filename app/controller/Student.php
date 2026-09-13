<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Photo;
use app\model\Ticket;
use app\service\UploadService;

/**
 * 学生端：扫码（带 token 链接）上传整改照片
 * 无需登录，凭 token 访问
 */
class Student extends BaseController
{
    /** @var Ticket|null */
    protected $ticket = null;

    protected function initialize()
    {
        $token  = (string) $this->request->param('token', '');
        $ticket = Ticket::with(['room', 'deductions', 'problemPhotos', 'fixPhotos'])
            ->where('token', $token)->find();
        if (!$ticket) {
            throw new \think\exception\HttpException(404, '链接无效或已过期');
        }
        $this->ticket = $ticket;
    }

    /** 整改页面 */
    public function show($token)
    {
        return $this->view('student/show', [
            'ticket'   => $this->ticket,
            'editable' => $this->ticket->isEditable(),
            'maxPhoto' => (int) config('dorm.student_max_photos'),
        ]);
    }

    /** 上传整改照片（AJAX，可多张） */
    public function upload($token)
    {
        if (!$this->ticket->isEditable()) {
            return $this->fail('当前状态不能上传（已提交或已通过）');
        }
        $max   = (int) config('dorm.student_max_photos');
        $count = $this->ticket->fixPhotos()->count();
        if ($count >= $max) {
            return $this->fail('最多上传 ' . $max . ' 张整改照片');
        }

        $files = $this->request->file('photos') ?: [];
        if (!is_array($files)) {
            $files = [$files];
        }
        if (!$files) {
            return $this->fail('请选择图片');
        }
        if ($count + count($files) > $max) {
            return $this->fail('超出数量限制，还可上传 ' . ($max - $count) . ' 张');
        }

        $saved = [];
        try {
            foreach ($files as $file) {
                $photo   = UploadService::saveImage($file, $this->ticket->id, Photo::KIND_FIX, 'student');
                $saved[] = ['id' => $photo->id, 'url' => $photo->url, 'thumb' => $photo->thumb_url];
            }
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->ok($saved, '上传成功');
    }

    /** 删除整改照片（未提交前） */
    public function deletePhoto($token, $id)
    {
        if (!$this->ticket->isEditable()) {
            return $this->fail('当前状态不能删除');
        }
        $photo = Photo::where('ticket_id', $this->ticket->id)
            ->where('kind', Photo::KIND_FIX)->where('id', (int) $id)->find();
        if (!$photo) {
            return $this->fail('照片不存在');
        }
        UploadService::deletePhoto($photo);
        return $this->ok([], '已删除');
    }

    /** 整改照片排序 */
    public function sort($token)
    {
        if (!$this->ticket->isEditable()) {
            return $this->fail('当前状态不能排序');
        }
        $ids = (array) $this->request->post('ids/a', []);
        if (!$ids) {
            return $this->fail('参数错误');
        }
        $sort = 1;
        foreach ($ids as $pid) {
            Photo::where('ticket_id', $this->ticket->id)->where('kind', Photo::KIND_FIX)
                ->where('id', (int) $pid)->update(['sort' => $sort++]);
        }
        return $this->ok([], '排序已保存');
    }

    /** 提交整改 */
    public function submit($token)
    {
        if (!$this->ticket->canTransitionTo(Ticket::STATUS_SUBMITTED)) {
            return $this->fail('当前状态不能提交');
        }
        if ($this->ticket->fixPhotos()->count() === 0) {
            return $this->fail('请先上传整改后的照片再提交');
        }
        $this->ticket->status       = Ticket::STATUS_SUBMITTED;
        $this->ticket->submitted_at = date('Y-m-d H:i:s');
        $this->ticket->save();

        return $this->ok([], '整改已提交，等待辅导员/管理员复查');
    }
}
