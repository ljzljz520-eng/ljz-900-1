<?php
use think\facade\Route;
use app\middleware\Auth;
use app\middleware\AdminAuth;
use app\middleware\CounselorAuth;

// 首页按登录态跳转
Route::get('/', function () {
    $role = \think\facade\Session::get('role');
    if ($role === 'admin')  return redirect((string) url('/admin/issues'));
    if ($role === 'counselor') return redirect((string) url('/counselor/issues'));
    return redirect((string) url('/login'));
});

// 登录 / 退出
Route::get('login', 'AuthController/loginForm');
Route::post('login', 'AuthController/login');
Route::get('logout', 'AuthController/logout');

// 学生扫码（token，免登录）
Route::get('s/:token', 'StudentController/show')->pattern(['token' => '[0-9a-f]{48}']);
Route::post('s/:token/upload', 'StudentController/upload')->pattern(['token' => '[0-9a-f]{48}']);

// 管理员后台
Route::group('admin', function () {
    Route::get('issues', 'admin.IssueController/index')->completeMatch();
    Route::get('issues/create', 'admin.IssueController/create');
    Route::post('issues', 'admin.IssueController/store')->completeMatch();
    Route::get('issues/:id', 'admin.IssueController/read')->pattern(['id' => '\d+']);
    Route::post('issues/:id/photos', 'admin.IssueController/uploadPhoto')->pattern(['id' => '\d+']);
    Route::post('issues/:id/reorder', 'admin.IssueController/reorderPhotos')->pattern(['id' => '\d+']);
    Route::delete('issues/:id/photos/:photoId', 'admin.IssueController/deletePhoto');

    Route::get('rooms', 'admin.RoomController/index');
    Route::post('rooms', 'admin.RoomController/store')->completeMatch();
    Route::put('rooms/:id', 'admin.RoomController/update')->pattern(['id' => '\d+']);
    Route::delete('rooms/:id', 'admin.RoomController/delete')->pattern(['id' => '\d+']);

    Route::get('items', 'admin.ItemController/index');
    Route::post('items', 'admin.ItemController/store')->completeMatch();
    Route::put('items/:id', 'admin.ItemController/update')->pattern(['id' => '\d+']);
    Route::delete('items/:id', 'admin.ItemController/delete')->pattern(['id' => '\d+']);
})->middleware([Auth::class, AdminAuth::class]);

// 辅导员汇总（管理员也可访问）
Route::group('counselor', function () {
    Route::get('issues', 'counselor.IssueController/index')->completeMatch();
    Route::get('issues/:id', 'counselor.IssueController/read')->pattern(['id' => '\d+']);
    Route::post('issues/:id/approve', 'counselor.IssueController/approve')->pattern(['id' => '\d+']);
    Route::post('issues/:id/reject', 'counselor.IssueController/reject')->pattern(['id' => '\d+']);
})->middleware([Auth::class, CounselorAuth::class]);
