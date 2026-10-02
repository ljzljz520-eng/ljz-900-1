<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Issue;
use app\model\Photo;
use think\Request;

/**
 * 学生端：凭二维码 token 查看问题并上传整改图（无需登录）
 */
class Student extends BaseController
{
    /**
     * 整改页面：问题照片 + 扣分项 + 上传整改图
     */
    public function show(string $token)
    {
        $issue = $this->findByToken($token);
        if (!$issue) {
            return $this->fetch('student/invalid');
        }

        return $this->fetch('student/show', [
            'issue'         => $issue,
            'problemPhotos' => $issue->problemPhotos,
            'rectifyPhotos' => $issue->rectifyPhotos,
        ]);
    }

    /**
     * 上传整改图
     */
    public function upload(Request $request, string $token)
    {
        $issue = $this->findByToken($token);
        if (!$issue) {
            return $this->fetch('student/invalid');
        }
        if ($issue->status === Issue::STATUS_CONFIRMED) {
            return $this->renderShow($issue, '该问题单已确认完成，无需再上传');
        }

        $files = $request->file('photos') ?: [];
        if (!is_array($files)) {
            $files = [$files];
        }
        $files = array_values(array_filter($files));
        if (!$files) {
            return $this->renderShow($issue, '请选择要上传的整改照片');
        }
        if ($err = $this->checkImages($files)) {
            return $this->renderShow($issue, $err);
        }

        $sort = Photo::nextSort($issue->id, Photo::TYPE_RECTIFY);
        foreach ($files as $file) {
            Photo::create([
                'issue_id'  => $issue->id,
                'type'      => Photo::TYPE_RECTIFY,
                'file_path' => $this->storeImage($file),
                'sort'      => $sort++,
                'uploader'  => trim((string) $request->post('uploader', '')) ?: '学生',
            ]);
        }

        // 首次或重复提交后，状态置为“已提交整改”
        if ($issue->status !== Issue::STATUS_SUBMITTED) {
            $issue->status = Issue::STATUS_SUBMITTED;
            $issue->save();
        }

        return redirect((string) url('Student/show', ['token' => $token]))->with('success', '整改照片上传成功');
    }

    /**
     * 删除自己上传的整改图（确认完成前可删）
     */
    public function photoDelete(Request $request, string $token)
    {
        $issue = $this->findByToken($token);
        if (!$issue) {
            return json(['code' => 1, 'msg' => '链接无效']);
        }
        if ($issue->status === Issue::STATUS_CONFIRMED) {
            return json(['code' => 1, 'msg' => '已确认完成，不能删除']);
        }

        $photo = Photo::where('issue_id', $issue->id)
            ->where('type', Photo::TYPE_RECTIFY)
            ->where('id', $request->post('id/d'))
            ->find();
        if (!$photo) {
            return json(['code' => 1, 'msg' => '照片不存在']);
        }

        $file = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . ltrim($photo->file_path, '/');
        if (is_file($file)) {
            @unlink($file);
        }
        $photo->delete();
        Photo::resequence($issue->id, Photo::TYPE_RECTIFY);

        return json(['code' => 0, 'msg' => '已删除']);
    }

    private function findByToken(string $token): ?Issue
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }
        return Issue::with(['room'])->where('token', $token)->find();
    }

    private function renderShow(Issue $issue, string $error)
    {
        return $this->fetch('student/show', [
            'issue'         => $issue,
            'problemPhotos' => $issue->problemPhotos,
            'rectifyPhotos' => $issue->rectifyPhotos,
            'error'         => $error,
        ]);
    }
}
