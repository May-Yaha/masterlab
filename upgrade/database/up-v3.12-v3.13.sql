ALTER TABLE `issue_filter` ADD `adv_query_sort_field` VARCHAR(40) NOT NULL DEFAULT '' COMMENT '高级查询的排序字段' AFTER `is_adv_query`, ADD `adv_query_sort_by` VARCHAR(12) NOT NULL DEFAULT 'desc' COMMENT '高级查询的排序' AFTER `adv_query_sort_field`;