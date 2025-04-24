
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- banner
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `banner`;

CREATE TABLE `banner`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `url` VARCHAR(255),
    `image` VARCHAR(255),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- banner_category
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `banner_category`;

CREATE TABLE `banner_category`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `category_id` INTEGER,
    `banner_id` INTEGER,
    `position` INTEGER,
    `size` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `fi_banner_category_id` (`category_id`),
    INDEX `fi_banner_category_banner_id` (`banner_id`),
    CONSTRAINT `fk_banner_category_id`
        FOREIGN KEY (`category_id`)
        REFERENCES `category` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_banner_category_banner_id`
        FOREIGN KEY (`banner_id`)
        REFERENCES `banner` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- banner_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `banner_i18n`;

CREATE TABLE `banner_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `description` VARCHAR(255),
    `button_label` VARCHAR(255),
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `banner_i18n_fk_b1ae56`
        FOREIGN KEY (`id`)
        REFERENCES `banner` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
