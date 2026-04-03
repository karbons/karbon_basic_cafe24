ALTER TABLE `g5_member_refresh`
ADD COLUMN `fcm_token` varchar(512) NOT NULL DEFAULT '' COMMENT 'FCM Token',
ADD COLUMN `device_model` varchar(255) NOT NULL DEFAULT '' COMMENT 'Device Model',
ADD COLUMN `os_version` varchar(50) NOT NULL DEFAULT '' COMMENT 'OS Version';
