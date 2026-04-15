USE azveria;

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
    JSON_OBJECT('O', 1),
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
