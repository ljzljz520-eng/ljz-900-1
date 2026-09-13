# 宿舍卫生整改拍照站

基于 **ThinkPHP 8 + MySQL** 的宿舍卫生整改闭环系统：管理员上传问题照片 → 系统生成整改单 key、扣分项和带 token 的整改二维码 → 学生扫码上传整改照片 → 辅导员在汇总页按房间 + key 成对查看整改前后照片。

## 功能流程

```
管理员                系统                  学生                 辅导员/管理员
  │ 上传问题照片        │                     │                      │
  │ 选择扣分项   ───►   │ 生成 key            │                      │
  │                    │ 生成 token 二维码 ──► │ 扫码打开整改页        │
  │                    │                     │ 上传整改照片/排序/提交  │
  │ 复查通过/驳回 ◄──── │ 状态流转 ◄────────── │                      │
  │                    │                     │                 汇总页 │
  │                    │                     │            按房间+key  │
  │                    │                     │            成对查看照片 │
```

## 角色与权限

| 功能 | 管理员 admin | 辅导员 counselor | 学生（token 链接） |
|---|---|---|---|
| 登录后台 | ✓ | ✓ | 无需登录 |
| 房间管理 | ✓ | ✗ | ✗ |
| 创建整改单 / 上传问题照片 | ✓ | ✗ | ✗ |
| 查看整改二维码 | ✓ | ✗ | — |
| 复查（通过 / 驳回） | ✓ | ✗ | ✗ |
| 上传整改照片 / 排序 / 提交 | ✗ | ✗ | ✓（扫码） |
| 汇总页（成对查看） | ✓ | ✓ | ✗ |

## 快速开始

### 1. 环境要求
- PHP ≥ 8.0（需 pdo_mysql、gd、mbstring、fileinfo）
- MySQL 5.7+ / MariaDB 10.3+

### 2. 安装
```bash
composer install          # 安装依赖（topthink/framework、bacon/qr-code）
```

### 3. 配置数据库
编辑 `.env`：
```ini
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = dorm_clean
USERNAME = dorm
PASSWORD = dorm123
HOSTPORT = 3306
CHARSET = utf8mb4
```

### 4. 初始化数据
```bash
mysql -u root -p dorm_clean < database/dorm_clean.sql
# 插入初始账号（密码均为 bcrypt，可自行替换）
```
初始账号：
- 管理员：`admin` / `admin123`
- 辅导员：`counselor` / `counselor123`

### 5. 启动
```bash
# 开发环境（调大上传限制）
php -d upload_max_filesize=20M -d post_max_size=60M \
    -S 127.0.0.1:8000 -t public public/router.php

# 生产环境：Nginx/Apache 将域名指向 public/ 目录，PATH_INFO 重写参考 ThinkPHP 官方文档
```

访问 `http://127.0.0.1:8000` → 管理员登录 → 房间管理添加房间 → 新建整改单。

## 主要页面

| 路径 | 说明 |
|---|---|
| `/login` | 管理员/辅导员登录 |
| `/admin` | 整改单列表（状态筛选、key 搜索） |
| `/admin/rooms` | 房间管理 |
| `/admin/ticket/create` | 新建整改单（问题照片 + 扣分项） |
| `/admin/ticket/{id}` | 详情：二维码、照片管理、复查 |
| `/admin/ticket/{id}/qrcode` | 整改二维码（SVG，内容为带 token 的链接） |
| `/s/{token}` | 学生整改页（免登录，凭 token） |
| `/summary` | 辅导员汇总页：按房间 + key 成对查看问题照/整改照 |

## 整改单状态机

```
待整改(0) ──学生提交──► 已提交(1) ──复查通过──► 通过(2)
   ▲                      │
   └────── 驳回(3) ◄──────┘
```

## 目录结构

```
app/
├── controller/        # Index(登录) admin/Room admin/Ticket Student Summary
├── middleware/Auth.php# 登录与角色权限中间件
├── model/             # User Room Ticket TicketDeduction Photo
├── service/           # View(原生模板) UploadService(上传+GD缩略图)
└── view/              # PHP 原生模板
config/dorm.php        # 扣分项预设、上传限制
database/dorm_clean.sql# 建表 SQL
public/uploads/        # 上传图片（原图 + _thumb 缩略图）
route/app.php          # 全部路由（强制路由模式）
```

## 安全设计

- 密码 bcrypt 哈希；Session 鉴权 + 角色中间件分层控制，管理端控制器内二次校验录入人角色
- 整改单状态机集中定义（`Ticket::TRANSITIONS`），所有状态变更先过 `canTransitionTo` 校验，关键环节不可跳过
- 学生提交后问题照/整改照即冻结（补传/删除/排序一律拒绝），驳回后自动解冻
- 学生端凭 32 位随机 token 访问，无需账号；状态非法时禁止上传/删除/提交
- 登记字段服务端校验：房间楼栋/房号限长限字符，扣分项/照片数量设上限，录入人标识非空限长
- 上传校验：扩展名白名单 + MIME 检测 + 10MB 限制；GD 重编码生成缩略图
- 文件名仅使用服务端随机串，业务编号（ticket_key）与用户输入绝不进入文件名
- SQL 全部走 ORM 参数绑定；视图输出统一 `htmlspecialchars`
- 强制路由模式（`url_route_must`），未定义 URL 一律 404
