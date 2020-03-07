CREATE TABLE IF NOT EXISTS `__PREFIX__admin_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `module_name` varchar(25) NOT NULL DEFAULT '' COMMENT '模块名称',
  `controller` varchar(100) NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(25) NOT NULL DEFAULT '' COMMENT '动作',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT 'url',
  `action_name` varchar(25) NOT NULL DEFAULT '' COMMENT '动作名称',
  `action_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '动作类型',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台权限表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
  `sort` int(4) NOT NULL DEFAULT '0' COMMENT '排序',
  `title` varchar(25) NOT NULL DEFAULT '' COMMENT '标题',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT 'url地址',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '图标',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台菜单表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sort` int(4) unsigned DEFAULT '0' COMMENT '排序',
  `name` varchar(25) NOT NULL DEFAULT '' COMMENT '角色名称',
  `description` varchar(100) DEFAULT '' COMMENT '描述',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台角色表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_role_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `controller_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '控制器id',
  `permission_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权限id',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_role_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  `menu_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '菜单id',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '角色组',
  `username` varchar(30) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(10) NOT NULL DEFAULT '' COMMENT '密码',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(120) DEFAULT '' COMMENT '头像',
  `phone` varchar(20) DEFAULT '' COMMENT '电话',
  `email` varchar(55) DEFAULT '' COMMENT '邮箱',
  `errors` int(10) unsigned DEFAULT '0' COMMENT '错误次数',
  `enable` tinyint(1) unsigned DEFAULT '1' COMMENT '启用',
  `login_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '登录时间',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限表';

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_operation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户名',
  `path` varchar(200) NOT NULL DEFAULT '' COMMENT '路径',
  `method` varchar(10) NOT NULL DEFAULT '' COMMENT '方法',
  `ip` varchar(120) DEFAULT '' COMMENT 'ip',
  `data` text DEFAULT '' COMMENT '数据',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限表';

-- 默认权限
INSERT INTO `__PREFIX__admin_role` (`id`, `sort`, `name`, `description`, `create_time`, `update_time`) VALUES
(1, 1, '超级管理员', '超级管理员，拥有所有权限', '2020-03-01 20:11:43', '2020-03-01 20:16:03');

-- 默认管理员 admin tpextadmin
INSERT INTO `__PREFIX__admin_user` (`id`, `role_id`, `username`, `password`, `salt`, `name`, `avatar`, `phone`, `email`, `errors`, `login_time`, `create_time`, `update_time`) VALUES
(1, 1, 'admin', '0796647d241a5014670013b324a155ee', '15fb7db', '管理员', '/assets/lightyearadmin/images/no-avatar.jpg', '', '', 0, '2020-01-01 00:00:00', '2020-03-01 20:19:11', '2020-03-01 20:19:11');

-- 菜单
INSERT INTO `__PREFIX__admin_menu` (`id`, `parent_id`, `sort`, `title`, `url`, `icon`, `create_time`, `update_time`) VALUES
(1, 0, 1, '首页', '/admin/index/welcome', 'mdi mdi-home', '2020-03-03 20:26:49', '2020-03-03 20:26:49'),
(2, 0, 1, '权限管理', '#', 'mdi mdi-account-check', '2020-03-03 20:27:14', '2020-03-03 20:27:14'),
(3, 2, 1, '菜单管理', '/admin/menu/index', 'mdi mdi-arrange-send-to-back', '2020-03-03 20:27:49', '2020-03-03 20:34:40'),
(4, 2, 1, '权限设置', '/admin/permission/index', 'mdi mdi-account-key', '2020-03-03 20:28:35', '2020-03-03 20:28:35'),
(5, 2, 1, '管理员', '/admin/admin/index', 'mdi mdi-account-card-details', '2020-03-03 20:29:07', '2020-03-03 20:34:25'),
(6, 2, 1, '角色管理', '/admin/role/index', 'mdi mdi-account-multiple', '2020-03-03 20:31:22', '2020-03-03 20:31:22'),
(7, 2, 1, '操作记录', '/admin/operationlog/index', 'mdi mdi-playlist-check', '2020-03-03 20:32:06', '2020-03-03 20:32:06'),
(8, 0, 1, '系统管理', '#', 'mdi mdi-settings', '2020-03-03 20:35:11', '2020-03-03 20:35:11'),
(9, 8, 1, '扩展管理', '/admin/extension/index', 'mdi mdi-blur', '2020-03-03 20:36:54', '2020-03-03 20:36:54'),
(10, 8, 1, '平台设置', '/admin/config/index', 'mdi mdi-settings-box', '2020-03-03 20:37:29', '2020-03-03 20:37:29');