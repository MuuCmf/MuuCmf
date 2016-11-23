-- -----------------------------
-- 表结构 `ocenter_paper`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_paper` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `title` varchar(25) NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(2) NOT NULL,
  `sort` int(6) NOT NULL,
  `update_time` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  `category` int(11) NOT NULL COMMENT '分类id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='文章文章表';


-- -----------------------------
-- 表结构 `ocenter_paper_category`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_paper_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(25) NOT NULL,
  `sort` int(6) NOT NULL,
  `status` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='文章分类';

INSERT INTO `ocenter_paper_category` VALUES ('1', '默认分类', '1', '1');
