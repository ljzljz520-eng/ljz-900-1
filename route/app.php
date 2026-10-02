<?php
// 路由定义
use app\middleware\AdminAuth;
use app\middleware\CounselorAuth;
use think\facade\Route;

// 首页
Route::get('/', 'Index/index');

// 登录 / 注销
Route::get('login', 'Auth/login');
Route::post('login', 'Auth/doLogin');
Route::get('logout', 'Auth/logout');

// 学生端：扫码进入（token 即权限凭证，无需登录）
Route::get('s/:token', 'Student/show')->pattern(['token' => '[a-f0-9]{32}']);
Route::post('s/:token/upload', 'Student/upload')->pattern(['token' => '[a-f0-9]{32}']);
Route::post('s/:token/photo/delete', 'Student/photoDelete')->pattern(['token' => '[a-f0-9]{32}']);

// 管理员端
Route::group('admin', function () {
    Route::get('/', 'Admin/index');
    // 房间
    Route::get('room/create', 'Admin/roomCreate');
    Route::post('room/create', 'Admin/roomStore');
    Route::post('room/delete', 'Admin/roomDelete');
    Route::get('room/:id', 'Admin/roomDetail')->pattern(['id' => '\d+']);
    // 问题单
    Route::get('issue/create', 'Admin/issueCreate');
    Route::post('issue/create', 'Admin/issueStore');
    Route::get('issue/:id', 'Admin/issueDetail')->pattern(['id' => '\d+']);
    Route::post('issue/delete', 'Admin/issueDelete');
    Route::post('issue/status', 'Admin/issueStatus');
    // 照片：上传 / 排序 / 删除
    Route::post('photo/upload', 'Admin/photoUpload');
    Route::post('photo/sort', 'Admin/photoSort');
    Route::post('photo/delete', 'Admin/photoDelete');
    // 整改二维码
    Route::get('qrcode/:token', 'Admin/qrcode')->pattern(['token' => '[a-f0-9]{32}']);
})->middleware(AdminAuth::class);

// 辅导员端
Route::group('counselor', function () {
    Route::get('/', 'Counselor/index');
    Route::post('issue/confirm', 'Counselor/confirm');
})->middleware(CounselorAuth::class);

// 404
Route::miss(function () {
    return response('页面不存在', 404);
});
