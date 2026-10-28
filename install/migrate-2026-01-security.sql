-- ---------------------------------------------------------------------------
-- Spark / GDIID migration — bug-fix pass
--
-- Run once against the live schema. Safe to re-run: each statement is guarded.
-- MySQL 5.7+/8.0.
-- ---------------------------------------------------------------------------

-- H5: password reset tokens need an issue time so they can expire.
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'members'
      AND COLUMN_NAME  = 'reset_token_at'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `members` ADD COLUMN `reset_token_at` DATETIME NULL DEFAULT NULL AFTER `reset_token`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- H5: an unexpired token is looked up by value on every reset; index it.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'members'
      AND INDEX_NAME   = 'idx_members_reset_token'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_members_reset_token` ON `members` (`reset_token`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- H3: login and the member lookups filter on email.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'members'
      AND INDEX_NAME   = 'idx_members_email'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_members_email` ON `members` (`member_email`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- C3: the delete cascade and every portal query filter children by project_id.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'project_payment'
      AND INDEX_NAME   = 'idx_project_payment_project'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_project_payment_project` ON `project_payment` (`project_id`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'project_loa'
      AND INDEX_NAME   = 'idx_project_loa_project'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_project_loa_project` ON `project_loa` (`project_id`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- C4: downloads resolve a record by stored file name; index those columns.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'project_payment'
      AND INDEX_NAME   = 'idx_project_payment_proof'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_project_payment_proof` ON `project_payment` (`payment_proof`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- C1: `members_role.code` is joined on every admin page load.
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'members_role'
      AND INDEX_NAME   = 'idx_members_role_code'
);
SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX `idx_members_role_code` ON `members_role` (`code`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Housekeeping
-- ---------------------------------------------------------------------------

-- Any reset token issued before this migration has unknown age. Clear them so
-- nobody can keep using a link of unbounded age; users request a new one.
UPDATE `members` SET `reset_token` = NULL, `reset_token_at` = NULL
WHERE `reset_token` IS NOT NULL;
