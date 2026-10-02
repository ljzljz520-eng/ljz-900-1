<?php
declare(strict_types=1);
namespace app\controller\admin;

use app\BaseController;
use app\model\DeductionItem;
use app\model\Issue;
use app\model\IssuePhoto;
use app\model\Room;
use app\service\PhotoService;
use think\facade\Db;
use think\facade\View;
use think\response\Json;

class IssueController extends BaseController
{
    /** 问题单列表（管理员看全部） */
    public function index()
    {
        $status = $this->request->param('status', '');
        $keyword = trim((string) $this->request->param('keyword', ''));

        $query = Issue::with(['room', 'item', 'photos', 'rectification.photos'])
            ->order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($keyword !== '') {
            $query->whereLike('issue_key', '%' . $keyword . '%');
        }
        $issues = $query->paginate(15)
            ->appends(array_filter(['status' => $status, 'keyword' => $keyword]));

        View::assign([
            'issues'   => $issues,
            'status'    => $status,
            'keyword'   => $keyword,
            'statusMap' => Issue::statusMap(),
            'nav'       => 'issues',
        ]);
        return View::fetch('/admin/issues');
    }

    /** 新建问题单表单 */
    public function create()
    {
        $rooms = Room::order('building asc, room_no asc')->select();
        $items = DeductionItem::where('is_active', 1)->order('sort desc, id asc')->select();
        return View::fetch('/admin/issue_form', [
            'rooms' => $rooms,
            'items' => $items,
            'nav'   => 'issues',
        ]);
    }

    /** 保存问题单（可同时带首张照片） */
    public function store()
    {
        $roomId = (int) $this->request->post('room_id', 0);
        $itemId = (int) $this->request->post('deduction_item_id', 0);
        $score  = (int) $this->request->post('score', 0);
        $desc   = trim((string) $this->request->post('description', ''));

        $room = Room::find($roomId);
        $item = DeductionItem::find($itemId);
        if (!$room) {
            return $this->fail('请选择有效房间');
        }
        if (!$item) {
            return $this->fail('请选择扣分项');
        }
        if ($score < 0 || $score > 100) {
            $score = (int) $item->default_score;
        }

        Db::startTrans();
        try {
            $issue = new Issue();
            $issue->save([
                'issue_key'         => Issue::generateKey(),
                'room_id'           => $room->id,
                'counselor_id'      => $room->counselor_id,
                'deduction_item_id' => $item->id,
                'score'             => $score,
                'description'       => mb_substr($desc, 0, 500),
                'token'             => Issue::generateToken(),
                'status'            => Issue::STATUS_PENDING,
                'creator_id'        => (int) $this->request->userId,
            ]);

            $savedPhoto = '';
            $files = $this->request->file('photos');
            if ($files) {
                $files = is_array($files) ? $files : [$files];
                $sort = 0;
                foreach ($files as $file) {
                    if (!$file) {
                        continue;
                    }
                    $path = PhotoService::save($file, PhotoService::TYPE_ISSUE);
                    (new IssuePhoto())->save([
                        'issue_id' => $issue->id,
                        'path'     => $path,
                        'sort'     => $sort * 10,
                    ]);
                    $sort++;
                    $savedPhoto = $path;
                }
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            if (isset($savedPhoto) && $savedPhoto) {
                PhotoService::delete($savedPhoto);
            }
            return $this->fail('创建失败：' . $e->getMessage());
        }

        return redirect((string) url('/admin/issues/' . $issue->id));
    }

    /** 问题单详情（问题图 + 二维码 + 整改图） */
    public function read(int $id)
    {
        $issue = Issue::with(['room', 'item', 'creator', 'counselor', 'photos', 'rectification.photos'])->find($id);
        if (!$issue) {
            return $this->fail('问题单不存在', 404);
        }
        return View::fetch('/admin/issue_detail', [
            'issue'     => $issue,
            'statusMap' => Issue::statusMap(),
            'nav'       => 'issues',
        ]);
    }

    /** 追加问题照片（AJAX） */
    public function uploadPhoto(int $id): Json
    {
        $issue = Issue::find($id);
        if (!$issue) {
            return $this->jsonFail('问题单不存在', 404);
        }
        $file = $this->request->file('photo');
        if (!$file) {
            return $this->jsonFail('未收到图片');
        }
        try {
            $path = PhotoService::save($file, PhotoService::TYPE_ISSUE);
        } catch (\Throwable $e) {
            return $this->jsonFail($e->getMessage());
        }
        $maxSort = (int) IssuePhoto::where('issue_id', $id)->max('sort');
        $photo = new IssuePhoto();
        $photo->save([
            'issue_id' => $id,
            'path'     => $path,
            'sort'     => $maxSort + 10,
        ]);
        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['id' => $photo->id, 'path' => $path, 'sort' => $photo->sort],
        ]);
    }

    /** 删除问题照片（AJAX） */
    public function deletePhoto(int $id, int $photoId): Json
    {
        $photo = IssuePhoto::where('id', $photoId)->where('issue_id', $id)->find();
        if (!$photo) {
            return $this->jsonFail('照片不存在', 404);
        }
        PhotoService::delete($photo->path);
        $photo->delete();
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /** 更新照片排序（AJAX），入参 ids=[3,1,2] 按顺序 */
    public function reorderPhotos(int $id): Json
    {
        $ids = $this->request->post('ids/a', []);
        if (!$ids) {
            return $this->jsonFail('缺少排序数据');
        }
        $sort = 0;
        Db::startTrans();
        try {
            foreach ($ids as $photoId) {
                IssuePhoto::where('id', (int) $photoId)
                    ->where('issue_id', $id)
                    ->update(['sort' => $sort * 10]);
                $sort++;
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->jsonFail('排序失败');
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    private function fail(string $msg, int $status = 400)
    {
        return response($msg)->code($status);
    }

    private function jsonFail(string $msg, int $http = 400): Json
    {
        return json(['code' => 1, 'msg' => $msg])->code($http);
    }
}
