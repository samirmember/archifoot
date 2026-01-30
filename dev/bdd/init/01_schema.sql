SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `country`;
CREATE TABLE `country` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(150) NULL COMMENT 'Country name',
  `iso2` VARCHAR(2) NULL COMMENT 'ISO 3166-1 alpha-2',
  `iso3` VARCHAR(3) NULL COMMENT 'ISO 3166-1 alpha-3',
  `fifa_code` VARCHAR(3) NULL COMMENT 'FIFA code',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_country_iso2` (`iso2`),
  UNIQUE KEY `uq_country_iso3` (`iso3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `region`;
CREATE TABLE `region` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `country_id` INT NULL COMMENT 'FK country',
  `name` VARCHAR(150) NULL COMMENT 'Region/Wilaya/State',
  `type` VARCHAR(30) NULL COMMENT 'wilaya/region/state/province',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_region_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `city`;
CREATE TABLE `city` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `country_id` INT NULL COMMENT 'FK country',
  `region_id` INT NULL COMMENT 'FK region',
  `name` VARCHAR(150) NULL COMMENT 'City',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_city_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_city_region_id` FOREIGN KEY (`region_id`) REFERENCES `region`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stadium`;
CREATE TABLE `stadium` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(200) NULL COMMENT 'Stadium',
  `city_id` INT NULL COMMENT 'FK city',
  `country_id` INT NULL COMMENT 'FK country',
  `capacity` INT NULL COMMENT 'Capacity',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_stadium_city_id` FOREIGN KEY (`city_id`) REFERENCES `city`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_stadium_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(120) NULL COMMENT 'Category (Seniors, U23, Women...)',
  `gender` VARCHAR(10) NULL COMMENT 'M/F/Mixed',
  `age_min` INT NULL COMMENT 'Min age',
  `age_max` INT NULL COMMENT 'Max age',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `competition`;
CREATE TABLE `competition` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(200) NULL COMMENT 'Competition name',
  `type` VARCHAR(30) NULL COMMENT 'league/cup/friendly/qualifier',
  `organizer` VARCHAR(120) NULL COMMENT 'FAF/CAF/FIFA/UEFA/etc',
  `category_id` INT NULL COMMENT 'FK category',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competition_name_category_id` (`name`, `category_id`),
  CONSTRAINT `fk_competition_category_id` FOREIGN KEY (`category_id`) REFERENCES `category`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `season`;
CREATE TABLE `season` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(20) NULL COMMENT '1962/1963 or 2025/2026',
  `year_start` INT NULL COMMENT 'Start year',
  `year_end` INT NULL COMMENT 'End year',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_season_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `division`;
CREATE TABLE `division` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(120) NULL COMMENT 'Division name',
  `level` INT NULL COMMENT '1=top',
  `country_id` INT NULL COMMENT 'FK country',
  `in_championship` TINYINT(1) NULL COMMENT 'Part of league system' CHECK (`in_championship` IN (0,1)),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_division_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `edition`;
CREATE TABLE `edition` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `competition_id` INT NULL COMMENT 'FK competition',
  `season_id` INT NULL COMMENT 'FK season',
  `name` VARCHAR(120) NULL COMMENT 'Edition name',
  `division_id` INT NULL COMMENT 'FK division (optional)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edition_competition_id_season_id_name` (`competition_id`, `season_id`, `name`),
  CONSTRAINT `fk_edition_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competition`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_edition_season_id` FOREIGN KEY (`season_id`) REFERENCES `season`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_edition_division_id` FOREIGN KEY (`division_id`) REFERENCES `division`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stage`;
CREATE TABLE `stage` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `edition_id` INT NULL COMMENT 'FK edition',
  `name` VARCHAR(120) NULL COMMENT 'Stage/Round/Phase (Group, Final, J1...)',
  `stage_type` VARCHAR(30) NULL COMMENT 'group/knockout/league/round',
  `is_final` TINYINT(1) NULL COMMENT '1 if final' CHECK (`is_final` IN (0,1)),
  `sort_order` INT NULL COMMENT 'Ordering',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_stage_edition_id` FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `matchday`;
CREATE TABLE `matchday` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `edition_id` INT NULL COMMENT 'FK edition',
  `label` VARCHAR(50) NULL COMMENT 'Journée/Matchday label (J1...)',
  `number` INT NULL COMMENT 'Matchday number',
  `start_date` DATE NULL COMMENT 'Optional',
  `end_date` DATE NULL COMMENT 'Optional',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matchday_edition_id_label` (`edition_id`, `label`),
  CONSTRAINT `fk_matchday_edition_id` FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `club`;
CREATE TABLE `club` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(200) NULL COMMENT 'Club name',
  `short_name` VARCHAR(80) NULL COMMENT 'Short name',
  `country_id` INT NULL COMMENT 'FK country',
  `city_id` INT NULL COMMENT 'FK city',
  `region_id` INT NULL COMMENT 'FK region/wilaya',
  `founded_year` INT NULL COMMENT 'Founded year',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_club_name_country_id` (`name`, `country_id`),
  CONSTRAINT `fk_club_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_club_city_id` FOREIGN KEY (`city_id`) REFERENCES `city`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_club_region_id` FOREIGN KEY (`region_id`) REFERENCES `region`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `national_team`;
CREATE TABLE `national_team` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `country_id` INT NULL COMMENT 'FK country',
  `category_id` INT NULL COMMENT 'FK category',
  `name` VARCHAR(200) NULL COMMENT 'Team name',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_national_team_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_national_team_category_id` FOREIGN KEY (`category_id`) REFERENCES `category`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `team_type` VARCHAR(10) NULL COMMENT 'CLUB/NATIONAL',
  `club_id` INT NULL COMMENT 'FK club',
  `national_team_id` INT NULL COMMENT 'FK national_team',
  `display_name` VARCHAR(200) NULL COMMENT 'Resolved name',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_team_type_club_id_national_team_id` (`team_type`, `club_id`, `national_team_id`),
  CONSTRAINT `fk_team_club_id` FOREIGN KEY (`club_id`) REFERENCES `club`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_team_national_team_id` FOREIGN KEY (`national_team_id`) REFERENCES `national_team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `person`;
CREATE TABLE `person` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `full_name` VARCHAR(200) NULL COMMENT 'Name',
  `birth_date` DATE NULL COMMENT 'Birth date',
  `birth_city_id` INT NULL COMMENT 'FK city',
  `birth_region_id` INT NULL COMMENT 'FK region',
  `birth_country_id` INT NULL COMMENT 'FK country',
  `nationality_country_id` INT NULL COMMENT 'FK country',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_person_birth_city_id` FOREIGN KEY (`birth_city_id`) REFERENCES `city`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_person_birth_region_id` FOREIGN KEY (`birth_region_id`) REFERENCES `region`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_person_birth_country_id` FOREIGN KEY (`birth_country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_person_nationality_country_id` FOREIGN KEY (`nationality_country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `position`;
CREATE TABLE `position` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `code` VARCHAR(10) NULL COMMENT 'GK/DF/MF/FW',
  `label` VARCHAR(80) NULL COMMENT 'Position label',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NULL COMMENT 'FK person',
  `primary_position_id` INT NULL COMMENT 'FK position',
  `preferred_foot` VARCHAR(10) NULL COMMENT 'L/R/Both',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_player_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_player_primary_position_id` FOREIGN KEY (`primary_position_id`) REFERENCES `position`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coach`;
CREATE TABLE `coach` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NULL COMMENT 'FK person',
  `role` VARCHAR(50) NULL COMMENT 'Head/Assistant/Trainer',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_coach_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `referee`;
CREATE TABLE `referee` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NULL COMMENT 'FK person',
  `country_id` INT NULL COMMENT 'FK country',
  `level` VARCHAR(50) NULL COMMENT 'International/National',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_referee_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_referee_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `player_national_stats`;
CREATE TABLE `player_national_stats` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `player_id` INT NULL COMMENT 'FK player',
  `team_id` INT NULL COMMENT 'FK team (national)',
  `caps` INT NULL COMMENT 'Caps',
  `goals` INT NULL COMMENT 'Goals',
  `from_date` DATE NULL COMMENT 'Optional',
  `to_date` DATE NULL COMMENT 'Optional',
  `source_note` VARCHAR(200) NULL COMMENT 'Source',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_player_national_stats_player_id` FOREIGN KEY (`player_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_player_national_stats_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `person_club_history`;
CREATE TABLE `person_club_history` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NULL COMMENT 'FK person',
  `club_id` INT NULL COMMENT 'FK club',
  `role` VARCHAR(20) NULL COMMENT 'PLAYER/COACH/STAFF',
  `from_year` INT NULL COMMENT 'Optional',
  `to_year` INT NULL COMMENT 'Optional',
  `note` VARCHAR(200) NULL COMMENT 'Source/text',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_person_club_history_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_person_club_history_club_id` FOREIGN KEY (`club_id`) REFERENCES `club`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixture`;
CREATE TABLE `fixture` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `external_match_no` INT NULL COMMENT 'N° du match (source)',
  `competition_id` INT NULL COMMENT 'FK competition',
  `season_id` INT NULL COMMENT 'FK season',
  `edition_id` INT NULL COMMENT 'FK edition',
  `stage_id` INT NULL COMMENT 'FK stage',
  `matchday_id` INT NULL COMMENT 'FK matchday',
  `division_id` INT NULL COMMENT 'FK division',
  `category_id` INT NULL COMMENT 'FK category',
  `match_date` DATE NULL COMMENT 'Match date',
  `stadium_id` INT NULL COMMENT 'FK stadium',
  `city_id` INT NULL COMMENT 'FK city',
  `country_id` INT NULL COMMENT 'FK country (venue)',
  `played` TINYINT(1) NULL COMMENT 'Played' CHECK (`played` IN (0,1)),
  `is_official` TINYINT(1) NULL COMMENT 'Official match' CHECK (`is_official` IN (0,1)),
  `notes` TEXT NULL COMMENT 'Notes',
  PRIMARY KEY (`id`),
  KEY `ix_fixture_match_date` (`match_date`),
  KEY `ix_fixture_competition_id` (`competition_id`),
  KEY `ix_fixture_season_id` (`season_id`),
  KEY `ix_fixture_edition_id` (`edition_id`),
  KEY `ix_fixture_stage_id` (`stage_id`),
  CONSTRAINT `fk_fixture_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competition`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_season_id` FOREIGN KEY (`season_id`) REFERENCES `season`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_edition_id` FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_stage_id` FOREIGN KEY (`stage_id`) REFERENCES `stage`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_matchday_id` FOREIGN KEY (`matchday_id`) REFERENCES `matchday`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_division_id` FOREIGN KEY (`division_id`) REFERENCES `division`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_category_id` FOREIGN KEY (`category_id`) REFERENCES `category`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_stadium_id` FOREIGN KEY (`stadium_id`) REFERENCES `stadium`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_city_id` FOREIGN KEY (`city_id`) REFERENCES `city`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixture_participant`;
CREATE TABLE `fixture_participant` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `fixture_id` INT NULL COMMENT 'FK fixture',
  `team_id` INT NULL COMMENT 'FK team',
  `role` VARCHAR(1) NULL COMMENT 'A or B (from source)',
  `score` INT NULL COMMENT 'Goals in regular time',
  `score_extra` INT NULL COMMENT 'Extra time goals (optional)',
  `score_penalty` INT NULL COMMENT 'Penalty shootout goals (optional)',
  `is_winner` TINYINT(1) NULL COMMENT 'Winner' CHECK (`is_winner` IN (0,1)),
  `venue_role` VARCHAR(10) NULL COMMENT 'HOME/AWAY/NEUTRAL/UNKNOWN (derived)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fixture_participant_fixture_id_role` (`fixture_id`, `role`),
  KEY `ix_fixture_participant_team_id` (`team_id`),
  KEY `ix_fixture_participant_fixture_id` (`fixture_id`),
  CONSTRAINT `fk_fixture_participant_fixture_id` FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fixture_participant_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scoresheet`;
CREATE TABLE `scoresheet` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `fixture_id` INT NULL COMMENT 'FK fixture (unique)',
  `attendance` INT NULL COMMENT 'Attendance',
  `fixed_time` VARCHAR(5) NULL COMMENT 'Scheduled time HH:MM',
  `kickoff_time` VARCHAR(5) NULL COMMENT 'Kick-off time HH:MM',
  `half_time` VARCHAR(5) NULL COMMENT 'Half-time HH:MM',
  `second_half_start` VARCHAR(5) NULL COMMENT 'Second half start HH:MM',
  `full_time` VARCHAR(5) NULL COMMENT 'Full time HH:MM',
  `stoppage_time` VARCHAR(5) NULL COMMENT 'Stoppage time HH:MM',
  `match_stop_time` VARCHAR(5) NULL COMMENT 'Match stopped at HH:MM',
  `reservations` TEXT NULL COMMENT 'Reservations',
  `report` TEXT NULL COMMENT 'Report',
  `signed_place` VARCHAR(150) NULL COMMENT 'Signed at',
  `signed_on` DATE NULL COMMENT 'Signed on',
  `coach_id` INT NULL COMMENT 'FK coach (person)',
  `form_state` VARCHAR(1) NULL COMMENT '0=draft,1=validated,2=archived',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_scoresheet_fixture_id` (`fixture_id`),
  CONSTRAINT `fk_scoresheet_fixture_id` FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_scoresheet_coach_id` FOREIGN KEY (`coach_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scoresheet_official`;
CREATE TABLE `scoresheet_official` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `scoresheet_id` INT NULL COMMENT 'FK scoresheet',
  `role` VARCHAR(30) NULL COMMENT 'MAIN_REF/AR1/AR2/FOURTH/DELEGATE/DOCTOR',
  `person_id` INT NULL COMMENT 'FK person',
  `name_text` VARCHAR(150) NULL COMMENT 'Fallback',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_scoresheet_official_scoresheet_id` FOREIGN KEY (`scoresheet_id`) REFERENCES `scoresheet`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_scoresheet_official_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scoresheet_lineup`;
CREATE TABLE `scoresheet_lineup` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `scoresheet_id` INT NULL COMMENT 'FK scoresheet',
  `team_id` INT NULL COMMENT 'FK team',
  `player_id` INT NULL COMMENT 'FK player',
  `player_name_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `shirt_number` INT NULL COMMENT 'Shirt number',
  `lineup_role` VARCHAR(12) NULL COMMENT 'STARTER/SUB',
  `is_captain` TINYINT(1) NULL COMMENT 'Captain' CHECK (`is_captain` IN (0,1)),
  `position_id` INT NULL COMMENT 'FK position',
  `sort_order` INT NULL COMMENT 'Order',
  PRIMARY KEY (`id`),
  KEY `ix_scoresheet_lineup_player_id` (`player_id`),
  KEY `ix_scoresheet_lineup_scoresheet_id` (`scoresheet_id`),
  CONSTRAINT `fk_scoresheet_lineup_scoresheet_id` FOREIGN KEY (`scoresheet_id`) REFERENCES `scoresheet`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_scoresheet_lineup_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_scoresheet_lineup_player_id` FOREIGN KEY (`player_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_scoresheet_lineup_position_id` FOREIGN KEY (`position_id`) REFERENCES `position`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scoresheet_substitution`;
CREATE TABLE `scoresheet_substitution` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `scoresheet_id` INT NULL COMMENT 'FK scoresheet',
  `team_id` INT NULL COMMENT 'FK team',
  `player_out_id` INT NULL COMMENT 'FK player out',
  `player_in_id` INT NULL COMMENT 'FK player in',
  `player_out_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `player_in_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `minute` VARCHAR(8) NULL COMMENT 'Minute e.g. 74, 90+2',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_scoresheet_substitution_scoresheet_id` FOREIGN KEY (`scoresheet_id`) REFERENCES `scoresheet`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_scoresheet_substitution_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_scoresheet_substitution_player_out_id` FOREIGN KEY (`player_out_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_scoresheet_substitution_player_in_id` FOREIGN KEY (`player_in_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `match_goal`;
CREATE TABLE `match_goal` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `fixture_id` INT NULL COMMENT 'FK fixture',
  `team_id` INT NULL COMMENT 'FK team',
  `scorer_id` INT NULL COMMENT 'FK player',
  `scorer_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `minute` VARCHAR(8) NULL COMMENT 'Minute',
  `goal_type` VARCHAR(20) NULL COMMENT 'normal/penalty/own_goal',
  PRIMARY KEY (`id`),
  KEY `ix_match_goal_scorer_id` (`scorer_id`),
  KEY `ix_match_goal_fixture_id` (`fixture_id`),
  CONSTRAINT `fk_match_goal_fixture_id` FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_match_goal_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_match_goal_scorer_id` FOREIGN KEY (`scorer_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `match_card`;
CREATE TABLE `match_card` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `fixture_id` INT NULL COMMENT 'FK fixture',
  `team_id` INT NULL COMMENT 'FK team',
  `player_id` INT NULL COMMENT 'FK player',
  `player_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `minute` VARCHAR(8) NULL COMMENT 'Minute',
  `card_type` VARCHAR(5) NULL COMMENT 'Y/R/Y2',
  `reason` VARCHAR(200) NULL COMMENT 'Reason',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_match_card_fixture_id` FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_match_card_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_match_card_player_id` FOREIGN KEY (`player_id`) REFERENCES `player`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `standing`;
CREATE TABLE `standing` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `edition_id` INT NULL COMMENT 'FK edition',
  `stage_id` INT NULL COMMENT 'FK stage',
  `matchday_id` INT NULL COMMENT 'FK matchday (optional)',
  `team_id` INT NULL COMMENT 'FK team',
  `rank` INT NULL COMMENT 'Rank',
  `played` INT NULL COMMENT 'Played',
  `won` INT NULL COMMENT 'Won',
  `draw` INT NULL COMMENT 'Draw',
  `lost` INT NULL COMMENT 'Lost',
  `goals_for` INT NULL COMMENT 'GF',
  `goals_against` INT NULL COMMENT 'GA',
  `goal_diff` INT NULL COMMENT 'GD',
  `points` INT NULL COMMENT 'Points',
  `observation` VARCHAR(200) NULL COMMENT 'Observation',
  `source_note` VARCHAR(200) NULL COMMENT 'Source',
  PRIMARY KEY (`id`),
  KEY `ix_standing_team_id` (`team_id`),
  KEY `ix_standing_edition_id` (`edition_id`),
  CONSTRAINT `fk_standing_edition_id` FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_standing_stage_id` FOREIGN KEY (`stage_id`) REFERENCES `stage`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_standing_matchday_id` FOREIGN KEY (`matchday_id`) REFERENCES `matchday`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_standing_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `trophy`;
CREATE TABLE `trophy` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `name` VARCHAR(200) NULL COMMENT 'Trophy/Title',
  `competition_id` INT NULL COMMENT 'FK competition',
  `trophy_type` VARCHAR(30) NULL COMMENT 'winner/runner_up/top_scorer/champion_squad',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_trophy_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competition`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `trophy_award`;
CREATE TABLE `trophy_award` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `trophy_id` INT NULL COMMENT 'FK trophy',
  `season_id` INT NULL COMMENT 'FK season',
  `edition_id` INT NULL COMMENT 'FK edition',
  `team_id` INT NULL COMMENT 'FK team',
  `person_id` INT NULL COMMENT 'FK person',
  `rank` INT NULL COMMENT '1=winner',
  `note` VARCHAR(200) NULL COMMENT 'Note/Source',
  PRIMARY KEY (`id`),
  KEY `ix_trophy_award_team_id` (`team_id`),
  KEY `ix_trophy_award_person_id` (`person_id`),
  KEY `ix_trophy_award_season_id` (`season_id`),
  CONSTRAINT `fk_trophy_award_trophy_id` FOREIGN KEY (`trophy_id`) REFERENCES `trophy`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_trophy_award_season_id` FOREIGN KEY (`season_id`) REFERENCES `season`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_trophy_award_edition_id` FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_trophy_award_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_trophy_award_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `trophy_award_person`;
CREATE TABLE `trophy_award_person` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `trophy_award_id` INT NULL COMMENT 'FK trophy_award',
  `person_id` INT NULL COMMENT 'FK person',
  `role` VARCHAR(20) NULL COMMENT 'PLAYER/COACH/STAFF',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_trophy_award_person_trophy_award_id` FOREIGN KEY (`trophy_award_id`) REFERENCES `trophy_award`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_trophy_award_person_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `name_alias`;
CREATE TABLE `name_alias` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `entity_type` VARCHAR(30) NULL COMMENT 'team/club/stadium/competition/person/city',
  `entity_id` INT NULL COMMENT 'Target id',
  `alias` VARCHAR(200) NULL COMMENT 'Alias (source)',
  `normalized` VARCHAR(200) NULL COMMENT 'Normalized key',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name_alias_entity_type_entity_id_normalized` (`entity_type`, `entity_id`, `normalized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `source_file`;
CREATE TABLE `source_file` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `filename` VARCHAR(255) NULL COMMENT 'Source filename',
  `checksum_sha1` VARCHAR(40) NULL COMMENT 'Checksum',
  `imported_at` DATETIME NULL COMMENT 'Imported at',
  `note` VARCHAR(255) NULL COMMENT 'Note',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `import_batch`;
CREATE TABLE `import_batch` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `source_file_id` INT NULL COMMENT 'FK source_file',
  `entity` VARCHAR(50) NULL COMMENT 'fixture/scoresheet/player...',
  `status` VARCHAR(20) NULL COMMENT 'started/done/failed',
  `inserted` INT NULL COMMENT 'Inserted',
  `updated` INT NULL COMMENT 'Updated',
  `skipped` INT NULL COMMENT 'Skipped',
  `started_at` DATETIME NULL COMMENT 'Started',
  `finished_at` DATETIME NULL COMMENT 'Finished',
  `error` TEXT NULL COMMENT 'Error',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_import_batch_source_file_id` FOREIGN KEY (`source_file_id`) REFERENCES `source_file`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;