<?php
// 路由定义
use app\middleware\Auth;
use think\facade\Route;

// 首页 / 登录
Route::get('/', [app\controller\Index::class, 'index']);
Route::get('login', [app\controller\Index::class, 'login']);
Route::post('login', [app\controller\Index::class, 'doLogin']);
Route::get('logout', [app\controller\Index::class, 'logout']);

// 管理端（仅管理员）
Route::group('admin', function () {
    Route::get('/', [app\controller\admin\Ticket::class, 'index']);
    // 房间管理
    Route::get('rooms', [app\controller\admin\Room::class, 'index']);
    Route::post('rooms', [app\controller\admin\Room::class, 'save']);
    Route::post('rooms/<id>/delete', [app\controller\admin\Room::class, 'delete']);
    // 整改单
    Route::get('ticket/create', [app\controller\admin\Ticket::class, 'create']);
    Route::post('ticket', [app\controller\admin\Ticket::class, 'save']);
    Route::get('ticket/<id>', [app\controller\admin\Ticket::class, 'detail']);
    Route::get('ticket/<id>/qrcode', [app\controller\admin\Ticket::class, 'qrcode']);
    Route::post('ticket/<id>/review', [app\controller\admin\Ticket::class, 'review']);
    Route::post('ticket/<id>/photo', [app\controller\admin\Ticket::class, 'uploadPhoto']);
    Route::post('ticket/<id>/sort', [app\controller\admin\Ticket::class, 'sortPhotos']);
    Route::post('photo/<id>/delete', [app\controller\admin\Ticket::class, 'deletePhoto']);
})->middleware(Auth::class, 'admin');

// 辅导员汇总（管理员 + 辅导员）
Route::get('summary', [app\controller\Summary::class, 'index'])
    ->middleware(Auth::class, 'admin,counselor');

// 学生端（凭 token 访问，无需登录）
Route::get('s/<token>', [app\controller\Student::class, 'show']);
Route::post('s/<token>/upload', [app\controller\Student::class, 'upload']);
Route::post('s/<token>/photo/<id>/delete', [app\controller\Student::class, 'deletePhoto']);
Route::post('s/<token>/sort', [app\controller\Student::class, 'sort']);
Route::post('s/<token>/submit', [app\controller\Student::class, 'submit']);
