-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema boardgame_cafe
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema boardgame_cafe
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `boardgame_cafe` DEFAULT CHARACTER SET utf8 ;
USE `boardgame_cafe` ;

-- -----------------------------------------------------
-- Table `boardgame_cafe`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`user` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nickname` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `profile_image` VARCHAR(255) NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'USER',
  `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '탈퇴 여부 (0: 회원, 1: 탈퇴)',
  `deleted_at` DATETIME NULL COMMENT '탈퇴일시',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`site_menu`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`site_menu` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`site_menu` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '메뉴 고유 ID',
  `page_code` VARCHAR(50) NOT NULL COMMENT '영문 고유 코드 (home, notice 등)',
  `menu_title` VARCHAR(100) NOT NULL COMMENT '화면 표시 한글 메뉴명',
  `menu_type` VARCHAR(20) NOT NULL DEFAULT 'PAGE' COMMENT '메뉴 종류 (PAGE, BOARD, LINK)',
  `page_url` VARCHAR(255) NOT NULL COMMENT '이동할 링크 주소',
  `sort_order` INT NULL DEFAULT 0 COMMENT '노출 정렬 순서',
  `is_exposed` TINYINT NOT NULL DEFAULT 1 COMMENT '노출 여부 (0:숨김, 1:노출)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `page_code_UNIQUE` (`page_code` ASC) VISIBLE)
ENGINE = InnoDB
COMMENT = '사이트 메뉴 및 설정';


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '글 고유 ID',
  `site_menu_id` INT NULL COMMENT '사이트 메뉴 ID',
  `user_id` INT NULL COMMENT '사용자 ID',
  `writer_nickname` VARCHAR(50) NOT NULL COMMENT '작성 당시 닉네임',
  `title` VARCHAR(200) NOT NULL COMMENT '제목',
  `content` TEXT NOT NULL COMMENT '내용',
  `thumbnail` VARCHAR(255) NULL COMMENT '썸네일',
  `hit` INT NULL DEFAULT 0 COMMENT '조회수',
  `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '삭제 여부 (0: 미삭제, 1: 삭제)',
  `deleted_at` DATETIME NULL COMMENT '삭제일시',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  INDEX `fk_post_site_menu1_idx` (`site_menu_id` ASC) VISIBLE,
  INDEX `fk_post_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `idx_post_site_menu_deleted_created` (`site_menu_id` ASC, `is_deleted` ASC, `created_at` ASC) VISIBLE,
  CONSTRAINT `fk_post_site_menu1`
    FOREIGN KEY (`site_menu_id`)
    REFERENCES `boardgame_cafe`.`site_menu` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_post_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post_category` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post_category` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '카테고리 고유 ID',
  `site_menu_id` INT NULL COMMENT '사이트 메뉴 ID',
  `name` VARCHAR(50) NOT NULL COMMENT '카테고리 이름',
  `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '삭제 여부 (0: 미삭제, 1: 삭제)',
  `deleted_at` DATETIME NULL COMMENT '삭제일시',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  INDEX `fk_category_site_menu1_idx` (`site_menu_id` ASC) VISIBLE,
  CONSTRAINT `fk_category_site_menu1`
    FOREIGN KEY (`site_menu_id`)
    REFERENCES `boardgame_cafe`.`site_menu` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`boardgame_level`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`boardgame_level` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`boardgame_level` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '카테고리 고유 ID',
  `name` VARCHAR(20) NOT NULL COMMENT '카테고리 이름',
  `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '삭제 여부 (0: 미삭제, 1: 삭제)',
  `deleted_at` DATETIME NULL COMMENT '삭제일시',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`boardgame_detail`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`boardgame_detail` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`boardgame_detail` (
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `slug` VARCHAR(255) NOT NULL,
  `post_category_id` INT NOT NULL,
  `level` INT NULL,
  `play_time` VARCHAR(20) NULL COMMENT '플레이 시간',
  `player_count` VARCHAR(20) NULL COMMENT '플레이어 수',
  `hashtag` VARCHAR(255) NULL COMMENT '해시태그',
  PRIMARY KEY (`post_id`),
  UNIQUE INDEX `slug_UNIQUE` (`slug` ASC) VISIBLE,
  INDEX `fk_boardgame_detail_post_category1_idx` (`post_category_id` ASC) INVISIBLE,
  INDEX `fk_boardgame_detail_boardgame_level1_idx` (`level` ASC) VISIBLE,
  CONSTRAINT `fk_boardgame_detail_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_boardgame_detail_post_category1`
    FOREIGN KEY (`post_category_id`)
    REFERENCES `boardgame_cafe`.`post_category` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_boardgame_detail_boardgame_level1`
    FOREIGN KEY (`level`)
    REFERENCES `boardgame_cafe`.`boardgame_level` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post_like`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post_like` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post_like` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '좋아요 고유 ID',
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `user_id` INT NOT NULL COMMENT '사용자 ID',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`id`),
  INDEX `fk_boardgame_like_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_post_like_post1_idx` (`post_id` ASC) VISIBLE,
  UNIQUE INDEX `unique_post_user` (`post_id` ASC, `user_id` ASC) VISIBLE,
  CONSTRAINT `fk_post_like_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_post_like_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`branch_detail`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`branch_detail` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`branch_detail` (
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `address` VARCHAR(255) NOT NULL COMMENT '주소',
  `latitude` DECIMAL(10,7) NULL COMMENT '지도 표시용 위도 좌표',
  `longitude` DECIMAL(10,7) NULL COMMENT '지도 표시용 경도 좌표',
  PRIMARY KEY (`post_id`),
  CONSTRAINT `fk_branch_detail_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`notice_detail`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`notice_detail` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`notice_detail` (
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `is_pinned` TINYINT NOT NULL DEFAULT 0 COMMENT '고정 여부 (0: 미고정, 1: 고정)',
  PRIMARY KEY (`post_id`),
  CONSTRAINT `fk_notice_detail_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`review_detail`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`review_detail` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`review_detail` (
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `post_category_id` INT NULL,
  PRIMARY KEY (`post_id`),
  INDEX `fk_review_detail_post_category1_idx` (`post_category_id` ASC) VISIBLE,
  CONSTRAINT `fk_review_detail_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_review_detail_post_category1`
    FOREIGN KEY (`post_category_id`)
    REFERENCES `boardgame_cafe`.`post_category` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post_comment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post_comment` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post_comment` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '댓글 고유 ID',
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `user_id` INT NULL COMMENT '사용자 ID',
  `writer_nickname` VARCHAR(50) NOT NULL COMMENT '작성 당시 닉네임',
  `content` TEXT NOT NULL COMMENT '내용',
  `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '삭제 여부 (0: 미삭제, 1: 삭제)',
  `deleted_at` DATETIME NULL COMMENT '삭제일시',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  INDEX `fk_review_comment_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_post_comment_post1_idx` (`post_id` ASC) VISIBLE,
  CONSTRAINT `fk_post_comment_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_post_comment_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post_image`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post_image` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post_image` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '이미지 고유 ID',
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `image_path` VARCHAR(500) NOT NULL COMMENT '이미지 경로',
  `org_name` VARCHAR(255) NOT NULL COMMENT '원본 이름',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '이미지 순서',
  `file_type` VARCHAR(20) NOT NULL DEFAULT 'DETAIL' COMMENT '이미지 종류 (THUMBNAIL, DETAIL)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`id`),
  INDEX `fk_post_image_post1_idx` (`post_id` ASC) VISIBLE,
  CONSTRAINT `fk_post_image_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`post_file`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`post_file` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`post_file` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '파일 고유 ID',
  `post_id` INT NOT NULL COMMENT '글 고유 ID',
  `file_path` VARCHAR(500) NOT NULL COMMENT '파일 경로',
  `org_name` VARCHAR(255) NOT NULL COMMENT '원본 이름',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  PRIMARY KEY (`id`),
  INDEX `fk_post_file_post1_idx` (`post_id` ASC) VISIBLE,
  CONSTRAINT `fk_post_file_post1`
    FOREIGN KEY (`post_id`)
    REFERENCES `boardgame_cafe`.`post` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`token`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`token` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`token` (
  `token` VARCHAR(128) NOT NULL,
  `user_id` INT NOT NULL,
  `expires` DATETIME NOT NULL,
  `purpose` VARCHAR(30) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  INDEX `fk_token_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `idx_member_purpose` (`user_id` ASC, `purpose` ASC) VISIBLE,
  CONSTRAINT `fk_token_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`user_login_log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`user_login_log` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`user_login_log` (
  `id` BIGINT NOT NULL AUTO_INCREMENT COMMENT '로그 고유 ID',
  `user_id` INT NULL COMMENT '사용자 ID',
  `ip_address` VARCHAR(45) NOT NULL COMMENT '접속 IP 주소',
  `user_agent` VARCHAR(500) NULL COMMENT '브라우저 및 기기 정보',
  `status` VARCHAR(20) NOT NULL COMMENT 'success 또는 fail',
  `fail_reason` VARCHAR(255) NULL COMMENT '실패 사유',
  `login_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '로그인 일시',
  PRIMARY KEY (`id`),
  INDEX `fk_user_login_logs_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `idx_user_login` (`user_id` ASC, `login_at` ASC) VISIBLE,
  CONSTRAINT `fk_user_login_logs_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`user_password_change_log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`user_password_change_log` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`user_password_change_log` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '변경 기록 고유 ID',
  `user_id` INT NOT NULL COMMENT '사용자 ID',
  `change_method` VARCHAR(20) NOT NULL COMMENT '변경 방식 (mypage 또는 email)',
  `ip_address` VARCHAR(45) NOT NULL COMMENT '변경 요청 IP 주소',
  `user_agent` VARCHAR(500) NULL COMMENT '브라우저 및 기기 정보',
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '변경 일시',
  PRIMARY KEY (`id`),
  INDEX `fk_user_password_change_log_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `idx_user_password_change` (`user_id` ASC, `changed_at` ASC) VISIBLE,
  CONSTRAINT `fk_user_password_change_log_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `boardgame_cafe`.`user_profile_change_log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boardgame_cafe`.`user_profile_change_log` ;

CREATE TABLE IF NOT EXISTS `boardgame_cafe`.`user_profile_change_log` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '변경 기록 고유 ID',
  `user_id` INT NOT NULL COMMENT '사용자 ID',
  `change_type` VARCHAR(20) NOT NULL COMMENT '변경 항목 (profile_image, nickname, email)',
  `old_value` VARCHAR(255) NULL COMMENT '변경 전 값 (복구 및 비교용)',
  `new_value` VARCHAR(255) NULL COMMENT '변경 후 값',
  `ip_address` VARCHAR(45) NOT NULL COMMENT '변경 요청 IP 주소',
  `user_agent` VARCHAR(500) NULL COMMENT '브라우저 및 기기 정보',
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '변경 일시',
  PRIMARY KEY (`id`),
  INDEX `fk_user_profile_change_log_user1_idx` (`user_id` ASC) VISIBLE,
  INDEX `idx_user_profile_change` (`user_id` ASC, `changed_at` ASC) VISIBLE,
  CONSTRAINT `fk_user_profile_change_log_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `boardgame_cafe`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
