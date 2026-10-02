-- 宿舍卫生整改拍照站 数据库结构
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS dorm_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  real_name VARCHAR(50) NOT NULL,
  role ENUM('admin','counselor') NOT NULL DEFAULT 'counselor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  create_time DATETIME NULL,
  update_time DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员/辅导员账号';

CREATE TABLE IF NOT EXISTS dorm_rooms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building VARCHAR(20) NOT NULL COMMENT '楼栋',
  room_no VARCHAR(20) NOT NULL COMMENT '房间号',
  counselor_id INT UNSIGNED NULL COMMENT '归属辅导员',
  create_time DATETIME NULL,
  update_time DATETIME NULL,
  UNIQUE KEY uk_build_room (building, room_no),
  KEY idx_counselor (counselor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='宿舍房间';

CREATE TABLE IF NOT EXISTS dorm_deduction_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL COMMENT '扣分项名称',
  default_score TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '默认扣分',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0,
  UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='扣分项字典';

CREATE TABLE IF NOT EXISTS dorm_issues (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_key VARCHAR(20) NOT NULL UNIQUE COMMENT '业务key 如 XG...',
  room_id INT UNSIGNED NOT NULL,
  counselor_id INT UNSIGNED NULL COMMENT '创建时房间归属辅导员快照',
  deduction_item_id INT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '本次扣分',
  description VARCHAR(500) NOT NULL DEFAULT '' COMMENT '问题描述',
  token CHAR(64) NOT NULL UNIQUE COMMENT '学生扫码令牌',
  status TINYINT NOT NULL DEFAULT 0 COMMENT '0待整改 1待复核 2已通过 3已驳回',
  creator_id INT UNSIGNED NOT NULL,
  rectified_at DATETIME NULL COMMENT '学生最近提交时间',
  close_time DATETIME NULL COMMENT '辅导员通过时间',
  create_time DATETIME NULL,
  update_time DATETIME NULL,
  KEY idx_room (room_id),
  KEY idx_status (status),
  KEY idx_counselor (counselor_id),
  KEY idx_item (deduction_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卫生问题单';

CREATE TABLE IF NOT EXISTS dorm_issue_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL,
  path VARCHAR(200) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  create_time DATETIME NULL,
  KEY idx_issue (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问题照片';

CREATE TABLE IF NOT EXISTS dorm_rectifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL UNIQUE,
  note VARCHAR(500) NOT NULL DEFAULT '' COMMENT '整改说明',
  create_time DATETIME NULL,
  update_time DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='整改记录(一单一条)';

CREATE TABLE IF NOT EXISTS dorm_rectification_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rectification_id INT UNSIGNED NOT NULL,
  issue_id INT UNSIGNED NOT NULL,
  path VARCHAR(200) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  create_time DATETIME NULL,
  KEY idx_rect (rectification_id),
  KEY idx_issue (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='整改照片';
