SET NAMES utf8mb4;
INSERT INTO dorm_users (username,password_hash,real_name,role,create_time,update_time) VALUES
('admin','$2y$10$dcTHrpyVM9of2tF7s/o6Beai909lEpn4S9azqSQ9csXdaBthImBNC','系统管理员','admin',NOW(),NOW()),
('laowang','$2y$10$lS/HZujqC9B2OC97KQ5lruIwgr5RBkQRWhmS4H.QQ/ttIQsfJPQ6W','王辅导员','counselor',NOW(),NOW())
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), real_name=VALUES(real_name), role=VALUES(role), update_time=NOW();

INSERT INTO dorm_rooms (building,room_no,counselor_id,create_time,update_time) VALUES
('1号楼','101',2,NOW(),NOW()),
('1号楼','102',2,NOW(),NOW()),
('1号楼','201',2,NOW(),NOW()),
('2号楼','305',2,NOW(),NOW()),
('2号楼','410',2,NOW(),NOW());

INSERT INTO dorm_deduction_items (name,default_score,sort) VALUES
('地面垃圾/污渍',3,100),
('床铺未整理',2,90),
('物品摆放凌乱',2,80),
('阳台/卫生间脏乱',3,70),
('垃圾未倒',1,60),
('异味明显',2,40)
ON DUPLICATE KEY UPDATE name=VALUES(name);
