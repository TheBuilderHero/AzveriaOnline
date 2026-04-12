USE azveria;

SET @has_seafront_pct := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'nation_terrain_stats'
    AND COLUMN_NAME = 'seafront_pct'
);
SET @sql := IF(
  @has_seafront_pct = 0,
  'ALTER TABLE nation_terrain_stats ADD COLUMN seafront_pct DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER desert_pct',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_archived_at := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_members'
    AND COLUMN_NAME = 'archived_at'
);
SET @sql := IF(
  @has_archived_at = 0,
  'ALTER TABLE chat_members ADD COLUMN archived_at TIMESTAMP NULL AFTER user_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_deleted_at := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_members'
    AND COLUMN_NAME = 'deleted_at'
);
SET @sql := IF(
  @has_deleted_at = 0,
  'ALTER TABLE chat_members ADD COLUMN deleted_at TIMESTAMP NULL AFTER archived_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_year_started_at := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'game_time'
    AND COLUMN_NAME = 'year_started_at'
);
SET @sql := IF(
  @has_year_started_at = 0,
  'ALTER TABLE game_time ADD COLUMN year_started_at TIMESTAMP NULL AFTER started_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_elapsed_hours_in_year := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'game_time'
    AND COLUMN_NAME = 'elapsed_hours_in_year'
);
SET @sql := IF(
  @has_elapsed_hours_in_year = 0,
  'ALTER TABLE game_time ADD COLUMN elapsed_hours_in_year DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER processed_years',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_auto_increment_enabled := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'game_time'
    AND COLUMN_NAME = 'auto_increment_enabled'
);
SET @sql := IF(
  @has_auto_increment_enabled = 0,
  'ALTER TABLE game_time ADD COLUMN auto_increment_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER elapsed_hours_in_year',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_year_label_offset := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'game_time'
    AND COLUMN_NAME = 'year_label_offset'
);
SET @sql := IF(
  @has_year_label_offset = 0,
  'ALTER TABLE game_time ADD COLUMN year_label_offset INT NOT NULL DEFAULT 0 AFTER auto_increment_enabled',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE nation_terrain_stats
SET square_miles_json = JSON_SET(
    COALESCE(square_miles_json, JSON_OBJECT()),
    '$.seafront',
    COALESCE(JSON_EXTRACT(square_miles_json, '$.seafront'), 0)
  )
WHERE square_miles_json IS NOT NULL;

UPDATE nation_terrain_stats
SET seafront_pct = 0
WHERE seafront_pct IS NULL;

UPDATE game_time
SET year_started_at = COALESCE(year_started_at, started_at, updated_at, NOW()),
    elapsed_hours_in_year = COALESCE(elapsed_hours_in_year, 0),
    auto_increment_enabled = COALESCE(auto_increment_enabled, 1),
    year_label_offset = COALESCE(year_label_offset, 0);
