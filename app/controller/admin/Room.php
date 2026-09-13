<?php
declare (strict_types = 1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Room as RoomModel;
use app\model\Ticket;

/**
 * 房间管理（仅管理员）
 */
class Room extends BaseController
{
    /** 房间列表 */
    public function index()
    {
        $rooms = RoomModel::withCount('tickets')->order('building', 'asc')->order('room_no', 'asc')->select();
        return $this->view('admin/room_list', ['rooms' => $rooms]);
    }

    /** 添加房间 */
    public function save()
    {
        $building = trim((string) $this->request->post('building', ''));
        $roomNo   = trim((string) $this->request->post('room_no', ''));

        if ($building === '' || $roomNo === '') {
            return $this->fail('楼栋和房间号不能为空');
        }
        if (RoomModel::where('building', $building)->where('room_no', $roomNo)->find()) {
            return $this->fail('该房间已存在');
        }
        $room           = new RoomModel();
        $room->building = $building;
        $room->room_no  = $roomNo;
        $room->save();

        return $this->ok(['id' => $room->id], '房间添加成功');
    }

    /** 删除房间 */
    public function delete($id)
    {
        $room = RoomModel::find((int) $id);
        if (!$room) {
            return $this->fail('房间不存在');
        }
        if (Ticket::where('room_id', $room->id)->count() > 0) {
            return $this->fail('该房间下已有整改单，不能删除');
        }
        $room->delete();
        return $this->ok([], '房间已删除');
    }
}
