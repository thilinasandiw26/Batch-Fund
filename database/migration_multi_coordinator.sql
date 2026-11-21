-- Run ONCE on an existing BatchFund database (phpMyAdmin -> select `batchfund` -> Import / SQL).
-- Fresh installs do not need this: database/schema.sql already contains these columns.
USE batchfund;

ALTER TABLE users
  ADD COLUMN is_treasurer TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

ALTER TABLE collections
  ADD COLUMN updated_by INT UNSIGNED NULL AFTER created_by,
  ADD CONSTRAINT fk_collections_updater FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL;

ALTER TABLE expenses
  ADD COLUMN updated_by INT UNSIGNED NULL AFTER approved_at,
  ADD COLUMN updated_at DATETIME NULL AFTER updated_by,
  ADD CONSTRAINT fk_expenses_updater FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL;

ALTER TABLE announcements
  ADD COLUMN updated_by INT UNSIGNED NULL AFTER created_by,
  ADD CONSTRAINT fk_announcements_updater FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS event_images (
  image_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  collection_id INT UNSIGNED NOT NULL,
  stored_name   VARCHAR(80) NOT NULL,
  original_name VARCHAR(200) NOT NULL,
  mime_type     VARCHAR(80) NOT NULL,
  file_size     INT UNSIGNED NOT NULL,
  uploaded_by   INT UNSIGNED NULL,
  uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_event_images_collection (collection_id),
  CONSTRAINT fk_event_images_collection FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
  CONSTRAINT fk_event_images_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Optional: only if "Add student" fails with "Field 'student_id' doesn't have a default value".
-- SET FOREIGN_KEY_CHECKS = 0;
-- ALTER TABLE students MODIFY student_id INT UNSIGNED NOT NULL AUTO_INCREMENT;
-- SET FOREIGN_KEY_CHECKS = 1;
