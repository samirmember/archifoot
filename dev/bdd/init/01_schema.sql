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
  UNIQUE KEY `uq_country_fifa_code` (`fifa_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Insertion de tous les pays du monde avec codes ISO et FIFA
-- Pour table country (application football)

INSERT INTO `country` (`name`, `iso2`, `iso3`, `fifa_code`) VALUES
('Algérie', 'DZ', 'DZA', 'ALG'),
('Allemagne','DE','DEU','GER'),
('Espagne','ES','ESP','ESP'),
('Italie','IT','ITA','ITA'),
('Angleterre','GB','GBR','ENG'),
('Pays-Bas','NL','NLD','NED'),
('Belgique','BE','BEL','BEL'),
('Portugal','PT','PRT','POR'),
('Suisse','CH','CHE','SUI'),
('Autriche','AT','AUT','AUT'),
('Pologne','PL','POL','POL'),
('Ukraine','UA','UKR','UKR'),
('Tchéquie','CZ','CZE','CZE'),
('Croatie','HR','HRV','CRO'),
('Danemark','DK','DNK','DEN'),
('Suède','SE','SWE','SWE'),
('Norvège','NO','NOR','NOR'),
('Finlande','FI','FIN','FIN'),
('France','FR','FRA','FRA'),
('Russie','RU','RUS','RUS'),
('Turquie','TR','TUR','TUR'),
('Grèce','GR','GRC','GRE'),
('Roumanie','RO','ROU','ROU'),
('Hongrie','HU','HUN','HUN'),
('Serbie','RS','SRB','SRB'),
('Slovaquie','SK','SVK','SVK'),
('Bulgarie','BG','BGR','BUL'),
('Slovénie','SI','SVN','SVN'),
('Irlande','IE','IRL','IRL'),
('Écosse','GB','GBR','SCO'),
('Pays de Galles','GB','GBR','WAL'),
('Irlande du Nord','GB','GBR','NIR'),
('Islande','IS','ISL','ISL'),
('Bosnie-Herzégovine','BA','BIH','BIH'),
('Albanie','AL','ALB','ALB'),
('Macédoine du Nord','MK','MKD','MKD'),
('Monténégro','ME','MNE','MNE'),
('Kosovo','XK','XKX','KVX'),
('Lituanie','LT','LTU','LTU'),
('Lettonie','LV','LVA','LVA'),
('Estonie','EE','EST','EST'),
('Biélorussie','BY','BLR','BLR'),
('Luxembourg','LU','LUX','LUX'),
('Malte','MT','MLT','MLT'),
('Chypre','CY','CYP','CYP'),
('Moldavie','MD','MDA','MDA'),
('Arménie','AM','ARM','ARM'),
('Géorgie','GE','GEO','GEO'),
('Azerbaïdjan','AZ','AZE','AZE'),
('Kazakhstan','KZ','KAZ','KAZ'),
('Liechtenstein','LI','LIE','LIE'),
('Andorre','AD','AND','AND'),
('Monaco','MC','MCO','MCO'),
('Saint-Marin','SM','SMR','SMR'),
('Gibraltar','GI','GIB','GIB'),
('Îles Féroé','FO','FRO','FRO'),
('Brésil','BR','BRA','BRA'),
('Argentine','AR','ARG','ARG'),
('Uruguay','UY','URY','URU'),
('Colombie','CO','COL','COL'),
('Chili','CL','CHL','CHI'),
('Paraguay','PY','PRY','PAR'),
('Pérou','PE','PER','PER'),
('Équateur','EC','ECU','ECU'),
('Bolivie','BO','BOL','BOL'),
('Venezuela','VE','VEN','VEN'),
('Guyana','GY','GUY','GUY'),
('Suriname','SR','SUR','SUR'),
('États-Unis','US','USA','USA'),
('Mexique','MX','MEX','MEX'),
('Canada','CA','CAN','CAN'),
('Costa Rica','CR','CRI','CRC'),
('Panama','PA','PAN','PAN'),
('Jamaïque','JM','JAM','JAM'),
('Honduras','HN','HND','HON'),
('Trinité-et-Tobago','TT','TTO','TRI'),
('Guatemala','GT','GTM','GUA'),
('El Salvador','SV','SLV','SLV'),
('Curaçao','CW','CUW','CUW'),
('Haïti','HT','HTI','HAI'),
('Nicaragua','NI','NIC','NCA'),
('Cuba','CU','CUB','CUB'),
('Grenade','GD','GRD','GRN'),
('Saint-Christophe-et-Niévès','KN','KNA','SKN'),
('Sainte-Lucie','LC','LCA','LCA'),
('Saint-Vincent-et-les-Grenadines','VC','VCT','VIN'),
('Antigua-et-Barbuda','AG','ATG','ATG'),
('Barbade','BB','BRB','BRB'),
('Dominique','DM','DMA','DMA'),
('Bermudes','BM','BMU','BER'),
('Belize','BZ','BLZ','BLZ'),
('Bahamas','BS','BHS','BAH'),
('Guadeloupe','GP','GLP','GLP'),
('Martinique','MQ','MTQ','MTQ'),
('Guyane française','GF','GUF','GUF'),
('République dominicaine','DO','DOM','DOM'),
('Porto Rico','PR','PRI','PUR'),
('Îles Vierges américaines','VI','VIR','VIR'),
('Îles Caïmans','KY','CYM','CAY'),
('Îles Turques-et-Caïques','TC','TCA','TCA'),
('Aruba','AW','ABW','ARU'),
('Saint-Martin','MF','MAF','SMN'),
('Sint Maarten','SX','SXM','SXM'),
('Bonaire','BQ','BES','BOE'),
('Anguilla','AI','AIA','AIA'),
('Montserrat','MS','MSR','MSR'),
('Îles Vierges britanniques','VG','VGB','VGB'),
('Sénégal','SN','SEN','SEN'),
('Maroc','MA','MAR','MAR'),
('Tunisie','TN','TUN','TUN'),
('Égypte','EG','EGY','EGY'),
('Nigeria','NG','NGA','NGA'),
('Cameroun','CM','CMR','CMR'),
('Ghana','GH','GHA','GHA'),
('Côte d\'Ivoire','CI','CIV','CIV'),
('Afrique du Sud','ZA','ZAF','RSA'),
('Burkina Faso','BF','BFA','BFA'),
('Mali','ML','MLI','MLI'),
('Kenya','KE','KEN','KEN'),
('Zambie','ZM','ZMB','ZAM'),
('Zimbabwe','ZW','ZWE','ZIM'),
('Angola','AO','AGO','ANG'),
('Guinée','GN','GIN','GUI'),
('Ouganda','UG','UGA','UGA'),
('Gabon','GA','GAB','GAB'),
('Bénin','BJ','BEN','BEN'),
('Togo','TG','TGO','TOG'),
('Congo','CG','COG','CGO'),
('RD Congo','CD','COD','COD'),
('Guinée Équatoriale','GQ','GNQ','EQG'),
('Cap-Vert','CV','CPV','CPV'),
('Mauritanie','MR','MRT','MTN'),
('Libéria','LR','LBR','LBR'),
('Sierra Leone','SL','SLE','SLE'),
('Gambie','GM','GMB','GAM'),
('Guinée-Bissau','GW','GNB','GNB'),
('Niger','NE','NER','NIG'),
('Tchad','TD','TCD','CHA'),
('Éthiopie','ET','ETH','ETH'),
('Érythrée','ER','ERI','ERI'),
('Somalie','SO','SOM','SOM'),
('Soudan','SD','SDN','SUD'),
('Soudan du Sud','SS','SSD','SSD'),
('Tanzanie','TZ','TZA','TAN'),
('Rwanda','RW','RWA','RWA'),
('Burundi','BI','BDI','BDI'),
('Madagascar','MG','MDG','MAD'),
('Malawi','MW','MWI','MWI'),
('Mozambique','MZ','MOZ','MOZ'),
('Namibie','NA','NAM','NAM'),
('Botswana','BW','BWA','BOT'),
('Eswatini','SZ','SWZ','SWZ'),
('Lesotho','LS','LSO','LES'),
('Maurice','MU','MUS','MRI'),
('Comores','KM','COM','COM'),
('Seychelles','SC','SYC','SEY'),
('Djibouti','DJ','DJI','DJI'),
('Libye','LY','LBY','LBY'),
('São Tomé-et-Principe','ST','STP','STP'),
('Centrafrique','CF','CAF','CTA'),
('Réunion','RE','REU','REU'),
('Zanzibar','TZ','TZA','ZAN'),
('Japon','JP','JPN','JPN'),
('Corée du Sud','KR','KOR','KOR'),
('Corée du Nord','KP','PRK','PRK'),
('Chine','CN','CHN','CHN'),
('Australie','AU','AUS','AUS'),
('Iran','IR','IRN','IRN'),
('Arabie saoudite','SA','SAU','KSA'),
('Qatar','QA','QAT','QAT'),
('Émirats Arabes Unis','AE','ARE','UAE'),
('Irak','IQ','IRQ','IRQ'),
('Ouzbékistan','UZ','UZB','UZB'),
('Thaïlande','TH','THA','THA'),
('Viêt Nam','VN','VNM','VIE'),
('Inde','IN','IND','IND'),
('Oman','OM','OMN','OMA'),
('Jordanie','JO','JOR','JOR'),
('Bahreïn','BH','BHR','BHR'),
('Koweït','KW','KWT','KUW'),
('Syrie','SY','SYR','SYR'),
('Liban','LB','LBN','LBN'),
('Palestine','PS','PSE','PLE'),
('Yémen','YE','YEM','YEM'),
('Kirghizistan','KG','KGZ','KGZ'),
('Tadjikistan','TJ','TJK','TJK'),
('Turkménistan','TM','TKM','TKM'),
('Afghanistan','AF','AFG','AFG'),
('Pakistan','PK','PAK','PAK'),
('Bangladesh','BD','BGD','BAN'),
('Sri Lanka','LK','LKA','SRI'),
('Maldives','MV','MDV','MDV'),
('Népal','NP','NPL','NEP'),
('Bhoutan','BT','BTN','BHU'),
('Myanmar','MM','MMR','MYA'),
('Laos','LA','LAO','LAO'),
('Cambodge','KH','KHM','CAM'),
('Malaisie','MY','MYS','MAS'),
('Singapour','SG','SGP','SIN'),
('Indonésie','ID','IDN','IDN'),
('Philippines','PH','PHL','PHI'),
('Brunei','BN','BRN','BRU'),
('Timor oriental','TL','TLS','TLS'),
('Mongolie','MN','MNG','MGL'),
('Hong Kong','HK','HKG','HKG'),
('Macao','MO','MAC','MAC'),
('Taïwan','TW','TWN','TPE'),
('Guam','GU','GUM','GUM'),
('Nouvelle-Zélande','NZ','NZL','NZL'),
('Fidji','FJ','FJI','FIJ'),
('Papouasie-Nouvelle-Guinée','PG','PNG','PNG'),
('Nouvelle-Calédonie','NC','NCL','NCL'),
('Tahiti','PF','PYF','TAH'),
('Îles Salomon','SB','SLB','SOL'),
('Vanuatu','VU','VUT','VAN'),
('Samoa','WS','WSM','SAM'),
('Îles Cook','CK','COK','COK'),
('Tonga','TO','TON','TGA'),
('Samoa américaines','AS','ASM','ASA'),
('Tuvalu','TV','TUV','TUV'),
('Kiribati','KI','KIR','KIR'),
('Palaos','PW','PLW','PLW'),
('Nauru','NR','NRU','NRU'),
('Micronésie','FM','FSM','FSM'),
('Îles Marshall','MH','MHL','MHL'),
('Niue','NU','NIU','NIU'),
('Tokelau','TK','TKL','TKL'),
('Yougoslavie','YU','YUG','YUG'),
('Tchécoslovaquie','CZ','CSK','TCH'),
('URSS','SU','SUN','URS')
;

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
  `competition_id` INT NOT NULL COMMENT 'FK competition',
  `season_id` INT NULL COMMENT 'FK season',
  `name` VARCHAR(120) NULL COMMENT 'Edition name',
  `division_id` INT NULL COMMENT 'FK division (optional)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edition_competition_id_season_id_name` (`competition_id`, `season_id`, `name`),
  CONSTRAINT `fk_edition_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competition`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_edition_season_id` FOREIGN KEY (`season_id`) REFERENCES `season`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_edition_division_id` FOREIGN KEY (`division_id`) REFERENCES `division`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stage`;
CREATE TABLE `stage` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `edition_id` INT NULL COMMENT 'FK edition',
  `name` VARCHAR(120) NULL COMMENT 'Stage/Round/Phase (Group, Final, J1...)',
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

INSERT INTO `national_team` (id, country_id, category_id, name) VALUES (1, 1, 1, 'Algérie');

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

INSERT INTO `team` (id, team_type, club_id, national_team_id, display_name) VALUES (1, 'NATIONAL', NULL, 1, 'Algérie');

DROP TABLE IF EXISTS `person`;
CREATE TABLE `person` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `external_number` VARCHAR(50) DEFAULT NULL COMMENT 'External number',
  `full_name` VARCHAR(200) NULL COMMENT 'Name',
  `birth_date` DATE NULL COMMENT 'Birth date',
  `birth_city_id` INT NULL COMMENT 'FK city',
  `birth_region_id` INT NULL COMMENT 'FK region',
  `birth_country_id` INT NULL COMMENT 'FK country',
  `nationality_country_id` INT NULL COMMENT 'FK country',
  `death_date` DATE DEFAULT NULL COMMENT 'Death date',
  `photo_url` VARCHAR(150) DEFAULT NULL COMMENT 'Photo URL',
  `feature_photo_url` VARCHAR(150) DEFAULT NULL COMMENT 'Photo de couverture',
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

INSERT INTO `position` (id, code, label) VALUES (1, 'GK', 'Gardien de but');
INSERT INTO `position` (id, code, label) VALUES (2, 'DF', 'Défenseur');
INSERT INTO `position` (id, code, label) VALUES (3, 'MF', 'Milieu de terrain');
INSERT INTO `position` (id, code, label) VALUES (4, 'FW', 'Attaquant');

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `external_number` VARCHAR(50) DEFAULT NULL COMMENT 'External number',
  `person_id` INT NULL COMMENT 'FK person',
  `primary_position_id` INT NULL COMMENT 'FK position',
  `selections` INT DEFAULT NULL COMMENT 'Number of selections',
  `goals` INT DEFAULT NULL COMMENT 'Number of goals',
  `main_clubs` JSON DEFAULT NULL COMMENT 'Main clubs',
  `career` VARCHAR(100) DEFAULT NULL COMMENT 'Career',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_player_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_player_primary_position_id` FOREIGN KEY (`primary_position_id`) REFERENCES `position`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coach`;
CREATE TABLE `coach` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `external_number` VARCHAR(50) DEFAULT NULL COMMENT 'External number',
  `person_id` INT NULL COMMENT 'FK person',
  `role` VARCHAR(50) NULL COMMENT 'Head/Assistant/Trainer',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_coach_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `referee`;
CREATE TABLE `referee` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NULL COMMENT 'FK person',
  `level` VARCHAR(50) NULL COMMENT 'International/National',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_referee_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
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
  `season_id` INT NULL COMMENT 'FK season',
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
  `internal_notes` TEXT NULL COMMENT 'Notes internes',
  PRIMARY KEY (`id`),
  KEY `ix_fixture_match_date` (`match_date`),
  KEY `ix_fixture_season_id` (`season_id`),
  CONSTRAINT `fk_fixture_season_id` FOREIGN KEY (`season_id`) REFERENCES `season`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_matchday_id` FOREIGN KEY (`matchday_id`) REFERENCES `matchday`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_division_id` FOREIGN KEY (`division_id`) REFERENCES `division`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_category_id` FOREIGN KEY (`category_id`) REFERENCES `category`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_stadium_id` FOREIGN KEY (`stadium_id`) REFERENCES `stadium`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_city_id` FOREIGN KEY (`city_id`) REFERENCES `city`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_fixture_country_id` FOREIGN KEY (`country_id`) REFERENCES `country`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixture_competition`;
CREATE TABLE `fixture_competition` (
  `fixture_id` INT NOT NULL COMMENT 'FK fixture',
  `competition_id` INT NOT NULL COMMENT 'FK competition',
  PRIMARY KEY (`fixture_id`, `competition_id`),
  KEY `ix_fixture_competition_competition_id` (`competition_id`),
  CONSTRAINT `fk_fixture_competition_fixture_id`
    FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fixture_competition_competition_id`
    FOREIGN KEY (`competition_id`) REFERENCES `competition`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixture_edition`;
CREATE TABLE `fixture_edition` (
  `fixture_id` INT NOT NULL COMMENT 'FK fixture',
  `edition_id` INT NOT NULL COMMENT 'FK edition',
  PRIMARY KEY (`fixture_id`, `edition_id`),
  KEY `ix_fixture_edition_edition_id` (`edition_id`),
  CONSTRAINT `fk_fixture_edition_fixture_id`
    FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fixture_edition_edition_id`
    FOREIGN KEY (`edition_id`) REFERENCES `edition`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixture_stage`;
CREATE TABLE `fixture_stage` (
  `fixture_id` INT NOT NULL COMMENT 'FK fixture',
  `stage_id` INT NOT NULL COMMENT 'FK stage',
  PRIMARY KEY (`fixture_id`, `stage_id`),
  KEY `ix_fs_stage_id` (`stage_id`),
  CONSTRAINT `fk_fs_fixture_id`
    FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fs_stage_id`
    FOREIGN KEY (`stage_id`) REFERENCES `stage`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
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
  `outcome` TINYINT(1) NULL COMMENT 'Winner or not' CHECK (`outcome` IN (0,1,2)),
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
  `status` VARCHAR(1) NULL COMMENT '0=draft,1=validated,2=archived',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_scoresheet_fixture_id` (`fixture_id`),
  CONSTRAINT `fk_scoresheet_fixture_id` FOREIGN KEY (`fixture_id`) REFERENCES `fixture`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
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

DROP TABLE IF EXISTS `scoresheet_staff`;
CREATE TABLE `scoresheet_staff` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `scoresheet_id` INT NULL COMMENT 'FK scoresheet',
  `team_id` INT NULL COMMENT 'FK team',
  `person_id` INT NULL COMMENT 'FK person',
  `role` VARCHAR(32) NOT NULL COMMENT 'HEAD_COACH / ASSISTANT_COACH',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_scoresheet_staff_scoresheet_id` FOREIGN KEY (`scoresheet_id`) REFERENCES `scoresheet`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_scoresheet_staff_team_id` FOREIGN KEY (`team_id`) REFERENCES `team`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_scoresheet_staff_person_id` FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `match_goal`;
CREATE TABLE `match_goal` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `fixture_id` INT NULL COMMENT 'FK fixture',
  `team_id` INT NULL COMMENT 'FK team',
  `scorer_id` INT NULL COMMENT 'FK player',
  `scorer_text` VARCHAR(200) NULL COMMENT 'Fallback',
  `minute` VARCHAR(8) NULL COMMENT 'Minute',
  `goal_type` VARCHAR(20) NULL COMMENT 'normal/penalty/own_goal/unknown',
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


DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `type` ENUM('PLAYER', 'COACH', 'REFEREE') NOT NULL COMMENT 'Person type (PLAYER, COACH, REFEREE...)',
  `code` VARCHAR(30) NOT NULL COMMENT 'Code technique (PLAYER, HEAD_COACH...)',
  `label` VARCHAR(80) NOT NULL COMMENT 'Libellé',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role` (`type`, `code`, `label`) VALUES
('PLAYER','PLAYER','Joueur'),
('COACH','HEAD_COACH','Entraîneur principal'),
('COACH','ASSISTANT_COACH','Entraîneur assistant'),
('REFEREE','MAIN_REFEREE','Arbitre principal'),
('REFEREE','ASSISTANT_REFEREE','Arbitre assistant'),
('REFEREE','FOURTH_OFFICIAL','Quatrième arbitre')
;

DROP TABLE IF EXISTS `person_assignment`;
CREATE TABLE `person_assignment` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '#',
  `person_id` INT NOT NULL COMMENT 'FK person',
  `team_id`   INT NOT NULL COMMENT 'FK team',
  `role_id`   INT NOT NULL COMMENT 'FK role',
  `season_id` INT NULL COMMENT 'FK season',  -- saison optionnelle (utile quand tu sais juste "saison 2019/2020" sans dates précises)
  `from_date` DATE NULL COMMENT 'Début (NULL si inconnu)', -- dates optionnelles (utile quand tu sais les dates exactes ou approximatives)
  `to_date`   DATE NULL COMMENT 'Fin (NULL = en cours / inconnu)',
  PRIMARY KEY (`id`),
  KEY `ix_tpr_role_dates` (`team_id`, `role_id`, `from_date`, `to_date`),
  KEY `ix_tpr_person_dates` (`person_id`, `from_date`, `to_date`),
  KEY `ix_tpr_season` (`season_id`),
  UNIQUE KEY `uq_tpr_exact` (`person_id`, `team_id`, `role_id`, `season_id`, `from_date`, `to_date`),
  CONSTRAINT `fk_tpr_person_id`
    FOREIGN KEY (`person_id`) REFERENCES `person`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_tpr_team_id`
    FOREIGN KEY (`team_id`) REFERENCES `team`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_tpr_role_id`
    FOREIGN KEY (`role_id`) REFERENCES `role`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_tpr_season_id`
    FOREIGN KEY (`season_id`) REFERENCES `season`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `person_photo`;
CREATE TABLE `person_photo` (
  id INT AUTO_INCREMENT NOT NULL,
  person_id INT NOT NULL,
  image_url VARCHAR(150) NOT NULL,
  caption VARCHAR(150) DEFAULT NULL,
  sort_order INT DEFAULT 0 NOT NULL,
  INDEX IDX_BC9D1BE217BBB47 (person_id),
  PRIMARY KEY(id),
  CONSTRAINT FK_BC9D1BE217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;


# Liaison joueur - clubs ou équipe nationale
-- =========================================================
-- Ajout d'une table robuste pour :
-- - club actuel d'un joueur
-- - club à la date d'un match (fixture.match_date)
-- - historique complet des clubs
-- Compatible avec le schéma (team/team_type, player, fixture, source_file)
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;

-- 1) Garantir que chaque club a une ligne team (team_type='CLUB')
-- (idempotent grâce à l'UNIQUE uq_team_team_type_club_id_national_team_id)
INSERT IGNORE INTO `team` (`team_type`, `club_id`, `national_team_id`, `display_name`)
SELECT 'CLUB', c.id, NULL, COALESCE(c.short_name, c.name)
FROM `club` c;


-- =========================================================
-- VUES "CURRENT" basées sur person_assignment + role=PLAYER
-- =========================================================

DROP VIEW IF EXISTS v_player_current_club;
CREATE VIEW v_player_current_club AS
SELECT
  pa.person_id AS player_id,
  pa.team_id,
  t.display_name AS club_name,
  pa.from_date
FROM person_assignment pa
JOIN team t   ON t.id = pa.team_id
JOIN role r   ON r.id = pa.role_id
WHERE r.code = 'PLAYER'
  AND t.team_type = 'CLUB'
  AND (pa.from_date IS NULL OR pa.from_date <= CURDATE())
  AND (pa.to_date   IS NULL OR pa.to_date   >= CURDATE());


DROP VIEW IF EXISTS v_player_current_national;
CREATE VIEW v_player_current_national AS
SELECT
  pa.person_id AS player_id,
  pa.team_id,
  t.display_name AS national_team_name,
  pa.from_date
FROM person_assignment pa
JOIN team t ON t.id = pa.team_id
JOIN role r ON r.id = pa.role_id
WHERE r.code = 'PLAYER'
  AND t.team_type = 'NATIONAL'
  AND (pa.from_date IS NULL OR pa.from_date <= CURDATE())
  AND (pa.to_date   IS NULL OR pa.to_date   >= CURDATE());


DROP VIEW IF EXISTS v_player_current_team;
CREATE VIEW v_player_current_team AS
SELECT
  pa.person_id AS player_id,
  t.team_type,
  pa.team_id,
  t.display_name AS team_name,
  pa.from_date
FROM person_assignment pa
JOIN team t ON t.id = pa.team_id
JOIN role r ON r.id = pa.role_id
WHERE r.code = 'PLAYER'
  AND (pa.from_date IS NULL OR pa.from_date <= CURDATE())
  AND (pa.to_date   IS NULL OR pa.to_date   >= CURDATE());


-- =========================================================
-- INDEX person_assignment (ex-ptm)
-- =========================================================

-- Lookup "current" (par person + role) : utile pour vues current et requêtes rapides
CREATE INDEX ix_pa_person_role_current
  ON person_assignment(person_id, role_id, to_date, from_date);

-- Requêtes à date (person + période)
CREATE INDEX ix_pa_person_from_to
  ON person_assignment(person_id, from_date, to_date);

-- Variante utile si beaucoup de to_date NULL
CREATE INDEX ix_pa_person_to_from
  ON person_assignment(person_id, to_date, from_date);

-- Requêtes par team (effectif à date / historique)
CREATE INDEX ix_pa_team_from_to
  ON person_assignment(team_id, from_date, to_date);

-- Filtre rôle (PLAYER vs HEAD_COACH...) quand tu requêtes beaucoup par rôle
CREATE INDEX ix_pa_role_team_dates
  ON person_assignment(role_id, team_id, from_date, to_date);

-- (inchangé) filtre team_type
CREATE INDEX ix_team_type
  ON team(team_type);




# Les vues:
-- 1) Team_id de l'Equipe Nationale d'Algérie
CREATE OR REPLACE VIEW v_team_nt_alg AS
SELECT t.id AS team_id
FROM team t
JOIN national_team nt ON nt.id = t.national_team_id
WHERE t.team_type = 'NATIONAL'
  AND (
    nt.country_id = 1
  );

-- 2) Liste des joueurs de l'EN Algérie (toutes périodes / sans date)
CREATE OR REPLACE VIEW v_nt_alg_players AS
SELECT DISTINCT
  p.id        AS person_id,
  p.full_name AS full_name
FROM person_assignment pa
JOIN v_team_nt_alg alg ON alg.team_id = pa.team_id
JOIN role r ON r.id = pa.role_id
JOIN person p ON p.id = pa.person_id
WHERE r.code = 'PLAYER';

-- 3) Liste des entraîneurs de l'EN Algérie (toutes périodes / sans date)
CREATE OR REPLACE VIEW v_nt_alg_coaches AS
SELECT DISTINCT
  p.id        AS person_id,
  p.full_name AS full_name,
  r.code      AS role_code
FROM person_assignment pa
JOIN v_team_nt_alg alg ON alg.team_id = pa.team_id
JOIN role r ON r.id = pa.role_id
JOIN person p ON p.id = pa.person_id
WHERE r.code IN ('HEAD_COACH', 'ASSISTANT_COACH');

# MIGRATIONS
CREATE TABLE user (
	id INT AUTO_INCREMENT NOT NULL,
	email VARCHAR(180) NOT NULL,
	roles JSON NOT NULL,
	password VARCHAR(255) NOT NULL,
	UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
	PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;