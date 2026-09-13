<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Room;
use app\model\Ticket;

/**
 * 辅导员汇总页：按房间 + key 成对查看问题照/整改照
 * 管理员与辅导员均可访问
 */
class Summary extends BaseController
{
    public function index()
    {
        $roomId = (int) $this->request->get('room_id', 0);
        $status = $this->request->get('status', '');
        $kw     = trim((string) $this->request->get('kw', ''));

        $query = Ticket::with(['room', 'deductions', 'problemPhotos', 'fixPhotos'])
            ->order('id', 'desc');
        if ($roomId > 0) {
            $query->where('room_id', $roomId);
        }
        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int) $status);
        }
        if ($kw !== '') {
            $query->whereLike('ticket_key', '%' . $kw . '%');
        }
        $tickets = $query->paginate(['list_rows' => 10, 'query' => $this->request->get()]);

        // 为每个整改单生成 问题照<->整改照 配对
        $pairsMap = [];
        foreach ($tickets as $t) {
            $problems = $t->problemPhotos;
            $fixes    = $t->fixPhotos;
            $pairs    = [];
            $max      = max(count($problems), count($fixes));
            for ($i = 0; $i < $max; $i++) {
                $pairs[] = [
                    'problem' => $problems[$i] ?? null,
                    'fix'     => $fixes[$i] ?? null,
                ];
            }
            $pairsMap[$t->id] = $pairs;
        }

        return $this->view('summary/index', [
            'tickets'  => $tickets,
            'pairsMap' => $pairsMap,
            'rooms'    => Room::order('building', 'asc')->order('room_no', 'asc')->select(),
            'roomId'   => $roomId,
            'status'   => $status,
            'kw'       => $kw,
        ]);
    }
}
