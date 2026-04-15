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

SET @has_last_read_message_id := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_members'
    AND COLUMN_NAME = 'last_read_message_id'
);
SET @sql := IF(
  @has_last_read_message_id = 0,
  'ALTER TABLE chat_members ADD COLUMN last_read_message_id BIGINT UNSIGNED NULL AFTER user_id',
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

INSERT INTO shop_categories (code, display_name) VALUES
  ('refinement', 'Refinement'),
  ('structures', 'Structures'),
  ('upgrades', 'Upgrades'),
  ('recruitment', 'Recruitment'),
  ('crafting', 'Crafting'),
  ('currency_exchange', 'Currency Exchange')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

INSERT INTO map_layers (layer_type, image_path, updated_at) VALUES
  ('main', 'maps/main-map.png', NOW()),
  ('terrain', 'maps/terrain-map.png', NOW()),
  ('political', 'maps/political-map.png', NOW())
ON DUPLICATE KEY UPDATE image_path = VALUES(image_path), updated_at = VALUES(updated_at);

INSERT INTO game_time (id, started_at, year_started_at, seconds_per_year, processed_years, elapsed_hours_in_year, auto_increment_enabled, year_label_offset, updated_at) VALUES
  (1, NOW(), NOW(), 172800, 0, 0, 1, 0, NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO building_catalog (code, display_name, max_level) VALUES
  ('capital', 'Capital', 10),
  ('refinery', 'Refinery', 3),
  ('barracks', 'Barracks', 5),
  ('factory', 'Factory', 5),
  ('saf', 'Specialized Armaments Factory', 10),
  ('shipyard', 'Shipyard', 10),
  ('airfield', 'Airfield', 10),
  ('training_ground', 'Training Ground', 5),
  ('trf', 'Technological Research Facility', 5),
  ('teleporter', 'Teleporter', 5),
  ('city', 'City', 5),
  ('farm', 'Farm', 5),
  ('mine', 'Mine', 5),
  ('lodging', 'Lodging', 5)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), max_level = VALUES(max_level);

INSERT INTO unit_catalog (code, display_name, class_name, base_stats_json, upkeep_json, unlocked_by_structure, created_at, updated_at)
VALUES
  (
    'dak_light_infantry',
    'Dakotian Light Infantry',
    'infantry',
    JSON_OBJECT('ATK', 9, 'DEF', 7.2, 'DMG', 0.8, 'HP', 2.7, 'MVT', 1.35, 'RNG', 0.5, 'ACT', 2),
    JSON_OBJECT('X', 5, 'F', 0.5),
    'barracks_l1',
    NOW(),
    NOW()
  ),
  (
    'dak_armored_infantry',
    'Dakotian Armored Infantry',
    'infantry',
    JSON_OBJECT('ATK', 12, 'DEF', 14.4, 'DMG', 0.8, 'HP', 3.6, 'MVT', 1.35, 'RNG', 0.5, 'ACT', 2),
    JSON_OBJECT('X', 5, 'F', 0.5),
    'barracks_l2',
    NOW(),
    NOW()
  ),
  (
    'dak_light_artillery',
    'Dakotian Light Artillery',
    'artillery',
    JSON_OBJECT('ATK', 12, 'DEF', 4.8, 'DMG', 1.6, 'HP', 2.7, 'MVT', 0.45, 'RNG', 2, 'ACT', 2),
    JSON_OBJECT('X', 10),
    'factory_l1',
    NOW(),
    NOW()
  ),
  (
    'dak_tank',
    'Dakotian Tank',
    'tank',
    JSON_OBJECT('ATK', 18, 'DEF', 19.2, 'DMG', 1.6, 'HP', 4.5, 'MVT', 1.8, 'RNG', 1, 'ACT', 2, 'ON_DEATH_ENEMY_GAIN', '1O'),
    JSON_OBJECT('X', 10),
    'factory_l3',
    NOW(),
    NOW()
  ),
  (
    'dak_recon_plane',
    'Dakotian Recon Plane',
    'aircraft',
    JSON_OBJECT('ATK', 9, 'DEF', 7.2, 'DMG', 0.8, 'HP', 0.9, 'ACT', 2),
    JSON_OBJECT('X', 10),
    'airfield_l1',
    NOW(),
    NOW()
  )
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), class_name = VALUES(class_name), base_stats_json = VALUES(base_stats_json), upkeep_json = VALUES(upkeep_json), unlocked_by_structure = VALUES(unlocked_by_structure), updated_at = VALUES(updated_at);

INSERT INTO shop_items (category_id, code, display_name, cost_json, effect_json, is_active)
SELECT c.id, 'refine_ore_to_metal', 'Refine Ore to Metal', JSON_OBJECT('ore', 5), JSON_OBJECT('refined', JSON_OBJECT('M', 1)), 1
FROM shop_categories c
WHERE c.code = 'refinement'
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), cost_json = VALUES(cost_json), effect_json = VALUES(effect_json), is_active = VALUES(is_active);

INSERT INTO shop_items (category_id, code, display_name, cost_json, effect_json, is_active)
SELECT c.id, 'buy_light_infantry', 'Recruit Light Infantry', JSON_OBJECT('cow', 20, 'food', 1), JSON_OBJECT('unit_code', 'dak_light_infantry', 'qty', 1), 1
FROM shop_categories c
WHERE c.code = 'recruitment'
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), cost_json = VALUES(cost_json), effect_json = VALUES(effect_json), is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS player_visibility_rules (
  viewer_user_id BIGINT UNSIGNED NOT NULL,
  subject_user_id BIGINT UNSIGNED NOT NULL,
  field_key VARCHAR(80) NOT NULL,
  is_allowed TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (viewer_user_id, subject_user_id, field_key),
  CONSTRAINT fk_visibility_viewer FOREIGN KEY (viewer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_visibility_subject FOREIGN KEY (subject_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS game_documents (
  code VARCHAR(80) PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content_text LONGTEXT NOT NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_game_docs_updater FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO game_documents (code, title, content_text, updated_at) VALUES
  ('reptonians', 'Reptonians', 'Azveria reptonian unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('elves', 'Elves', 'Azveria elf unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('kilonites', 'Kilonites', 'Azveria kilonite unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('goblins', 'Goblins', 'Azveria goblin unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('testudians', 'Testudians', 'Azveria testudian unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('zeptins', 'Zeptins', 'Azveria zeptin unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('centaurs', 'Centaurs', 'Azveria centaur division stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('dwarves', 'Dwarves', 'Azveria dwarf unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('humans', 'Humans', 'Azveria human (base) unit stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('structures_and_terrain', 'Structures and Terrain', 'Azveria structure and terrain stats. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('war_rules_and_such', 'War Rules and Such', 'Azveria war and army rules. Use Edit to replace this placeholder with full rules text.', NOW()),
  ('rules_and_resources', 'Rules and Resources', 'Azveria general rules and resources. Use Edit to replace this placeholder with full rules text.', NOW())
ON DUPLICATE KEY UPDATE title = VALUES(title), updated_at = VALUES(updated_at);
