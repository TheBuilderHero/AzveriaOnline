USE azveria;

ALTER TABLE shop_items
  ADD COLUMN IF NOT EXISTS requirement_json JSON NULL AFTER effect_json;

CREATE TABLE IF NOT EXISTS nation_research (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nation_id BIGINT UNSIGNED NOT NULL,
  shop_item_id BIGINT UNSIGNED NULL,
  research_code VARCHAR(120) NOT NULL,
  researched_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_nation_research (nation_id, research_code),
  CONSTRAINT fk_nation_research_nation FOREIGN KEY (nation_id) REFERENCES nations(id) ON DELETE CASCADE,
  CONSTRAINT fk_nation_research_item FOREIGN KEY (shop_item_id) REFERENCES shop_items(id) ON DELETE SET NULL
);

INSERT INTO shop_categories (code, display_name) VALUES
  ('craft', 'Craft'),
  ('build', 'Build'),
  ('recruit', 'Recruit'),
  ('research', 'Research')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

SET @craft_id = (SELECT id FROM shop_categories WHERE code = 'craft' LIMIT 1);
SET @build_id = (SELECT id FROM shop_categories WHERE code = 'build' LIMIT 1);
SET @recruit_id = (SELECT id FROM shop_categories WHERE code = 'recruit' LIMIT 1);

UPDATE shop_items si
JOIN shop_categories sc ON sc.id = si.category_id
SET si.category_id = @craft_id
WHERE sc.code IN ('refinement', 'crafting', 'currency_exchange')
  AND @craft_id IS NOT NULL;

UPDATE shop_items si
JOIN shop_categories sc ON sc.id = si.category_id
SET si.category_id = @build_id
WHERE sc.code IN ('structures', 'upgrades')
  AND @build_id IS NOT NULL;

UPDATE shop_items si
JOIN shop_categories sc ON sc.id = si.category_id
SET si.category_id = @recruit_id
WHERE sc.code IN ('recruitment')
  AND @recruit_id IS NOT NULL;

DELETE FROM shop_categories
WHERE code IN ('refinement', 'crafting', 'currency_exchange', 'structures', 'upgrades', 'recruitment');
