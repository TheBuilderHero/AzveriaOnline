CREATE DATABASE IF NOT EXISTS azveria;
USE azveria;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'player') NOT NULL DEFAULT 'player',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS personal_access_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT NULL,
  last_used_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
);

CREATE TABLE IF NOT EXISTS nations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_user_id BIGINT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL UNIQUE,
  is_placeholder TINYINT(1) NOT NULL DEFAULT 0,
  leader_name VARCHAR(120) NULL,
  alliance_name VARCHAR(120) NULL,
  about_text TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_nations_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS nation_resources (
  nation_id BIGINT UNSIGNED PRIMARY KEY,
  cow DECIMAL(14,2) NOT NULL DEFAULT 0,
  wood DECIMAL(14,2) NOT NULL DEFAULT 0,
  ore DECIMAL(14,2) NOT NULL DEFAULT 0,
  food DECIMAL(14,2) NOT NULL DEFAULT 0,
  extra_json JSON NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_nation_resources_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nation_terrain_stats (
  nation_id BIGINT UNSIGNED PRIMARY KEY,
  grassland_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  mountain_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  freshwater_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  hills_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  desert_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  square_miles_json JSON NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_nation_terrain_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS unit_catalog (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  display_name VARCHAR(160) NOT NULL,
  class_name VARCHAR(64) NOT NULL,
  base_stats_json JSON NOT NULL,
  upkeep_json JSON NULL,
  unlocked_by_structure VARCHAR(64) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS nation_units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nation_id BIGINT UNSIGNED NOT NULL,
  unit_catalog_id BIGINT UNSIGNED NULL,
  custom_name VARCHAR(120) NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('owned', 'training') NOT NULL DEFAULT 'owned',
  training_ready_at TIMESTAMP NULL,
  stats_override_json JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_nation_units_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE,
  CONSTRAINT fk_nation_units_catalog FOREIGN KEY (unit_catalog_id) REFERENCES unit_catalog(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS building_catalog (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  display_name VARCHAR(160) NOT NULL,
  max_level INT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS nation_buildings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nation_id BIGINT UNSIGNED NOT NULL,
  building_catalog_id BIGINT UNSIGNED NOT NULL,
  level INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('built', 'constructing', 'upgrading') NOT NULL DEFAULT 'built',
  finishes_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_nation_buildings_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE,
  CONSTRAINT fk_nation_buildings_catalog FOREIGN KEY (building_catalog_id) REFERENCES building_catalog(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS announcements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_user_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_announcements_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chats (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  type ENUM('group', 'dm', 'announcement', 'global') NOT NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_chats_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_members (
  chat_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(chat_id, user_id),
  CONSTRAINT fk_chat_members_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chat_id BIGINT UNSIGNED NOT NULL,
  sender_user_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_chat_messages_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS map_layers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  layer_type ENUM('main', 'terrain', 'political') NOT NULL UNIQUE,
  image_path VARCHAR(255) NOT NULL,
  uploaded_by_user_id BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_map_layers_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS shop_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  display_name VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS shop_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  display_name VARCHAR(160) NOT NULL,
  description_text TEXT NULL,
  cost_json JSON NOT NULL,
  maintenance_json JSON NULL,
  yearly_effect_json JSON NULL,
  effect_json JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  visibility_json JSON NULL COMMENT 'null = global; ["all"] = global; [1,2,3] = only those user IDs',
  CONSTRAINT fk_shop_items_category FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nation_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nation_id BIGINT UNSIGNED NOT NULL,
  shop_item_id BIGINT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_nation_assets_unique (nation_id, shop_item_id),
  CONSTRAINT fk_nation_assets_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE,
  CONSTRAINT fk_nation_assets_item FOREIGN KEY (shop_item_id) REFERENCES shop_items(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  meta_json JSON NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS game_time (
  id TINYINT UNSIGNED PRIMARY KEY,
  started_at TIMESTAMP NOT NULL,
  seconds_per_year INT UNSIGNED NOT NULL DEFAULT 1209600,
  processed_years INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS user_settings (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
  color_blind_mode ENUM('none', 'protanopia', 'deuteranopia', 'tritanopia') NOT NULL DEFAULT 'none',
  dog_bark_enabled TINYINT(1) NOT NULL DEFAULT 0,
  extra_json JSON NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO shop_categories (code, display_name) VALUES
  ('refinement', 'Refinement'),
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

INSERT INTO game_time (id, started_at, seconds_per_year, processed_years, updated_at) VALUES
  (1, NOW(), 1209600, 0, NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
