CREATE TABLE IF NOT EXISTS `__PREFIX__permission` (
   `id` int(10) NOT NULL COMMENT '主键',
  `name` varchar(25) NOT NULL DEFAULT '' COMMENT '名称',
  `slug` varchar(25) NOT NULL DEFAULT '' COMMENT '唯一标识',
  `http_path` varchar(100) NOT NULL DEFAULT '' COMMENT '类名',
  `create_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '2020-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='权限表';