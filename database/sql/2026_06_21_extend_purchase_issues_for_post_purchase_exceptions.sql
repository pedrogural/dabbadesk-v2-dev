ALTER TABLE `purchase_issues`
  ADD COLUMN IF NOT EXISTS `affected_qty` int unsigned DEFAULT NULL AFTER `qty`,
  ADD COLUMN IF NOT EXISTS `issue_stage` varchar(30) NOT NULL DEFAULT 'pre_purchase' AFTER `issue_type`,
  ADD COLUMN IF NOT EXISTS `arrival_expectation` varchar(30) NOT NULL DEFAULT 'expected' AFTER `issue_stage`,
  ADD INDEX IF NOT EXISTS `purchase_issues_issue_stage_index` (`issue_stage`),
  ADD INDEX IF NOT EXISTS `purchase_issues_arrival_expectation_index` (`arrival_expectation`);

UPDATE `purchase_issues`
SET `affected_qty` = `qty`
WHERE `affected_qty` IS NULL;
