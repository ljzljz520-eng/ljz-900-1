# 宿舍卫生整改拍照站（ThinkPHP 8 + MySQL/MariaDB）

管理员给每间宿舍上传卫生问题照片 → 系统生成业务 **key**、记录**扣分项**、生成带 **token 的整改二维码**；
学生扫码免登录上传整改照片；辅导员在汇总页**按房间、按 key 把问题图与整改图成对查看**，可通过/驳回。

## 角色与权限

| 角色 | 入口 | 能做什么 |
|------|------|----------|
| 管理员 `admin` | `/admin/*` | 建问题单、传/删/排序问题图、生成二维码、管理房间与扣分项、查看全部汇总 |
| 辅导员 `laowang` | `/counselor/*` | 只看自己负责房间；成对查看；审核通过 / 驳回 |
| 学生 | `/s/{token}`（扫码） | 免登录，凭 48 位随机 token 查看问题并上传整改图 |

- 管理端：Session 登录 + 角色中间件（`Auth` / `AdminAuth` / `CounselorAuth`）。
- 数据隔离：辅导员只能访问 `counselor_id = 自己` 的问题单，越权返回 403。
- 学生端：token 经正则 `[0-9a-f]{48}` 校验后再查库；整改通过后锁定，不能再传。
- 图片上传：扩展名白名单 + `finfo` 真实 MIME + `getimagesize` 三重校验，单张 ≤ 10MB，
  仅允许 jpg/jpeg/png/webp/gif，按 `public/uploads/{issues|rectify}/年月/` 存放。

## 核心数据模型

- `dorm_users` 管理员/辅导员（`role=admin|counselor`，`password_hash` 用 `password_hash`）
- `dorm_rooms` 房间（楼栋+房号唯一，归属辅导员）
- `dorm_deduction_items` 扣分项字典（名称、默认扣分）
- `dorm_issues` 问题单：`issue_key`（如 `XG2610-9A3F2C`）、`token`（48 位）、扣分项、分数、状态
- `dorm_issue_photos` 问题照片（`sort` 控制顺序，支持拖拽排序）
- `dorm_rectifications` 整改记录（一单一条）
- `dorm_rectification_photos` 整改照片（学生重新提交会整体替换旧图并清理磁盘文件）

问题单状态：`0 待整改 → 1 待复核 → 2 已通过`；复核不通过 `3 已驳回`（学生可重新上传）。

## 演示账号

| 账号 | 密码 | 角色 |
|------|------|------|
| admin | admin123 | 管理员 |
| laowang | teacher123 | 王辅导员 |
| laoli | t2pass | 李辅导员（无负责房间，用于验证数据隔离） |

## 运行方式

### A. 标准环境（已装 PHP 8.x + MySQL/MariaDB）

```bash
composer install
# 导入库表与演示数据
mysql -u<user> -p < database/schema.sql
mysql -u<user> -p dorm < database/seed.sql
# 按实际改 .env（DB_HOST/DB_NAME/DB_USER/DB_PASS/DB_PREFIX=dorm_）
php think run -H 0.0.0.0 -p 8088
# 或使用内置服务器路由：php -S 127.0.0.1:8088 -t public public/router.php
```

生产环境请把 Web 根目录指向 `public/`（Nginx/Apache rewrite 到 `index.php`），并将 `.env` 的
`APP_DEBUG` 设为 `false`。需要 PHP 扩展：`pdo_mysql, gd, mbstring, fileinfo, curl, zip, openssl, ctype, iconv`。

### B. 本机无 root 便携模式（本仓库实际使用）

运行时位于 `~/local`（静态解压的 PHP 8.2 与 MariaDB 10.11），数据库已初始化并监听 `127.0.0.1:3306`：

```bash
bash bin/start.sh     # 自动拉起 MariaDB（如需）+ PHP 内置服务器，监听 8088
bash bin/stop.sh      # 停止
```

打开 <http://127.0.0.1:8088> 即可。

## 完整业务闭环（已端到端验证）

1. 管理员「新建问题单」：选房间、扣分项、扣分，可一次传多张问题图；
   系统生成唯一 `issue_key` 与 `token`（302 跳详情页）。
2. 详情页展示含 token 的整改链接与二维码（本地 `qrcodejs` 纯前端生成，无需联网/PHP 图形接口）；
   支持**继续追加照片、删除、HTML5 拖拽排序**（AJAX 持久化 `sort`）。
3. 学生扫码打开 `/s/{token}`：看问题图与说明 → 多选整改图提交，状态变「待复核」。
4. 辅导员汇总页：顶部四态统计 + 房间/状态/key/「只看待复核」筛选；按房间分组，
   每张单左右成对展示问题图与整改图。
5. 辅导员详情页审核：**通过**（锁定）或**驳回**（学生可替换重传，旧图从磁盘清除）。

## 目录要点

```
app/
  controller/        AuthController、StudentController
    admin/           Issue/Room/Item 控制器（仅管理员）
    counselor/       汇总与审核控制器（辅导员+管理员）
  middleware/        Auth / AdminAuth / CounselorAuth
  model/             7 个模型（Issue 内含 key/token 生成与状态字典）
  service/PhotoService.php   安全上传、落盘、删除
route/app.php        全部显式路由 + 中间件分组（集合路由用 completeMatch 避免吞嵌套路由）
view/                auth/admin/counselor/student 模板（Think 模板继承）
database/            schema.sql（建表）、seed.sql（账号/房间/扣分项）
public/uploads/      问题图(issues) 与整改图(rectify)
public/static/js/    qrcode.min.js（本地）
```

> 二维码内容为 `http(s)://<当前域名>/s/<token>`，换部署域名后自动跟随，无需改库。
