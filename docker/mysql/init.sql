-- -----------------------------------------------------
-- Schema
-- -----------------------------------------------------

CREATE SCHEMA IF NOT EXISTS `global` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
CREATE SCHEMA IF NOT EXISTS `shard1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
CREATE SCHEMA IF NOT EXISTS `shard2` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

-- -----------------------------------------------------
-- Table
-- -----------------------------------------------------

-- global

DROP TABLE IF EXISTS `global`.`sequences` ;
CREATE TABLE IF NOT EXISTS `global`.`sequences` (
  `sequence_name` VARCHAR(255) NOT NULL,
  `sequence_value` INT NOT NULL DEFAULT '1',
  `sequence_increment_by` INT NOT NULL DEFAULT '1',
  PRIMARY KEY (`sequence_name`))
ENGINE = InnoDB;

DROP TABLE IF EXISTS `global`.`items` ;
CREATE TABLE IF NOT EXISTS `global`.`items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- shard1

DROP TABLE IF EXISTS `shard1`.`users` ;
CREATE TABLE IF NOT EXISTS `shard1`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

DROP TABLE IF EXISTS `shard1`.`posts` ;
CREATE TABLE IF NOT EXISTS `shard1`.`posts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `content` TEXT NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- shard2

DROP TABLE IF EXISTS `shard2`.`users` ;
CREATE TABLE IF NOT EXISTS `shard2`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

DROP TABLE IF EXISTS `shard2`.`posts` ;
CREATE TABLE IF NOT EXISTS `shard2`.`posts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `content` TEXT NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;
