SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `bts_users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_users` ;

CREATE  TABLE IF NOT EXISTS `bts_users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `username` VARCHAR(45) NOT NULL ,
  `password` VARCHAR(255) NOT NULL ,
  `email` VARCHAR(255) NOT NULL ,
  `nickname` VARCHAR(45) NULL ,
  `status` TINYINT UNSIGNED NOT NULL ,
  `acl` TINYTEXT NULL ,
  `openid` VARCHAR(255) NULL ,
  PRIMARY KEY (`user_id`) ,
  UNIQUE INDEX `username` (`username` ASC) ,
  UNIQUE INDEX `email` (`email` ASC) ,
  UNIQUE INDEX `openid` (`openid` ASC) )
ENGINE = InnoDB
COMMENT = 'Stores users/admins for authentication/authorization.';


-- -----------------------------------------------------
-- Table `bts_options`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_options` ;

CREATE  TABLE IF NOT EXISTS `bts_options` (
  `option_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `option_name` VARCHAR(64) NOT NULL ,
  `option_value` LONGTEXT NOT NULL ,
  `autoload` TINYINT(1)  NOT NULL DEFAULT true ,
  PRIMARY KEY (`option_id`) ,
  UNIQUE INDEX `autoloaded_options` (`autoload` ASC, `option_name` ASC) ,
  UNIQUE INDEX `options` (`option_name` ASC) )
ENGINE = InnoDB
COMMENT = 'Uses a modified WordPress table structure to store options.';


-- -----------------------------------------------------
-- Table `bts_events`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_events` ;

CREATE  TABLE IF NOT EXISTS `bts_events` (
  `event_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `event_time` DATETIME NOT NULL ,
  `owner` INT UNSIGNED NOT NULL ,
  `secure_hash` CHAR(64) ASCII NOT NULL COMMENT 'A SHA-256 hash used for security with the generated tickets.' ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Examples: created, already occurred, cancelled' ,
  `creation_time` DATETIME NOT NULL ,
  `slug` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`event_id`) ,
  INDEX `events_user_id` (`owner` ASC) ,
  CONSTRAINT `events_user_id`
    FOREIGN KEY (`owner` )
    REFERENCES `bts_users` (`user_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Stores events that are being managed through BTS.';


-- -----------------------------------------------------
-- Table `bts_attendees`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_attendees` ;

CREATE  TABLE IF NOT EXISTS `bts_attendees` (
  `attendee_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `first_name` VARCHAR(64) NOT NULL ,
  `last_name` VARCHAR(64) NOT NULL ,
  `unique_id` VARCHAR(255) NOT NULL COMMENT 'Examples: student number, drivers license, employee number, etc.' ,
  `email` VARCHAR(255) NULL COMMENT 'Used for event organizers to contact attendees or for self-serve ticket site.' ,
  `password` VARCHAR(255) NULL COMMENT 'Used for possible self-serve ticket site.' ,
  `status` TINYINT UNSIGNED NOT NULL COMMENT 'Examples: current/valid, credit balance, banned' ,
  `openid` VARCHAR(255) NULL COMMENT 'Used for possible self-serve ticket site.' ,
  `balance` DECIMAL(4,2) NOT NULL DEFAULT 0.00 ,
  `comments` MEDIUMTEXT NULL ,
  PRIMARY KEY (`attendee_id`) ,
  UNIQUE INDEX `unique_id` (`unique_id` ASC) ,
  UNIQUE INDEX `openid` (`openid` ASC) ,
  UNIQUE INDEX `email` (`email` ASC) )
ENGINE = InnoDB
COMMENT = 'Stores records of ticketholders and relevant info.';


-- -----------------------------------------------------
-- Table `bts_tickets`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_tickets` ;

CREATE  TABLE IF NOT EXISTS `bts_tickets` (
  `ticket_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `batch` MEDIUMINT UNSIGNED NOT NULL ,
  `event_id` INT UNSIGNED NOT NULL ,
  `checksum` CHAR(2) ASCII NOT NULL COMMENT 'Last two characters of human-readable label; contains first and last chars of the secure hash for this ticket (not stored in db).' ,
  `seller_id` INT UNSIGNED NOT NULL ,
  `attendee_id` INT UNSIGNED NULL COMMENT 'If the ticket status is past sold, which attendee owns this ticket?' ,
  `status` TINYINT UNSIGNED NOT NULL COMMENT 'Examples: unsold, sold, checked in, lost/stolen, refunded, etc.' ,
  `checkin_time` DATETIME NULL ,
  PRIMARY KEY (`ticket_id`) ,
  INDEX `tickets_event_id` (`event_id` ASC) ,
  UNIQUE INDEX `human_readable_UNIQUE` USING HASH (`event_id` ASC, `batch` ASC, `ticket_id` ASC, `checksum` ASC) ,
  INDEX `tickets_seller_id` (`seller_id` ASC) ,
  INDEX `tickets_attendee_id` (`attendee_id` ASC) ,
  CONSTRAINT `tickets_event_id`
    FOREIGN KEY (`event_id` )
    REFERENCES `bts_events` (`event_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `tickets_seller_id`
    FOREIGN KEY (`seller_id` )
    REFERENCES `bts_users` (`user_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `tickets_attendee_id`
    FOREIGN KEY (`attendee_id` )
    REFERENCES `bts_attendees` (`attendee_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Tickets and their info, such as owner, time of check in.';


-- -----------------------------------------------------
-- Table `bts_eventmeta`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_eventmeta` ;

CREATE  TABLE IF NOT EXISTS `bts_eventmeta` (
  `emeta_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `event_id` INT UNSIGNED NOT NULL ,
  `meta_key` VARCHAR(64) NOT NULL ,
  `meta_value` LONGTEXT NOT NULL ,
  PRIMARY KEY (`emeta_id`) ,
  INDEX `emeta_event_id` (`event_id` ASC) ,
  UNIQUE INDEX `meta` (`event_id` ASC, `meta_key` ASC) ,
  CONSTRAINT `emeta_event_id`
    FOREIGN KEY (`event_id` )
    REFERENCES `bts_events` (`event_id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Stores more info about events in an extensible manner.';


-- -----------------------------------------------------
-- Table `bts_clients`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_clients` ;

CREATE  TABLE IF NOT EXISTS `bts_clients` (
  `client_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sys_name` VARCHAR(64) NOT NULL ,
  `api_key` CHAR(64) NOT NULL ,
  `status` TINYINT NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`client_id`) ,
  UNIQUE INDEX `sys_name_UNIQUE` (`sys_name` ASC) )
ENGINE = InnoDB
COMMENT = 'Data about software applications that can access the system.';


-- -----------------------------------------------------
-- Table `bts_sessions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `bts_sessions` ;

CREATE  TABLE IF NOT EXISTS `bts_sessions` (
  `session_id` BINARY(20) NOT NULL COMMENT 'SHA1 hash stored with UNHEX() unique to this combination of client_id, user_id, and NOW().' ,
  `client_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `expire_time` DATETIME NOT NULL ,
  INDEX `sessions_client_id` (`client_id` ASC) ,
  INDEX `sessions_user_id` (`user_id` ASC) ,
  PRIMARY KEY (`session_id`) ,
  CONSTRAINT `sessions_client_id`
    FOREIGN KEY (`client_id` )
    REFERENCES `bts_clients` (`client_id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `sessions_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `bts_users` (`user_id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Stores active sessions (user logged in on client).';



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
