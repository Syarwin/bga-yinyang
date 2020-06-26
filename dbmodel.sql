
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- YinYang implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql


CREATE TABLE IF NOT EXISTS `board` (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `piece` int(11),
   `x` int(4) NOT NULL,
   `y` int(4) NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `domino` (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `player_id` int(11),
   `type` varchar(16) DEFAULT 'empty',
   `location` varchar(16) NOT NULL,
   `cause00` int(3) DEFAULT 0,
   `cause01` int(3) DEFAULT 0,
   `cause10` int(3) DEFAULT 0,
   `cause11` int(3) DEFAULT 0,
   `effect00` int(3) DEFAULT 0,
   `effect01` int(3) DEFAULT 0,
   `effect10` int(3) DEFAULT 0,
   `effect11` int(3) DEFAULT 0,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;



CREATE TABLE IF NOT EXISTS `log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `round` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `move_id` int(11) NOT NULL,
  `action` varchar(16) NOT NULL,
  `domino_id` int(11),
  `action_arg` json,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;
