-- 宿舍卫生整改拍照站 数据库结构（MySQL 5.7+ / MariaDB 10.3+，utf8mb4）
SET NAMES utf8mb4;

-- 后台账号（管理员 / 辅导员）
CREATE TABLE IF NOT EXISTS `admin_user` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)  NOT NULL COMMENT '登录名',
    `password`   VARCHAR(255) NOT NULL COMMENT '密码散列',
    `role`       VARCHAR(20)  NOT NULL DEFAULT 'admin' COMMENT 'admin=管理员 counselor=辅导员',
    `name`       VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '姓名',
    `created_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台账号';

-- 宿舍房间
CREATE TABLE IF NOT EXISTS `room` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `building`   VARCHAR(50)  NOT NULL COMMENT '楼栋',
    `room_no`    VARCHAR(50)  NOT NULL COMMENT '房号',
    `created_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_building_room` (`building`, `room_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='宿舍房间';

-- 整改问题单
CREATE TABLE IF NOT EXISTS `issue` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id`      INT UNSIGNED NOT NULL COMMENT '房间ID',
    `issue_key`    VARCHAR(64)  NOT NULL COMMENT '系统生成的问题单key',
    `deduct_item`  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '扣分项',
    `deduct_score` INT          NOT NULL DEFAULT 0 COMMENT '扣分值',
    `token`        CHAR(32)     NOT NULL COMMENT '整改二维码token',
    `remark`       VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `status`       VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending待整改 submitted已提交 confirmed已确认',
    `admin_id`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人',
    `created_at`   DATETIME     NULL,
    `updated_at`   DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_issue_key` (`issue_key`),
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_room` (`room_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='整改问题单';

-- 照片（问题图 / 整改图）
CREATE TABLE IF NOT EXISTS `photo` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `issue_id`   INT UNSIGNED NOT NULL COMMENT '问题单ID',
    `type`       VARCHAR(20)  NOT NULL DEFAULT 'problem' COMMENT 'problem问题图 rectify整改图',
    `file_path`  VARCHAR(255) NOT NULL COMMENT '文件路径',
    `sort`       INT          NOT NULL DEFAULT 0 COMMENT '排序号',
    `uploader`   VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '上传人',
    `created_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    KEY `idx_issue_type` (`issue_id`, `type`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片';
