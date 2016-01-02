-- MySQL Script to Create DB for Alyssa
-- Thu Dec 31 13:06:23 2015
-- Version: 1.0
-- Author: Yichao Dong

-- -----------------------------------------------------
-- Schema AlyssaDB
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `AlyssaDB` DEFAULT CHARACTER SET utf8 ;
USE `AlyssaDB` ;

-- -----------------------------------------------------
-- Table `AlyssaDB`.`User`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `AlyssaDB`.`User` (
  `user_id` INT NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(45) NOT NULL,
  `user_password` VARCHAR(45) NOT NULL,
  `user_nickname` VARCHAR(45) NOT NULL,
  `user_created_time` TIMESTAMP NOT NULL,
  UNIQUE (user_nickname),
  UNIQUE INDEX `user_email_UNIQUE` (`user_email` ASC),
  PRIMARY KEY (`user_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `AlyssaDB`.`Font`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `AlyssaDB`.`Font` (
  `font_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `fontname` VARCHAR(45) NOT NULL,
  `copyright` VARCHAR(45) NOT NULL,
  `version` VARCHAR(45) NOT NULL,
  `font_created_time` VARCHAR(45) NOT NULL,
  `font_last_modified_time` TIMESTAMP NOT NULL,
  `font_active` TINYINT(1) NOT NULL,
  PRIMARY KEY (`font_id`),
  CONSTRAINT `user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `AlyssaDB`.`User` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `AlyssaDB`.`Glyph`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `AlyssaDB`.`Glyph` (
  `glyph_id` INT NOT NULL AUTO_INCREMENT,
  `font_id` INT NOT NULL,
  `charname` VARCHAR(45) NOT NULL,
  `glyph_created_time` TIMESTAMP NOT NULL,
  `glyph_active` TINYINT(1) NOT NULL,
  PRIMARY KEY (`glyph_id`),
  CONSTRAINT `font_id`
    FOREIGN KEY (`font_id`)
    REFERENCES `AlyssaDB`.`Font` (`font_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `AlyssaDB`.`UserValidation`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `AlyssaDB`.`UserValidation` (
  `vc_email` VARCHAR(45) NOT NULL,
  `validation_code` TEXT NOT NULL,
  `vc_created_time` TIMESTAMP NOT NULL,
  PRIMARY KEY (`vc_email`),
  CONSTRAINT `vc_email`
    FOREIGN KEY (`vc_email`)
    REFERENCES `AlyssaDB`.`User` (`user_email`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;