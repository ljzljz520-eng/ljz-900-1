<?php
declare(strict_types=1);
namespace app\controller;

use app\BaseController;
use app\model\Issue;
use app\model\Rectification;
use app\model\RectificationPhoto;
use app\service\PhotoService;
use think\facade\Db;
use think\facade\View;
use think\response\Json;

class StudentController extends BaseController
{
    /** 学生扫码落地页：凭 token 展示问题与整改入口 */
    public function show(string $token)
    {
        $issue = $this->findByToken($token);
        if (!$issue) {
            return View::fetch('/student/invalid', ['token' => $token]);
        }
        $issue = Issue::with(['room', 'item', 'photos', 'rectification.photos'])->find($issue->id);
        return View::fetch('/student/show', [
            'issue'     => $issue,
            'statusMap' => Issue::statusMap(),
            'token'     => $token,
        ]);
    }

    /** 上传/替换整改照片。重新提交会清除旧整改图（保留一版整改结果） */
    public function upload(string $token): Json
    {
        $issue = $this->findByToken($token);
        if (!$issue) {
            return json(['code' => 1, 'msg' => '链接无效或已失效'])->code(404);
        }
        if ((int) $issue->status === Issue::STATUS_PASSED) {
            return json(['code' => 1, 'msg' => '该问题已通过审核，无需再上传']);
        }
        $files = $this->request->file('photos');
        if (!$files) {
            return json(['code' => 1, 'msg' => '请选择至少一张图片']);
        }
        $files = is_array($files) ? $files : [$files];
        $note = trim((string) $this->request->post('note', ''));

        $savedPaths = [];
        Db::startTrans();
        try {
            // 一条问题对应一条整改记录（不存在则建）
            $rect = Rectification::where('issue_id', $issue->id)->find();
            if (!$rect) {
                $rect = new Rectification();
                $rect->save(['issue_id' => $issue->id, 'note' => mb_substr($note, 0, 500)]);
            } else {
                $rect->save(['note' => mb_substr($note, 0, 500)]);
                // 重新提交：删除旧整改照片
                $old = RectificationPhoto::where('rectification_id', $rect->id)->select();
                foreach ($old as $oldPhoto) {
                    PhotoService::delete($oldPhoto->path);
                    $oldPhoto->delete();
                }
            }

            $sort = 0;
            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }
                $path = PhotoService::save($file, PhotoService::TYPE_RECTIFY);
                $savedPaths[] = $path;
                (new RectificationPhoto())->save([
                    'rectification_id' => $rect->id,
                    'issue_id'         => $issue->id,
                    'path'             => $path,
                    'sort'             => $sort * 10,
                ]);
                $sort++;
            }
            if (!$savedPaths) {
                throw new \RuntimeException('没有可保存的图片');
            }

            $issue->save([
                'status'       => Issue::STATUS_SUBMITTED,
                'rectified_at' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            foreach ($savedPaths as $p) {
                PhotoService::delete($p);
            }
            return json(['code' => 1, 'msg' => '上传失败：' . $e->getMessage()]);
        }

        return json(['code' => 0, 'msg' => '整改照片已提交，等待辅导员复核', 'data' => ['paths' => $savedPaths]]);
    }

    private function findByToken(string $token): ?Issue
    {
        if (!preg_match('/^[0-9a-f]{48}$/', $token)) {
            return null;
        }
        return Issue::where('token', $token)->find();
    }
}
