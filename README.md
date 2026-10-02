# 宿舍卫生整改拍照站

基于 **ThinkPHP 8 + MySQL** 的宿舍卫生整改闭环系统：

- **管理员**：维护宿舍房间 → 上传问题照片 → 系统自动生成 **问题单 key、扣分项、带 token 的整改二维码**
- **学生**：手机扫码（无需登录，token 即凭证）→ 查看问题照片 → 上传整改照片
- **辅导员**：汇总页 **按房间 + key 分组，问题图/整改图成对查看**，确认或退回整改

## 快速启动

```bash
bin/start.sh        # 启动 MariaDB(127.0.0.1:3307) + PHP内置服务器(0.0.0.0:8000)，并自动建表/写初始账号
bin/stop.sh         # 停止服务
```

访问 http://127.0.0.1:8000 ，初始账号：

| 账号 | 密码 | 角色 |
|---|---|---|
| admin | admin123 | 管理员 |
| counselor | counselor123 | 辅导员 |

> 环境说明：本机无系统 PHP/MySQL 时，`bin/start.sh` 使用 `.runtime/` 下的静态 PHP 8.3 与用户态 MariaDB 10.11。
> 若部署到常规 LAMP/LNMP 环境，只需 `mysql < database.sql && php bin/setup.php`，再用 Apache/Nginx 指向 `public/` 即可（已附 `.htaccess`）。

## 业务流程

```
管理员上传问题照片 ──► 生成问题单(key + 扣分项 + token二维码)
                           │ 学生扫码 /s/<token>
                           ▼
                     学生上传整改图（状态→已提交整改）
                           │
                           ▼
                辅导员汇总页成对查看 → 确认完成 / 退回整改
```

状态机：`pending 待整改` → `submitted 已提交整改` → `confirmed 已确认完成`（可退回 pending）

## 功能清单

- **图片上传**：多图上传、类型/大小校验（jpg/png/webp/gif ≤ 8MB）、按日期分目录存储、点击放大
- **排序**：问题图/整改图均可上移/下移（`photo.sort` 字段，交换相邻序号，自动重排去重）
- **权限**：
  - `AdminAuth` 中间件：仅管理员可进 `/admin/*`
  - `CounselorAuth` 中间件：管理员/辅导员可进 `/counselor/*`
  - 学生端 `/s/<token>` 无需登录，32 位随机 token 即凭证；确认完成后禁止再传/删图
- **二维码**：`endroid/qr-code` 生成 PNG，内容为绝对整改链接
- **汇总页**：按房间分组 → 每个 key 下问题图与整改图按顺序成对展示；支持楼栋/状态/关键字(key 或房号)筛选 + 分页

## 目录结构

```
app/
  controller/   Admin(房间/问题单/照片/二维码) Student(扫码上传) Counselor(汇总确认) Auth(登录)
  middleware/   AdminAuth / CounselorAuth
  model/        Room / Issue / Photo / AdminUser
config/dorm.php 扣分项预设、上传限制
route/app.php   全部路由
view/           模板（admin / counselor / student / auth）
public/uploads/ 上传图片
database.sql    建表语句
bin/setup.php   初始化（建表 + 初始账号，幂等）
```

## 数据表

- `admin_user`：后台账号（role: admin/counselor，password_hash）
- `room`：宿舍房间（building + room_no 唯一）
- `issue`：问题单（issue_key 唯一、token 唯一、扣分项/扣分、状态）
- `photo`：照片（type: problem/rectify，sort 排序，uploader）

## 常用命令

```bash
php bin/setup.php   # 初始化/重置数据表与初始账号
php think           # ThinkPHP 控制台
```
