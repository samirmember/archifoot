SET FOREIGN_KEY_CHECKS = 0;

SET @tables = NULL;

SELECT GROUP_CONCAT(CONCAT('`', table_name, '`') SEPARATOR ', ')
INTO @tables
FROM information_schema.tables
WHERE table_schema = DATABASE();

SET @stmt = CONCAT('DROP TABLE IF EXISTS ', @tables);

PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;