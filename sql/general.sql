CREATE TABLE captains (
  user_id int unsigned NOT NULL,
  captain_id int unsigned NOT NULL AUTO_INCREMENT,
  captain_data json NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (captain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE fleet_assignments (
  user_id int unsigned NOT NULL,
  ship_slot tinyint unsigned NOT NULL,
  captain_id int unsigned NOT NULL DEFAULT 0,
  ship_data json NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (user_id, ship_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE starbases (
  user_id int unsigned NOT NULL,
  starbase_id int unsigned NOT NULL AUTO_INCREMENT,
  starbase_data json NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (starbase_id),
  KEY starbases_user_id_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
