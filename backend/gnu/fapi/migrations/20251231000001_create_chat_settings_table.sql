CREATE TABLE IF NOT EXISTS `g5_chat_room_settings` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `room_id` VARCHAR(255) NOT NULL COMMENT 'Firebase Room ID',
    `mb_id` VARCHAR(20) NOT NULL COMMENT 'Member ID',
    `room_alias` VARCHAR(255) NULL COMMENT 'Personal Room Name',
    `room_image` VARCHAR(255) NULL COMMENT 'Personal Room Image URL',
    `bg_color` VARCHAR(50) NULL COMMENT 'Background Color Code',
    `bg_image` VARCHAR(255) NULL COMMENT 'Background Image URL',
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Pin to Top',
    `is_favorite` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Favorite Room',
    `is_alarm` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Notification: 1=On, 0=Off',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_member` (`room_id`, `mb_id`),
    INDEX `idx_mb_id` (`mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User specific settings for chat rooms';
