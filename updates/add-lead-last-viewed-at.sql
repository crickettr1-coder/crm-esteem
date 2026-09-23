-- Run once on the live CRM database before deploying the lead-list code.
ALTER TABLE `rise_clients`
    ADD COLUMN `last_viewed_at` datetime DEFAULT NULL AFTER `created_date`;

CREATE INDEX `idx_rise_clients_last_viewed_at`
    ON `rise_clients` (`last_viewed_at`);
