# Give - Database HealthCheck

This plugin will check payment meta data which stored in *give_ paymentmeta table and create new meta key without deleting existing payment meta if missing.

# How To Use
##### Note: Please confirm with Ravinder Kumar before using any version of this plugin.

1. https://github.com/impress-org/give-database-healthcheck/tree/recover-donation-metadata-and-table<br>
   This branch will be use to recover donation metadata and create `*_give_donationmeta` table if missing.
   After updating from Give < 2.0, we found that most of cases `donation_id` has `NULL` value, so this branch helps to fix that issue.
   
2. https://github.com/impress-org/give-database-healthcheck/tree/missing-comment-table<br>
   This branch will use to create `*_give_comments` table and move donation and donor note to this table.
   After updating from Give < 2.3.0, we found that comment table does not exist in most of sites, so this branch we help to fix that issue.