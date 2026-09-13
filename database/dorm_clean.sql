-- 宿舍卫生整改拍照站 数据库结构
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL COMMENT '登录名',
  `password`   VARCHAR(255) NOT NULL COMMENT '密码哈希',
  `role`       ENUM('admin','counselor') NOT NULL DEFAULT 'counselor' COMMENT '角色',
  `name`       VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '姓名',
  `created_at` DATETIME     NULL,
  `updated_at` DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户（管理员/辅导员）';

CREATE TABLE IF NOT EXISTS `rooms` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `building`   VARCHAR(50) NOT NULL COMMENT '楼栋',
  `room_no`    VARCHAR(50) NOT NULL COMMENT '房间号',
  `created_at` DATETIME    NULL,
  `updated_at` DATETIME    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_building_room` (`building`, `room_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='宿舍房间';

CREATE TABLE IF NOT EXISTS `tickets` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_key`   VARCHAR(32) NOT NULL COMMENT '整改单key',
  `room_id`      INT UNSIGNED NOT NULL COMMENT '房间ID',
  `token`        VARCHAR(64) NOT NULL COMMENT '学生端访问token',
  `status`       TINYINT NOT NULL DEFAULT 0 COMMENT '0待整改 1已提交 2复查通过 3复查驳回',
  `remark`       VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
  `review_note`  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '复查意见',
  `created_by`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人',
  `submitted_at` DATETIME NULL COMMENT '学生提交时间',
  `reviewed_at`  DATETIME NULL COMMENT '复查时间',
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`ticket_key`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_room` (`room_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='整改单';

CREATE TABLE IF NOT EXISTS `ticket_deductions` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL COMMENT '整改单ID',
  `item`      VARCHAR(100) NOT NULL COMMENT '扣分项',
  `points`    DECIMAL(4,1) NOT NULL DEFAULT 1.0 COMMENT '扣分分值',
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扣分项';

CREATE TABLE IF NOT EXISTS `photos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id`   INT UNSIGNED NOT NULL COMMENT '整改单ID',
  `kind`        ENUM('problem','fix') NOT NULL COMMENT 'problem问题照 fix整改照',
  `file_path`   VARCHAR(255) NOT NULL COMMENT '原图路径',
  `thumb_path`  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '缩略图路径',
  `sort`        INT NOT NULL DEFAULT 0 COMMENT '排序号',
  `uploaded_by` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '上传者',
  `created_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_kind` (`ticket_id`, `kind`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片';
