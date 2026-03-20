-- WordPress database dump for testing
-- This is a mock SQL file to test database backup functionality

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `wp_posts`
(
    `ID`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `post_title`   text                NOT NULL,
    `post_content` longtext            NOT NULL,
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

INSERT INTO `wp_posts` (`post_title`, `post_content`)
VALUES ('Test Post 1', 'This is a test post content'),
       ('Test Post 2', 'Another test post content');
