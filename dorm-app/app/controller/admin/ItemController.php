<?php
declare(strict_types=1);
namespace app\controller\admin;

use app\BaseController;
use app\model\DeductionItem;
use think\facade\View;

class ItemController extends BaseController
{
    public function index()
    {
        $items = DeductionItem::order('sort desc, id asc')->paginate(30);
        return View::fetch('/admin/items', ['items' => $items, 'nav' => 'items']);
    }

    public function store()
    {
        $name = trim((string) $this->request->post('name', ''));
        $score = max(0, min(100, (int) $this->request->post('default_score', 0)));
        if ($name === '') {
            return redirect((string) url('/admin/items'));
        }
        if (DeductionItem::where('name', $name)->find()) {
            return redirect((string) url('/admin/items'));
        }
        (new DeductionItem())->save([
            'name'          => $name,
            'default_score' => $score,
            'sort'          => (int) $this->request->post('sort', 0),
            'is_active'     => 1,
        ]);
        return redirect((string) url('/admin/items'));
    }

    public function update(int $id)
    {
        $item = DeductionItem::find($id);
        if (!$item) {
            return response('扣分项不存在', 404);
        }
        $item->save([
            'name'          => trim((string) $this->request->post('name', $item->name)),
            'default_score' => max(0, min(100, (int) $this->request->post('default_score', $item->default_score))),
            'is_active'     => $this->request->post('is_active', $item->is_active) ? 1 : 0,
        ]);
        return redirect((string) url('/admin/items'));
    }

    public function delete(int $id)
    {
        $item = DeductionItem::find($id);
        if (!$item) {
            return json(['code' => 1, 'msg' => '扣分项不存在'])->code(404);
        }
        $item->delete();
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
