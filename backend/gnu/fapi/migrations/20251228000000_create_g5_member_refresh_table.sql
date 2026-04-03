CREATE TABLE IF NOT EXISTS `g5_member_refresh` (
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '회원아이디',
  `uuid` varchar(255) NOT NULL DEFAULT '' COMMENT '고유식별자',
  `agent` varchar(255) NOT NULL DEFAULT '' COMMENT 'User Agent',
  `refresh_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'Refresh Token',
  `reg_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`uuid`),
  KEY `mb_id` (`mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 Refresh Token 관리';
