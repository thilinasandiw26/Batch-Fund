
CREATE DATABASE IF NOT EXISTS batchfund;
USE batchfund;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, email_logs, announcements, notifications, expense_documents, expenses,
                     payment_receipts, payments, collections, students, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role           ENUM('coordinator','student') NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password       VARCHAR(255) NOT NULL,
  full_name      VARCHAR(120) NOT NULL,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at  DATETIME NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- 2. students: extra profile data for users with role = student (1:1 with users)
CREATE TABLE students (
  student_id  INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  reg_no      VARCHAR(30) NOT NULL,
  contact_no  VARCHAR(20) NOT NULL,
  district    VARCHAR(60) NOT NULL,
  degree     VARCHAR(60) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_students_user (user_id),
  UNIQUE KEY uq_students_reg (reg_no),
  KEY idx_students_district (district),
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. collections: monthly contributions AND special-event collections (the "events" table is merged here)
CREATE TABLE collections (
  collection_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind             ENUM('monthly','event') NOT NULL,
  title            VARCHAR(150) NOT NULL,
  description      TEXT NULL,
  amount           DECIMAL(10,2) NOT NULL,            -- contribution per student
  target_amount    DECIMAL(12,2) NULL,                -- events only (optional)
  collection_month DATE NULL,                         -- monthly only (first day of month)
  event_date       DATE NULL,                         -- events only (optional)
  deadline         DATE NOT NULL,
  status           ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_by       INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_collections_kind (kind, status),
  KEY idx_collections_deadline (deadline),
  CONSTRAINT fk_collections_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. payments: ONE row per student per collection. Resubmitting a rejected payment updates this row (attempt_no + 1)
CREATE TABLE payments (
  payment_id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id       INT UNSIGNED NOT NULL,
  collection_id    INT UNSIGNED NOT NULL,
  amount           DECIMAL(10,2) NOT NULL,
  reference_no     VARCHAR(60) NOT NULL,
  status           ENUM('receipt_submitted','under_verification','approved','rejected') NOT NULL DEFAULT 'receipt_submitted',
  attempt_no       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  rejection_reason VARCHAR(250) NULL,
  submitted_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by      INT UNSIGNED NULL,
  reviewed_at      DATETIME NULL,
  UNIQUE KEY uq_payment_student_collection (student_id, collection_id),   -- no duplicate payment records
  UNIQUE KEY uq_payment_reference (reference_no),
  KEY idx_payments_status (status),
  KEY idx_payments_collection (collection_id),
  CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_collection FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. payment_receipts: uploaded files, one per submission attempt (files live in /uploads, served only through PHP)
CREATE TABLE payment_receipts (
  receipt_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  payment_id    INT UNSIGNED NOT NULL,
  attempt_no    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  stored_name   VARCHAR(80) NOT NULL,
  original_name VARCHAR(200) NOT NULL,
  mime_type     VARCHAR(80) NOT NULL,
  file_size     INT UNSIGNED NOT NULL,
  uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_receipts_payment (payment_id),
  CONSTRAINT fk_receipts_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. expenses (optionally linked to a collection/event)
CREATE TABLE expenses (
  expense_id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  category      VARCHAR(50) NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  expense_date  DATE NOT NULL,
  collection_id INT UNSIGNED NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_by    INT UNSIGNED NULL,
  approved_by   INT UNSIGNED NULL,
  approved_at   DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_expenses_status (status),
  KEY idx_expenses_date (expense_date),
  CONSTRAINT fk_expenses_collection FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE SET NULL,
  CONSTRAINT fk_expenses_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_expenses_approver FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. expense_documents: invoices; is_public = 1 lets students open the file (only while the expense is approved)
CREATE TABLE expense_documents (
  document_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expense_id    INT UNSIGNED NOT NULL,
  stored_name   VARCHAR(80) NOT NULL,
  original_name VARCHAR(200) NOT NULL,
  mime_type     VARCHAR(80) NOT NULL,
  file_size     INT UNSIGNED NOT NULL,
  is_public     TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_expdocs_expense (expense_id),
  CONSTRAINT fk_expdocs_expense FOREIGN KEY (expense_id) REFERENCES expenses(expense_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. notifications (ref_key makes automatic deadline/overdue notices idempotent)
CREATE TABLE notifications (
  notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  type            VARCHAR(30) NOT NULL,
  message         VARCHAR(255) NOT NULL,
  ref_key         VARCHAR(60) NULL,
  is_read         TINYINT(1) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_notification_ref (user_id, ref_key),
  KEY idx_notifications_user (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. announcements
CREATE TABLE announcements (
  announcement_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(150) NOT NULL,
  body            TEXT NOT NULL,
  status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
  publish_date    DATE NOT NULL,
  created_by      INT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_announcements_status (status, publish_date),
  CONSTRAINT fk_announcements_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. email_logs: every send attempt (success or failure)
CREATE TABLE email_logs (
  log_id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NULL,
  recipient_email VARCHAR(150) NOT NULL,
  subject         VARCHAR(200) NOT NULL,
  collection_id   INT UNSIGNED NULL,
  status          ENUM('sent','failed') NOT NULL,
  error_message   VARCHAR(250) NULL,
  sent_by         INT UNSIGNED NULL,
  sent_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_logs_sent (sent_at),
  CONSTRAINT fk_email_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
  CONSTRAINT fk_email_collection FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE SET NULL,
  CONSTRAINT fk_email_sender FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. audit_logs: important financial and administrative actions
CREATE TABLE audit_logs (
  log_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(60) NOT NULL,
  entity     VARCHAR(40) NOT NULL,
  entity_id  INT UNSIGNED NULL,
  details    VARCHAR(500) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created (created_at),
  KEY idx_audit_entity (entity, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
