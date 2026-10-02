<?php
declare(strict_types=1);
namespace app\controller\admin;

use app\BaseController;
use app\model\Room;
use app\model\User;
use think\facade\Db;
use think\facade\View;

class RoomController extends BaseController
{
    public function index()
    {
        $rooms = Room::with(['counselor'])
            ->order('building asc, room_no asc')
            ->paginate(20);
        // 每间房的问题统计
        foreach ($rooms as $room) {
            $room->pending_count = Db::name('issues')
                ->where('room_id', $room->id)
                ->whereIn('status', [0, 1, 3])
                ->count();
        }
        return View::fetch('/admin/rooms', [
            'rooms'    => $rooms,
            'counselors' => User::where('role', 'counselor')->where('is_active', 1)->select(),
            'nav'      => 'rooms',
        ]);
    }

    public function store()
    {
        $building = trim((string) $this->request->post('building', ''));
        $roomNo   = trim((string) $this->request->post('room_no', ''));
        $counselorId = (int) $this->request->post('counselor_id', 0) ?: null;

        if ($building === '' || $roomNo === '') {
            return redirect((string) url('/admin/rooms'))->with('error', '楼栋和房间号必填');
        }
        $exists = Room::where('building', $building)->where('room_no', $roomNo)->find();
        if ($exists) {
            return redirect((string) url('/admin/rooms'))->with('error', '该房间已存在');
        }
        (new Room())->save([
            'building'     => $building,
            'room_no'      => $roomNo,
            'counselor_id' => $counselorId,
        ]);
        return redirect((string) url('/admin/rooms'));
    }

    public function update(int $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response('房间不存在', 404);
        }
        $room->save([
            'building'     => trim((string) $this->request->post('building', $room->building)),
            'room_no'      => trim((string) $this->request->post('room_no', $room->room_no)),
            'counselor_id' => (int) $this->request->post('counselor_id', 0) ?: null,
        ]);
        return redirect((string) url('/admin/rooms'));
    }

    public function delete(int $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return json(['code' => 1, 'msg' => '房间不存在'])->code(404);
        }
        $issueCount = Db::name('issues')->where('room_id', $id)->count();
        if ($issueCount > 0) {
            return json(['code' => 1, 'msg' => "该房间下有 {$issueCount} 个问题单，不能删除"]);
        }
        $room->delete();
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
